<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    /**
     * Decrement stock for a sale safely
     */
    public function decrementStock($productId, $quantity, $businessId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return;
        }
        
        $qty = intval($quantity);
        if ($qty <= 0) {
            return;
        }

        if ($product->stock > 0) {
            $decrementQty = min($product->stock, $qty);
            $product->decrement('stock', $decrementQty);
        }
    }

    /**
     * Increment stock for a purchase
     */
    public function incrementStock($productId, $quantity, $businessId)
    {
        $product = Product::find($productId);
        if ($product) {
            $product->increment('stock', intval($quantity));
        }
    }
}
