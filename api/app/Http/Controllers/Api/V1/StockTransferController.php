<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Exception;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    protected StockTransferService $transferService;

    public function __construct(StockTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Display a listing of stock transfers.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->hasRole('Cashier')) {
            return response()->json(['message' => 'Unauthorized: Cashiers cannot access stock transfers.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        $query = StockTransfer::where('business_id', $businessId)
            ->with(['fromBranch', 'toBranch', 'items.product', 'dispatchedBy', 'receivedBy'])
            ->latest();

        // Scope for Branch Manager
        if (!$user->hasRole('Business Owner')) {
            $assignedBranchIds = [];
            if ($user->branch_id) {
                $assignedBranchIds[] = (int) $user->branch_id;
            }
            if (method_exists($user, 'branches')) {
                $assignedBranchIds = array_unique(array_merge(
                    $assignedBranchIds,
                    $user->branches()->pluck('branches.id')->map(fn($id) => (int)$id)->toArray()
                ));
            }

            $query->where(function ($q) use ($assignedBranchIds) {
                $q->whereIn('from_branch_id', $assignedBranchIds)
                  ->orWhereIn('to_branch_id', $assignedBranchIds);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_branch_id')) {
            $query->where('from_branch_id', (int) $request->from_branch_id);
        }

        if ($request->filled('to_branch_id')) {
            $query->where('to_branch_id', (int) $request->to_branch_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * Store a newly created draft stock transfer.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->hasRole('Cashier')) {
            return response()->json(['message' => 'Unauthorized: Cashiers cannot create stock transfers.'], 403);
        }

        $validated = $request->validate([
            'from_branch_id' => 'required|integer',
            'to_branch_id' => 'required|integer|different:from_branch_id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        try {
            $transfer = $this->transferService->createTransfer($validated, $user);
            return response()->json([
                'message' => 'Stock transfer created successfully.',
                'data' => $transfer
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified stock transfer.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if ($user->hasRole('Cashier')) {
            return response()->json(['message' => 'Unauthorized: Cashiers cannot view stock transfers.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $transfer = StockTransfer::where('business_id', $businessId)
            ->with(['fromBranch', 'toBranch', 'items.product', 'dispatchedBy', 'receivedBy', 'cancelledBy'])
            ->findOrFail($id);

        if (!$user->hasRole('Business Owner')) {
            $isAuthorized = $this->transferService->isUserAuthorizedForBranch($user, (int) $transfer->from_branch_id)
                || $this->transferService->isUserAuthorizedForBranch($user, (int) $transfer->to_branch_id);
            if (!$isAuthorized) {
                return response()->json(['message' => 'Unauthorized: You are not assigned to either branch in this transfer.'], 403);
            }
        }

        return response()->json(['data' => $transfer]);
    }

    /**
     * Dispatch the stock transfer (source stock decrement).
     */
    public function dispatch(Request $request, $id)
    {
        $user = $request->user();
        try {
            $transfer = $this->transferService->dispatchTransfer((int) $id, $user);
            return response()->json([
                'message' => 'Stock transfer dispatched successfully. Goods are now in-transit.',
                'data' => $transfer
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Receive the stock transfer (destination stock increment).
     */
    public function receive(Request $request, $id)
    {
        $user = $request->user();
        try {
            $transfer = $this->transferService->receiveTransfer((int) $id, $user);
            return response()->json([
                'message' => 'Stock transfer received and verified successfully.',
                'data' => $transfer
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cancel the stock transfer.
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $reason = $request->input('reason', 'Cancelled by user');

        try {
            $transfer = $this->transferService->cancelTransfer((int) $id, $user, $reason);
            return response()->json([
                'message' => 'Stock transfer cancelled successfully.',
                'data' => $transfer
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Printable Delivery Challan Data.
     */
    public function challan(Request $request, $id)
    {
        $user = $request->user();
        if ($user->hasRole('Cashier')) {
            return response()->json(['message' => 'Unauthorized: Cashiers cannot print transfer challans.'], 403);
        }

        try {
            $data = $this->transferService->getChallanData((int) $id);
            return response()->json(['data' => $data]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
