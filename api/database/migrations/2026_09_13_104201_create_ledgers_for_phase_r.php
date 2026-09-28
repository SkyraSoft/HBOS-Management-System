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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('type'); // 'purchase', 'sale', 'return', 'adjustment'
            $table->morphs('reference'); // allows linking to Sale, Purchase, etc. (reference_id, reference_type)
            $table->integer('quantity'); // positive or negative
            $table->integer('balance'); // running total for the branch
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('type'); // 'sale', 'expense', 'supplier_payment', 'customer_payment', 'deposit', 'withdrawal'
            $table->morphs('reference'); // allows linking to Sale, Expense, etc.
            $table->decimal('amount', 12, 2); // positive (in) or negative (out)
            $table->decimal('balance', 12, 2); // running balance for the branch
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_ledgers');
        Schema::dropIfExists('inventory_movements');
    }
};
