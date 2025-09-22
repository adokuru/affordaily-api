<?php

namespace App\Actions\Booking;

use App\Actions\BaseAction;
use App\Models\Booking;
use App\Services\CacheService;

class CheckoutBookingAction extends BaseAction
{
    /**
     * Check out a booking.
     *
     * @param Booking $booking
     * @param string|null $damageNotes
     * @param bool $keyReturned
     * @param bool $earlyCheckout
     * @return Booking
     */
    public function execute(
        Booking $booking,
        ?string $damageNotes = null,
        bool $keyReturned = true,
        bool $earlyCheckout = false
    ): Booking {
        if ($booking->status !== 'active' && $booking->status !== 'pending_checkout') {
            throw new \Exception('Booking is not in a valid state for checkout');
        }

        $booking->update([
            'status' => $earlyCheckout ? 'early_checkout' : 'completed',
            'check_out_time' => now(),
            'damage_notes' => $damageNotes,
            'key_returned' => $keyReturned,
        ]);

        // Make room available again
        $booking->room->update(['is_available' => true]);

        // Deactivate all visitor passes
        $booking->visitorPasses()->update(['is_active' => false]);

        // Clear room-related cache
        CacheService::clearRoomCache();

        return $booking->fresh(['room', 'guest', 'payments']);
    }
}