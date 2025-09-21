<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['booking.room', 'processedBy']);

        if ($request->booking_id) {
            $query->where('booking_id', $request->booking_id);
        }

        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->is_confirmed !== null) {
            $query->where('is_confirmed', $request->is_confirmed);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments
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
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($request->booking_id);
            
            // Check if payment amount doesn't exceed remaining balance
            $remainingBalance = $booking->remaining_balance;
            if ($request->amount > $remainingBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount exceeds remaining balance'
                ], 400);
            }

            $payment = Payment::create([
                'booking_id' => $request->booking_id,
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'payer_name' => $request->payer_name,
                'reference' => $request->reference,
                'processed_by' => Auth::id(),
            ]);

            // Update booking amount_paid
            $booking->increment('amount_paid', $request->amount);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['booking.room', 'processedBy'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating payment: ' . $e->getMessage()
            ], 500);
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
            'data' => $payment
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
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payment = Payment::findOrFail($id);
            
            if ($payment->is_confirmed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update confirmed payment'
                ], 400);
            }

            $oldAmount = $payment->amount;
            $payment->update($request->only(['payment_method', 'amount', 'payer_name', 'reference']));

            // Update booking amount_paid if amount changed
            if ($request->has('amount') && $request->amount != $oldAmount) {
                $payment->booking->increment('amount_paid', $request->amount - $oldAmount);
            }

            return response()->json([
                'success' => true,
                'data' => $payment->load(['booking.room', 'processedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            if ($payment->is_confirmed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete confirmed payment'
                ], 400);
            }

            // Decrease booking amount_paid
            $payment->booking->decrement('amount_paid', $payment->amount);
            
            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payment: ' . $e->getMessage()
            ], 500);
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
                    'message' => 'Payment is already confirmed'
                ], 400);
            }

            $payment->update([
                'is_confirmed' => true,
                'confirmed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['booking.room', 'processedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error confirming payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment ledger.
     */
    public function ledger(Request $request)
    {
        $query = Payment::with(['booking.room', 'processedBy']);

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->confirmed !== null) {
            $query->where('is_confirmed', $request->confirmed);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(50);

        // Calculate summary
        $summary = [
            'total_amount' => $payments->sum('amount'),
            'cash_total' => $payments->where('payment_method', 'cash')->sum('amount'),
            'transfer_total' => $payments->where('payment_method', 'transfer')->sum('amount'),
            'confirmed_total' => $payments->where('is_confirmed', true)->sum('amount'),
            'pending_total' => $payments->where('is_confirmed', false)->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $payments,
            'summary' => $summary
        ]);
    }
}
