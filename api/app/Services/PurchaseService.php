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

            $subtotal = '0.00';
            $validatedItems = [];

            foreach ($data['items'] as $itemData) {
                $rawQty = (string) ($itemData['quantity'] ?? '0');
                if (!preg_match('/^\d+(\.\d{1,3})?$/', $rawQty)) {
                    throw new Exception("Purchase item quantity must be a positive number with at most 3 decimal places.");
                }
                $quantity = $rawQty;
                $unitCost = $this->roundMoney((string) ($itemData['unit_cost'] ?? 0), 2);

                if (bccomp((string) $quantity, '0.001', 3) < 0) {
                    throw new Exception("Purchase item quantity must be at least 0.001.");
                }
                if (bccomp((string) $unitCost, '0.00', 2) < 0) {
                    throw new Exception("Purchase item unit cost cannot be negative.");
                }

                // Verify Product belongs to active business tenant
                $product = Product::where('business_id', $businessId)->find($itemData['product_id']);
                if (!$product) {
                    throw new Exception("Product ID {$itemData['product_id']} does not belong to active business tenant.");
                }

                $lineTotal = $this->roundMoney(bcmul((string) $quantity, (string) $unitCost, 4), 2);
                $subtotal = bcadd((string) $subtotal, (string) $lineTotal, 2);

                $validatedItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total' => $lineTotal
                ];
            }

            $subtotal = $this->roundMoney((string) $subtotal, 2);
            $tax = isset($data['tax']) ? $this->roundMoney((string) $data['tax'], 2) : '0.00';
            $discount = isset($data['discount']) ? $this->roundMoney((string) $data['discount'], 2) : '0.00';
            $freightAmount = isset($data['freight_amount']) ? $this->roundMoney((string) $data['freight_amount'], 2) : '0.00';
            $total = bcadd(bcsub(bcadd((string) $subtotal, (string) $tax, 2), (string) $discount, 2), (string) $freightAmount, 2);

            $paidAmount = isset($data['paid_amount']) ? $this->roundMoney((string) $data['paid_amount'], 2) : '0.00';
            if (bccomp((string) $paidAmount, '0.00', 2) < 0) {
                throw new Exception("Paid amount cannot be negative.");
            }
            if (bccomp((string) $paidAmount, (string) $total, 2) > 0) {
                throw new Exception("Paid amount ({$paidAmount}) cannot exceed total purchase amount ({$total}).");
            }

            $dueAmount = $this->roundMoney(bcsub((string) $total, (string) $paidAmount, 4), 2);

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
                'freight_amount' => $freightAmount,
                'landed_cost_allocated' => bccomp((string) $freightAmount, '0.00', 2) > 0,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => 'received',
                'notes' => $data['notes'] ?? null,
            ]);

            // 5. Proportional Freight Landed Cost Allocation with Remainder Reconciliation
            $lineFreights = [];
            $totalAllocatedFreight = '0.00';
            $highestSubtotalIndex = 0;
            $highestSubtotal = '-1.00';

            foreach ($validatedItems as $idx => $itemData) {
                if (bccomp((string) $freightAmount, '0.00', 2) > 0 && bccomp((string) $subtotal, '0.00', 2) > 0) {
                    $ratio = bcdiv((string) $itemData['total'], (string) $subtotal, 8);
                    $allocated = $this->roundMoney(bcmul((string) $freightAmount, $ratio, 6), 2);
                } else {
                    $allocated = '0.00';
                }
                $lineFreights[$idx] = $allocated;
                $totalAllocatedFreight = bcadd((string) $totalAllocatedFreight, (string) $allocated, 2);

                if (bccomp((string) $itemData['total'], (string) $highestSubtotal, 2) > 0) {
                    $highestSubtotal = (string) $itemData['total'];
                    $highestSubtotalIndex = $idx;
                }
            }

            // Assign rounding residual discrepancy to highest subtotal line item
            if (bccomp((string) $freightAmount, '0.00', 2) > 0 && bccomp((string) $subtotal, '0.00', 2) > 0 && count($validatedItems) > 0) {
                $delta = bcsub((string) $freightAmount, (string) $totalAllocatedFreight, 2);
                if (bccomp((string) $delta, '0.00', 2) !== 0) {
                    $lineFreights[$highestSubtotalIndex] = bcadd((string) $lineFreights[$highestSubtotalIndex], (string) $delta, 2);
                }
            }

            // 6. Create PurchaseItems & Receive Stock via InventoryService (with effective landed cost)
            foreach ($validatedItems as $idx => $itemData) {
                $itemFreight = $lineFreights[$idx];
                $unitFreight = bccomp((string) $itemData['quantity'], '0.000', 3) > 0
                    ? bcdiv((string) $itemFreight, (string) $itemData['quantity'], 6)
                    : '0.000000';
                $effectiveUnitCost = $this->roundMoney(bcadd((string) $itemData['unit_cost'], $unitFreight, 6), 4);

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
                    'unit_cost' => $effectiveUnitCost,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'performed_by' => $user->id,
                    'notes' => "Purchase PO #{$purchase->po_number}"
                ]);
            }

            // 7. Handle Supplier Balance & Payment with Atomic Account Outflow
            if ($paidAmount > 0) {
                // Resolve & validate target financial account
                $targetAccount = null;
                if (!empty($data['financial_account_id'])) {
                    $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                        ->where('id', $data['financial_account_id'])
                        ->where('status', 'active')
                        ->first();
                    if (!$targetAccount) {
                        throw new Exception("Selected financial account is invalid or inactive.");
                    }
                    if ($targetAccount->branch_id && (int) $targetAccount->branch_id !== (int) $branchId) {
                        throw new Exception("Selected financial account does not belong to the purchase branch.");
                    }
                } else {
                    $isCash = strtolower(trim($data['payment_method'] ?? 'cash')) === 'cash';
                    if ($isCash) {
                        $targetAccount = app(\App\Services\FinancialAccountService::class)->getDefaultCashAccount($businessId, $branchId);
                        if (!$targetAccount) {
                            $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                                ->where('type', 'cash')
                                ->where('status', 'active')
                                ->where(function ($q) use ($branchId) {
                                    $q->where('branch_id', $branchId)->orWhereNull('branch_id');
                                })
                                ->first();
                        }
                    } else {
                        $targetAccount = \App\Models\FinancialAccount::where('business_id', $businessId)
                            ->where('type', 'bank')
                            ->where('status', 'active')
                            ->where(function ($q) use ($branchId) {
                                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
                            })
                            ->first();
                    }
                }

                if (!$targetAccount || $targetAccount->status !== 'active') {
                    throw new Exception("No active financial account available for purchase payment outflow.");
                }

                $supplierPayment = null;
                if ($supplier) {
                    $supplierPayment = SupplierPayment::create([
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

                // Post physical outflow account_movement atomically
                app(\App\Services\AccountMovementService::class)->postOutflow($targetAccount, [
                    'branch_id' => $branchId,
                    'movement_category' => 'supplier_payment',
                    'reference_type' => $supplierPayment ? SupplierPayment::class : Purchase::class,
                    'reference_id' => $supplierPayment ? $supplierPayment->id : $purchase->id,
                    'amount' => $paidAmount,
                    'date' => $purchase->date,
                    'description' => "Supplier Payment for Purchase PO #{$purchase->po_number}" . ($supplier ? " ({$supplier->name})" : ""),
                    'user_id' => $user->id,
                ]);

                if ($supplierPayment) {
                    app(\App\Services\AuditService::class)->log(
                        logName: 'supplier',
                        event: 'created',
                        description: "Supplier payment of {$paidAmount} recorded for purchase PO #{$purchase->po_number}",
                        subject: $supplierPayment,
                        properties: [
                            'action' => 'supplier_payment',
                            'payment_id' => $supplierPayment->id,
                            'purchase_id' => $purchase->id,
                            'supplier_id' => $supplier->id,
                            'amount' => $paidAmount,
                            'payment_method' => $supplierPayment->payment_method,
                            'financial_account_id' => $targetAccount->id,
                        ],
                        branchId: $branchId,
                        businessId: (int) $businessId
                    );
                }
            }

            if ($supplier && $dueAmount > 0) {
                $this->supplierBalanceService->recordPurchaseLiability($supplier, $dueAmount);
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

            // Verify received stock has not already been consumed/sold
            foreach ($purchase->items as $item) {
                $inventory = \App\Models\BranchInventory::where('business_id', $businessId)
                    ->where('branch_id', $branchId)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
                $onHand = $inventory ? round((float) $inventory->quantity_on_hand, 3) : 0.000;
                if (bccomp((string) $onHand, (string) $item->quantity, 3) < 0) {
                    $productName = $item->product ? $item->product->name : "ID #{$item->product_id}";
                    throw new Exception("Cannot cancel purchase: insufficient stock on hand for product '{$productName}'. Received: {$item->quantity}, Available on hand: {$onHand}.");
                }
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

    /**
     * Exact 2-decimal (or arbitrary scale) deterministic half-up monetary rounding.
     * Uses PHP 8.4 native bcround with pure-string fallback.
     */
    protected function roundMoney(string|float|int $amount, int $scale = 2): string
    {
        $str = is_numeric($amount) ? (string) $amount : '0';
        if (function_exists('bcround')) {
            return bcround($str, $scale);
        }
        $negative = str_starts_with($str, '-');
        $unsigned = ltrim($str, '-');
        $parts = explode('.', $unsigned);
        $intPart = $parts[0] === '' ? '0' : $parts[0];
        $fracPart = $parts[1] ?? '';
        if (strlen($fracPart) <= $scale) {
            $res = $intPart . ($scale > 0 ? '.' . str_pad($fracPart, $scale, '0', STR_PAD_RIGHT) : '');
            return $negative ? '-' . $res : $res;
        }
        $targetFrac = substr($fracPart, 0, $scale);
        $nextDigit = (int) $fracPart[$scale];
        if ($nextDigit >= 5) {
            $factor = bcpow('10', (string) $scale);
            $scaled = bcadd(bcmul($intPart, $factor, 0), $targetFrac === '' ? '0' : $targetFrac, 0);
            $scaledPlus = bcadd($scaled, '1', 0);
            $newInt = bcdiv($scaledPlus, $factor, 0);
            $newFrac = str_pad(bcmod($scaledPlus, $factor), $scale, '0', STR_PAD_LEFT);
            $res = $newInt . ($scale > 0 ? '.' . $newFrac : '');
        } else {
            $res = $intPart . ($scale > 0 ? '.' . $targetFrac : '');
        }
        return $negative ? '-' . $res : $res;
    }
}

