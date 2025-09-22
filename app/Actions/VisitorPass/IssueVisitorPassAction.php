<?php

namespace App\Actions\VisitorPass;

use App\Actions\BaseAction;
use App\Actions\Guest\FindOrCreateGuestAction;
use App\Models\Booking;
use App\Models\VisitorPass;

class IssueVisitorPassAction extends BaseAction
{
    protected FindOrCreateGuestAction $findOrCreateGuestAction;

    public function __construct(FindOrCreateGuestAction $findOrCreateGuestAction)
    {
        $this->findOrCreateGuestAction = $findOrCreateGuestAction;
    }

    /**
     * Issue a visitor pass for a guest.
     *
     * @param int $bookingId
     * @param string $visitorPhone
     * @param string $visitorName
     * @param int $issuedBy
     * @return VisitorPass
     */
    public function execute(int $bookingId, string $visitorPhone, string $visitorName, int $issuedBy): VisitorPass
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->status !== 'active') {
            throw new \Exception('Cannot issue visitor pass for inactive booking');
        }

        // Find or create the visitor as a guest
        $guest = $this->findOrCreateGuestAction->execute(
            $visitorPhone,
            $visitorName
        );

        // Check if guest is blacklisted
        if ($guest->is_blacklisted) {
            throw new \Exception('Visitor is blacklisted: ' . $guest->blacklist_reason);
        }

        // Check if visitor already has an active pass for this booking
        $existingPass = VisitorPass::where('booking_id', $bookingId)
            ->where('guest_id', $guest->id)
            ->where('is_active', true)
            ->first();

        if ($existingPass) {
            throw new \Exception('Visitor already has an active pass for this booking');
        }

        // Create visitor pass
        $visitorPass = VisitorPass::create([
            'booking_id' => $bookingId,
            'guest_id' => $guest->id,
            'check_in_time' => now(),
            'is_active' => true,
            'issued_by' => $issuedBy,
        ]);

        return $visitorPass->load(['guest', 'booking.room', 'issuedBy']);
    }
}