<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Services\RoomAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingController extends Controller
{
    protected $roomAssignmentService;

    public function __construct(RoomAssignmentService $roomAssignmentService)
    {
        $this->roomAssignmentService = $roomAssignmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bookings = Booking::with(['room', 'payments', 'visitorPasses'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Store a newly created resource in storage (Check-in).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'required|string|max:20',
            'id_photo_path' => 'nullable|string',
            'number_of_nights' => 'required|integer|min:1',
            'preferred_bed_type' => 'nullable|in:A,B',
            'payment_method' => 'required|in:cash,transfer',
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
            // Assign room
            $room = $this->roomAssignmentService->assignRoom($request->preferred_bed_type);
            
            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'No available rooms found'
                ], 400);
            }

            // Calculate total amount
            $totalAmount = $this->roomAssignmentService->calculateTotalAmount(
                $room->bed_type, 
                $request->number_of_nights
            );

            // Generate booking reference
            $bookingReference = $this->generateBookingReference();

            // Create booking
            $checkInTime = now();
            $scheduledCheckoutTime = $checkInTime->copy()->addDays($request->number_of_nights)->setTime(12, 0);

            $booking = Booking::create([
                'booking_reference' => $bookingReference,
                'room_id' => $room->id,
                'guest_name' => $request->guest_name,
                'guest_phone' => $request->guest_phone,
                'id_photo_path' => $request->id_photo_path,
                'check_in_time' => $checkInTime,
                'scheduled_checkout_time' => $scheduledCheckoutTime,
                'number_of_nights' => $request->number_of_nights,
                'status' => 'active',
                'total_amount' => $totalAmount,
                'amount_paid' => $totalAmount,
                'created_by' => Auth::id(),
            ]);

            // Create payment record
            $booking->payments()->create([
                'payment_method' => $request->payment_method,
                'amount' => $totalAmount,
                'payer_name' => $request->payer_name,
                'reference' => $request->reference,
                'is_confirmed' => true,
                'confirmed_at' => now(),
                'processed_by' => Auth::id(),
            ]);

            // Update room availability
            $room->update(['is_available' => false]);

            return response()->json([
                'success' => true,
                'data' => $booking->load(['room', 'payments'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $booking = Booking::with(['room', 'payments', 'visitorPasses', 'createdBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    /**
     * Check out a booking.
     */
    public function checkout(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'damage_notes' => 'nullable|string',
            'key_returned' => 'boolean',
            'early_checkout' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($id);
            
            if ($booking->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking is not active'
                ], 400);
            }

            // Update booking
            $booking->update([
                'status' => $request->early_checkout ? 'early_checkout' : 'completed',
                'check_out_time' => now(),
                'damage_notes' => $request->damage_notes,
                'key_returned' => $request->key_returned ?? false,
            ]);

            // Make room available again
            $booking->room->update(['is_available' => true]);

            // Deactivate all visitor passes
            $booking->visitorPasses()->update([
                'is_active' => false,
                'check_out_time' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $booking->load(['room', 'payments', 'visitorPasses'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during checkout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extend a booking.
     */
    public function extend(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'additional_nights' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::with('room')->findOrFail($id);
            
            if ($booking->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking is not active'
                ], 400);
            }

            // Calculate additional amount
            $additionalAmount = $this->roomAssignmentService->calculateTotalAmount(
                $booking->room->bed_type, 
                $request->additional_nights
            );

            // Update booking
            $newScheduledCheckout = $booking->scheduled_checkout_time->addDays($request->additional_nights);
            $booking->update([
                'scheduled_checkout_time' => $newScheduledCheckout,
                'number_of_nights' => $booking->number_of_nights + $request->additional_nights,
                'total_amount' => $booking->total_amount + $additionalAmount,
            ]);

            return response()->json([
                'success' => true,
                'data' => $booking->load(['room', 'payments']),
                'additional_amount' => $additionalAmount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error extending booking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search bookings.
     */
    public function search(Request $request)
    {
        $query = Booking::with(['room', 'payments']);

        if ($request->guest_name) {
            $query->where('guest_name', 'like', '%' . $request->guest_name . '%');
        }

        if ($request->guest_phone) {
            $query->where('guest_phone', 'like', '%' . $request->guest_phone . '%');
        }

        if ($request->room_number) {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('room_number', 'like', '%' . $request->room_number . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Get active bookings.
     */
    public function active()
    {
        $bookings = Booking::active()
            ->with(['room', 'visitorPasses'])
            ->orderBy('scheduled_checkout_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Generate a unique booking reference.
     *
     * @return string
     */
    private function generateBookingReference(): string
    {
        do {
            $reference = 'REF' . strtoupper(uniqid());
        } while (Booking::where('booking_reference', $reference)->exists());

        return $reference;
    }
}
