<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure sale_items.cost_price snapshot invariant remains NOT NULL.
     */
    public function up(): void
    {
        if (Schema::hasTable('sale_items') && Schema::hasColumn('sale_items', 'cost_price')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('cost_price', 12, 2)->default(0)->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sale_items') && Schema::hasColumn('sale_items', 'cost_price')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('cost_price', 12, 2)->default(0)->change();
            });
        }
    }
};
