<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MIG-011 (GAP-031): Add monthly_budget column to expense_categories table.
     */
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('expense_categories', 'monthly_budget')) {
                $table->decimal('monthly_budget', 15, 2)->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('monthly_budget');
        });
    }
};
