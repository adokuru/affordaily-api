<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'guest' => [
                'id' => $this->guest?->id,
                'name' => $this->guest_name,
                'phone' => $this->guest_phone,
                'email' => $this->guest?->email,
                'total_stays' => $this->guest?->total_stays,
                'total_spent' => $this->guest?->total_spent,
            ],
            'room' => [
                'id' => $this->room?->id,
                'room_number' => $this->room?->room_number,
                'bed_type' => $this->room?->bed_type,
            ],
            'check_in_time' => $this->check_in_time?->toISOString(),
            'check_out_time' => $this->check_out_time?->toISOString(),
            'scheduled_checkout_time' => $this->scheduled_checkout_time?->toISOString(),
            'number_of_nights' => $this->number_of_nights,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'remaining_balance' => $this->remaining_balance,
            'damage_notes' => $this->damage_notes,
            'key_returned' => $this->key_returned,
            'created_at' => $this->created_at?->toISOString(),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'visitor_passes' => VisitorPassResource::collection($this->whenLoaded('visitorPasses')),
        ];
    }
}
