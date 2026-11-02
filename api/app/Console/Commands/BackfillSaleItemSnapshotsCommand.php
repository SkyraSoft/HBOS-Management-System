<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SaleItem;
use App\Models\Product;

class BackfillSaleItemSnapshotsCommand extends Command
{
    protected $signature = 'hbos:backfill-sale-item-snapshots {--apply : Apply changes to database instead of dry-run}';
    protected $description = 'REPAIR-001 (GAP-001): Convenience backfill for legacy sale_items snapshots from current product master (Non-authoritative approximation)';

    public function handle()
    {
        $apply = $this->option('apply');
        $this->info("Starting REPAIR-001: Sale item snapshot backfill (" . ($apply ? "APPLY MODE" : "DRY RUN MODE") . ")");

        $items = SaleItem::whereNull('product_name')
            ->orWhereNull('barcode')
            ->orWhereNull('sku')
            ->get();

        $this->info("Examined items: " . $items->count());
        $updated = 0;

        foreach ($items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $updated++;
                if ($apply) {
                    $item->update([
                        'barcode' => $item->barcode ?? $product->barcode,
                        'sku' => $item->sku ?? $product->sku,
                        'product_name' => $item->product_name ?? $product->name,
                        'unit_cost' => ($item->unit_cost && $item->unit_cost > 0) ? $item->unit_cost : ($product->cost_price ?? 0),
                    ]);
                }
            }
        }

        $this->info("Rows " . ($apply ? "updated: " : "to update: ") . $updated);
        $this->info("Classification: APPROXIMATE_NON_HISTORICAL_BACKFILL (Preserves non-authoritative provenance for historical records)");
        return 0;
    }
}
