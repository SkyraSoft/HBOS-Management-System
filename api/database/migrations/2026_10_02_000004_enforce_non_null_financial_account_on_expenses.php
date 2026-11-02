<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MIG-004 (GAP-013): Operating expense financial account integrity.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Ensure financial_account_id index exists
            if (Schema::hasColumn('expenses', 'financial_account_id')) {
                $table->index('financial_account_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['financial_account_id']);
        });
    }
};
