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
        if (!Schema::hasTable('customer_payments')) {
            Schema::create('customer_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
                $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('set null');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('payment_number');
                $table->decimal('amount', 12, 2);
                $table->string('payment_method')->default('cash');
                $table->date('date');
                $table->text('notes')->nullable();
                $table->string('idempotency_key')->nullable();
                $table->string('status')->default('posted'); // posted, voided
                $table->timestamp('reversed_at')->nullable();
                $table->foreignId('reversed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('reversal_reason')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'payment_number'], 'cp_business_payment_num_unique');
                $table->unique(['business_id', 'idempotency_key'], 'cp_business_idempotency_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
