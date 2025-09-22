<?php

namespace App\Actions\VisitorPass;

use App\Actions\BaseAction;
use App\Models\VisitorPass;

class CheckoutVisitorAction extends BaseAction
{
    /**
     * Check out a visitor.
     *
     * @param VisitorPass $visitorPass
     * @return VisitorPass
     */
    public function execute(VisitorPass $visitorPass): VisitorPass
    {
        if (!$visitorPass->is_active) {
            throw new \Exception('Visitor pass is already inactive');
        }

        $visitorPass->update([
            'is_active' => false,
            'check_out_time' => now(),
        ]);

        return $visitorPass->fresh(['guest', 'booking.room', 'issuedBy']);
    }
}
