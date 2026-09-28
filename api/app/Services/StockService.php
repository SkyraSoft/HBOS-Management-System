<?php

namespace App\Services;

use App\Services\InventoryService;
use App\Models\Product;

/**
 * @deprecated Legacy StockService wrapper - Use App\Services\InventoryService directly.
 */
class StockService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Decrement stock for a sale safely via InventoryService
     */
    public function decrementStock($productId, $quantity, $businessId, $branchId = null)
    {
        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        $qty = intval($quantity);
        if ($qty <= 0) {
            return;
        }

        $this->inventoryService->issueStock([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'product_id' => $productId,
            'quantity' => $qty,
            'type' => 'sale',
        ]);
    }

    /**
     * Increment stock for a purchase via InventoryService
     */
    public function incrementStock($productId, $quantity, $businessId, $branchId = null)
    {
        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        $qty = intval($quantity);
        if ($qty <= 0) {
            return;
        }

        $this->inventoryService->receiveStock([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'product_id' => $productId,
            'quantity' => $qty,
            'type' => 'purchase',
        ]);
    }
}
