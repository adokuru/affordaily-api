<?php

namespace App\Actions\Payment;

use App\Actions\BaseAction;
use App\Models\Payment;
use App\Models\Booking;

class CreatePaymentAction extends BaseAction
{
    /**
     * Create a payment record.
     *
     * @param Booking $booking
     * @param string $paymentMethod
     * @param float $amount
     * @param string $payerName
     * @param string|null $reference
     * @param int $processedBy
     * @param bool $isConfirmed
     * @return Payment
     */
    public function execute(
        Booking $booking,
        string $paymentMethod,
        float $amount,
        string $payerName,
        ?string $reference,
        int $processedBy,
        bool $isConfirmed = true
    ): Payment {
        $payment = $booking->payments()->create([
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'payer_name' => $payerName,
            'reference' => $reference,
            'is_confirmed' => $isConfirmed,
            'confirmed_at' => $isConfirmed ? now() : null,
            'processed_by' => $processedBy,
        ]);

        // Update booking amount paid if confirmed
        if ($isConfirmed) {
            $booking->increment('amount_paid', $amount);
        }

        return $payment;
    }
}