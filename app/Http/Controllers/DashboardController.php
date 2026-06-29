<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomRate;
use App\Models\VisitorPass;
use App\Services\PaymentLedgerService;
use App\Services\RoomAssignmentService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly RoomAssignmentService $roomAssignmentService,
        private readonly PaymentLedgerService $paymentLedgerService
    ) {
    }

    /**
     * Show the dashboard.
     */
    public function index()
    {
        $stats = $this->getDashboardStats();
        $occupancyStats = $this->roomAssignmentService->getOccupancyStats();
        $recentBookings = Booking::with(['room', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $pendingCheckouts = Booking::pendingCheckout()
            ->with(['room'])
            ->orderBy('scheduled_checkout_time')
            ->get();

        return view('dashboard', compact('stats', 'occupancyStats', 'recentBookings', 'pendingCheckouts'));
    }

    /**
     * Show the rooms overview.
     */
    public function rooms()
    {
        $rooms = Room::with(['activeBooking'])
            ->orderBy('bed_type')
            ->orderBy('room_number')
            ->get();

        return view('rooms', compact('rooms'));
    }

    /**
     * Show the roll call (current occupants).
     */
    public function rollCall()
    {
        $activeBookings = Booking::active()
            ->with(['room', 'visitorPasses' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('scheduled_checkout_time')
            ->get();

        return view('roll-call', compact('activeBookings'));
    }

    /**
     * Show the payments ledger.
     */
    public function payments(Request $request)
    {
        $filters = $this->paymentLedgerService->validatedFilters($request);
        $query = $this->paymentLedgerService->query($filters);
        $summary = $this->paymentLedgerService->summary($query);

        $payments = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('payments', compact('payments', 'summary'));
    }

    /**
     * Show admin settings.
     */
    public function settings()
    {
        $roomRates = RoomRate::active()->get();
        $rooms = Room::orderBy('bed_type')->orderBy('room_number')->get();

        return view('settings', compact('roomRates', 'rooms'));
    }

    /**
     * Get dashboard statistics.
     */
    private function getDashboardStats()
    {
        return [
            'total_rooms' => Room::count(),
            'occupied_rooms' => Room::whereHas('activeBooking')->count(),
            'available_rooms' => Room::whereDoesntHave('activeBooking')->count(),
            'active_bookings' => Booking::active()->count(),
            'pending_checkouts' => Booking::pendingCheckout()->count(),
            'total_visitors' => VisitorPass::active()->count(),
            'today_payments' => Payment::whereDate('created_at', today())->sum('amount'),
            'month_payments' => Payment::whereMonth('created_at', now()->month)->sum('amount'),
        ];
    }
}
