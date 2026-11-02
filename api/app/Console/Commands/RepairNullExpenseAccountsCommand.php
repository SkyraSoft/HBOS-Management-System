<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Business;

class RepairNullExpenseAccountsCommand extends Command
{
    protected $signature = 'hbos:repair-null-expense-accounts {--apply : Apply changes to database instead of dry-run}';
    protected $description = 'REPAIR-003 (GAP-013): Allocate legacy NULL account expenses to explicit unallocated holding account (Truth-preserving reconciliation)';

    public function handle()
    {
        $apply = $this->option('apply');
        $this->info("Starting REPAIR-003: Legacy null expense account reconciliation (" . ($apply ? "APPLY MODE" : "DRY RUN MODE") . ")");

        $expenses = Expense::whereNull('financial_account_id')->get();
        $this->info("Legacy expenses with null financial_account_id: " . $expenses->count());
        $reconciled = 0;

        $unallocatedAccounts = [];

        foreach ($expenses as $expense) {
            $businessId = $expense->business_id;
            if (!isset($unallocatedAccounts[$businessId])) {
                $account = FinancialAccount::where('business_id', $businessId)
                    ->where('name', 'UNALLOCATED_HISTORICAL_EXPENSES')
                    ->first();

                if (!$account && $apply) {
                    $account = FinancialAccount::create([
                        'business_id' => $businessId,
                        'name' => 'UNALLOCATED_HISTORICAL_EXPENSES',
                        'type' => 'cash',
                        'status' => 'active',
                        'description' => 'Holding account for legacy unallocated expense reconciliation',
                    ]);
                }
                $unallocatedAccounts[$businessId] = $account;
            }

            $reconciled++;
            if ($apply && $unallocatedAccounts[$businessId]) {
                $expense->update([
                    'financial_account_id' => $unallocatedAccounts[$businessId]->id,
                ]);
            }
        }

        $this->info("Expenses " . ($apply ? "reconciled: " : "to reconcile: ") . $reconciled);
        $this->info("Classification: MANUAL_RECONCILIATION_REQUIRED (Allocates to dedicated UNALLOCATED holding account to prevent fabricating false cash source)");
        return 0;
    }
}
