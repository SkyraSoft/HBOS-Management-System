<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class SaleController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('view sales') && !$user->hasAnyRole(['Business Owner', 'Branch Manager', 'Salesperson'])) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = $user ? $this->saleService->getActiveBusinessId($user) : null;
        $query = Sale::with(['items.product', 'customer', 'branch', 'user', 'returns'])
            ->orderBy('created_at', 'desc');

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        // Branch & Role scoping
        if ($user && !$user->hasRole('Business Owner')) {
            if ($user->branch_id && intval($user->branch_id) > 0) {
                $query->where('branch_id', $user->branch_id);
            }
            // Salespersons (roles without 'manage sales' permission) see their own sales only
            if (!$user->can('manage sales')) {
                $query->where('user_id', $user->id);
            }
        }

        $sales = $query->get();
        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('create sales') && !$user->hasAnyRole(['Business Owner', 'Branch Manager', 'Salesperson'])) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'branch_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'invoice_number' => 'nullable|string',
            'date' => 'nullable|date',
            'subtotal' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric',
            'items.*.discount' => 'nullable|numeric',
        ]);

        try {
            $sale = $this->saleService->createSale($request->all(), $user);
            return response()->json($sale, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        if ($user && !$user->can('view sales') && !$user->hasAnyRole(['Business Owner', 'Branch Manager', 'Salesperson'])) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = $user ? $this->saleService->getActiveBusinessId($user) : null;
        $query = Sale::with(['items.product', 'customer', 'branch', 'user', 'returns.items.product', 'cancelledBy']);
        
        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $sale = $query->findOrFail($id);

        // Branch & Role authorization check
        if ($user && !$user->hasRole('Business Owner')) {
            if ($user->branch_id && intval($sale->branch_id) !== intval($user->branch_id)) {
                return response()->json(['message' => 'Unauthorized access to sale record.'], 403);
            }
            if (!$user->can('manage sales') && intval($sale->user_id) !== intval($user->id)) {
                return response()->json(['message' => 'Unauthorized access to sale record.'], 403);
            }
        }

        return response()->json($sale);
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        if ($user && !$user->can('manage sales') && !$user->hasRole('Business Owner')) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = $this->saleService->getActiveBusinessId($user);
        $sale = Sale::where('business_id', $businessId)->findOrFail($id);

        // Immutability Protection: Cannot modify financial or line item details of a posted sale
        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $sale->update([
            'notes' => $request->notes ?? $sale->notes,
        ]);

        return response()->json($sale);
    }

    public function destroy(Request $request, string $id)
    {
        // HARD DELETE PROHIBITION
        return response()->json([
            'error' => 'Posted sales cannot be deleted. Use cancellation or return endpoint.'
        ], 422);
    }

    public function cancel(Request $request, string $id)
    {
        $user = $request->user();
        if ($user && !$user->can('manage sales') && !$user->hasAnyRole(['Business Owner', 'Branch Manager'])) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $businessId = $this->saleService->getActiveBusinessId($user);
        $sale = Sale::where('business_id', $businessId)->findOrFail($id);

        try {
            $cancelledSale = $this->saleService->cancelSale($sale, $request->reason, $user);

            app(\App\Services\AuditService::class)->log(
                logName: 'sale',
                event: 'cancelled',
                description: "Sale {$sale->invoice_number} cancelled. Reason: {$request->reason}",
                subject: $cancelledSale,
                properties: [
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total' => (float) $sale->total,
                    'reason' => $request->reason,
                ],
                branchId: $sale->branch_id,
                businessId: (int) $sale->business_id
            );

            return response()->json($cancelledSale);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function returns(Request $request, string $id)
    {
        $user = $request->user();
        if ($user && !$user->can('return sales') && !$user->hasAnyRole(['Business Owner', 'Branch Manager'])) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $businessId = $this->saleService->getActiveBusinessId($user);
        $sale = Sale::where('business_id', $businessId)->findOrFail($id);

        try {
            $saleReturn = $this->saleService->processReturn($sale, $request->items, $request->reason, $user, $request->idempotency_key);

            app(\App\Services\AuditService::class)->log(
                logName: 'sale',
                event: 'returned',
                description: "Sale return processed for invoice {$sale->invoice_number}. Refund obligation: {$saleReturn->refund_amount}",
                subject: $saleReturn,
                properties: [
                    'sale_id' => $sale->id,
                    'sale_return_id' => $saleReturn->id,
                    'invoice_number' => $sale->invoice_number,
                    'refund_amount' => (float) $saleReturn->refund_amount,
                    'reason' => $request->reason,
                ],
                branchId: $sale->branch_id,
                businessId: (int) $sale->business_id
            );

            return response()->json($saleReturn, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function listReturns(Request $request, string $id)
    {
        $user = $request->user();
        if ($user && !$user->can('view sales') && !$user->hasAnyRole(['Business Owner', 'Branch Manager', 'Salesperson'])) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $businessId = $this->saleService->getActiveBusinessId($user);
        $sale = Sale::where('business_id', $businessId)->findOrFail($id);
        $returns = $sale->returns()->with(['items.product', 'user', 'branch'])->get();

        return response()->json($returns);
    }
}
