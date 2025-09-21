<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomRate;
use Carbon\Carbon;

class RoomAssignmentService
{
    /**
     * Assign the best available room based on bed type preference and availability.
     *
     * @param string|null $preferredBedType
     * @return Room|null
     */
    public function assignRoom(?string $preferredBedType = null): ?Room
    {
        // If preferred bed type is specified, try to find available room of that type
        if ($preferredBedType && in_array($preferredBedType, ['A', 'B'])) {
            $room = Room::available()
                ->byBedType($preferredBedType)
                ->whereDoesntHave('activeBooking')
                ->orderBy('room_number')
                ->first();
            
            if ($room) {
                return $room;
            }
        }

        // If no preferred type or no room of preferred type available,
        // find any available room
        $room = Room::available()
            ->whereDoesntHave('activeBooking')
            ->orderBy('bed_type')
            ->orderBy('room_number')
            ->first();

        return $room;
    }

    /**
     * Get available rooms grouped by bed type.
     *
     * @return array
     */
    public function getAvailableRoomsByType(): array
    {
        $rooms = Room::available()
            ->whereDoesntHave('activeBooking')
            ->orderBy('bed_type')
            ->orderBy('room_number')
            ->get()
            ->groupBy('bed_type');

        return [
            'A' => $rooms->get('A', collect()),
            'B' => $rooms->get('B', collect()),
        ];
    }

    /**
     * Calculate total amount for a booking.
     *
     * @param string $bedType
     * @param int $numberOfNights
     * @return float
     */
    public function calculateTotalAmount(string $bedType, int $numberOfNights): float
    {
        $rate = RoomRate::active()
            ->byBedType($bedType)
            ->first();

        if (!$rate) {
            throw new \Exception("No active rate found for bed type: {$bedType}");
        }

        return $rate->rate_per_night * $numberOfNights;
    }

    /**
     * Check if a room is available for a specific time period.
     *
     * @param Room $room
     * @param Carbon $checkIn
     * @param Carbon $checkOut
     * @return bool
     */
    public function isRoomAvailableForPeriod(Room $room, Carbon $checkIn, Carbon $checkOut): bool
    {
        // Check if there are any conflicting bookings
        $conflictingBookings = $room->bookings()
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where(function ($q) use ($checkIn, $checkOut) {
                    // Booking starts before our check-in and ends after our check-in
                    $q->where('check_in_time', '<', $checkOut)
                      ->where('scheduled_checkout_time', '>', $checkIn)
                      ->whereIn('status', ['active', 'pending_checkout']);
                });
            })
            ->exists();

        return !$conflictingBookings;
    }

    /**
     * Get room occupancy statistics.
     *
     * @return array
     */
    public function getOccupancyStats(): array
    {
        $totalRooms = Room::count();
        $occupiedRooms = Room::whereHas('activeBooking')->count();
        $availableRooms = $totalRooms - $occupiedRooms;

        $occupiedByType = [
            'A' => Room::byBedType('A')->whereHas('activeBooking')->count(),
            'B' => Room::byBedType('B')->whereHas('activeBooking')->count(),
        ];

        return [
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedRooms,
            'available_rooms' => $availableRooms,
            'occupancy_rate' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0,
            'occupied_by_type' => $occupiedByType,
        ];
    }
}