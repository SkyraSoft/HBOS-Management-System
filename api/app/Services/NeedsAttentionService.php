<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\SaleReturn;
use App\Models\Sale;
use App\Models\CustomerPayment;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class NeedsAttentionService
{
    protected CustomerAccountService $customerService;
    protected SupplierBalanceService $supplierService;
    protected FinancialAccountService $accountService;
    protected AccountMovementService $movementService;

    public function __construct(
        CustomerAccountService $customerService,
        SupplierBalanceService $supplierService,
        FinancialAccountService $accountService,
        AccountMovementService $movementService
    ) {
        $this->customerService = $customerService;
        $this->supplierService = $supplierService;
        $this->accountService = $accountService;
        $this->movementService = $movementService;
    }

    /**
     * Evaluate the 8 deterministic Needs Your Attention rules for active business & branch scope.
     * Returns a structured list of alert objects.
     */
    public function evaluateAlerts(int $businessId, ?int $branchId = null, bool $isOwner = true): array
    {
        $alerts = [];

        // Determine target branches
        $branchQuery = Branch::where('business_id', $businessId);
        if ($branchId) {
            $branchQuery->where('id', $branchId);
        }
        $branches = $branchQuery->get();

        // 1. Low Stock Alert
        $lowStockQuery = BranchInventory::with(['product', 'branch'])
            ->where('business_id', $businessId)
            ->where('quantity_on_hand', '>', 0)
            ->whereColumn('quantity_on_hand', '<=', 'minimum_stock');

        if ($branchId) {
            $lowStockQuery->where('branch_id', $branchId);
        }

        $lowStockItems = $lowStockQuery->get();
        if ($lowStockItems->count() > 0) {
            $alerts[] = [
                'type' => 'low_stock',
                'title' => 'Low Stock Items Detected',
                'message' => "{$lowStockItems->count()} product(s) have reached or fallen below minimum stock thresholds.",
                'severity' => 'warning',
                'branch_id' => $branchId,
                'entity_type' => 'BranchInventory',
                'count' => $lowStockItems->count(),
                'items' => $lowStockItems->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product ? $item->product->name : 'Product #' . $item->product_id,
                    'branch_name' => $item->branch ? $item->branch->name : 'Branch #' . $item->branch_id,
                    'quantity_on_hand' => (int) $item->quantity_on_hand,
                    'minimum_stock' => (int) $item->minimum_stock,
                ])->take(10)->values()->all(),
            ];
        }

        // 2. Out of Stock Alert
        $outOfStockQuery = BranchInventory::with(['product', 'branch'])
            ->where('business_id', $businessId)
            ->where('quantity_on_hand', '<=', 0);

        if ($branchId) {
            $outOfStockQuery->where('branch_id', $branchId);
        }

        $outOfStockItems = $outOfStockQuery->get();
        if ($outOfStockItems->count() > 0) {
            $alerts[] = [
                'type' => 'out_of_stock',
                'title' => 'Out of Stock Items Detected',
                'message' => "{$outOfStockItems->count()} product(s) are currently completely out of stock.",
                'severity' => 'critical',
                'branch_id' => $branchId,
                'entity_type' => 'BranchInventory',
                'count' => $outOfStockItems->count(),
                'items' => $outOfStockItems->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product ? $item->product->name : 'Product #' . $item->product_id,
                    'branch_name' => $item->branch ? $item->branch->name : 'Branch #' . $item->branch_id,
                    'quantity_on_hand' => (int) $item->quantity_on_hand,
                ])->take(10)->values()->all(),
            ];
        }

        // 3. Outstanding Customer Receivable Alert
        $customers = Customer::where('business_id', $businessId)->get();
        $receivableCustomers = [];
        $totalOutstandingExposure = 0.00;

        if ($customers->isNotEmpty()) {
            $salesQuery = Sale::where('business_id', $businessId)
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('customer_id');

            $paymentsQuery = CustomerPayment::where('business_id', $businessId)
                ->where('status', 'posted')
                ->whereNotNull('customer_id');

            if ($branchId) {
                $salesQuery->where('branch_id', $branchId);
                $paymentsQuery->where('branch_id', $branchId);
            }

            $sales = $salesQuery->get();
            $saleIds = $sales->pluck('id');

            $refundsBySale = SaleReturn::whereIn('sale_id', $saleIds)
                ->select('sale_id', DB::raw('SUM(refund_amount) as total_refund'))
                ->groupBy('sale_id')
                ->pluck('total_refund', 'sale_id');

            $paymentsByCustomer = $paymentsQuery
                ->select('customer_id', DB::raw('SUM(amount) as total_paid'))
                ->groupBy('customer_id')
                ->pluck('total_paid', 'customer_id');

            $salesByCustomer = $sales->groupBy('customer_id');

            foreach ($customers as $customer) {
                $custSales = $salesByCustomer->get($customer->id, collect());
                $saleDue = 0.00;
                foreach ($custSales as $sale) {
                    $refund = (float) ($refundsBySale[$sale->id] ?? 0.00);
                    $net = max(0.00, (float) $sale->total - $refund);
                    $due = max(0.00, $net - (float) $sale->paid_amount);
                    $saleDue += $due;
                }

                $paid = (float) ($paymentsByCustomer[$customer->id] ?? 0.00);

                if ($branchId) {
                    $raw = round($saleDue - $paid, 2);
                    $exposure = max(0.00, $raw);
                } else {
                    $opBal = (float) ($customer->opening_balance ?? 0.00);
                    $raw = round($opBal + $saleDue - $paid, 2);
                    $exposure = max(0.00, $raw);
                }

                if ($exposure > 0) {
                    $totalOutstandingExposure += $exposure;
                    $receivableCustomers[] = [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'outstanding' => round($exposure, 2),
                    ];
                }
            }
        }

        if (count($receivableCustomers) > 0) {
            $alerts[] = [
                'type' => 'outstanding_receivable',
                'title' => 'Outstanding Customer Receivables',
                'message' => count($receivableCustomers) . " customer(s) have outstanding balances totaling PKR " . number_format($totalOutstandingExposure, 2) . ".",
                'severity' => 'warning',
                'branch_id' => $branchId,
                'entity_type' => 'Customer',
                'count' => count($receivableCustomers),
                'total_amount' => round($totalOutstandingExposure, 2),
                'items' => array_slice($receivableCustomers, 0, 10),
            ];
        }

        // 4. Supplier Payable Alert (Owner only or aggregate)
        if ($isOwner && !$branchId) {
            $suppliers = Supplier::where('business_id', $businessId)->get();
            $payableSuppliers = [];
            $totalPayable = 0.00;

            if ($suppliers->isNotEmpty()) {
                $purchaseTotals = Purchase::where('business_id', $businessId)
                    ->where('status', 'received')
                    ->select('supplier_id', DB::raw('SUM(total) as total_purchased'))
                    ->groupBy('supplier_id')
                    ->pluck('total_purchased', 'supplier_id');

                $paymentTotals = DB::table('supplier_payments')
                    ->where('business_id', $businessId)
                    ->select('supplier_id', DB::raw('SUM(amount) as total_paid'))
                    ->groupBy('supplier_id')
                    ->pluck('total_paid', 'supplier_id');

                foreach ($suppliers as $supplier) {
                    $opBal = (float) ($supplier->opening_balance ?? 0.00);
                    $purchased = (float) ($purchaseTotals[$supplier->id] ?? 0.00);
                    $paid = (float) ($paymentTotals[$supplier->id] ?? 0.00);
                    $payable = round($opBal + $purchased - $paid, 2);

                    if ($payable > 0) {
                        $totalPayable += $payable;
                        $payableSuppliers[] = [
                            'supplier_id' => $supplier->id,
                            'supplier_name' => $supplier->name,
                            'payable' => round($payable, 2),
                        ];
                    }
                }
            }

            if (count($payableSuppliers) > 0) {
                $alerts[] = [
                    'type' => 'supplier_payable',
                    'title' => 'Pending Supplier Obligations',
                    'message' => count($payableSuppliers) . " supplier(s) have pending payable balances totaling PKR " . number_format($totalPayable, 2) . ".",
                    'severity' => 'info',
                    'branch_id' => null,
                    'entity_type' => 'Supplier',
                    'count' => count($payableSuppliers),
                    'total_amount' => round($totalPayable, 2),
                    'items' => array_slice($payableSuppliers, 0, 10),
                ];
            }
        }

        // 5. Unconfigured Cash Drawer Alert & 6. Inactive Branch Account Configured Alert
        $defaultCashAccounts = FinancialAccount::where('business_id', $businessId)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->where('type', 'cash')
            ->where('is_default', true)
            ->get()
            ->groupBy('branch_id');

        foreach ($branches as $branch) {
            $branchAccounts = $defaultCashAccounts->get($branch->id, collect());
            $activeDefault = $branchAccounts->firstWhere('status', 'active');
            if (!$activeDefault) {
                // Check if an inactive default cash drawer exists
                $inactiveDefault = $branchAccounts->firstWhere('status', 'inactive');

                if ($inactiveDefault) {
                    // Rule 6: Inactive Branch Account Configured Alert
                    $alerts[] = [
                        'type' => 'inactive_branch_account',
                        'title' => 'Default Cash Drawer Inactive',
                        'message' => "Branch '{$branch->name}' has a default cash drawer ('{$inactiveDefault->name}') that is currently set to inactive.",
                        'severity' => 'critical',
                        'branch_id' => $branch->id,
                        'entity_type' => 'FinancialAccount',
                        'entity_id' => $inactiveDefault->id,
                    ];
                } else {
                    // Rule 5: Unconfigured Cash Drawer Alert
                    $alerts[] = [
                        'type' => 'unconfigured_cash_drawer',
                        'title' => 'Missing Default Cash Drawer',
                        'message' => "Branch '{$branch->name}' has no active default cash account configured for POS cash operations.",
                        'severity' => 'warning',
                        'branch_id' => $branch->id,
                        'entity_type' => 'Branch',
                        'entity_id' => $branch->id,
                    ];
                }
            }
        }

        // 7. Unsettled Refund Exposure Alert
        $returnsQuery = SaleReturn::where('business_id', $businessId)
            ->where('refund_amount', '>', 0);
        if ($branchId) {
            $returnsQuery->where('branch_id', $branchId);
        }
        $returns = $returnsQuery->get();

        $unsettledReturns = [];
        $totalUnsettledRefund = 0.00;

        if ($returns->isNotEmpty()) {
            $refundMovements = AccountMovement::where('business_id', $businessId)
                ->where('reference_type', SaleReturn::class)
                ->whereIn('reference_id', $returns->pluck('id'))
                ->where('movement_category', 'refund')
                ->where('status', 'posted')
                ->select('reference_id', DB::raw('SUM(amount) as total_refunded'))
                ->groupBy('reference_id')
                ->pluck('total_refunded', 'reference_id');

            foreach ($returns as $return) {
                $postedRefundMovementsSum = (float) ($refundMovements[$return->id] ?? 0.00);
                $unsettledAmount = round((float) $return->refund_amount - $postedRefundMovementsSum, 2);
                if ($unsettledAmount > 0) {
                    $totalUnsettledRefund += $unsettledAmount;
                    $unsettledReturns[] = [
                        'return_id' => $return->id,
                        'return_number' => $return->return_number,
                        'unsettled_amount' => $unsettledAmount,
                    ];
                }
            }
        }

        if (count($unsettledReturns) > 0) {
            $alerts[] = [
                'type' => 'unsettled_refund_exposure',
                'title' => 'Unsettled Sale Refunds',
                'message' => count($unsettledReturns) . " sale return(s) have unsettled refund amounts totaling PKR " . number_format($totalUnsettledRefund, 2) . ".",
                'severity' => 'warning',
                'branch_id' => $branchId,
                'entity_type' => 'SaleReturn',
                'count' => count($unsettledReturns),
                'total_amount' => round($totalUnsettledRefund, 2),
                'items' => array_slice($unsettledReturns, 0, 10),
            ];
        }

        // 8. Account Balance Anomaly Alert
        $accountsQuery = FinancialAccount::where('business_id', $businessId)->where('status', 'active');
        if ($branchId) {
            $accountsQuery->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }
        if (!$isOwner) {
            $accountsQuery->where('type', 'cash');
        }

        $accounts = $accountsQuery->get();
        $negativeAccounts = [];

        if ($accounts->isNotEmpty()) {
            $movementStats = AccountMovement::whereIn('account_id', $accounts->pluck('id'))
                ->where('status', 'posted')
                ->select(
                    'account_id',
                    DB::raw("SUM(CASE WHEN type = 'inflow' THEN amount ELSE 0 END) as total_inflows"),
                    DB::raw("SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END) as total_outflows")
                )
                ->groupBy('account_id')
                ->get()
                ->keyBy('account_id');

            foreach ($accounts as $account) {
                $stat = $movementStats->get($account->id);
                $inflows = $stat ? (float) $stat->total_inflows : 0.00;
                $outflows = $stat ? (float) $stat->total_outflows : 0.00;
                $rawBal = round((float) $account->opening_balance + $inflows - $outflows, 2);

                if ($rawBal < 0) {
                    $negativeAccounts[] = [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'type' => $account->type,
                        'balance' => $rawBal,
                    ];
                }
            }
        }

        if (count($negativeAccounts) > 0) {
            $alerts[] = [
                'type' => 'account_balance_anomaly',
                'title' => 'Negative Account Balance Detected',
                'message' => count($negativeAccounts) . " financial account(s) have negative authoritative calculated balances.",
                'severity' => 'critical',
                'branch_id' => $branchId,
                'entity_type' => 'FinancialAccount',
                'count' => count($negativeAccounts),
                'items' => $negativeAccounts,
            ];
        }

        return $alerts;
    }
}
