<?php

namespace App\Actions\Payment;

use App\Actions\BaseAction;
use App\Models\Payment;

class ConfirmPaymentAction extends BaseAction
{
    /**
     * Confirm a payment.
     *
     * @param Payment $payment
     * @param int $processedBy
     * @return Payment
     */
    public function execute(Payment $payment, int $processedBy): Payment
    {
        if ($payment->is_confirmed) {
            throw new \Exception('Payment is already confirmed');
        }

        $payment->update([
            'is_confirmed' => true,
            'confirmed_at' => now(),
            'processed_by' => $processedBy,
        ]);

        // Update booking amount paid
        $payment->booking->increment('amount_paid', $payment->amount);

        return $payment->fresh();
    }
}
