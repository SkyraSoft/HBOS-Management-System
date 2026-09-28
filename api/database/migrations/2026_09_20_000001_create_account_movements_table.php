<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->enum('type', ['inflow', 'outflow']);
            $table->enum('movement_category', [
                'sale_pos',
                'customer_payment',
                'supplier_payment',
                'expense',
                'refund',
                'capital_in',
                'capital_out',
                'transfer_in',
                'transfer_out'
            ]);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key')->nullable();
            $table->enum('status', ['posted', 'voided'])->default('posted');
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reversal_reason')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'idempotency_key'], 'acc_mov_biz_idem_unique');
            $table->unique(['business_id', 'reference_type', 'reference_id', 'movement_category'], 'acc_mov_biz_ref_cat_unique');
            $table->index(['account_id', 'status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_movements');
    }
};
