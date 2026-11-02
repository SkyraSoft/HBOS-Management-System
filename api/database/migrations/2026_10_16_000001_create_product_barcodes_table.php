<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SRT-GAP-012: Canonical Barcode Indexing
     */
    public function up(): void
    {
        if (!Schema::hasTable('product_barcodes')) {
            Schema::create('product_barcodes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('barcode', 100);
                $table->string('barcode_type', 20)->default('ALTERNATE'); // PRIMARY or ALTERNATE
                $table->timestamps();

                $table->unique(['business_id', 'barcode'], 'uq_product_barcodes_tenant_barcode');
                $table->index(['business_id', 'product_id'], 'idx_product_barcodes_tenant_product');
            });
        }

        // Backfill existing products.barcode as PRIMARY barcode identities
        if (Schema::hasTable('products')) {
            $existingProducts = DB::table('products')
                ->whereNotNull('barcode')
                ->where('barcode', '!=', '')
                ->select('id', 'business_id', 'barcode')
                ->get();

            if ($existingProducts->isNotEmpty()) {
                // Anomaly Detection: check for same-tenant barcode collisions
                $duplicateCheck = DB::table('products')
                    ->whereNotNull('barcode')
                    ->where('barcode', '!=', '')
                    ->select('business_id', 'barcode', DB::raw('COUNT(*) as occurrences'))
                    ->groupBy('business_id', 'barcode')
                    ->having('occurrences', '>', 1)
                    ->get();

                if ($duplicateCheck->isNotEmpty()) {
                    $collisions = $duplicateCheck->map(fn($c) => "Biz {$c->business_id}: Barcode '{$c->barcode}' ({$c->occurrences} times)")->implode(', ');
                    throw new RuntimeException("Pre-migration collision detected in products.barcode: {$collisions}. Aborting canonical barcode migration to prevent data corruption.");
                }

                $now = now();
                $rowsToInsert = [];
                foreach ($existingProducts as $prod) {
                    $rowsToInsert[] = [
                        'business_id' => $prod->business_id,
                        'product_id' => $prod->id,
                        'barcode' => (string) $prod->barcode, // Preserve leading zeros & opaque string representation
                        'barcode_type' => 'PRIMARY',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($rowsToInsert, 500) as $chunk) {
                    DB::table('product_barcodes')->insert($chunk);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
