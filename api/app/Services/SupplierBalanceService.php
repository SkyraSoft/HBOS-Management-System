<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Exception;

class SupplierBalanceService
{
    /**
     * Increment supplier payable balance when a purchase with unpaid due_amount is created.
     */
    public function recordPurchaseLiability(Supplier $supplier, float $dueAmount): void
    {
        $dueAmount = round($dueAmount, 2);
        if ($dueAmount <= 0) {
            return;
        }

        DB::transaction(function () use ($supplier, $dueAmount) {
            $lockedSupplier = Supplier::where('id', $supplier->id)->lockForUpdate()->first();
            if ($lockedSupplier) {
                $newBalance = round((float) $lockedSupplier->balance + $dueAmount, 2);
                $lockedSupplier->update(['balance' => $newBalance]);
            }
        });
    }

    /**
     * Decrement supplier payable balance when a supplier payment is made.
     */
    public function recordPayment(Supplier $supplier, float $amount): void
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new Exception("Payment amount must be greater than zero.");
        }

        DB::transaction(function () use ($supplier, $amount) {
            $lockedSupplier = Supplier::where('id', $supplier->id)->lockForUpdate()->first();
            if (!$lockedSupplier) {
                throw new Exception("Supplier not found.");
            }

            $currentBalance = round((float) $lockedSupplier->balance, 2);

            // For Phase 4 MVP: reject overpayment if payment exceeds outstanding balance
            if ($amount > $currentBalance + 0.0001) {
                throw new Exception("Payment amount ({$amount}) exceeds outstanding supplier balance ({$currentBalance}).");
            }

            $newBalance = round($currentBalance - $amount, 2);
            $lockedSupplier->update(['balance' => $newBalance]);
        });
    }

    /**
     * Reverse supplier payable liability when a purchase is cancelled.
     */
    public function reversePurchaseLiability(Supplier $supplier, float $dueAmount): void
    {
        $dueAmount = round($dueAmount, 2);
        if ($dueAmount <= 0) {
            return;
        }

        DB::transaction(function () use ($supplier, $dueAmount) {
            $lockedSupplier = Supplier::where('id', $supplier->id)->lockForUpdate()->first();
            if ($lockedSupplier) {
                $currentBalance = round((float) $lockedSupplier->balance, 2);
                $newBalance = max(0.0, round($currentBalance - $dueAmount, 2));
                $lockedSupplier->update(['balance' => $newBalance]);
            }
        });
    }

    /**
     * Recompute expected supplier balance using Gross Total formula:
     * opening_balance + SUM(received_purchases.total) - SUM(ALL supplier_payments.amount)
     */
    public function reconcileBalance(Supplier $supplier): float
    {
        $receivedPurchasesTotal = Purchase::where('supplier_id', $supplier->id)
            ->where('status', 'received')
            ->sum('total');

        $allPaymentsTotal = SupplierPayment::where('supplier_id', $supplier->id)
            ->sum('amount');

        return round((float) $supplier->opening_balance + (float) $receivedPurchasesTotal - (float) $allPaymentsTotal, 2);
    }

    /**
     * Recompute expected supplier balance using Net Due formula:
     * opening_balance + SUM(received_purchases.due_amount) - SUM(standalone_payments.amount [where purchase_id IS NULL])
     */
    public function reconcileBalanceFromDue(Supplier $supplier): float
    {
        $receivedPurchasesDue = Purchase::where('supplier_id', $supplier->id)
            ->where('status', 'received')
            ->sum('due_amount');

        $standalonePaymentsTotal = SupplierPayment::where('supplier_id', $supplier->id)
            ->whereNull('purchase_id')
            ->sum('amount');

        return round((float) $supplier->opening_balance + (float) $receivedPurchasesDue - (float) $standalonePaymentsTotal, 2);
    }
}
