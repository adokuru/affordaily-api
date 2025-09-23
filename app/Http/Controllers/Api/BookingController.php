<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Actions\Guest\FindOrCreateGuestAction;
use App\Actions\Guest\UpdateGuestStatsAction;
use App\Actions\Booking\CreateBookingAction;
use App\Actions\Booking\CheckoutBookingAction;
use App\Actions\Booking\ExtendBookingAction;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BookingController extends Controller
{
    protected FindOrCreateGuestAction $findOrCreateGuestAction;
    protected UpdateGuestStatsAction $updateGuestStatsAction;
    protected CreateBookingAction $createBookingAction;
    protected CheckoutBookingAction $checkoutBookingAction;
    protected ExtendBookingAction $extendBookingAction;

    public function __construct(
        FindOrCreateGuestAction $findOrCreateGuestAction,
        UpdateGuestStatsAction $updateGuestStatsAction,
        CreateBookingAction $createBookingAction,
        CheckoutBookingAction $checkoutBookingAction,
        ExtendBookingAction $extendBookingAction
    ) {
        $this->findOrCreateGuestAction = $findOrCreateGuestAction;
        $this->updateGuestStatsAction = $updateGuestStatsAction;
        $this->createBookingAction = $createBookingAction;
        $this->checkoutBookingAction = $checkoutBookingAction;
        $this->extendBookingAction = $extendBookingAction;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bookings = Booking::with(['guest', 'room', 'payments', 'visitorPasses'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings)
        ]);
    }

    /**
     * Store a newly created resource in storage (Check-in).
     */
    public function store(CreateBookingRequest $request)
    {
        try {
            // Find or create guest
            $guest = $this->findOrCreateGuestAction->execute(
                $request->guest_phone,
                $request->guest_name,
                $request->id_photo_path
            );

            $idPhotoPath = $request->id_photo_path ? $this->uploadIdPhoto($request) : null;

            // Create booking using action
            $booking = $this->createBookingAction->execute(
                $guest,
                $request->guest_name,
                $request->guest_phone,
                $idPhotoPath,
                $request->number_of_nights,
                $request->preferred_bed_type,
                $request->payment_method,
                $request->guest_name,
                $request->reference ?? $this->generateBookingReference(),
                Auth::id()
            );

            // Update guest statistics
            $this->updateGuestStatsAction->execute($guest, $booking->total_amount);

            return response()->json([
                'success' => true,
                'data' => new BookingResource($booking)
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

    /**
     * Find existing guest or create new one.
     *
     * @param Request $request
     * @return Guest
     */
    private function findOrCreateGuest(Request $request): Guest
    {
        // First, try to find existing guest by phone
        $guest = Guest::byPhone($request->guest_phone)->first();

        if ($guest) {
            // Update guest information if provided
            $updateData = [];
            if ($request->guest_name && $request->guest_name !== $guest->name) {
                $updateData['name'] = $request->guest_name;
            }
            if ($request->id_photo_path && $request->id_photo_path !== $guest->id_photo_path) {
                $updateData['id_photo_path'] = $request->id_photo_path;
            }

            if (!empty($updateData)) {
                $guest->update($updateData);
            }

            return $guest;
        }

        // Create new guest
        return Guest::create([
            'name' => $request->guest_name,
            'phone' => $request->guest_phone,
            'id_photo_path' => $request->id_photo_path,
        ]);
    }

    /**
     * Upload ID photo.
     */
    public function uploadIdPhoto(Request $request)
    {
        $request->validate([
            'id_photo' => 'required|image|max:2048',
        ]);

        $path = Storage::disk('public')->put('id_photos', $request->file('id_photo'));

        $url = Storage::disk('public')->url($path);

        return response()->json([
            'success' => true,
            'data' => $url
        ]);
    }
}