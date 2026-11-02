<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MIG-009 (GAP-029): Add payroll/salary columns to users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'salary_amount')) {
                $table->decimal('salary_amount', 15, 2)->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'payment_cycle')) {
                $table->string('payment_cycle')->nullable()->after('salary_amount');
            }
            if (!Schema::hasColumn('users', 'next_payment_date')) {
                $table->date('next_payment_date')->nullable()->after('payment_cycle');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['salary_amount', 'payment_cycle', 'next_payment_date']);
        });
    }
};
