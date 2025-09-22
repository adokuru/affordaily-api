<?php

namespace App\Actions\Room;

use App\Actions\BaseAction;
use App\Models\Room;

class GetRoomOccupancyStatsAction extends BaseAction
{
    /**
     * Get room occupancy statistics.
     *
     * @return array
     */
    public function execute(): array
    {
        $totalRooms = Room::count();
        $occupiedRooms = Room::whereHas('activeBooking')->count();
        $availableRooms = $totalRooms - $occupiedRooms;

        $occupiedByType = [
            'A' => Room::byBedType('A')->whereHas('activeBooking')->count(),
            'B' => Room::byBedType('B')->whereHas('activeBooking')->count(),
        ];

        $availableByType = [
            'A' => Room::byBedType('A')->whereDoesntHave('activeBooking')->count(),
            'B' => Room::byBedType('B')->whereDoesntHave('activeBooking')->count(),
        ];

        return [
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedRooms,
            'available_rooms' => $availableRooms,
            'occupancy_rate' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0,
            'occupied_by_type' => $occupiedByType,
            'available_by_type' => $availableByType,
        ];
    }
}