<?php

namespace App\Services;

use App\Models\BranchInventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Branch;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    /**
     * Receive stock into a branch (Positive movement)
     */
    public function receiveStock(array $data): InventoryMovement
    {
        $quantity = intval($data['quantity'] ?? 0);
        if ($quantity <= 0) {
            throw new Exception("Stock receive quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($data, $quantity) {
            $productId = $data['product_id'];
            $product = Product::findOrFail($productId);
            
            $businessId = $data['business_id'] ?? \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();
            if ($product->business_id != $businessId) {
                throw new Exception("Product does not belong to active business.");
            }

            $branchId = $data['branch_id'] ?? (Auth::user()->branch_id ?? null);
            if (!$branchId) {
                throw new Exception("Branch context is required for inventory operations.");
            }

            $branch = Branch::where('id', $branchId)->where('business_id', $businessId)->first();
            if (!$branch) {
                throw new Exception("Invalid branch for the active business.");
            }

            // Lock or create BranchInventory
            $inventory = BranchInventory::where('business_id', $businessId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $inventory = BranchInventory::create([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity_on_hand' => 0,
                    'minimum_stock' => $product->min_stock ?? 0,
                ]);
            }

            $inventory->quantity_on_hand += $quantity;
            $inventory->save();

            // Also update legacy product stock field as non-authoritative fallback
            $product->increment('stock', $quantity);

            $performedBy = $data['performed_by'] ?? (Auth::id() ?? null);
            $type = $data['type'] ?? 'purchase';

            $movement = InventoryMovement::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'type' => $type,
                'quantity' => $quantity, // Positive
                'unit_cost' => $data['unit_cost'] ?? ($product->cost_price ?? null),
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'performed_by' => $performedBy,
                'balance' => $inventory->quantity_on_hand,
                'notes' => $data['notes'] ?? $data['description'] ?? null,
            ]);

            return $movement;
        });
    }

    /**
     * Issue stock from a branch (Negative movement)
     */
    public function issueStock(array $data): InventoryMovement
    {
        $quantity = intval($data['quantity'] ?? 0);
        if ($quantity <= 0) {
            throw new Exception("Stock issue quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($data, $quantity) {
            $productId = $data['product_id'];
            $product = Product::findOrFail($productId);
            
            $businessId = $data['business_id'] ?? \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();
            if ($product->business_id != $businessId) {
                throw new Exception("Product does not belong to active business.");
            }

            $branchId = $data['branch_id'] ?? (Auth::user()->branch_id ?? null);
            if (!$branchId) {
                throw new Exception("Branch context is required for inventory operations.");
            }

            $branch = Branch::where('id', $branchId)->where('business_id', $businessId)->first();
            if (!$branch) {
                throw new Exception("Invalid branch for the active business.");
            }

            // Lock BranchInventory
            $inventory = BranchInventory::where('business_id', $businessId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $currentStock = $inventory ? $inventory->quantity_on_hand : 0;

            // Insufficient stock enforcement (No silent capping, no negative stock)
            if ($currentStock < $quantity) {
                throw new Exception("Insufficient stock for product '{$product->name}' in branch '{$branch->name}'. Available: {$currentStock}, Requested: {$quantity}.");
            }

            $inventory->quantity_on_hand -= $quantity;
            $inventory->save();

            // Also update legacy product stock field as non-authoritative fallback
            if ($product->stock >= $quantity) {
                $product->decrement('stock', $quantity);
            } else {
                $product->stock = max(0, $product->stock - $quantity);
                $product->save();
            }

            $performedBy = $data['performed_by'] ?? (Auth::id() ?? null);
            $type = $data['type'] ?? 'sale';

            $movement = InventoryMovement::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'type' => $type,
                'quantity' => -$quantity, // Negative
                'unit_cost' => $data['unit_cost'] ?? ($product->cost_price ?? null),
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'performed_by' => $performedBy,
                'balance' => $inventory->quantity_on_hand,
                'notes' => $data['notes'] ?? $data['description'] ?? null,
            ]);

            return $movement;
        });
    }

    /**
     * Manual inventory adjustment (adjustment_in, adjustment_out, damage)
     */
    public function adjustStock(array $data): InventoryMovement
    {
        $type = $data['type'] ?? null;
        if (!in_array($type, ['adjustment_in', 'adjustment_out', 'damage'])) {
            throw new Exception("Invalid adjustment type. Must be adjustment_in, adjustment_out, or damage.");
        }

        if (empty($data['notes'])) {
            throw new Exception("Mandatory notes required for manual stock adjustment.");
        }

        if ($type === 'adjustment_in') {
            return $this->receiveStock($data);
        } else {
            return $this->issueStock($data);
        }
    }

    /**
     * Transfer stock between branches within the same business
     */
    public function transferStock(array $data): array
    {
        $fromBranchId = intval($data['from_branch_id'] ?? 0);
        $toBranchId = intval($data['to_branch_id'] ?? 0);

        if ($fromBranchId <= 0 || $toBranchId <= 0) {
            throw new Exception("Source and destination branch IDs are required for transfer.");
        }

        if ($fromBranchId === $toBranchId) {
            throw new Exception("Source and destination branches must be different.");
        }

        return DB::transaction(function () use ($data, $fromBranchId, $toBranchId) {
            $productId = $data['product_id'];
            $product = Product::findOrFail($productId);
            $businessId = $data['business_id'] ?? \App\Http\Middleware\ResolveActiveBusiness::requireActiveBusinessId();

            $fromBranch = Branch::where('id', $fromBranchId)->where('business_id', $businessId)->first();
            $toBranch = Branch::where('id', $toBranchId)->where('business_id', $businessId)->first();

            if (!$fromBranch || !$toBranch) {
                throw new Exception("Both branches must belong to the active business.");
            }

            $quantity = intval($data['quantity'] ?? 0);
            $notes = $data['notes'] ?? "Stock transfer from {$fromBranch->name} to {$toBranch->name}";

            // 1. Issue from source branch
            $outMovement = $this->issueStock([
                'business_id' => $businessId,
                'branch_id' => $fromBranchId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'type' => 'transfer_out',
                'notes' => $notes,
                'performed_by' => $data['performed_by'] ?? Auth::id(),
            ]);

            // 2. Receive in destination branch
            $inMovement = $this->receiveStock([
                'business_id' => $businessId,
                'branch_id' => $toBranchId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'type' => 'transfer_in',
                'reference_type' => InventoryMovement::class,
                'reference_id' => $outMovement->id,
                'notes' => $notes,
                'performed_by' => $data['performed_by'] ?? Auth::id(),
            ]);

            return ['out' => $outMovement, 'in' => $inMovement];
        });
    }

    /**
     * Reverse a previous movement using a compensating movement
     */
    public function reverseMovement(InventoryMovement $movement, string $reason = 'Reversal'): InventoryMovement
    {
        if ($movement->quantity < 0) {
            // Original was OUT, compensating movement is IN
            return $this->receiveStock([
                'business_id' => $movement->business_id,
                'branch_id' => $movement->branch_id,
                'product_id' => $movement->product_id,
                'quantity' => abs($movement->quantity),
                'type' => 'sale_return',
                'reference_type' => InventoryMovement::class,
                'reference_id' => $movement->id,
                'notes' => "Reversal: {$reason}",
                'performed_by' => Auth::id(),
            ]);
        } else {
            // Original was IN, compensating movement is OUT
            return $this->issueStock([
                'business_id' => $movement->business_id,
                'branch_id' => $movement->branch_id,
                'product_id' => $movement->product_id,
                'quantity' => abs($movement->quantity),
                'type' => 'purchase_return',
                'reference_type' => InventoryMovement::class,
                'reference_id' => $movement->id,
                'notes' => "Reversal: {$reason}",
                'performed_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Get current branch balance
     */
    public function getBranchBalance(int $productId, int $branchId): int
    {
        return intval(
            BranchInventory::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->value('quantity_on_hand') ?? 0
        );
    }
}
