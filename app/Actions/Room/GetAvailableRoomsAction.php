<?php

namespace App\Actions\Room;

use App\Actions\BaseAction;
use App\Models\Room;

class GetAvailableRoomsAction extends BaseAction
{
    /**
     * Get available rooms grouped by bed type.
     *
     * @param string|null $bedType
     * @return array
     */
    public function execute(?string $bedType = null): array
    {
        $query = Room::available()
            ->whereDoesntHave('activeBooking');

        if ($bedType) {
            $query->byBedType($bedType);
        }

        $rooms = $query->orderBy('bed_type')
            ->orderBy('room_number')
            ->get()
            ->groupBy('bed_type');

        return [
            'A' => $rooms->get('A', collect()),
            'B' => $rooms->get('B', collect()),
            'total_available' => $rooms->flatten()->count(),
        ];
    }
}