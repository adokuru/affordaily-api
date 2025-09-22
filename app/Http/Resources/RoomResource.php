<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
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
            'room_number' => $this->room_number,
            'bed_type' => $this->bed_type,
            'is_available' => $this->is_available,
            'description' => $this->description,
            'active_booking' => $this->whenLoaded('activeBooking', function () {
                return [
                    'id' => $this->activeBooking->id,
                    'guest_name' => $this->activeBooking->guest_name,
                    'scheduled_checkout_time' => $this->activeBooking->scheduled_checkout_time?->toISOString(),
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
