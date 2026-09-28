<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;

class CustomerAccountService
{
    /**
     * Calculate signed raw account balance:
     * raw_account_balance = opening_balance + SUM(effective_due across eligible Sales) - SUM(posted CustomerPayments)
     */
    public function calculateRawBalance(Customer $customer): float
    {
        $openingBalance = (float) ($customer->opening_balance ?? 0.00);

        $sales = Sale::where('customer_id', $customer->id)
            ->where('business_id', $customer->business_id)
            ->where('status', '!=', 'cancelled')
            ->get();

        $totalSaleDue = 0.00;
        foreach ($sales as $sale) {
            $refunds = (float) SaleReturn::where('sale_id', $sale->id)->sum('refund_amount');
            $effectiveNet = max(0.00, (float) $sale->total - $refunds);
            $effectiveDue = max(0.00, $effectiveNet - (float) $sale->paid_amount);
            $totalSaleDue += $effectiveDue;
        }

        $postedPayments = (float) CustomerPayment::where('customer_id', $customer->id)
            ->where('business_id', $customer->business_id)
            ->where('status', 'posted')
            ->sum('amount');

        $rawBalance = $openingBalance + $totalSaleDue - $postedPayments;

        return round($rawBalance, 2);
    }

    /**
     * Outstanding amount owed by Customer to Business: max(raw_account_balance, 0)
     */
    public function calculateOutstanding(Customer $customer): float
    {
        $raw = $this->calculateRawBalance($customer);
        return max(0.00, $raw);
    }

    /**
     * Available account credit / amount owed by Business to Customer: max(-raw_account_balance, 0)
     */
    public function calculateCredit(Customer $customer): float
    {
        $raw = $this->calculateRawBalance($customer);
        return max(0.00, -$raw);
    }

    /**
     * Calculate assigned Branch exposure for Branch Manager:
     * branch_outstanding = max(SUM(Branch Sale effective_due) - SUM(Branch posted CustomerPayments), 0)
     * (Excludes unallocated Business-wide opening_balance).
     */
    public function calculateBranchExposure(Customer $customer, Branch $branch): float
    {
        $sales = Sale::where('customer_id', $customer->id)
            ->where('business_id', $customer->business_id)
            ->where('branch_id', $branch->id)
            ->where('status', '!=', 'cancelled')
            ->get();

        $branchSaleDue = 0.00;
        foreach ($sales as $sale) {
            $refunds = (float) SaleReturn::where('sale_id', $sale->id)->sum('refund_amount');
            $effectiveNet = max(0.00, (float) $sale->total - $refunds);
            $effectiveDue = max(0.00, $effectiveNet - (float) $sale->paid_amount);
            $branchSaleDue += $effectiveDue;
        }

        $branchPayments = (float) CustomerPayment::where('customer_id', $customer->id)
            ->where('business_id', $customer->business_id)
            ->where('branch_id', $branch->id)
            ->where('status', 'posted')
            ->sum('amount');

        $branchRaw = $branchSaleDue - $branchPayments;

        return max(0.00, round($branchRaw, 2));
    }

    /**
     * Recalculates signed raw_account_balance and atomically updates customers.balance cache.
     */
    public function recalculateBalance(Customer $customer): float
    {
        $raw = $this->calculateRawBalance($customer);
        $customer->forceFill(['balance' => $raw])->save();
        return $raw;
    }

    /**
     * Generates a deterministic chronological account statement / ledger for the Customer.
     */
    public function getAccountLedger(Customer $customer): array
    {
        $ledger = [];

        // 1. Opening Balance entry
        $opBal = (float) ($customer->opening_balance ?? 0.00);
        $ledger[] = [
            'id' => 'opening-' . $customer->id,
            'date' => $customer->created_at ? $customer->created_at->toDateString() : date('Y-m-d'),
            'created_at' => $customer->created_at ? $customer->created_at->toDateTimeString() : date('Y-m-d H:i:s'),
            'type' => 'Opening Balance',
            'reference' => 'OPENING',
            'branch' => 'All Branches',
            'debit' => $opBal > 0 ? $opBal : 0.00,
            'credit' => $opBal < 0 ? abs($opBal) : 0.00,
            'status' => 'posted',
            'notes' => 'Migrated pre-cutover account opening balance',
        ];

        // 2. Credit Sales entries (where effective_due > 0)
        $sales = Sale::with('branch')
            ->where('customer_id', $customer->id)
            ->where('business_id', $customer->business_id)
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($sales as $sale) {
            $refunds = (float) SaleReturn::where('sale_id', $sale->id)->sum('refund_amount');
            $effectiveNet = max(0.00, (float) $sale->total - $refunds);
            $effectiveDue = max(0.00, $effectiveNet - (float) $sale->paid_amount);

            if ($effectiveDue > 0) {
                $ledger[] = [
                    'id' => 'sale-' . $sale->id,
                    'date' => $sale->created_at->toDateString(),
                    'created_at' => $sale->created_at->toDateTimeString(),
                    'type' => 'Credit Sale',
                    'reference' => $sale->invoice_number,
                    'branch' => $sale->branch ? $sale->branch->name : 'N/A',
                    'debit' => round($effectiveDue, 2),
                    'credit' => 0.00,
                    'status' => $sale->status,
                    'notes' => 'Credit Sale invoice ' . $sale->invoice_number,
                ];
            }
        }

        // 3. Customer Payments entries
        $payments = CustomerPayment::with(['branch', 'sale'])
            ->where('customer_id', $customer->id)
            ->where('business_id', $customer->business_id)
            ->get();

        foreach ($payments as $payment) {
            $isPosted = $payment->status === 'posted';
            $ledger[] = [
                'id' => 'payment-' . $payment->id,
                'date' => $payment->date ? $payment->date->toDateString() : $payment->created_at->toDateString(),
                'created_at' => $payment->created_at->toDateTimeString(),
                'type' => $isPosted ? 'Payment Received' : 'Payment Voided',
                'reference' => $payment->payment_number,
                'branch' => $payment->branch ? $payment->branch->name : 'N/A',
                'debit' => 0.00,
                'credit' => $isPosted ? (float) $payment->amount : 0.00,
                'status' => $payment->status,
                'notes' => $isPosted ? ('Collection receipt ' . $payment->payment_number) : ('VOIDED: ' . $payment->reversal_reason),
            ];
        }

        // Sort ledger chronologically by date, created_at, id
        usort($ledger, function ($a, $b) {
            if ($a['date'] === $b['date']) {
                return strcmp($a['created_at'], $b['created_at']);
            }
            return strcmp($a['date'], $b['date']);
        });

        // Compute running balance
        $runningBalance = 0.00;
        foreach ($ledger as &$entry) {
            $runningBalance += ($entry['debit'] - $entry['credit']);
            $entry['running_balance'] = round($runningBalance, 2);
        }

        return $ledger;
    }
}
