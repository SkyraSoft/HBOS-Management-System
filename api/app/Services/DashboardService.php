<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Expense;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DashboardService
{
    protected NeedsAttentionService $needsAttentionService;
    protected CustomerAccountService $customerService;
    protected SupplierBalanceService $supplierService;
    protected AccountMovementService $movementService;
    protected FinancialAccountService $accountService;

    public function __construct(
        NeedsAttentionService $needsAttentionService,
        CustomerAccountService $customerService,
        SupplierBalanceService $supplierService,
        AccountMovementService $movementService,
        FinancialAccountService $accountService
    ) {
        $this->needsAttentionService = $needsAttentionService;
        $this->customerService = $customerService;
        $this->supplierService = $supplierService;
        $this->movementService = $movementService;
        $this->accountService = $accountService;
    }

    protected function formatDateValue($val, $fallback = null): ?string
    {
        if (empty($val)) {
            if (empty($fallback)) return null;
            return $fallback instanceof \DateTimeInterface ? $fallback->format('Y-m-d') : (string) $fallback;
        }
        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d');
        }
        return (string) $val;
    }

    /**
     * Resolve date range boundaries in PKT (Asia/Karachi) timezone.
     */
    public function resolveDateRange(array $params): array
    {
        $timezone = 'Asia/Karachi';
        $today = Carbon::now($timezone)->format('Y-m-d');
        $preset = $params['preset'] ?? $params['date_preset'] ?? 'this_month';

        if (strtolower($preset) === 'custom' || (!empty($params['from']) && !empty($params['to']))) {
            $fromStr = $params['from'] ?? $params['start_date'] ?? $today;
            $toStr = $params['to'] ?? $params['end_date'] ?? $today;

            if ($fromStr > $toStr) {
                throw ValidationException::withMessages([
                    'from' => ['Start date (from) cannot be greater than end date (to).'],
                ]);
            }

            if ($fromStr > $today || $toStr > $today) {
                throw ValidationException::withMessages([
                    'to' => ['Future reporting dates are not permitted.'],
                ]);
            }

            $startDate = Carbon::parse($fromStr, $timezone)->startOfDay();
            $endDate = Carbon::parse($toStr, $timezone)->endOfDay();
            $presetName = 'custom';
        } else {
            $now = Carbon::now($timezone);
            switch (strtolower(str_replace(' ', '_', $preset))) {
                case 'today':
                    $startDate = $now->copy()->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $presetName = 'today';
                    break;
                case 'yesterday':
                    $startDate = $now->copy()->subDay()->startOfDay();
                    $endDate = $now->copy()->subDay()->endOfDay();
                    $presetName = 'yesterday';
                    break;
                case 'last_7_days':
                    $startDate = $now->copy()->subDays(6)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $presetName = 'last_7_days';
                    break;
                case 'last_30_days':
                    $startDate = $now->copy()->subDays(29)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $presetName = 'last_30_days';
                    break;
                case 'previous_month':
                case 'last_month':
                    $startDate = $now->copy()->subMonth()->startOfMonth()->startOfDay();
                    $endDate = $now->copy()->subMonth()->endOfMonth()->endOfDay();
                    $presetName = 'last_month';
                    break;
                case 'this_month':
                default:
                    $startDate = $now->copy()->startOfMonth()->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $presetName = 'this_month';
                    break;
            }
        }

        // Calculate comparison window (equal duration immediately preceding $startDate)
        $diffInDays = max(1, $startDate->diffInDays($endDate) + 1);
        $prevEndDate = $startDate->copy()->subSecond();
        $prevStartDate = $startDate->copy()->subDays($diffInDays)->startOfDay();

        return [
            'preset' => $presetName,
            'from' => $startDate->toDateString(),
            'to' => $endDate->toDateString(),
            'start_datetime' => $startDate->toDateTimeString(),
            'end_datetime' => $endDate->toDateTimeString(),
            'comparison' => [
                'from' => $prevStartDate->toDateString(),
                'to' => $prevEndDate->toDateString(),
                'start_datetime' => $prevStartDate->toDateTimeString(),
                'end_datetime' => $prevEndDate->toDateTimeString(),
            ]
        ];
    }

    /**
     * Compute zero-denominator percentage change.
     */
    public function calculatePercentageChange(float $current, float $previous): array
    {
        $current = round($current, 2);
        $previous = round($previous, 2);

        if ($previous == 0.00 && $current == 0.00) {
            return [
                'current' => 0.00,
                'previous' => 0.00,
                'change_percentage' => 0.0,
                'state' => 'neutral',
            ];
        }

        if ($previous == 0.00 && $current > 0.00) {
            return [
                'current' => $current,
                'previous' => 0.00,
                'change_percentage' => null,
                'state' => 'new',
            ];
        }

        $change = (($current - $previous) / $previous) * 100.0;
        return [
            'current' => $current,
            'previous' => $previous,
            'change_percentage' => round($change, 1),
            'state' => $change > 0 ? 'increase' : ($change < 0 ? 'decrease' : 'neutral'),
        ];
    }

    /**
     * Build Business Owner Dashboard Payload.
     */
    public function getOwnerDashboard(int $businessId, ?int $branchId, array $dateRange): array
    {
        $from = $dateRange['from'];
        $to = $dateRange['to'];

        // Sales & COGS
        $salesMetrics = $this->calculateSalesMetrics($businessId, $branchId, $from, $to);
        $prevSalesMetrics = $this->calculateSalesMetrics($businessId, $branchId, $dateRange['comparison']['from'], $dateRange['comparison']['to']);

        // Expenses
        $expensesTotal = $this->calculateExpenses($businessId, $branchId, $from, $to);
        $prevExpensesTotal = $this->calculateExpenses($businessId, $branchId, $dateRange['comparison']['from'], $dateRange['comparison']['to']);

        // Operating Position
        $operatingPosition = $salesMetrics['is_cogs_complete']
            ? round($salesMetrics['gross_profit'] - $expensesTotal, 2)
            : null;

        // Cash & Bank Balances
        $cashAndBank = $this->calculateCashAndBank($businessId, $branchId, true);

        // Period Cash Flow Movement
        $cashMovement = $this->calculatePeriodCashMovement($businessId, $branchId, $from, $to);

        // Customer Receivables & Supplier Payables
        $receivables = $this->calculateCustomerReceivables($businessId, null);
        $payables = $this->calculateSupplierPayables($businessId);

        // Inventory Metrics
        $inventory = $this->calculateInventoryMetrics($businessId, $branchId);

        // Product Performance
        $productPerformance = $this->calculateProductPerformance($businessId, $branchId, $from, $to);

        // Branch Comparison (Owner only)
        $branchComparison = $this->calculateBranchComparison($businessId, $from, $to);

        // Needs Attention
        $needsAttention = $this->needsAttentionService->evaluateAlerts($businessId, $branchId, true);

        // Recent Activity
        $recentActivity = $this->getRecentActivity($businessId, $branchId, 10);

        return [
            'role' => 'Business Owner',
            'period' => [
                'preset' => $dateRange['preset'],
                'from' => $from,
                'to' => $to,
            ],
            'scope' => [
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'branch_name' => $branchId ? (Branch::find($branchId)?->name ?? "Branch #{$branchId}") : 'All Branches',
            ],
            'sales' => [
                'gross_sales' => $salesMetrics['gross_sales'],
                'return_adjustment' => $salesMetrics['return_adjustment'],
                'net_sales' => $salesMetrics['net_sales'],
                'transaction_count' => $salesMetrics['transaction_count'],
                'average_order_value' => $salesMetrics['average_order_value'],
                'comparison' => $this->calculatePercentageChange($salesMetrics['net_sales'], $prevSalesMetrics['net_sales']),
            ],
            'cogs' => [
                'gross_cogs' => $salesMetrics['gross_cogs'],
                'returned_cogs' => $salesMetrics['returned_cogs'],
                'net_cogs' => $salesMetrics['net_cogs'],
                'cogs_status' => $salesMetrics['cogs_status'],
                'is_partial' => !$salesMetrics['is_cogs_complete'],
                'uncosted_sales_count' => $salesMetrics['uncosted_sales_count'],
            ],
            'gross_profit' => [
                'amount' => $salesMetrics['gross_profit'],
                'is_partial' => !$salesMetrics['is_cogs_complete'],
            ],
            'operating_position' => [
                'amount' => $operatingPosition,
                'is_partial' => !$salesMetrics['is_cogs_complete'],
            ],
            'expenses' => [
                'total' => $expensesTotal,
                'comparison' => $this->calculatePercentageChange($expensesTotal, $prevExpensesTotal),
            ],
            'cash_and_bank' => $cashAndBank,
            'period_cash_movement' => $cashMovement,
            'receivables' => $receivables,
            'payables' => $payables,
            'inventory' => $inventory,
            'product_performance' => $productPerformance,
            'branch_comparison' => $branchComparison,
            'needs_attention' => $needsAttention,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * Build Branch Manager Dashboard Payload.
     */
    public function getManagerDashboard(int $businessId, int $branchId, array $dateRange, User $user): array
    {
        $from = $dateRange['from'];
        $to = $dateRange['to'];
        $branch = Branch::where('business_id', $businessId)->findOrFail($branchId);

        // Sales & COGS
        $salesMetrics = $this->calculateSalesMetrics($businessId, $branchId, $from, $to);
        $prevSalesMetrics = $this->calculateSalesMetrics($businessId, $branchId, $dateRange['comparison']['from'], $dateRange['comparison']['to']);

        // Expenses
        $expensesTotal = $this->calculateExpenses($businessId, $branchId, $from, $to);

        // Branch Cash Drawer Only
        $defaultDrawer = $this->accountService->getDefaultCashAccount($businessId, $branchId);
        $cashDrawerBalance = $defaultDrawer ? $this->movementService->calculateBalance($defaultDrawer) : 0.00;

        // Branch Customer Receivable Exposure
        $branchExposure = $this->calculateCustomerReceivables($businessId, $branch);

        // Inventory Metrics
        $inventory = $this->calculateInventoryMetrics($businessId, $branchId);

        // Needs Attention
        $needsAttention = $this->needsAttentionService->evaluateAlerts($businessId, $branchId, false);

        // Recent Branch Activity
        $recentActivity = $this->getRecentActivity($businessId, $branchId, 10);

        return [
            'role' => 'Branch Manager',
            'period' => [
                'preset' => $dateRange['preset'],
                'from' => $from,
                'to' => $to,
            ],
            'scope' => [
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'branch_name' => $branch->name,
            ],
            'sales' => [
                'gross_sales' => $salesMetrics['gross_sales'],
                'return_adjustment' => $salesMetrics['return_adjustment'],
                'net_sales' => $salesMetrics['net_sales'],
                'transaction_count' => $salesMetrics['transaction_count'],
                'average_order_value' => $salesMetrics['average_order_value'],
                'comparison' => $this->calculatePercentageChange($salesMetrics['net_sales'], $prevSalesMetrics['net_sales']),
            ],
            'expenses' => [
                'total' => $expensesTotal,
            ],
            'cash_drawer' => [
                'account_id' => $defaultDrawer?->id,
                'account_name' => $defaultDrawer?->name ?? 'Default Cash Drawer',
                'balance' => round($cashDrawerBalance, 2),
            ],
            'receivables' => $branchExposure,
            'inventory' => $inventory,
            'needs_attention' => $needsAttention,
            'recent_activity' => $recentActivity,
        ];
    }

    /**
     * Build Salesperson Dashboard Payload.
     */
    public function getSalespersonDashboard(int $businessId, int $branchId, array $dateRange, User $user): array
    {
        $from = $dateRange['from'];
        $to = $dateRange['to'];
        $branch = Branch::where('business_id', $businessId)->findOrFail($branchId);

        // Personal sales strictly filtered by user_id
        $salesQuery = Sale::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('date', [$from, $to]);

        $personalGrossSales = (float) $salesQuery->sum('total');
        $personalTxCount = (int) $salesQuery->count();

        // Personal Return adjustment
        $returnAdjustment = (float) DB::table('sale_returns')
            ->join('sales', 'sale_returns.sale_id', '=', 'sales.id')
            ->where('sale_returns.business_id', $businessId)
            ->where('sale_returns.branch_id', $branchId)
            ->where('sales.user_id', $user->id)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.date', [$from, $to])
            ->sum('sale_returns.refund_amount');

        $personalNetSales = max(0.00, round($personalGrossSales - $returnAdjustment, 2));

        // Unique Customers Served Today/Period
        $customersServed = (int) Sale::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        // Recent own sales
        $recentSales = Sale::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'invoice_number' => $s->invoice_number,
                'date' => $this->formatDateValue($s->date, $s->created_at),
                'total' => (float) $s->total,
                'status' => $s->status,
                'created_at' => $s->created_at ? $s->created_at->toDateTimeString() : null,
            ]);

        return [
            'role' => 'Salesperson',
            'period' => [
                'preset' => $dateRange['preset'],
                'from' => $from,
                'to' => $to,
            ],
            'scope' => [
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'branch_name' => $branch->name,
                'user_name' => $user->name,
            ],
            'personal_sales' => [
                'gross_sales' => round($personalGrossSales, 2),
                'return_adjustment' => round($returnAdjustment, 2),
                'net_sales' => $personalNetSales,
                'transaction_count' => $personalTxCount,
                'customers_served' => $customersServed,
            ],
            'recent_sales' => $recentSales,
        ];
    }

    /**
     * Sales & COGS Helper.
     */
    public function calculateSalesMetrics(int $businessId, ?int $branchId, string $from, string $to): array
    {
        $query = Sale::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $sales = $query->get();
        $grossSales = 0.00;
        $returnAdjustment = 0.00;
        $netSales = 0.00;
        $grossCogs = 0.00;
        $returnedCogs = 0.00;
        $uncostedSalesCount = 0;
        $totalItemsCount = 0;

        $saleIds = $sales->pluck('id');

        // Batch-load sale returns refunds to eliminate N+1 queries
        $refundsBySale = SaleReturn::whereIn('sale_id', $saleIds)
            ->select('sale_id', DB::raw('SUM(refund_amount) as total_refund'))
            ->groupBy('sale_id')
            ->pluck('total_refund', 'sale_id');

        // Batch-load sale items and their returned quantities
        $itemsBySale = SaleItem::whereIn('sale_id', $saleIds)->get()->groupBy('sale_id');
        $allItemIds = $itemsBySale->flatten()->pluck('id');

        $returnedQtyByItem = DB::table('sale_return_items')
            ->whereIn('sale_item_id', $allItemIds)
            ->select('sale_item_id', DB::raw('SUM(quantity) as returned_qty'))
            ->groupBy('sale_item_id')
            ->pluck('returned_qty', 'sale_item_id');

        foreach ($sales as $sale) {
            $grossSales += (float) $sale->total;

            $refunds = (float) ($refundsBySale[$sale->id] ?? 0.00);
            $returnAdjustment += $refunds;
            $netSales += max(0.00, (float) $sale->total - $refunds);

            // Compute Line Items COGS from pre-loaded collection
            $saleItems = $itemsBySale->get($sale->id, collect());
            $hasUncosted = false;

            foreach ($saleItems as $item) {
                $totalItemsCount++;
                if (is_null($item->cost_price)) {
                    $hasUncosted = true;
                } else {
                    $grossCogs += ((float) $item->quantity * (float) $item->cost_price);

                    // Returned COGS for this item from pre-aggregated map
                    $returnedQty = (int) ($returnedQtyByItem[$item->id] ?? 0);

                    if ($returnedQty > 0) {
                        $returnedCogs += ($returnedQty * (float) $item->cost_price);
                    }
                }
            }

            if ($hasUncosted) {
                $uncostedSalesCount++;
            }
        }

        $grossSales = round($grossSales, 2);
        $returnAdjustment = round($returnAdjustment, 2);
        $netSales = round($netSales, 2);
        $grossCogs = round($grossCogs, 2);
        $returnedCogs = round($returnedCogs, 2);
        $netCogs = round(max(0.00, $grossCogs - $returnedCogs), 2);

        $txCount = $sales->count();
        $aov = $txCount > 0 ? round($netSales / $txCount, 2) : 0.00;

        $isComplete = ($uncostedSalesCount === 0);
        $cogsStatus = $isComplete ? 'complete' : 'partial';
        $grossProfit = $isComplete ? round($netSales - $netCogs, 2) : null;

        return [
            'gross_sales' => $grossSales,
            'return_adjustment' => $returnAdjustment,
            'net_sales' => $netSales,
            'transaction_count' => $txCount,
            'average_order_value' => $aov,
            'gross_cogs' => $grossCogs,
            'returned_cogs' => $returnedCogs,
            'net_cogs' => $netCogs,
            'cogs_status' => $cogsStatus,
            'is_cogs_complete' => $isComplete,
            'uncosted_sales_count' => $uncostedSalesCount,
            'gross_profit' => $grossProfit,
        ];
    }

    /**
     * Posted Expenses Helper.
     */
    public function calculateExpenses(int $businessId, ?int $branchId, string $from, string $to): float
    {
        $query = Expense::where('business_id', $businessId)
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return round((float) $query->sum('amount'), 2);
    }

    /**
     * Cash & Bank Balances Helper.
     */
    public function calculateCashAndBank(int $businessId, ?int $branchId, bool $isOwner): array
    {
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
        $cashOnHand = 0.00;
        $bankBalance = 0.00;

        foreach ($accounts as $account) {
            $bal = $this->movementService->calculateBalance($account);
            if ($account->type === 'cash') {
                $cashOnHand += $bal;
            } elseif ($account->type === 'bank' && $isOwner) {
                $bankBalance += $bal;
            }
        }

        return [
            'cash_on_hand' => round($cashOnHand, 2),
            'bank_balance' => round($bankBalance, 2),
            'total_tracked_funds' => round($cashOnHand + $bankBalance, 2),
        ];
    }

    /**
     * Period Cash Flow Helper (Excludes internal transfers).
     */
    public function calculatePeriodCashMovement(int $businessId, ?int $branchId, string $from, string $to): array
    {
        $inflowsQuery = AccountMovement::where('business_id', $businessId)
            ->where('status', 'posted')
            ->where('type', 'inflow')
            ->whereNotIn('movement_category', ['transfer_in', 'transfer_out'])
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        $outflowsQuery = AccountMovement::where('business_id', $businessId)
            ->where('status', 'posted')
            ->where('type', 'outflow')
            ->whereNotIn('movement_category', ['transfer_in', 'transfer_out'])
            ->whereBetween('date', [$from, $to]);

        if ($branchId) {
            $inflowsQuery->where('branch_id', $branchId);
            $outflowsQuery->where('branch_id', $branchId);
        }

        $inflows = (float) $inflowsQuery->sum('amount');
        $outflows = (float) $outflowsQuery->sum('amount');

        return [
            'external_inflows' => round($inflows, 2),
            'external_outflows' => round($outflows, 2),
            'net_external_movement' => round($inflows - $outflows, 2),
        ];
    }

    /**
     * Customer Receivables Helper (Batched set-based evaluation).
     */
    public function calculateCustomerReceivables(int $businessId, ?Branch $branch): array
    {
        $customers = Customer::where('business_id', $businessId)->get();
        if ($customers->isEmpty()) {
            return ['outstanding' => 0.00, 'customer_credit' => 0.00];
        }

        $salesQuery = Sale::where('business_id', $businessId)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('customer_id');

        $paymentsQuery = CustomerPayment::where('business_id', $businessId)
            ->where('status', 'posted')
            ->whereNotNull('customer_id');

        if ($branch) {
            $salesQuery->where('branch_id', $branch->id);
            $paymentsQuery->where('branch_id', $branch->id);
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

        $totalOutstanding = 0.00;
        $totalCredit = 0.00;

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

            if ($branch) {
                $raw = $saleDue - $paid;
                $totalOutstanding += max(0.00, round($raw, 2));
            } else {
                $opBal = (float) ($customer->opening_balance ?? 0.00);
                $raw = round($opBal + $saleDue - $paid, 2);
                if ($raw > 0) {
                    $totalOutstanding += $raw;
                } else {
                    $totalCredit += abs($raw);
                }
            }
        }

        return [
            'outstanding' => round($totalOutstanding, 2),
            'customer_credit' => round($totalCredit, 2),
        ];
    }

    /**
     * Supplier Payables Helper (Batched set-based evaluation).
     */
    public function calculateSupplierPayables(int $businessId): array
    {
        $suppliers = Supplier::where('business_id', $businessId)->get();
        if ($suppliers->isEmpty()) {
            return ['total_payable' => 0.00];
        }

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

        $totalPayable = 0.00;

        foreach ($suppliers as $supplier) {
            $opBal = (float) ($supplier->opening_balance ?? 0.00);
            $purchased = (float) ($purchaseTotals[$supplier->id] ?? 0.00);
            $paid = (float) ($paymentTotals[$supplier->id] ?? 0.00);
            $payable = round($opBal + $purchased - $paid, 2);

            if ($payable > 0) {
                $totalPayable += $payable;
            }
        }

        return [
            'total_payable' => round($totalPayable, 2),
        ];
    }

    /**
     * Inventory Metrics Helper.
     */
    public function calculateInventoryMetrics(int $businessId, ?int $branchId): array
    {
        $query = BranchInventory::with('product')
            ->where('business_id', $businessId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $items = $query->get();
        $unitsOnHand = 0;
        $stockValue = 0.00;
        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($items as $item) {
            $qty = (int) $item->quantity_on_hand;
            $minStock = (int) $item->minimum_stock;
            $unitsOnHand += max(0, $qty);

            $costPrice = $item->product ? (float) $item->product->cost_price : 0.00;
            $stockValue += (max(0, $qty) * $costPrice);

            if ($qty <= 0) {
                $outOfStockCount++;
            } elseif ($qty <= $minStock) {
                $lowStockCount++;
            }
        }

        return [
            'units_on_hand' => $unitsOnHand,
            'current_stock_value_at_current_cost' => round($stockValue, 2),
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
        ];
    }

    /**
     * Product Performance Helper (Top Sellers & Slow Movers).
     */
    public function calculateProductPerformance(int $businessId, ?int $branchId, string $from, string $to): array
    {
        // 1. Calculate Net Units Sold & Net Sales per Product in date range
        $salesQuery = Sale::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }

        $saleIds = $salesQuery->pluck('id');

        $grossItemStats = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as gross_units'),
                DB::raw('SUM(total) as gross_revenue')
            )
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $returnItemStats = DB::table('sale_return_items')
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->whereIn('sale_returns.sale_id', $saleIds)
            ->select(
                'sale_return_items.product_id',
                DB::raw('SUM(sale_return_items.quantity) as returned_units'),
                DB::raw('SUM(sale_return_items.refund_amount) as returned_revenue')
            )
            ->groupBy('sale_return_items.product_id')
            ->get()
            ->keyBy('product_id');

        $productPerformance = [];
        $activeProductIdsWithSales = [];

        foreach ($grossItemStats as $productId => $grossStat) {
            $product = Product::where('business_id', $businessId)->find($productId);
            if (!$product) continue;

            $retStat = $returnItemStats[$productId] ?? null;
            $returnedUnits = $retStat ? (int) $retStat->returned_units : 0;
            $returnedRevenue = $retStat ? (float) $retStat->returned_revenue : 0.00;

            $grossUnits = (int) $grossStat->gross_units;
            $grossRevenue = (float) $grossStat->gross_revenue;

            $netUnits = max(0, $grossUnits - $returnedUnits);
            $netRevenue = max(0.00, round($grossRevenue - $returnedRevenue, 2));

            if ($netUnits > 0 || $netRevenue > 0) {
                $activeProductIdsWithSales[] = $productId;
            }

            $productPerformance[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'net_sales' => $netRevenue,
                'net_units_sold' => $netUnits,
                'gross_units' => $grossUnits,
                'returned_units' => $returnedUnits,
            ];
        }

        // Rank Top Sellers by Net Sales revenue descending
        usort($productPerformance, fn($a, $b) => $b['net_sales'] <=> $a['net_sales']);
        $topSellers = array_slice($productPerformance, 0, 5);

        // Slow Movers: BranchInventory quantity_on_hand > 0 AND Net Units Sold = 0
        $biQuery = BranchInventory::with('product')
            ->where('business_id', $businessId)
            ->where('quantity_on_hand', '>', 0);

        if ($branchId) {
            $biQuery->where('branch_id', $branchId);
        }

        if (!empty($activeProductIdsWithSales)) {
            $biQuery->whereNotIn('product_id', $activeProductIdsWithSales);
        }

        $slowMovers = $biQuery->limit(10)->get()->map(fn($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product ? $item->product->name : 'Product #' . $item->product_id,
            'sku' => $item->product ? $item->product->sku : null,
            'quantity_on_hand' => (int) $item->quantity_on_hand,
            'net_units_sold' => 0,
        ])->values()->all();

        return [
            'top_sellers' => $topSellers,
            'slow_movers' => $slowMovers,
        ];
    }

    /**
     * Branch Comparison Helper (Owner Only).
     */
    public function calculateBranchComparison(int $businessId, string $from, string $to): array
    {
        $branches = Branch::where('business_id', $businessId)->get();
        if ($branches->isEmpty()) {
            return [];
        }

        $branchIds = $branches->pluck('id');

        // 1. All completed sales in period across branches
        $allSales = Sale::where('business_id', $businessId)
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'completed')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get();

        $allSaleIds = $allSales->pluck('id');

        // 2. Returns for those sales
        $allReturns = $allSaleIds->isNotEmpty()
            ? SaleReturn::whereIn('sale_id', $allSaleIds)->get()
            : collect();

        $allReturnIds = $allReturns->pluck('id');

        // 3. Sale items for gross COGS
        $allSaleItems = $allSaleIds->isNotEmpty()
            ? SaleItem::whereIn('sale_id', $allSaleIds)->get()
            : collect();

        // 4. Return items for returned COGS
        $allReturnItems = $allReturnIds->isNotEmpty()
            ? SaleReturnItem::whereIn('sale_return_id', $allReturnIds)->get()
            : collect();

        // 5. Pre-aggregate expenses by branch
        $expensesByBranch = Expense::where('business_id', $businessId)
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->select('branch_id', DB::raw('SUM(amount) as total_expense'))
            ->groupBy('branch_id')
            ->pluck('total_expense', 'branch_id');

        // 6. Default cash drawers for branches
        $defaultDrawers = FinancialAccount::where('business_id', $businessId)
            ->whereIn('branch_id', $branchIds)
            ->where('type', 'cash')
            ->where('is_default', true)
            ->where('status', 'active')
            ->get()
            ->keyBy('branch_id');

        // 7. Movements for default drawers
        $drawerIds = $defaultDrawers->pluck('id');
        $drawerMovements = $drawerIds->isNotEmpty()
            ? AccountMovement::whereIn('account_id', $drawerIds)
                ->where('status', 'posted')
                ->select(
                    'account_id',
                    DB::raw("SUM(CASE WHEN type = 'inflow' THEN amount ELSE 0 END) as total_inflows"),
                    DB::raw("SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END) as total_outflows")
                )
                ->groupBy('account_id')
                ->get()
                ->keyBy('account_id')
            : collect();

        // Group data by branch_id in memory
        $salesByBranch = $allSales->groupBy('branch_id');
        $returnsBySale = $allReturns->groupBy('sale_id');
        $itemsBySale = $allSaleItems->groupBy('sale_id');
        $itemsByReturn = $allReturnItems->groupBy('sale_return_id');

        $comparison = [];

        foreach ($branches as $branch) {
            $branchSales = $salesByBranch->get($branch->id, collect());
            $grossSales = 0.00;
            $returnAdjustment = 0.00;
            $netSales = 0.00;
            $grossCogs = 0.00;
            $returnedCogs = 0.00;
            $uncostedSalesCount = 0;
            $totalItemsCount = 0;

            foreach ($branchSales as $sale) {
                $saleGross = (float) $sale->total;
                $grossSales += $saleGross;

                $saleReturns = $returnsBySale->get($sale->id, collect());
                $refund = (float) $saleReturns->sum('refund_amount');
                $returnAdjustment += $refund;

                $saleNet = max(0.00, $saleGross - $refund);
                $netSales += $saleNet;

                $items = $itemsBySale->get($sale->id, collect());
                $hasZeroCost = false;

                foreach ($items as $item) {
                    $totalItemsCount++;
                    $qty = (float) $item->quantity;
                    $cost = (float) ($item->cost_price ?? 0.00);

                    if ($cost == 0.00) {
                        $hasZeroCost = true;
                    }
                    $grossCogs += ($qty * $cost);
                }

                if ($hasZeroCost && $items->isNotEmpty()) {
                    $uncostedSalesCount++;
                }

                foreach ($saleReturns as $ret) {
                    $retItems = $itemsByReturn->get($ret->id, collect());
                    foreach ($retItems as $rItem) {
                        $rQty = (float) $rItem->quantity;
                        $rCost = (float) ($rItem->cost_price ?? 0.00);
                        $returnedCogs += ($rQty * $rCost);
                    }
                }
            }

            $netCogs = max(0.00, $grossCogs - $returnedCogs);
            $grossProfit = round($netSales - $netCogs, 2);
            $isCogsComplete = ($totalItemsCount > 0 && $uncostedSalesCount === 0);

            $drawer = $defaultDrawers->get($branch->id);
            $drawerBalance = 0.00;
            if ($drawer) {
                $stat = $drawerMovements->get($drawer->id);
                $inflows = $stat ? (float) $stat->total_inflows : 0.00;
                $outflows = $stat ? (float) $stat->total_outflows : 0.00;
                $drawerBalance = round((float) $drawer->opening_balance + $inflows - $outflows, 2);
            }

            $expenses = (float) ($expensesByBranch[$branch->id] ?? 0.00);

            $comparison[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'net_sales' => round($netSales, 2),
                'transaction_count' => $branchSales->count(),
                'gross_profit' => $grossProfit,
                'is_cogs_complete' => $isCogsComplete,
                'expenses' => round($expenses, 2),
                'cash_drawer_balance' => round($drawerBalance, 2),
            ];
        }

        return $comparison;
    }

    /**
     * Recent Activity Stream Helper.
     */
    public function getRecentActivity(int $businessId, ?int $branchId, int $limit = 10): array
    {
        $activities = [];

        // 1. Sales
        $salesQuery = Sale::where('business_id', $businessId);
        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }
        $sales = $salesQuery->orderBy('created_at', 'desc')->limit($limit)->get();

        foreach ($sales as $sale) {
            $activities[] = [
                'id' => 'sale-' . $sale->id,
                'type' => 'Sale',
                'reference' => $sale->invoice_number,
                'amount' => (float) $sale->total,
                'status' => $sale->status,
                'date' => $this->formatDateValue($sale->date, $sale->created_at),
                'created_at' => $sale->created_at ? $sale->created_at->toDateTimeString() : null,
            ];
        }

        // 2. Customer Payments
        $paymentsQuery = CustomerPayment::where('business_id', $businessId);
        if ($branchId) {
            $paymentsQuery->where('branch_id', $branchId);
        }
        $payments = $paymentsQuery->orderBy('created_at', 'desc')->limit($limit)->get();

        foreach ($payments as $payment) {
            $activities[] = [
                'id' => 'payment-' . $payment->id,
                'type' => 'CustomerPayment',
                'reference' => $payment->payment_number,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'date' => $this->formatDateValue($payment->date, $payment->created_at),
                'created_at' => $payment->created_at ? $payment->created_at->toDateTimeString() : null,
            ];
        }

        // 3. Expenses
        $expensesQuery = Expense::where('business_id', $businessId);
        if ($branchId) {
            $expensesQuery->where('branch_id', $branchId);
        }
        $expenses = $expensesQuery->orderBy('created_at', 'desc')->limit($limit)->get();

        foreach ($expenses as $expense) {
            $activities[] = [
                'id' => 'expense-' . $expense->id,
                'type' => 'Expense',
                'reference' => "EXP-{$expense->id}",
                'amount' => (float) $expense->amount,
                'status' => $expense->status,
                'date' => $this->formatDateValue($expense->date, $expense->created_at),
                'created_at' => $expense->created_at ? $expense->created_at->toDateTimeString() : null,
            ];
        }

        // Sort chronologically descending
        usort($activities, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return array_slice($activities, 0, $limit);
    }
}
