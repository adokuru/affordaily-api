<?php

namespace App\Actions\Booking;

use App\Actions\BaseAction;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Services\RoomAssignmentService;
use App\Services\CacheService;
use Carbon\Carbon;

class CreateBookingAction extends BaseAction
{
    protected RoomAssignmentService $roomAssignmentService;

    public function __construct(RoomAssignmentService $roomAssignmentService)
    {
        $this->roomAssignmentService = $roomAssignmentService;
    }

    /**
     * Create a new booking.
     *
     * @param Guest $guest
     * @param string $guestName
     * @param string $guestPhone
     * @param string|null $idPhotoPath
     * @param int $numberOfNights
     * @param string|null $preferredBedType
     * @param string $paymentMethod
     * @param string $payerName
     * @param string|null $reference
     * @param int $createdBy
     * @return Booking
     */
    public function execute(
        Guest $guest,
        string $guestName,
        string $guestPhone,
        ?string $idPhotoPath,
        int $numberOfNights,
        ?string $preferredBedType,
        string $paymentMethod,
        string $payerName,
        ?string $reference,
        int $createdBy
    ): Booking {
        // Check if guest is blacklisted
        if ($guest->is_blacklisted) {
            throw new \Exception('Guest is blacklisted: ' . $guest->blacklist_reason);
        }

        // Assign room
        $room = $this->roomAssignmentService->assignRoom($preferredBedType);
        
        if (!$room) {
            throw new \Exception('No available rooms found');
        }

        // Calculate total amount
        $totalAmount = $this->roomAssignmentService->calculateTotalAmount(
            $room->bed_type, 
            $numberOfNights
        );

        // Generate booking reference
        $bookingReference = $this->generateBookingReference();

        // Create booking
        $checkInTime = now();
        $scheduledCheckoutTime = $checkInTime->copy()->addDays($numberOfNights)->setTime(12, 0);

        $booking = Booking::create([
            'booking_reference' => $bookingReference,
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'guest_name' => $guestName,
            'guest_phone' => $guestPhone,
            'id_photo_path' => $idPhotoPath,
            'check_in_time' => $checkInTime,
            'scheduled_checkout_time' => $scheduledCheckoutTime,
            'number_of_nights' => $numberOfNights,
            'status' => 'active',
            'total_amount' => $totalAmount,
            'amount_paid' => $totalAmount,
            'created_by' => $createdBy,
        ]);

        // Create payment record
        $booking->payments()->create([
            'payment_method' => $paymentMethod,
            'amount' => $totalAmount,
            'payer_name' => $payerName,
            'reference' => $reference,
            'is_confirmed' => true,
            'confirmed_at' => now(),
            'processed_by' => $createdBy,
        ]);

        // Update room availability
        $room->update(['is_available' => false]);

        // Clear room-related cache
        CacheService::clearRoomCache();

        return $booking->load(['guest', 'room', 'payments']);
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
