<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Exception;

class PurchaseController extends Controller
{
    protected $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('view purchases')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $query = Purchase::with(['items.product', 'supplier', 'branch'])
            ->where('business_id', $businessId);

        // Branch Manager restricted to assigned branch
        if ($user->hasRole('Branch Manager') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $purchases = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();
        return response()->json($purchases);
    }

    public function store(StorePurchaseRequest $request)
    {
        $user = $request->user();

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        // Check PO Number uniqueness per tenant
        $exists = Purchase::where('business_id', $businessId)
            ->where('po_number', $request->po_number)
            ->exists();

        if ($exists) {
            return response()->json(['error' => "PO Number '{$request->po_number}' already exists for this business."], 422);
        }

        try {
            $purchase = $this->purchaseService->createPurchase($request->validated(), $user);
            return response()->json($purchase, 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('view purchases')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $query = Purchase::with(['items.product', 'supplier', 'branch'])
            ->where('business_id', $businessId);

        if ($user->hasRole('Branch Manager') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $purchase = $query->findOrFail($id);
        return response()->json($purchase);
    }

    public function cancel(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('return purchases')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $purchase = Purchase::where('business_id', $businessId)->findOrFail($id);

        try {
            $reason = $request->input('reason');
            $cancelledPurchase = $this->purchaseService->cancelPurchase($purchase, $user, $reason);

            app(\App\Services\AuditService::class)->log(
                logName: 'purchase',
                event: 'cancelled',
                description: "Purchase #{$purchase->id} cancelled. Reason: " . ($reason ?? 'No reason provided'),
                subject: $cancelledPurchase,
                properties: [
                    'purchase_id' => $purchase->id,
                    'supplier_id' => $purchase->supplier_id,
                    'total' => (float) $purchase->total,
                    'reason' => $reason,
                ],
                branchId: $purchase->branch_id ?? null,
                businessId: (int) $purchase->business_id
            );

            return response()->json($cancelledPurchase);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('return purchases')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        // Hard deletion of posted purchases is prohibited
        return response()->json([
            'error' => 'Hard deletion of posted purchases is prohibited for audit integrity. Use POST /api/v1/purchases/{id}/cancel to cancel a purchase.'
        ], 422);
    }
}
