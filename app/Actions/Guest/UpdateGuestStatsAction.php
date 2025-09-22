<?php

namespace App\Actions\Guest;

use App\Actions\BaseAction;
use App\Models\Guest;

class UpdateGuestStatsAction extends BaseAction
{
    /**
     * Update guest statistics after a booking.
     *
     * @param Guest $guest
     * @param float $amount
     * @return Guest
     */
    public function execute(Guest $guest, float $amount): Guest
    {
        $guest->increment('total_stays');
        $guest->increment('total_spent', $amount);
        $guest->update(['last_stay' => now()]);

        return $guest->fresh();
    }
}