<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_payments')) {
            Schema::table('supplier_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('supplier_payments', 'status')) {
                    $table->string('status', 30)->default('completed')->after('payment_method');
                }
                if (!Schema::hasColumn('supplier_payments', 'reversed_at')) {
                    $table->timestamp('reversed_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('supplier_payments', 'reversed_by')) {
                    $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('supplier_payments', 'reversal_reason')) {
                    $table->text('reversal_reason')->nullable()->after('reversed_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_payments')) {
            Schema::table('supplier_payments', function (Blueprint $table) {
                if (Schema::hasColumn('supplier_payments', 'reversed_by')) {
                    $table->dropForeign(['reversed_by']);
                    $table->dropColumn('reversed_by');
                }
                if (Schema::hasColumn('supplier_payments', 'reversed_at')) {
                    $table->dropColumn('reversed_at');
                }
                if (Schema::hasColumn('supplier_payments', 'reversal_reason')) {
                    $table->dropColumn('reversal_reason');
                }
                if (Schema::hasColumn('supplier_payments', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
