<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MIG-007 (GAP-010): Add restock condition flag to sale_return_items table.
     */
    public function up(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_return_items', 'is_restockable')) {
                $table->boolean('is_restockable')->default(true)->after('refund_amount');
            }
            if (!Schema::hasColumn('sale_return_items', 'return_reason')) {
                $table->string('return_reason')->nullable()->after('is_restockable');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropColumn(['is_restockable', 'return_reason']);
        });
    }
};
