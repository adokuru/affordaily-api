<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
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
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'id_photo_path' => $this->id_photo_path,
            'notes' => $this->notes,
            'total_stays' => $this->total_stays,
            'total_spent' => $this->total_spent,
            'last_stay' => $this->last_stay?->toISOString(),
            'is_blacklisted' => $this->is_blacklisted,
            'blacklist_reason' => $this->blacklist_reason,
            'created_at' => $this->created_at?->toISOString(),
            'bookings' => BookingResource::collection($this->whenLoaded('bookings')),
        ];
    }
}
