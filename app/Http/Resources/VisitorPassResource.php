<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitorPassResource extends JsonResource
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
            'visitor' => [
                'id' => $this->guest?->id,
                'name' => $this->guest?->name,
                'phone' => $this->guest?->phone,
                'email' => $this->guest?->email,
                'id_photo_path' => $this->guest?->id_photo_path,
            ],
            'booking' => [
                'id' => $this->booking?->id,
                'booking_reference' => $this->booking?->booking_reference,
                'room_number' => $this->booking?->room?->room_number,
            ],
            'is_active' => $this->is_active,
            'check_in_time' => $this->check_in_time?->toISOString(),
            'check_out_time' => $this->check_out_time?->toISOString(),
            'issued_by' => $this->issuedBy?->name,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
