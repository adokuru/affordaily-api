<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\VisitorPassController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Room routes
    Route::apiResource('rooms', RoomController::class);
    Route::get('/rooms/available', [RoomController::class, 'available']);
    Route::get('/rooms/occupancy', [RoomController::class, 'occupancy']);
    Route::get('/rooms/rates', [RoomController::class, 'rates']);
    Route::post('/rooms/rates', [RoomController::class, 'updateRates']);

    // Booking routes
    Route::apiResource('bookings', BookingController::class);
    Route::post('/bookings/{id}/checkout', [BookingController::class, 'checkout']);
    Route::post('/bookings/{id}/extend', [BookingController::class, 'extend']);
    Route::get('/bookings/search', [BookingController::class, 'search']);
    Route::get('/bookings/active', [BookingController::class, 'active']);

    // Visitor pass routes
    Route::apiResource('visitor-passes', VisitorPassController::class);
    Route::post('/visitor-passes/{id}/checkout', [VisitorPassController::class, 'checkout']);
    Route::get('/visitor-passes/booking/{bookingId}/active', [VisitorPassController::class, 'activeForBooking']);
    Route::get('/visitor-passes/booking/{bookingId}/all', [VisitorPassController::class, 'forBooking']);

    // Payment routes
    Route::apiResource('payments', PaymentController::class);
    Route::post('/payments/{id}/confirm', [PaymentController::class, 'confirm']);
    Route::get('/payments/ledger', [PaymentController::class, 'ledger']);

    // Dashboard routes
    Route::get('/dashboard/stats', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'total_rooms' => \App\Models\Room::count(),
                'occupied_rooms' => \App\Models\Room::whereHas('activeBooking')->count(),
                'available_rooms' => \App\Models\Room::whereDoesntHave('activeBooking')->count(),
                'active_bookings' => \App\Models\Booking::active()->count(),
                'pending_checkouts' => \App\Models\Booking::pendingCheckout()->count(),
                'total_visitors' => \App\Models\VisitorPass::active()->count(),
            ]
        ]);
    });

    Route::get('/dashboard/roll-call', function () {
        $activeBookings = \App\Models\Booking::active()
            ->with(['room', 'visitorPasses' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activeBookings
        ]);
    });

    Route::get('/dashboard/payments', function (Request $request) {
        $query = \App\Models\Payment::with(['booking.room', 'processedBy']);

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->confirmed !== null) {
            $query->where('is_confirmed', $request->confirmed);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    });
});