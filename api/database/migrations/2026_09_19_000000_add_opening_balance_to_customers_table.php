<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'opening_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('opening_balance', 12, 2)->default(0.00)->after('address');
            });
        }

        if (!Schema::hasColumn('customers', 'deleted_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Backfill existing Customer records:
        // opening_balance = legacy balance - existing effective credit Sale receivable
        $customers = DB::table('customers')->get();
        foreach ($customers as $customer) {
            $sales = DB::table('sales')
                ->where('customer_id', $customer->id)
                ->where('status', '!=', 'cancelled')
                ->get();

            $existingSaleDue = 0.00;
            foreach ($sales as $sale) {
                $refunds = DB::table('sale_returns')
                    ->where('sale_id', $sale->id)
                    ->sum('refund_amount');

                $effectiveNet = max(0, (float)$sale->total - (float)$refunds);
                $effectiveDue = max(0, $effectiveNet - (float)$sale->paid_amount);
                $existingSaleDue += $effectiveDue;
            }

            $openingBalance = (float)$customer->balance - $existingSaleDue;

            DB::table('customers')
                ->where('id', $customer->id)
                ->update(['opening_balance' => $openingBalance]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('customers', 'deleted_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('customers', 'opening_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('opening_balance');
            });
        }
    }
};
