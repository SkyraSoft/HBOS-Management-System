<?php

namespace App\Services;

use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\SupplierBalanceService;
use Illuminate\Support\Facades\DB;
use Exception;

class SupplierReturnService
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
     * Create a supplier return / debit note transaction atomically.
     */
    public function createSupplierReturn(array $data, User $user): SupplierReturn
    {
        return DB::transaction(function () use ($data, $user) {
            $businessId = \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();

            // 1. Resolve & Validate Branch
            $branchId = $data['branch_id'] ?? $user->branch_id;
            if (!$branchId) {
                $primaryBranch = Branch::where('business_id', $businessId)->where('is_primary', true)->first()
                    ?? Branch::where('business_id', $businessId)->first();
                $branchId = $primaryBranch ? $primaryBranch->id : null;
            }

            if (!$branchId) {
                throw new Exception("No active branch resolved for supplier return.");
            }

            $branch = Branch::where('business_id', $businessId)->find($branchId);
            if (!$branch) {
                throw new Exception("Branch does not belong to active business tenant.");
            }

            // 2. Validate Supplier
            $supplier = Supplier::where('business_id', $businessId)->findOrFail($data['supplier_id']);

            // 3. Validate Items & Stock Availability
            if (empty($data['items']) || !is_array($data['items'])) {
                throw new Exception("Supplier return must contain at least one item.");
            }

            $totalAmount = 0.0;
            $validatedItems = [];

            foreach ($data['items'] as $itemData) {
                $quantity = (int) ($itemData['quantity'] ?? 0);
                $unitCost = round((float) ($itemData['unit_cost'] ?? 0), 2);

                if ($quantity <= 0) {
                    throw new Exception("Return item quantity must be greater than zero.");
                }
                if ($unitCost < 0) {
                    throw new Exception("Return item unit cost cannot be negative.");
                }

                $product = Product::where('business_id', $businessId)->findOrFail($itemData['product_id']);

                // Check Branch Inventory on hand
                $inventory = BranchInventory::where('business_id', $businessId)
                    ->where('branch_id', $branchId)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                $onHand = $inventory ? (int) $inventory->quantity_on_hand : 0;
                if ($onHand < $quantity) {
                    throw new Exception("Insufficient stock on hand for product '{$product->name}'. Available: {$onHand}, Returning: {$quantity}.");
                }

                $lineTotal = round($quantity * $unitCost, 2);
                $totalAmount = round($totalAmount + $lineTotal, 2);

                $validatedItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total' => $lineTotal
                ];
            }

            $returnNumber = $data['return_number'] ?? ('SR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));

            // 4. Create SupplierReturn Record
            $supplierReturn = SupplierReturn::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'supplier_id' => $supplier->id,
                'purchase_id' => $data['purchase_id'] ?? null,
                'return_number' => $returnNumber,
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'reason' => $data['reason'] ?? null,
                'created_by' => $user->id,
            ]);

            // 5. Create Return Items & Issue Stock
            foreach ($validatedItems as $itemData) {
                SupplierReturnItem::create([
                    'supplier_return_id' => $supplierReturn->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'total' => $itemData['total']
                ]);

                $this->inventoryService->issueStock([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'type' => 'purchase_return',
                    'unit_cost' => $itemData['unit_cost'],
                    'reference_type' => SupplierReturn::class,
                    'reference_id' => $supplierReturn->id,
                    'performed_by' => $user->id,
                    'notes' => "Supplier Return #{$supplierReturn->return_number}"
                ]);
            }

            // 6. Record Debit Note reducing Supplier Payable Balance
            if ($totalAmount > 0) {
                $this->supplierBalanceService->recordDebitNote($supplier, $totalAmount);
            }

            return $supplierReturn->load(['items.product', 'supplier', 'branch']);
        });
    }
}
