<?php

namespace App\Actions\Booking;

use App\Actions\BaseAction;
use App\Models\Booking;
use App\Services\RoomAssignmentService;

class ExtendBookingAction extends BaseAction
{
    protected RoomAssignmentService $roomAssignmentService;

    public function __construct(RoomAssignmentService $roomAssignmentService)
    {
        $this->roomAssignmentService = $roomAssignmentService;
    }

    /**
     * Extend a booking.
     *
     * @param Booking $booking
     * @param int $additionalNights
     * @return Booking
     */
    public function execute(Booking $booking, int $additionalNights): Booking
    {
        if ($booking->status !== 'active') {
            throw new \Exception('Only active bookings can be extended');
        }

        if ($additionalNights <= 0) {
            throw new \Exception('Additional nights must be greater than 0');
        }

        // Calculate additional amount
        $additionalAmount = $this->roomAssignmentService->calculateTotalAmount(
            $booking->room->bed_type,
            $additionalNights
        );

        // Update booking
        $booking->update([
            'number_of_nights' => $booking->number_of_nights + $additionalNights,
            'total_amount' => $booking->total_amount + $additionalAmount,
            'amount_paid' => $booking->amount_paid + $additionalAmount,
            'scheduled_checkout_time' => $booking->scheduled_checkout_time->addDays($additionalNights),
        ]);

        return $booking->fresh(['room', 'guest', 'payments']);
    }
}
