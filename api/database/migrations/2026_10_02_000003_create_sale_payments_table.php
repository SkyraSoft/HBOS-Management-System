<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MIG-003 (GAP-003): Create sale_payments table for multi-tender breakdown persistence.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sale_payments')) {
            Schema::create('sale_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->unsignedBigInteger('sale_id');
                $table->string('payment_method');
                $table->decimal('amount', 15, 2);
                $table->unsignedBigInteger('financial_account_id')->nullable();
                $table->string('reference_number')->nullable();
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
                $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
                $table->foreign('financial_account_id')->references('id')->on('financial_accounts')->onDelete('set null');

                $table->index(['business_id', 'sale_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
