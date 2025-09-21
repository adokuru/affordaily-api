<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Payment;
use App\Models\VisitorPass;
use App\Services\RoomAssignmentService;

class DashboardController extends Controller
{
    protected $roomAssignmentService;

    public function __construct(RoomAssignmentService $roomAssignmentService)
    {
        $this->roomAssignmentService = $roomAssignmentService;
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
        $query = Payment::with(['booking.room', 'processedBy']);

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

        // Calculate summary
        $summary = [
            'total_amount' => $payments->sum('amount'),
            'cash_total' => $payments->where('payment_method', 'cash')->sum('amount'),
            'transfer_total' => $payments->where('payment_method', 'transfer')->sum('amount'),
            'confirmed_total' => $payments->where('is_confirmed', true)->sum('amount'),
            'pending_total' => $payments->where('is_confirmed', false)->sum('amount'),
        ];

        return view('payments', compact('payments', 'summary'));
    }

    /**
     * Show admin settings.
     */
    public function settings()
    {
        $roomRates = \App\Models\RoomRate::active()->get();
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
