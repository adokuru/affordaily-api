<?php

namespace App\Http\Controllers\Api;

use App\Actions\Room\GetAvailableRoomsAction;
use App\Actions\Room\GetRoomOccupancyStatsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Models\RoomRate;
use App\Services\CacheService;
use App\Services\RoomAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    protected RoomAssignmentService $roomAssignmentService;

    protected GetAvailableRoomsAction $getAvailableRoomsAction;

    protected GetRoomOccupancyStatsAction $getRoomOccupancyStatsAction;

    public function __construct(
        RoomAssignmentService $roomAssignmentService,
        GetAvailableRoomsAction $getAvailableRoomsAction,
        GetRoomOccupancyStatsAction $getRoomOccupancyStatsAction
    ) {
        $this->roomAssignmentService = $roomAssignmentService;
        $this->getAvailableRoomsAction = $getAvailableRoomsAction;
        $this->getRoomOccupancyStatsAction = $getRoomOccupancyStatsAction;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Room::with(['activeBooking']);

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
            'data' => RoomResource::collection($rooms),
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
                'errors' => $validator->errors(),
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
                'data' => $room,
            ], 201);

        } catch (\Exception $e) {
            return $this->serverError($e, 'Error creating room');
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
            'data' => $room,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = \Validator::make($request->all(), [
            'room_number' => 'sometimes|required|string|unique:rooms,room_number,'.$id,
            'bed_type' => 'sometimes|required|in:A,B',
            'description' => 'nullable|string',
            'is_available' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $room = Room::findOrFail($id);
            $room->update($request->only(['room_number', 'bed_type', 'description', 'is_available']));

            return response()->json([
                'success' => true,
                'data' => $room,
            ]);

        } catch (\Exception $e) {
            return $this->serverError($e, 'Error updating room');
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
                    'message' => 'Cannot delete room with active bookings',
                ], 400);
            }

            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully',
            ]);

        } catch (\Exception $e) {
            return $this->serverError($e, 'Error deleting room');
        }
    }

    /**
     * Get available rooms by bed type.
     */
    public function available(Request $request)
    {
        $availableRooms = CacheService::rememberAvailableRooms(function () use ($request) {
            return $this->getAvailableRoomsAction->execute($request->bed_type);
        }, $request->bed_type);

        return response()->json([
            'success' => true,
            'data' => $availableRooms,
        ]);
    }

    /**
     * Get room occupancy statistics.
     */
    public function occupancy()
    {
        $stats = CacheService::rememberRoomOccupancyStats(function () {
            return $this->getRoomOccupancyStatsAction->execute();
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
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
            'data' => $rates,
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
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $newRates = DB::transaction(function () use ($request) {
                RoomRate::active()->lockForUpdate()->update(['is_active' => false]);

                foreach ($request->rates as $bedType => $rate) {
                    RoomRate::create([
                        'bed_type' => $bedType,
                        'rate_per_night' => $rate,
                        'is_active' => true,
                    ]);
                }

                return RoomRate::active()->get();
            });

            CacheService::clearAllCache();

            return response()->json([
                'success' => true,
                'data' => $newRates,
            ]);

        } catch (\Exception $e) {
            return $this->serverError($e, 'Error updating rates');
        }
    }
}
