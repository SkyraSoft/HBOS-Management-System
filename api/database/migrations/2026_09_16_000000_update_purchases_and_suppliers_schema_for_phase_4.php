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
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('purchases', 'due_amount')) {
                $table->decimal('due_amount', 12, 2)->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('purchases', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('purchases', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('purchases', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }

            // Drop old global po_number unique constraint and add composite unique (business_id, po_number)
            $table->dropUnique('purchases_po_number_unique');
            $table->unique(['business_id', 'po_number'], 'purchases_business_po_number_unique');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->unique(['business_id', 'code'], 'suppliers_business_code_unique');
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_payments', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('supplier_id')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_payments', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_business_code_unique');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('purchases_business_po_number_unique');
            $table->unique('po_number', 'purchases_po_number_unique');

            $table->dropColumn(['paid_amount', 'due_amount', 'cancelled_at', 'cancellation_reason']);
            if (Schema::hasColumn('purchases', 'cancelled_by')) {
                $table->dropForeign(['cancelled_by']);
                $table->dropColumn('cancelled_by');
            }
        });
    }
};
