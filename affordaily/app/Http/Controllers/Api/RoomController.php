<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomRate;
use App\Services\RoomAssignmentService;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    protected $roomAssignmentService;

    public function __construct(RoomAssignmentService $roomAssignmentService)
    {
        $this->roomAssignmentService = $roomAssignmentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Room::with(['activeBooking.guest_name', 'activeBooking.scheduled_checkout_time']);

        if ($request->bed_type) {
            $query->byBedType($request->bed_type);
        }

        if ($request->available !== null) {
            if ($request->available) {
                $query->available()->whereDoesntHave('activeBooking');
            } else {
                $query->whereHas('activeBooking');
            }
        }

        $rooms = $query->orderBy('bed_type')->orderBy('room_number')->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'room_number' => 'required|string|unique:rooms,room_number',
            'bed_type' => 'required|in:A,B',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $room = Room::create([
                'room_number' => $request->room_number,
                'bed_type' => $request->bed_type,
                'description' => $request->description,
                'is_available' => true,
            ]);

            return response()->json([
                'success' => true,
                'data' => $room
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $room = Room::with(['activeBooking', 'bookings' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(5);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = \Validator::make($request->all(), [
            'room_number' => 'sometimes|required|string|unique:rooms,room_number,' . $id,
            'bed_type' => 'sometimes|required|in:A,B',
            'description' => 'nullable|string',
            'is_available' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $room = Room::findOrFail($id);
            $room->update($request->only(['room_number', 'bed_type', 'description', 'is_available']));

            return response()->json([
                'success' => true,
                'data' => $room
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $room = Room::findOrFail($id);
            
            // Check if room has active bookings
            if ($room->activeBooking()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete room with active bookings'
                ], 400);
            }

            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available rooms by bed type.
     */
    public function available(Request $request)
    {
        $availableRooms = $this->roomAssignmentService->getAvailableRoomsByType();

        return response()->json([
            'success' => true,
            'data' => $availableRooms
        ]);
    }

    /**
     * Get room occupancy statistics.
     */
    public function occupancy()
    {
        $stats = $this->roomAssignmentService->getOccupancyStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get room rates.
     */
    public function rates()
    {
        $rates = RoomRate::active()->get();

        return response()->json([
            'success' => true,
            'data' => $rates
        ]);
    }

    /**
     * Update room rates.
     */
    public function updateRates(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'rates' => 'required|array',
            'rates.A' => 'required|numeric|min:0',
            'rates.B' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Deactivate current rates
            RoomRate::active()->update(['is_active' => false]);

            // Create new rates
            foreach ($request->rates as $bedType => $rate) {
                RoomRate::create([
                    'bed_type' => $bedType,
                    'rate_per_night' => $rate,
                    'is_active' => true,
                ]);
            }

            $newRates = RoomRate::active()->get();

            return response()->json([
                'success' => true,
                'data' => $newRates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating rates: ' . $e->getMessage()
            ], 500);
        }
    }
}
