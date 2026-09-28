<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Branch;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('branch_inventories')) {
            Schema::create('branch_inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->integer('quantity_on_hand')->default(0);
                $table->integer('minimum_stock')->default(0);
                $table->integer('reorder_level')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'branch_id', 'product_id'], 'uq_bi_tenant_branch_product');
            });
        }

        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_movements', 'unit_cost')) {
                    $table->decimal('unit_cost', 10, 2)->nullable()->after('quantity');
                }
                if (!Schema::hasColumn('inventory_movements', 'performed_by')) {
                    $table->foreignId('performed_by')->nullable()->after('reference_id')->constrained('users')->onDelete('set null');
                }
            });

            // Make reference_type and reference_id nullable if they were created as non-null by morphs
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->string('reference_type')->nullable()->change();
                $table->unsignedBigInteger('reference_id')->nullable()->change();
                $table->integer('balance')->default(0)->nullable()->change();
            });
        }

        // Backfill legacy product stock into branch_inventories for deterministic primary branches
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            $primaryBranch = DB::table('branches')
                ->where('business_id', $product->business_id)
                ->where('is_primary', true)
                ->first();

            if (!$primaryBranch) {
                $branches = DB::table('branches')
                    ->where('business_id', $product->business_id)
                    ->get();
                if ($branches->count() === 1) {
                    $primaryBranch = $branches->first();
                }
            }

            if ($primaryBranch) {
                DB::table('branch_inventories')->updateOrInsert(
                    [
                        'business_id' => $product->business_id,
                        'branch_id' => $primaryBranch->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity_on_hand' => $product->stock ?? 0,
                        'minimum_stock' => $product->min_stock ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                if (intval($product->stock ?? 0) > 0) {
                    $exists = DB::table('inventory_movements')
                        ->where('business_id', $product->business_id)
                        ->where('branch_id', $primaryBranch->id)
                        ->where('product_id', $product->id)
                        ->where('type', 'opening')
                        ->exists();

                    if (!$exists) {
                        DB::table('inventory_movements')->insert([
                            'business_id' => $product->business_id,
                            'branch_id' => $primaryBranch->id,
                            'product_id' => $product->id,
                            'type' => 'opening',
                            'quantity' => intval($product->stock),
                            'unit_cost' => $product->cost_price ?? null,
                            'reference_type' => null,
                            'reference_id' => null,
                            'balance' => intval($product->stock),
                            'notes' => 'Legacy product stock backfill',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_movements', 'performed_by')) {
                    $table->dropForeign(['performed_by']);
                    $table->dropColumn('performed_by');
                }
                if (Schema::hasColumn('inventory_movements', 'unit_cost')) {
                    $table->dropColumn('unit_cost');
                }
            });
        }

        Schema::dropIfExists('branch_inventories');
    }
};
