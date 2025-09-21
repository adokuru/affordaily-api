<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GuestController extends Controller
{
    /**
     * Search for a guest by phone number.
     */
    public function searchByPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $guest = Guest::byPhone($request->phone)->notBlacklisted()->first();

        if (!$guest) {
            return response()->json([
                'success' => false,
                'message' => 'Guest not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $guest
        ]);
    }

    /**
     * Create or update guest information.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:guests,phone',
            'email' => 'nullable|email|max:255',
            'id_photo_path' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $guest = Guest::create($request->only([
                'name', 'phone', 'email', 'id_photo_path', 'notes'
            ]));

            return response()->json([
                'success' => true,
                'data' => $guest
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating guest: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update guest information.
     */
    public function update(Request $request, $id)
    {
        $guest = Guest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20|unique:guests,phone,' . $id,
            'email' => 'nullable|email|max:255',
            'id_photo_path' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $guest->update($request->only([
                'name', 'phone', 'email', 'id_photo_path', 'notes'
            ]));

            return response()->json([
                'success' => true,
                'data' => $guest->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating guest: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get guest details with booking history.
     */
    public function show($id)
    {
        $guest = Guest::with(['bookings.room', 'payments'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $guest
        ]);
    }

    /**
     * List all guests with pagination.
     */
    public function index(Request $request)
    {
        $query = Guest::notBlacklisted();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $guests = $query->orderBy('name')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $guests
        ]);
    }
}
