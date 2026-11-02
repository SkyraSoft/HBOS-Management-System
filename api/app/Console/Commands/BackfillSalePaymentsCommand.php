<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\AccountMovement;

class BackfillSalePaymentsCommand extends Command
{
    protected $signature = 'hbos:backfill-sale-payments {--apply : Apply changes to database instead of dry-run}';
    protected $description = 'REPAIR-002 (GAP-003): Populate sale_payments table from legacy single-tender sales';

    public function handle()
    {
        $apply = $this->option('apply');
        $this->info("Starting REPAIR-002: Sale payments backfill (" . ($apply ? "APPLY MODE" : "DRY RUN MODE") . ")");

        $sales = Sale::where('paid_amount', '>', 0)
            ->whereDoesntHave('payments')
            ->get();

        $this->info("Examined legacy sales without sale_payments: " . $sales->count());
        $created = 0;

        foreach ($sales as $sale) {
            $created++;
            if ($apply) {
                $movement = AccountMovement::where('reference_type', Sale::class)
                    ->where('reference_id', $sale->id)
                    ->first();

                SalePayment::create([
                    'business_id' => $sale->business_id,
                    'sale_id' => $sale->id,
                    'payment_method' => $sale->payment_method ?? 'Cash',
                    'amount' => $sale->paid_amount,
                    'financial_account_id' => $movement ? $movement->account_id : null,
                    'reference_number' => null,
                ]);
            }
        }

        $this->info("Payment records " . ($apply ? "created: " : "to create: ") . $created);
        $this->info("Classification: DETERMINISTIC_BACKFILL (1-to-1 mapping from existing single-tender sales)");
        return 0;
    }
}
