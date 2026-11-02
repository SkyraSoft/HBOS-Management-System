<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MIG-006 (GAP-007): Add transfer status to inventory_movements.
     */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_movements', 'transfer_status')) {
                $table->string('transfer_status')->nullable()->default('completed')->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('transfer_status');
        });
    }
};
