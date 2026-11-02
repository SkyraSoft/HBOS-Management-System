<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MIG-001 (GAP-001): Add immutable snapshot columns to sale_items table.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'barcode')) {
                $table->string('barcode')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('sale_items', 'sku')) {
                $table->string('sku')->nullable()->after('barcode');
            }
            if (!Schema::hasColumn('sale_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('sale_items', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->default(0)->after('cost_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'sku', 'product_name', 'unit_cost']);
        });
    }
};
