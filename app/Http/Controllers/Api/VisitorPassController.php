<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VisitorPass\IssueVisitorPassRequest;
use App\Http\Resources\VisitorPassResource;
use App\Actions\VisitorPass\IssueVisitorPassAction;
use App\Actions\VisitorPass\CheckoutVisitorAction;
use App\Models\VisitorPass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitorPassController extends Controller
{
    protected IssueVisitorPassAction $issueVisitorPassAction;
    protected CheckoutVisitorAction $checkoutVisitorAction;

    public function __construct(
        IssueVisitorPassAction $issueVisitorPassAction,
        CheckoutVisitorAction $checkoutVisitorAction
    ) {
        $this->issueVisitorPassAction = $issueVisitorPassAction;
        $this->checkoutVisitorAction = $checkoutVisitorAction;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = VisitorPass::with(['guest', 'booking.room', 'issuedBy']);

        if ($request->booking_id) {
            $query->where('booking_id', $request->booking_id);
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        $visitorPasses = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => VisitorPassResource::collection($visitorPasses)
        ]);
    }

    /**
     * Store a newly created resource in storage (Issue visitor pass).
     */
    public function store(IssueVisitorPassRequest $request)
    {
        try {
            $visitorPass = $this->issueVisitorPassAction->execute(
                $request->booking_id,
                $request->visitor_phone,
                $request->visitor_name,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'data' => new VisitorPassResource($visitorPass)
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
        $visitorPass = VisitorPass::with(['guest', 'booking.room', 'issuedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new VisitorPassResource($visitorPass)
        ]);
    }

    /**
     * Check out a visitor.
     */
    public function checkout(string $id)
    {
        try {
            $visitorPass = VisitorPass::findOrFail($id);
            $visitorPass = $this->checkoutVisitorAction->execute($visitorPass);

            return response()->json([
                'success' => true,
                'data' => new VisitorPassResource($visitorPass)
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
            $activeVisitorPasses = VisitorPass::where('booking_id', $bookingId)
                ->where('is_active', true)
                ->with(['guest', 'issuedBy'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => VisitorPassResource::collection($activeVisitorPasses)
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
            $visitorPasses = VisitorPass::where('booking_id', $bookingId)
                ->with(['guest', 'issuedBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => VisitorPassResource::collection($visitorPasses)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching visitor passes: ' . $e->getMessage()
            ], 500);
        }
    }
}
