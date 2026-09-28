<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Phase 8 reporting index optimization.
     */
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->index(['business_id', 'branch_id', 'date', 'status'], 'idx_sales_biz_branch_date_status');
            });
        }

        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('cost_price', 12, 2)->nullable()->change();
                $table->index(['sale_id', 'product_id'], 'idx_sale_items_sale_product');
            });
        }

        if (Schema::hasTable('sale_returns')) {
            Schema::table('sale_returns', function (Blueprint $table) {
                $table->index(['business_id', 'branch_id', 'created_at'], 'idx_sale_returns_biz_branch_created');
            });
        }

        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->index(['business_id', 'branch_id', 'date', 'status'], 'idx_expenses_biz_branch_date_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropIndex('idx_expenses_biz_branch_date_status');
            });
        }

        if (Schema::hasTable('sale_returns')) {
            Schema::table('sale_returns', function (Blueprint $table) {
                $table->dropIndex('idx_sale_returns_biz_branch_created');
            });
        }

        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropIndex('idx_sale_items_sale_product');
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropIndex('idx_sales_biz_branch_date_status');
            });
        }
    }
};
