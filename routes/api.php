<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\VisitorPassController;
use App\Http\Middleware\EnsureUserRole;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\VisitorPass;
use App\Services\PaymentLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// add V1 API routes
Route::prefix('v1')->group(function () {

    // Authentication routes
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // User info
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::get('/rooms/available', [RoomController::class, 'available']);
        Route::get('/rooms/occupancy', [RoomController::class, 'occupancy']);
        Route::get('/rooms/rates', [RoomController::class, 'rates']);
        Route::post('/rooms/rates', [RoomController::class, 'updateRates'])->middleware(EnsureUserRole::class.':admin');
        Route::apiResource('rooms', RoomController::class)->only(['index', 'show']);
        Route::apiResource('rooms', RoomController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware(EnsureUserRole::class.':admin');

        // Booking routes
        Route::get('/bookings/search', [BookingController::class, 'search']);
        Route::get('/bookings/active', [BookingController::class, 'active']);
        Route::post('/bookings/{id}/checkout', [BookingController::class, 'checkout']);
        Route::post('/bookings/{id}/extend', [BookingController::class, 'extend']);
        Route::apiResource('bookings', BookingController::class)->only(['index', 'store', 'show']);

        // Visitor pass routes
        Route::apiResource('visitor-passes', VisitorPassController::class)->only(['index', 'store', 'show']);
        Route::post('/visitor-passes/{id}/checkout', [VisitorPassController::class, 'checkout']);
        Route::get('/visitor-passes/booking/{bookingId}/active', [VisitorPassController::class, 'activeForBooking']);
        Route::get('/visitor-passes/booking/{bookingId}/all', [VisitorPassController::class, 'forBooking']);

        // Payment routes
        Route::get('/payments/ledger', [PaymentController::class, 'ledger']);
        Route::post('/payments/{id}/confirm', [PaymentController::class, 'confirm'])->middleware(EnsureUserRole::class.':admin');
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);
        Route::apiResource('payments', PaymentController::class)
            ->only(['update', 'destroy'])
            ->middleware(EnsureUserRole::class.':admin');

        // Guest routes
        Route::get('/guests/search/phone', [GuestController::class, 'searchByPhone']);
        Route::apiResource('guests', GuestController::class)->except(['destroy']);

        // Dashboard routes
        Route::get('/dashboard/stats', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_rooms' => Room::count(),
                    'occupied_rooms' => Room::whereHas('activeBooking')->count(),
                    'available_rooms' => Room::whereDoesntHave('activeBooking')->count(),
                    'active_bookings' => Booking::active()->count(),
                    'pending_checkouts' => Booking::pendingCheckout()->count(),
                    'total_visitors' => VisitorPass::active()->count(),
                ],
            ]);
        });

        Route::get('/dashboard/roll-call', function () {
            $activeBookings = Booking::active()
                ->with([
                    'room',
                    'visitorPasses' => function ($query) {
                        $query->where('is_active', true);
                    },
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $activeBookings,
            ]);
        });

        Route::get('/dashboard/payments', function (Request $request, PaymentLedgerService $paymentLedgerService) {
            $filters = $paymentLedgerService->validatedFilters($request);
            $query = $paymentLedgerService->query($filters);

            $payments = $query->orderBy('created_at', 'desc')->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $payments,
                'summary' => $paymentLedgerService->summary($query),
            ]);
        });
    });

});
