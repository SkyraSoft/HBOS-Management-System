<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveBusiness;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Purchase;
use App\Models\Product;
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

        // Check if querying last price for supplier and product
        if ($request->has('supplier_id') && $request->has('product_id') && ($request->query('action') === 'last-price' || $request->has('last_price') || $request->has('price_history'))) {
            return $this->lastPrice($request);
        }

        $query = Purchase::with(['items.product', 'supplier', 'branch'])
            ->where('business_id', $businessId);

        // Branch Manager restricted to assigned branch
        if ($user->hasRole('Branch Manager') && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchases = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();
        foreach ($purchases as $purchase) {
            $this->decorateLandedCost($purchase);
        }

        return response()->json($purchases);
    }

    public function lastPrice(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('view purchases')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();
        $supplierId = $request->input('supplier_id');
        $productId = $request->input('product_id');

        if (!$supplierId || !$productId) {
            return response()->json(['error' => 'supplier_id and product_id are required.'], 422);
        }

        $history = \App\Models\PurchaseItem::query()
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.business_id', $businessId)
            ->where('purchases.supplier_id', $supplierId)
            ->where('purchase_items.product_id', $productId)
            ->where('purchases.status', 'received')
            ->orderBy('purchases.date', 'desc')
            ->orderBy('purchases.id', 'desc')
            ->select('purchase_items.*', 'purchases.date as purchase_date', 'purchases.po_number')
            ->take(2)
            ->get();

        if ($history->isEmpty()) {
            return response()->json([
                'found' => false,
                'last_price' => null,
                'last_date' => null,
                'po_number' => null,
                'previous_price' => null,
                'trend_percentage' => 0.0,
                'product_id' => (int) $productId,
                'supplier_id' => (int) $supplierId
            ]);
        }

        $last = $history[0];
        $prev = $history->count() > 1 ? $history[1] : null;

        $trendPercentage = 0.0;
        if ($prev && (float) $prev->unit_cost > 0) {
            $trendPercentage = round((((float) $last->unit_cost - (float) $prev->unit_cost) / (float) $prev->unit_cost) * 100, 2);
        }

        $requestedUnit = $request->filled('unit') ? strtolower(trim((string)$request->input('unit'))) : null;
        $product = Product::where('business_id', $businessId)->find($productId);
        $baseUnit = $product ? strtolower(trim((string)$product->unit)) : null;
        $saleUnit = $product ? strtolower(trim((string)$product->sale_unit)) : null;
        $conversionFactor = $product ? (float) $product->conversion_factor : 1.0;

        $unitStatus = 'NORMALIZED';
        $normalizedLastPrice = (float) $last->unit_cost;
        $normalizedPrevPrice = $prev ? (float) $prev->unit_cost : null;

        if ($requestedUnit !== null) {
            if ($baseUnit && $requestedUnit === $baseUnit) {
                $unitStatus = 'NORMALIZED';
            } elseif ($saleUnit && $requestedUnit === $saleUnit && $conversionFactor > 0) {
                $unitStatus = 'NORMALIZED';
                $normalizedLastPrice = round($normalizedLastPrice / $conversionFactor, 4);
                if ($normalizedPrevPrice !== null) {
                    $normalizedPrevPrice = round($normalizedPrevPrice / $conversionFactor, 4);
                }
            } else {
                $unitStatus = 'NOT_COMPARABLE';
                $normalizedLastPrice = null;
                $normalizedPrevPrice = null;
                $trendPercentage = null;
            }
        }

        return response()->json([
            'found' => true,
            'last_price' => (float) $last->unit_cost,
            'last_date' => $last->purchase_date,
            'po_number' => $last->po_number,
            'previous_price' => $prev ? (float) $prev->unit_cost : null,
            'trend_percentage' => $trendPercentage,
            'unit_status' => $unitStatus,
            'normalized_last_price' => $normalizedLastPrice,
            'normalized_previous_price' => $normalizedPrevPrice,
            'product_id' => (int) $productId,
            'supplier_id' => (int) $supplierId
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->can('manage purchases')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $businessId = ResolveActiveBusiness::requireActiveBusinessId();

        $validated = $request->validate([
            'supplier_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'po_number' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('purchases', 'po_number')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                })
            ],
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'freight_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        try {
            $purchase = $this->purchaseService->createPurchase($validated, $user);
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
        $this->decorateLandedCost($purchase);

        return response()->json($purchase);
    }

    protected function decorateLandedCost(Purchase $purchase): void
    {
        $freightAmount = round((float) $purchase->freight_amount, 2);
        $subtotal = round((float) $purchase->subtotal, 2);

        if (!$purchase->relationLoaded('items')) {
            return;
        }

        $totalAllocated = 0.0;
        $highestSubtotal = -1.0;
        $highestItem = null;

        foreach ($purchase->items as $item) {
            $itemTotal = round((float) $item->total, 2);
            if ($freightAmount > 0 && $subtotal > 0) {
                $allocated = round($freightAmount * ($itemTotal / $subtotal), 2);
            } else {
                $allocated = 0.0;
            }
            $item->allocated_freight = $allocated;
            $totalAllocated = round($totalAllocated + $allocated, 2);

            if ($itemTotal > $highestSubtotal) {
                $highestSubtotal = $itemTotal;
                $highestItem = $item;
            }
        }

        // Reconcile residual discrepancy to highest subtotal line item
        if ($freightAmount > 0 && $subtotal > 0 && $highestItem) {
            $residual = round($freightAmount - $totalAllocated, 2);
            if ($residual != 0.0) {
                $highestItem->allocated_freight = round($highestItem->allocated_freight + $residual, 2);
            }
        }

        foreach ($purchase->items as $item) {
            $qty = (float) $item->quantity;
            $item->landed_unit_cost = ($qty > 0)
                ? round((float) $item->unit_cost + ($item->allocated_freight / $qty), 4)
                : (float) $item->unit_cost;
            $item->effective_unit_cost = $item->landed_unit_cost;
        }
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
