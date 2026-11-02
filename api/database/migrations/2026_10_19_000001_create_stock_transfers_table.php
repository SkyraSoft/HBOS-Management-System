<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * REQ-0018 / SRT-GAP-021: Inter-Branch Stock Transfers & Item Ledger
     */
    public function up(): void
    {
        if (!Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->string('transfer_number', 50)->index();
                $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('status', 20)->default('draft')->index(); // draft, dispatched, received, cancelled
                $table->text('notes')->nullable();
                $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('dispatched_at')->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('received_at')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancellation_reason', 255)->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status']);
                $table->index(['business_id', 'from_branch_id']);
                $table->index(['business_id', 'to_branch_id']);
            });
        }

        if (!Schema::hasTable('stock_transfer_items')) {
            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('quantity_requested', 12, 3);
                $table->decimal('quantity_sent', 12, 3);
                $table->decimal('quantity_received', 12, 3)->nullable();
                $table->decimal('unit_cost', 12, 2)->nullable();
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->index(['stock_transfer_id', 'product_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
