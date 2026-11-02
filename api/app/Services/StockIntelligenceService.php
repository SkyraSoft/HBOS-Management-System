<?php

namespace App\Services;

use App\Models\BranchInventory;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockIntelligenceService
{
    /**
     * Reorder analytics: Low stock, Out of stock, and Explainable Deficit
     */
    public function getReorderAnalytics(int $businessId, ?int $branchId = null): array
    {
        $query = BranchInventory::with(['product', 'branch'])
            ->where('business_id', $businessId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $inventories = $query->get();
        $reorderItems = [];
        $criticalCount = 0;
        $totalDeficit = 0.0;
        $estimatedReorderValue = 0.0;

        foreach ($inventories as $inv) {
            $product = $inv->product;
            if (!$product) {
                continue;
            }

            $qoh = round((float) $inv->quantity_on_hand, 3);
            $reorderLevel = !is_null($inv->reorder_level) ? (float) $inv->reorder_level : null;
            $minStock = !is_null($inv->minimum_stock) ? (float) $inv->minimum_stock : ((float) ($product->min_stock ?? 0));
            $threshold = $reorderLevel ?? $minStock ?? 0.0;

            $isOutOfStock = bccomp((string) $qoh, '0', 3) <= 0;
            $isReorderTriggered = bccomp((string) $qoh, (string) $threshold, 3) <= 0;

            if ($isReorderTriggered || $isOutOfStock) {
                $deficit = max(0.0, round($threshold - $qoh, 3));
                if ($isOutOfStock && $deficit == 0.0) {
                    $deficit = $threshold > 0 ? $threshold : 1.0;
                }

                $costPrice = round((float) ($product->cost_price ?? 0), 2);
                $lineReorderCost = round($deficit * $costPrice, 2);

                if ($isOutOfStock) {
                    $criticalCount++;
                }

                $totalDeficit = round($totalDeficit + $deficit, 3);
                $estimatedReorderValue = round($estimatedReorderValue + $lineReorderCost, 2);

                $reorderItems[] = [
                    'inventory_id' => $inv->id,
                    'product_id' => $product->id,
                    'branch_id' => $inv->branch_id,
                    'branch_name' => $inv->branch?->name ?? 'Main Store',
                    'product_name' => $product->name,
                    'sku' => $product->sku ?? '—',
                    'barcode' => $product->barcode ?? '—',
                    'unit' => $product->unit ?? 'pcs',
                    'quantity_on_hand' => $qoh,
                    'threshold' => $threshold,
                    'reorder_level' => $reorderLevel,
                    'minimum_stock' => $minStock,
                    'deficit' => $deficit,
                    'is_out_of_stock' => $isOutOfStock,
                    'cost_price' => $costPrice,
                    'estimated_reorder_cost' => $lineReorderCost,
                    'formula_explanation' => "Current Stock ({$qoh}) <= Threshold ({$threshold}) → Deficit = {$deficit}",
                ];
            }
        }

        return [
            'total_requiring_reorder' => count($reorderItems),
            'critical_count' => $criticalCount,
            'total_deficit_units' => $totalDeficit,
            'estimated_reorder_value' => $estimatedReorderValue,
            'items' => $reorderItems,
        ];
    }

    /**
     * Sales velocity ranking over configurable window (30, 60, 90 days)
     */
    public function getSalesVelocity(int $businessId, ?int $branchId = null, int $days = 30): array
    {
        $allowedDays = [30, 60, 90];
        if (!in_array($days, $allowedDays)) {
            $days = 30;
        }

        $startDate = now()->subDays($days)->toDateString();

        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->where('sales.status', 'completed')
            ->where('sales.date', '>=', $startDate);

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        }

        $velocityRecords = $query->select(
            'products.id as product_id',
            'products.name as product_name',
            'products.sku',
            'products.unit',
            DB::raw('SUM(sale_items.quantity) as total_sold_quantity'),
            DB::raw('COUNT(DISTINCT sales.id) as transactions_count'),
            DB::raw('SUM(sale_items.total) as total_revenue')
        )
        ->groupBy('products.id', 'products.name', 'products.sku', 'products.unit')
        ->orderByDesc('total_sold_quantity')
        ->get();

        $ranked = [];
        $rank = 1;
        foreach ($velocityRecords as $rec) {
            $soldQty = round((float) $rec->total_sold_quantity, 3);
            $dailyRate = round($soldQty / $days, 3);

            $ranked[] = [
                'rank' => $rank++,
                'product_id' => $rec->product_id,
                'product_name' => $rec->product_name,
                'sku' => $rec->sku ?? '—',
                'unit' => $rec->unit ?? 'pcs',
                'total_sold_quantity' => $soldQty,
                'transactions_count' => (int) $rec->transactions_count,
                'total_revenue' => round((float) $rec->total_revenue, 2),
                'daily_velocity' => $dailyRate,
                'days_window' => $days,
            ];
        }

        return [
            'days' => $days,
            'products_count' => count($ranked),
            'rankings' => $ranked,
        ];
    }

    /**
     * Dead stock analysis: Products with stock > 0 but ZERO sales in the selected window (30/60/90 days)
     */
    public function getDeadStock(int $businessId, ?int $branchId = null, int $days = 30): array
    {
        $allowedDays = [30, 60, 90];
        if (!in_array($days, $allowedDays)) {
            $days = 30;
        }

        $startDate = now()->subDays($days)->toDateString();

        // 1. Get product IDs that had sales in this window
        $activeSaleProductIds = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.business_id', $businessId)
            ->where('sales.status', 'completed')
            ->where('sales.date', '>=', $startDate)
            ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
            ->distinct()
            ->pluck('sale_items.product_id')
            ->toArray();

        // 2. Query inventories with quantity_on_hand > 0 where product not in active sales
        $query = BranchInventory::with(['product', 'branch'])
            ->where('business_id', $businessId)
            ->where('quantity_on_hand', '>', 0);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if (!empty($activeSaleProductIds)) {
            $query->whereNotIn('product_id', $activeSaleProductIds);
        }

        $deadInventories = $query->get();
        $deadItems = [];
        $totalLockedCapital = 0.0;
        $totalDeadQuantity = 0.0;

        foreach ($deadInventories as $inv) {
            $product = $inv->product;
            if (!$product) {
                continue;
            }

            $qoh = round((float) $inv->quantity_on_hand, 3);
            $costPrice = round((float) ($product->cost_price ?? 0), 2);
            $lockedCapital = round($qoh * $costPrice, 2);

            $totalLockedCapital = round($totalLockedCapital + $lockedCapital, 2);
            $totalDeadQuantity = round($totalDeadQuantity + $qoh, 3);

            $deadItems[] = [
                'inventory_id' => $inv->id,
                'product_id' => $product->id,
                'branch_id' => $inv->branch_id,
                'branch_name' => $inv->branch?->name ?? 'Main Store',
                'product_name' => $product->name,
                'sku' => $product->sku ?? '—',
                'unit' => $product->unit ?? 'pcs',
                'quantity_on_hand' => $qoh,
                'cost_price' => $costPrice,
                'locked_capital' => $lockedCapital,
                'days_without_sale' => $days,
            ];
        }

        return [
            'days' => $days,
            'dead_stock_count' => count($deadItems),
            'total_dead_quantity' => $totalDeadQuantity,
            'total_locked_capital' => $totalLockedCapital,
            'items' => $deadItems,
        ];
    }

    /**
     * Unified stock intelligence summary
     */
    public function getStockIntelligenceSummary(int $businessId, ?int $branchId = null, int $days = 30): array
    {
        return [
            'reorder_analytics' => $this->getReorderAnalytics($businessId, $branchId),
            'sales_velocity' => $this->getSalesVelocity($businessId, $branchId, $days),
            'dead_stock' => $this->getDeadStock($businessId, $branchId, $days),
        ];
    }
}
