<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MIG-005 (GAP-005): Add freight and landed cost columns to purchases table.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'freight_amount')) {
                $table->decimal('freight_amount', 15, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('purchases', 'landed_cost_allocated')) {
                $table->boolean('landed_cost_allocated')->default(false)->after('freight_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['freight_amount', 'landed_cost_allocated']);
        });
    }
};
