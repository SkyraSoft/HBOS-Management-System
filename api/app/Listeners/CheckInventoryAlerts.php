<?php

namespace App\Listeners;

use App\Events\SystemDailyCheck;
use App\Models\Product;
use App\Models\Business;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckInventoryAlerts
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SystemDailyCheck $event): void
    {
        Log::info("Starting CheckInventoryAlerts...");

        $businesses = Business::all();

        foreach ($businesses as $business) {
            $products = Product::where('business_id', $business->id)->get();

            foreach ($products as $product) {
                // Check 1: Low Stock Alert
                if ($product->stock > 0 && $product->stock <= $product->min_stock) {
                    // Avoid duplicate notifications (we could check if one exists in the last X days)
                    $business->notifications()->create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'type' => 'App\Notifications\LowStockNotification',
                        'data' => [
                            'title' => 'Low Stock Alert',
                            'message' => "Product '{$product->name}' is running low (Current: {$product->stock}, Min: {$product->min_stock}).",
                            'type' => 'Important/Soon',
                            'action_type' => 'REORDER_STOCK',
                            'action_payload' => ['product_id' => $product->id],
                            'icon' => 'bi-exclamation-triangle-fill',
                            'colorClass' => 'text-warning',
                            'bgClass' => 'bg-warning-subtle',
                        ],
                    ]);
                }

                // Check 2: Out of Stock
                if ($product->stock === 0) {
                    $business->notifications()->create([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'type' => 'App\Notifications\OutOfStockNotification',
                        'data' => [
                            'title' => 'Out of Stock Alert',
                            'message' => "Product '{$product->name}' is completely out of stock.",
                            'type' => 'Emergency/Critical',
                            'action_type' => 'REORDER_STOCK',
                            'action_payload' => ['product_id' => $product->id],
                            'icon' => 'bi-x-octagon-fill',
                            'colorClass' => 'text-danger',
                            'bgClass' => 'bg-danger-subtle',
                        ],
                    ]);
                }

                // Check 3: Slow Moving / Expired Batch
                if ($product->expected_sell_date && $product->stock > 0) {
                    $sellDate = Carbon::parse($product->expected_sell_date);
                    if (now()->greaterThanOrEqualTo($sellDate)) {
                        $business->notifications()->create([
                            'id' => \Illuminate\Support\Str::uuid(),
                            'type' => 'App\Notifications\SlowMovingInventoryNotification',
                            'data' => [
                                'title' => 'Slow Moving Inventory',
                                'message' => "Product '{$product->name}' has passed its expected sell date ({$sellDate->format('Y-m-d')}) but still has {$product->stock} items left.",
                                'type' => 'Common/General',
                                'action_type' => null, // Maybe create a DISCOUNT action later
                                'action_payload' => null,
                                'icon' => 'bi-clock-fill',
                                'colorClass' => 'text-info',
                                'bgClass' => 'bg-info-subtle',
                            ],
                        ]);
                    }
                }
            }
        }
    }
}
