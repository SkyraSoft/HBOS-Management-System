<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\CustomerPayment;

class AuditKhataLedgerDiscrepanciesCommand extends Command
{
    protected $signature = 'hbos:audit-khata-ledger-discrepancies';
    protected $description = 'REPAIR-005 (GAP-008): Read-only audit & discrepancy schedule for Khata customer ledger balances';

    public function handle()
    {
        $this->info("Starting REPAIR-005: Read-only Khata customer balance audit schedule");

        $customers = Customer::all();
        $discrepancies = 0;

        foreach ($customers as $customer) {
            $totalSalesDue = Sale::where('customer_id', $customer->id)
                ->where('status', '!=', 'cancelled')
                ->sum('due_amount');

            $totalPayments = CustomerPayment::where('customer_id', $customer->id)
                ->where('status', 'posted')
                ->sum('amount');

            $calculatedBalance = round(floatval($customer->opening_balance ?? 0) + floatval($totalSalesDue) - floatval($totalPayments), 2);
            $storedBalance = round(floatval($customer->balance ?? 0), 2);

            if (abs($calculatedBalance - $storedBalance) > 0.01) {
                $discrepancies++;
                $this->warn("Discrepancy on Customer #{$customer->id} ({$customer->name}): Stored={$storedBalance}, Calculated={$calculatedBalance}");
            }
        }

        $this->info("Total customers audited: " . $customers->count() . ", discrepancies found: " . $discrepancies);
        $this->info("Classification: MANUAL_RECONCILIATION_REQUIRED (Read-only discrepancy reporting for operator review)");
        return 0;
    }
}
