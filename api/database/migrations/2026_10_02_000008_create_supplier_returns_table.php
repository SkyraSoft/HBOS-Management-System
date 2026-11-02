<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * MIG-008 (GAP-011): Create supplier_returns table and supplier_return_items table.
     */
    public function up(): void
    {
        if (!Schema::hasTable('supplier_returns')) {
            Schema::create('supplier_returns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('supplier_id');
                $table->unsignedBigInteger('purchase_id')->nullable();
                $table->string('return_number');
                $table->decimal('total_amount', 15, 2);
                $table->string('status')->default('completed');
                $table->string('reason')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
                $table->unique(['business_id', 'return_number']);
            });
        }

        if (!Schema::hasTable('supplier_return_items')) {
            Schema::create('supplier_return_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_return_id');
                $table->unsignedBigInteger('product_id');
                $table->integer('quantity');
                $table->decimal('unit_cost', 15, 2);
                $table->decimal('total', 15, 2);
                $table->timestamps();

                $table->foreign('supplier_return_id')->references('id')->on('supplier_returns')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_return_items');
        Schema::dropIfExists('supplier_returns');
    }
};
