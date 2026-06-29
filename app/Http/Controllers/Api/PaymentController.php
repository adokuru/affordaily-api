<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentLedgerService $paymentLedgerService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $this->paymentLedgerService->validatedFilters($request);
        $query = $this->paymentLedgerService->query($filters);

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'payment_method' => 'required|in:cash,transfer',
            'amount' => 'required|numeric|min:0.01',
            'payer_name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request) {
                $booking = Booking::whereKey($request->booking_id)->lockForUpdate()->firstOrFail();

                if ((float) $request->amount > (float) $booking->remaining_balance) {
                    return ['error' => 'Payment amount exceeds remaining balance'];
                }

                $payment = Payment::create([
                    'booking_id' => $request->booking_id,
                    'payment_method' => $request->payment_method,
                    'amount' => $request->amount,
                    'payer_name' => $request->payer_name,
                    'reference' => $request->reference,
                    'processed_by' => Auth::id(),
                ]);

                $booking->increment('amount_paid', $request->amount);

                return ['payment' => $payment];
            });

            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['payment']->load(['booking.room', 'processedBy']),
            ], 201);

        } catch (\Exception $e) {
            return $this->serverError($e, 'Error creating payment');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment = Payment::with(['booking.room', 'processedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'sometimes|required|in:cash,transfer',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'payer_name' => 'sometimes|required|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $id) {
                $payment = Payment::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($payment->is_confirmed) {
                    return ['error' => 'Cannot update confirmed payment'];
                }

                $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();
                $oldAmount = (float) $payment->amount;
                $newAmount = $request->has('amount') ? (float) $request->amount : $oldAmount;
                $paidExcludingPayment = (float) $booking->amount_paid - $oldAmount;

                if ($newAmount > ((float) $booking->total_amount - $paidExcludingPayment)) {
                    return ['error' => 'Payment amount exceeds remaining balance'];
                }

                $payment->update($request->only(['payment_method', 'amount', 'payer_name', 'reference']));

                if ($request->has('amount') && $newAmount !== $oldAmount) {
                    $booking->increment('amount_paid', $newAmount - $oldAmount);
                }

                return ['payment' => $payment];
            });

            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['payment']->load(['booking.room', 'processedBy']),
            ]);

        } catch (\Exception $e) {
            return $this->serverError($e, 'Error updating payment');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $result = DB::transaction(function () use ($id) {
                $payment = Payment::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($payment->is_confirmed) {
                    return ['error' => 'Cannot delete confirmed payment'];
                }

                $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();
                $booking->decrement('amount_paid', $payment->amount);
                $payment->delete();

                return ['deleted' => true];
            });

            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully',
            ]);

        } catch (\Exception $e) {
            return $this->serverError($e, 'Error deleting payment');
        }
    }

    /**
     * Confirm a payment.
     */
    public function confirm(string $id)
    {
        try {
            $payment = Payment::findOrFail($id);

            if ($payment->is_confirmed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is already confirmed',
                ], 400);
            }

            $payment->update([
                'is_confirmed' => true,
                'confirmed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['booking.room', 'processedBy']),
            ]);

        } catch (\Exception $e) {
            return $this->serverError($e, 'Error confirming payment');
        }
    }

    /**
     * Get payment ledger.
     */
    public function ledger(Request $request)
    {
        $filters = $this->paymentLedgerService->validatedFilters($request);
        $query = $this->paymentLedgerService->query($filters);
        $summary = $this->paymentLedgerService->summary($query);

        $payments = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $payments,
            'summary' => $summary,
        ]);
    }
}
