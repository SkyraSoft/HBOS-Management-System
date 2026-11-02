<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expense;
use App\Models\AccountMovement;

class ReconcileFinancialMovementsCommand extends Command
{
    protected $signature = 'hbos:reconcile-financial-movements {--apply : Apply changes to database instead of dry-run}';
    protected $description = 'REPAIR-004 (GAP-014): Reconcile orphan account movements for deleted expenses via compensating reversal movements';

    public function handle()
    {
        $apply = $this->option('apply');
        $this->info("Starting REPAIR-004: Deleted expense movement reconciliation (" . ($apply ? "APPLY MODE" : "DRY RUN MODE") . ")");

        $deletedExpenses = Expense::onlyTrashed()->get();
        $this->info("Soft-deleted expenses examined: " . $deletedExpenses->count());
        $reconciled = 0;

        foreach ($deletedExpenses as $expense) {
            $activeMovement = AccountMovement::where('reference_type', Expense::class)
                ->where('reference_id', $expense->id)
                ->where('status', 'posted')
                ->first();

            if ($activeMovement) {
                $reconciled++;
                if ($apply) {
                    app(\App\Services\AccountMovementService::class)->voidMovement(
                        $activeMovement,
                        $expense->user_id ?? 1,
                        "Compensating reconciliation for historical deleted expense #{$expense->id}"
                    );
                }
            }
        }

        $this->info("Movements " . ($apply ? "reconciled with compensating entries: " : "to reconcile: ") . $reconciled);
        $this->info("Classification: COMPENSATING_MOVEMENT_OR_RECONCILIATION (Avoids destructive removal of history)");
        return 0;
    }
}
