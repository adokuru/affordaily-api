<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\VisitorPass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VisitorPassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = VisitorPass::with(['booking.room', 'issuedBy']);

        if ($request->booking_id) {
            $query->where('booking_id', $request->booking_id);
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        $visitorPasses = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $visitorPasses
        ]);
    }

    /**
     * Store a newly created resource in storage (Issue visitor pass).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'visitor_name' => 'required|string|max:255',
            'visitor_phone' => 'nullable|string|max:20',
            'visitor_id_photo_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($request->booking_id);
            
            if ($booking->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot issue visitor pass for inactive booking'
                ], 400);
            }

            $visitorPass = VisitorPass::create([
                'booking_id' => $request->booking_id,
                'visitor_name' => $request->visitor_name,
                'visitor_phone' => $request->visitor_phone,
                'visitor_id_photo_path' => $request->visitor_id_photo_path,
                'check_in_time' => now(),
                'is_active' => true,
                'issued_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $visitorPass->load(['booking.room', 'issuedBy'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error issuing visitor pass: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $visitorPass = VisitorPass::with(['booking.room', 'issuedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $visitorPass
        ]);
    }

    /**
     * Check out a visitor.
     */
    public function checkout(string $id)
    {
        try {
            $visitorPass = VisitorPass::findOrFail($id);
            
            if (!$visitorPass->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visitor pass is not active'
                ], 400);
            }

            $visitorPass->update([
                'is_active' => false,
                'check_out_time' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $visitorPass->load(['booking.room', 'issuedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking out visitor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active visitor passes for a booking.
     */
    public function activeForBooking(string $bookingId)
    {
        try {
            $booking = Booking::findOrFail($bookingId);
            
            $activeVisitorPasses = $booking->activeVisitorPasses()
                ->with('issuedBy')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $activeVisitorPasses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching visitor passes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all visitor passes for a booking.
     */
    public function forBooking(string $bookingId)
    {
        try {
            $booking = Booking::findOrFail($bookingId);
            
            $visitorPasses = $booking->visitorPasses()
                ->with('issuedBy')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $visitorPasses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching visitor passes: ' . $e->getMessage()
            ], 500);
        }
    }
}
