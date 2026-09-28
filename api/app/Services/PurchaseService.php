<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Branch;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\SupplierBalanceService;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseService
{
    protected $inventoryService;
    protected $supplierBalanceService;

    public function __construct(
        InventoryService $inventoryService,
        SupplierBalanceService $supplierBalanceService
    ) {
        $this->inventoryService = $inventoryService;
        $this->supplierBalanceService = $supplierBalanceService;
    }

    /**
     * Create a purchase transaction atomically.
     */
    public function createPurchase(array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $user) {
            $businessId = \App\Http\Middleware\ResolveActiveBusiness::getActiveBusinessId();
            if (!$businessId) {
                if ($user->businesses()->count() === 1) {
                    $businessId = (int) $user->businesses()->value('businesses.id');
                } else {
                    $businessId = \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();
                }
            }

            // 1. Resolve & Validate Branch
            $branchId = $data['branch_id'] ?? $user->branch_id;
            if (!$branchId) {
                $primaryBranch = Branch::where('business_id', $businessId)->where('is_primary', true)->first()
                    ?? Branch::where('business_id', $businessId)->first();
                $branchId = $primaryBranch ? $primaryBranch->id : null;
            }

            if (!$branchId) {
                throw new Exception("No active branch resolved for purchase transaction.");
            }

            // Verify Branch belongs to active business
            $branch = Branch::where('business_id', $businessId)->find($branchId);
            if (!$branch) {
                throw new Exception("Branch does not belong to active business tenant.");
            }

            // Verify user authorization for branch if user is Branch Manager
            if ($user->hasRole('Branch Manager') && $user->branch_id && (int)$user->branch_id !== (int)$branchId) {
                throw new Exception("Unauthorized: Branch Manager cannot create purchases for unassigned branch.");
            }

            // 2. Validate Supplier Tenant Scope
            $supplier = null;
            if (!empty($data['supplier_id'])) {
                $supplier = Supplier::where('business_id', $businessId)->find($data['supplier_id']);
                if (!$supplier) {
                    throw new Exception("Selected supplier does not belong to active business tenant.");
                }
            }

            // 3. Server-side Total Calculation & Item Validation
            if (empty($data['items']) || !is_array($data['items'])) {
                throw new Exception("Purchase must contain at least one item.");
            }

            $subtotal = 0.0;
            $validatedItems = [];

            foreach ($data['items'] as $itemData) {
                $quantity = (int) ($itemData['quantity'] ?? 0);
                $unitCost = round((float) ($itemData['unit_cost'] ?? 0), 2);

                if ($quantity <= 0) {
                    throw new Exception("Purchase item quantity must be greater than zero.");
                }
                if ($unitCost < 0) {
                    throw new Exception("Purchase item unit cost cannot be negative.");
                }

                // Verify Product belongs to active business tenant
                $product = Product::where('business_id', $businessId)->find($itemData['product_id']);
                if (!$product) {
                    throw new Exception("Product ID {$itemData['product_id']} does not belong to active business tenant.");
                }

                $lineTotal = round($quantity * $unitCost, 2);
                $subtotal = round($subtotal + $lineTotal, 2);

                $validatedItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total' => $lineTotal
                ];
            }

            $subtotal = round($subtotal, 2);
            $tax = round((float) ($data['tax'] ?? 0), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $total = round($subtotal + $tax - $discount, 2);

            $paidAmount = round((float) ($data['paid_amount'] ?? 0), 2);
            if ($paidAmount < 0) {
                throw new Exception("Paid amount cannot be negative.");
            }
            if ($paidAmount > $total + 0.0001) {
                throw new Exception("Paid amount ({$paidAmount}) cannot exceed total purchase amount ({$total}).");
            }

            $dueAmount = round($total - $paidAmount, 2);

            // 4. Create Purchase Record
            $purchase = Purchase::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'supplier_id' => $supplier ? $supplier->id : null,
                'user_id' => $user->id,
                'po_number' => $data['po_number'],
                'date' => $data['date'] ?? now()->toDateString(),
                'subtotal' => $subtotal,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => 'received',
                'notes' => $data['notes'] ?? null,
            ]);

            // 5. Create PurchaseItems & Receive Stock via InventoryService
            foreach ($validatedItems as $itemData) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'total' => $itemData['total']
                ]);

                $this->inventoryService->receiveStock([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'type' => 'purchase',
                    'unit_cost' => $itemData['unit_cost'],
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'performed_by' => $user->id,
                    'notes' => "Purchase PO #{$purchase->po_number}"
                ]);
            }

            // 6. Handle Supplier Balance & Payment
            if ($supplier) {
                // If initial payment was made at purchase creation, log SupplierPayment record with purchase_id provenance
                if ($paidAmount > 0) {
                    SupplierPayment::create([
                        'business_id' => $businessId,
                        'supplier_id' => $supplier->id,
                        'purchase_id' => $purchase->id,
                        'user_id' => $user->id,
                        'amount' => $paidAmount,
                        'date' => $purchase->date,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'notes' => "Initial payment for Purchase PO #{$purchase->po_number}"
                    ]);
                }

                // Record net unpaid due_amount liability on supplier balance
                if ($dueAmount > 0) {
                    $this->supplierBalanceService->recordPurchaseLiability($supplier, $dueAmount);
                }
            }

            return $purchase->load(['items.product', 'supplier', 'branch']);
        });
    }

    /**
     * Cancel a posted purchase transaction atomically.
     */
    public function cancelPurchase(Purchase $purchase, User $user, ?string $reason = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $user, $reason) {
            if ($purchase->status === 'cancelled') {
                throw new Exception("Purchase is already cancelled.");
            }

            // Financial Integrity Rule: Purchases with recorded payments cannot be cancelled directly
            if ((float) $purchase->paid_amount > 0) {
                throw new Exception("Purchase with recorded payments cannot be cancelled directly. Recorded payments must be resolved first.");
            }

            $businessId = $purchase->business_id;
            $branchId = $purchase->branch_id;

            // Check Branch Manager authorization
            if ($user->hasRole('Branch Manager') && $user->branch_id && (int)$user->branch_id !== (int)$branchId) {
                throw new Exception("Unauthorized: Branch Manager cannot cancel purchases for unassigned branch.");
            }

            // Issue compensating stock movements (type=purchase_return)
            foreach ($purchase->items as $item) {
                $this->inventoryService->issueStock([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'type' => 'purchase_return',
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'performed_by' => $user->id,
                    'notes' => "Purchase cancellation for PO #{$purchase->po_number}"
                ]);
            }

            // Reverse supplier liability
            if ($purchase->supplier_id && (float)$purchase->due_amount > 0) {
                $supplier = Supplier::where('business_id', $businessId)->find($purchase->supplier_id);
                if ($supplier) {
                    $this->supplierBalanceService->reversePurchaseLiability($supplier, (float)$purchase->due_amount);
                }
            }

            // Update status
            $purchase->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancellation_reason' => $reason
            ]);

            return $purchase->fresh(['items.product', 'supplier', 'branch']);
        });
    }
}
