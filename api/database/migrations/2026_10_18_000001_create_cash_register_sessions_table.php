<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 6: Cash register sessions tracking and drawer reconciliation (REQ-0024, REQ-0025, SRT-GAP-017, SRT-GAP-018)
     */
    public function up(): void
    {
        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('opening_float', 15, 2);
            $table->timestamp('opened_at');
            $table->decimal('closing_cash_counted', 15, 2)->nullable();
            $table->decimal('closing_cash_expected', 15, 2)->nullable();
            $table->decimal('cash_variance', 15, 2)->nullable();
            $table->text('variance_reason')->nullable();
            $table->foreignId('reconciliation_movement_id')->nullable()->constrained('account_movements')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'branch_id', 'financial_account_id', 'status'], 'crs_biz_branch_acc_status_idx');
            $table->index(['financial_account_id', 'status'], 'crs_account_status_idx');
            $table->index(['opened_at', 'closed_at'], 'crs_opened_closed_idx');
        });

        // Add 'adjustment' movement_category to account_movements
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE account_movements MODIFY COLUMN movement_category ENUM('sale_pos', 'customer_payment', 'supplier_payment', 'expense', 'refund', 'capital_in', 'capital_out', 'transfer_in', 'transfer_out', 'adjustment') NOT NULL");
        } elseif ($driver === 'sqlite') {
            Schema::table('account_movements', function (Blueprint $table) {
                $table->string('movement_category')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('account_movements')) {
            $hasAdjustments = DB::table('account_movements')->where('movement_category', 'adjustment')->exists();
            if ($hasAdjustments) {
                throw new \RuntimeException("Cannot rollback migration: account_movements table contains 'adjustment' category rows which would cause data loss.");
            }

            $driver = DB::getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'])) {
                DB::statement("ALTER TABLE account_movements MODIFY COLUMN movement_category ENUM('sale_pos', 'customer_payment', 'supplier_payment', 'expense', 'refund', 'capital_in', 'capital_out', 'transfer_in', 'transfer_out') NOT NULL");
            }
        }

        Schema::dropIfExists('cash_register_sessions');
    }
};
