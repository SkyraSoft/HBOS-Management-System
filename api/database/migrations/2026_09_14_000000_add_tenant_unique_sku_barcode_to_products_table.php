<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unique(['business_id', 'sku'], 'products_business_id_sku_unique');
            $table->unique(['business_id', 'barcode'], 'products_business_id_barcode_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_business_id_sku_unique');
            $table->dropUnique('products_business_id_barcode_unique');
        });
    }
};
