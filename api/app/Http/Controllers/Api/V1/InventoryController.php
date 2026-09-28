<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Models\BranchInventory;
use App\Models\InventoryMovement;
use App\Models\Branch;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Exception;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * List branch inventory balances
     */
    public function index(Request $request)
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        if ($businessId && function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($businessId);
        }

        if ($request->user() && !$request->user()->hasPermissionTo('view inventory')) {
            return response()->json(['message' => 'Unauthorized action. Required permission: view inventory'], 403);
        }

        $userBranchId = $request->user() ? $request->user()->branch_id : null;
        $isOwner = $request->user() && $request->user()->hasRole('Business Owner');

        $query = BranchInventory::with(['product', 'branch']);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        // Branch scoping
        if (!$isOwner && $userBranchId) {
            $query->where('branch_id', $userBranchId);
        } elseif ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', intval($request->branch_id));
        }

        // Product search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Low stock filter
        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity_on_hand', '<=', 'minimum_stock');
        }

        $inventories = $query->orderBy('updated_at', 'desc')->get();

        return response()->json($inventories);
    }

    /**
     * View inventory movement history
     */
    public function movements(Request $request)
    {
        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        if ($businessId && function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($businessId);
        }

        $user = $request->user();
        if ($user && !$user->hasRole('Business Owner') && !$user->hasPermissionTo('view stock movements')) {
            return response()->json(['message' => 'Unauthorized action. Required permission: view stock movements'], 403);
        }
        $userBranchId = $request->user() ? $request->user()->branch_id : null;
        $isOwner = $request->user() && $request->user()->hasRole('Business Owner');

        $query = InventoryMovement::with(['product', 'branch', 'performedBy']);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        if (!$isOwner && $userBranchId) {
            $query->where('branch_id', $userBranchId);
        } elseif ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', intval($request->branch_id));
        }

        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', intval($request->product_id));
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($movements);
    }

    /**
     * Perform manual stock adjustment (adjustment_in, adjustment_out, damage)
     */
    public function adjust(Request $request)
    {
        if ($request->user() && !$request->user()->hasPermissionTo('adjust inventory')) {
            return response()->json(['message' => 'Unauthorized action. Required permission: adjust inventory'], 403);
        }

        $request->validate([
            'branch_id' => 'required|integer',
            'product_id' => 'required|integer',
            'type' => 'required|string|in:adjustment_in,adjustment_out,damage',
            'quantity' => 'required|integer|min:1',
            'notes' => 'required|string|min:3',
            'unit_cost' => 'nullable|numeric',
        ]);

        try {
            $businessId = ResolveActiveBusiness::requireActiveBusinessId();
            $userBranchId = $request->user() ? $request->user()->branch_id : null;
            $isOwner = $request->user() && $request->user()->hasRole('Business Owner');

            if (!$isOwner && $userBranchId && intval($request->branch_id) !== intval($userBranchId)) {
                return response()->json(['message' => 'Cannot adjust inventory for a branch you are not assigned to.'], 403);
            }

            $movement = $this->inventoryService->adjustStock([
                'business_id' => $businessId,
                'branch_id' => intval($request->branch_id),
                'product_id' => intval($request->product_id),
                'type' => $request->type,
                'quantity' => intval($request->quantity),
                'notes' => $request->notes,
                'unit_cost' => $request->unit_cost,
                'performed_by' => $request->user() ? $request->user()->id : null,
            ]);

            app(\App\Services\AuditService::class)->log(
                logName: 'inventory',
                event: 'updated',
                description: "Stock adjustment ({$request->type}) of qty {$request->quantity} on product #{$request->product_id}",
                subject: $movement,
                properties: [
                    'action' => 'inventory_adjustment',
                    'product_id' => (int) $request->product_id,
                    'branch_id' => (int) $request->branch_id,
                    'type' => $request->type,
                    'quantity' => (int) $request->quantity,
                    'notes' => $request->notes,
                ],
                branchId: (int) $request->branch_id,
                businessId: (int) $businessId
            );

            return response()->json([
                'message' => 'Stock adjustment recorded successfully',
                'movement' => $movement->load(['product', 'branch'])
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Transfer stock between branches
     */
    public function transfer(Request $request)
    {
        if ($request->user() && !$request->user()->hasPermissionTo('transfer inventory')) {
            return response()->json(['message' => 'Unauthorized action. Required permission: transfer inventory'], 403);
        }

        $request->validate([
            'from_branch_id' => 'required|integer',
            'to_branch_id' => 'required|integer|different:from_branch_id',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            $businessId = ResolveActiveBusiness::requireActiveBusinessId();
            $userBranchId = $request->user() ? $request->user()->branch_id : null;
            $isOwner = $request->user() && $request->user()->hasRole('Business Owner');

            if (!$isOwner && $userBranchId && intval($request->from_branch_id) !== intval($userBranchId)) {
                return response()->json(['message' => 'Cannot transfer stock from a branch you are not assigned to.'], 403);
            }

            $result = $this->inventoryService->transferStock([
                'business_id' => $businessId,
                'from_branch_id' => intval($request->from_branch_id),
                'to_branch_id' => intval($request->to_branch_id),
                'product_id' => intval($request->product_id),
                'quantity' => intval($request->quantity),
                'notes' => $request->notes ?? 'Inter-branch stock transfer',
                'performed_by' => $request->user() ? $request->user()->id : null,
            ]);

            app(\App\Services\AuditService::class)->log(
                logName: 'inventory',
                event: 'updated',
                description: "Stock transfer of qty {$request->quantity} from Branch #{$request->from_branch_id} to Branch #{$request->to_branch_id}",
                subject: $result['out'],
                properties: [
                    'action' => 'inventory_transfer',
                    'product_id' => (int) $request->product_id,
                    'from_branch_id' => (int) $request->from_branch_id,
                    'to_branch_id' => (int) $request->to_branch_id,
                    'quantity' => (int) $request->quantity,
                    'notes' => $request->notes,
                ],
                branchId: (int) $request->from_branch_id,
                businessId: (int) $businessId
            );

            return response()->json([
                'message' => 'Stock transfer recorded successfully',
                'out_movement' => $result['out'],
                'in_movement' => $result['in']
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
