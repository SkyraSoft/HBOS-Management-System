<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Expense;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class ReportService
{
    protected DashboardService $dashboardService;
    protected CustomerAccountService $customerService;
    protected SupplierBalanceService $supplierService;
    protected AccountMovementService $movementService;

    public function __construct(
        DashboardService $dashboardService,
        CustomerAccountService $customerService,
        SupplierBalanceService $supplierService,
        AccountMovementService $movementService
    ) {
        $this->dashboardService = $dashboardService;
        $this->customerService = $customerService;
        $this->supplierService = $supplierService;
        $this->movementService = $movementService;
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
     * Sales Report (Summary + Detailed Sales Rows).
     */
    public function getSalesReport(int $businessId, ?int $branchId, string $from, string $to, ?string $format = null)
    {
        $salesMetrics = $this->dashboardService->calculateSalesMetrics($businessId, $branchId, $from, $to);
        $productPerformance = $this->dashboardService->calculateProductPerformance($businessId, $branchId, $from, $to);

        $query = Sale::with(['branch', 'customer'])
            ->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('date', 'desc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $salesRows = $query->get();

        if (strtolower($format ?? '') === 'csv') {
            $headers = ['Invoice Number', 'Date', 'Branch', 'Customer', 'Status', 'Paid Amount', 'Due Amount', 'Total'];
            $csvRows = $salesRows->map(fn($s) => [
                $s->invoice_number,
                $this->formatDateValue($s->date, $s->created_at),
                $s->branch ? $s->branch->name : 'N/A',
                $s->customer ? $s->customer->name : 'Walk-in Customer',
                $s->status,
                number_format((float)$s->paid_amount, 2),
                number_format((float)$s->due_amount, 2),
                number_format((float)$s->total, 2),
            ])->toArray();

            return $this->exportToCsv($headers, $csvRows, "sales_report_{$from}_to_{$to}.csv");
        }

        return [
            'summary' => $salesMetrics,
            'top_sellers' => $productPerformance['top_sellers'],
            'slow_movers' => $productPerformance['slow_movers'],
            'sales' => $salesRows->map(fn($s) => [
                'id' => $s->id,
                'invoice_number' => $s->invoice_number,
                'date' => $this->formatDateValue($s->date, $s->created_at),
                'branch_name' => $s->branch ? $s->branch->name : 'N/A',
                'customer_name' => $s->customer ? $s->customer->name : 'Walk-in Customer',
                'paid_amount' => (float) $s->paid_amount,
                'due_amount' => (float) $s->due_amount,
                'total' => (float) $s->total,
                'status' => $s->status,
            ]),
        ];
    }

    /**
     * Inventory Report.
     */
    public function getInventoryReport(int $businessId, ?int $branchId, ?string $format = null)
    {
        $summary = $this->dashboardService->calculateInventoryMetrics($businessId, $branchId);

        $query = BranchInventory::with(['product.category', 'branch'])
            ->where('business_id', $businessId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $inventoryRows = $query->get();

        if (strtolower($format ?? '') === 'csv') {
            $headers = ['Branch', 'Product SKU', 'Product Name', 'Category', 'Quantity on Hand', 'Minimum Stock', 'Unit Cost', 'Stock Value'];
            $csvRows = $inventoryRows->map(function ($item) {
                $cost = $item->product ? (float)$item->product->cost_price : 0.00;
                $qty = (int)$item->quantity_on_hand;
                return [
                    $item->branch ? $item->branch->name : 'N/A',
                    $item->product ? $item->product->sku : 'N/A',
                    $item->product ? $item->product->name : 'Product #' . $item->product_id,
                    $item->product && $item->product->category ? $item->product->category->name : 'N/A',
                    $qty,
                    (int)$item->minimum_stock,
                    number_format($cost, 2),
                    number_format($qty * $cost, 2),
                ];
            })->toArray();

            return $this->exportToCsv($headers, $csvRows, "inventory_report.csv");
        }

        return [
            'summary' => $summary,
            'items' => $inventoryRows->map(function ($item) {
                $cost = $item->product ? (float)$item->product->cost_price : 0.00;
                $qty = (int)$item->quantity_on_hand;
                $min = (int)$item->minimum_stock;
                $status = ($qty <= 0) ? 'Out of Stock' : (($qty <= $min) ? 'Low Stock' : 'In Stock');

                return [
                    'id' => $item->id,
                    'branch_name' => $item->branch ? $item->branch->name : 'N/A',
                    'product_id' => $item->product_id,
                    'product_name' => $item->product ? $item->product->name : 'Product #' . $item->product_id,
                    'sku' => $item->product ? $item->product->sku : null,
                    'category_name' => $item->product && $item->product->category ? $item->product->category->name : 'N/A',
                    'quantity_on_hand' => $qty,
                    'minimum_stock' => $min,
                    'unit_cost' => round($cost, 2),
                    'stock_value' => round($qty * $cost, 2),
                    'stock_status' => $status,
                ];
            }),
        ];
    }

    /**
     * Customer Report.
     */
    public function getCustomerReport(int $businessId, ?Branch $branch, ?string $format = null)
    {
        $customers = Customer::where('business_id', $businessId)->orderBy('name', 'asc')->get();
        if ($customers->isEmpty()) {
            return [
                'summary' => ['outstanding' => 0.00, 'customer_credit' => 0.00],
                'customers' => [],
            ];
        }

        // Pre-aggregate sales count and total purchased to prevent N+1 queries
        $salesStats = Sale::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->select('customer_id', DB::raw('COUNT(*) as sales_count'), DB::raw('SUM(total) as total_purchased'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

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

        $customerData = $customers->map(function ($c) use ($branch, $salesStats, $salesByCustomer, $refundsBySale, $paymentsByCustomer, &$totalOutstanding, &$totalCredit) {
            $custSales = $salesByCustomer->get($c->id, collect());
            $saleDue = 0.00;
            foreach ($custSales as $sale) {
                $refund = (float) ($refundsBySale[$sale->id] ?? 0.00);
                $net = max(0.00, (float) $sale->total - $refund);
                $due = max(0.00, $net - (float) $sale->paid_amount);
                $saleDue += $due;
            }

            $paid = (float) ($paymentsByCustomer[$c->id] ?? 0.00);

            if ($branch) {
                $raw = round($saleDue - $paid, 2);
                $outstanding = max(0.00, $raw);
                $credit = 0.00;
            } else {
                $opBal = (float) ($c->opening_balance ?? 0.00);
                $raw = round($opBal + $saleDue - $paid, 2);
                $outstanding = max(0.00, $raw);
                $credit = max(0.00, -$raw);
            }

            $totalOutstanding += $outstanding;
            $totalCredit += $credit;

            $stat = $salesStats->get($c->id);
            $salesCount = $stat ? (int) $stat->sales_count : 0;
            $totalPurchased = $stat ? (float) $stat->total_purchased : 0.00;

            return [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'opening_balance' => (float) ($c->opening_balance ?? 0),
                'raw_balance' => $raw,
                'outstanding' => $outstanding,
                'credit' => $credit,
                'sales_count' => $salesCount,
                'total_purchased' => round($totalPurchased, 2),
            ];
        });

        $summary = [
            'outstanding' => round($totalOutstanding, 2),
            'customer_credit' => round($totalCredit, 2),
        ];

        if (strtolower($format ?? '') === 'csv') {
            $headers = ['Customer Name', 'Phone', 'Opening Balance', 'Total Sales Volume', 'Outstanding Receivable', 'Customer Credit'];
            $csvRows = $customerData->map(fn($c) => [
                $c['name'],
                $c['phone'] ?? 'N/A',
                number_format($c['opening_balance'], 2),
                number_format($c['total_purchased'], 2),
                number_format($c['outstanding'], 2),
                number_format($c['credit'], 2),
            ])->toArray();

            return $this->exportToCsv($headers, $csvRows, "customer_report.csv");
        }

        return [
            'summary' => $summary,
            'customers' => $customerData,
        ];
    }

    /**
     * Supplier Report.
     */
    public function getSupplierReport(int $businessId, ?string $format = null)
    {
        $suppliers = Supplier::where('business_id', $businessId)->orderBy('name', 'asc')->get();
        $summary = $this->dashboardService->calculateSupplierPayables($businessId);

        // Pre-aggregate purchase stats and payment stats to eliminate N+1 queries
        $purchaseStats = Purchase::where('business_id', $businessId)
            ->where('status', 'received')
            ->select('supplier_id', DB::raw('COUNT(*) as purchases_count'), DB::raw('SUM(total) as total_purchased'))
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        $paymentStats = DB::table('supplier_payments')
            ->where('business_id', $businessId)
            ->select('supplier_id', DB::raw('SUM(amount) as total_paid'))
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        $supplierData = $suppliers->map(function ($s) use ($purchaseStats, $paymentStats) {
            $pStat = $purchaseStats->get($s->id);
            $purchasesCount = $pStat ? (int) $pStat->purchases_count : 0;
            $totalPurchased = $pStat ? (float) $pStat->total_purchased : 0.00;

            $payStat = $paymentStats->get($s->id);
            $totalPaid = $payStat ? (float) $payStat->total_paid : 0.00;

            $payable = round((float) ($s->opening_balance ?? 0) + $totalPurchased - $totalPaid, 2);

            return [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'phone' => $s->phone,
                'opening_balance' => (float) ($s->opening_balance ?? 0),
                'payable' => max(0.00, $payable),
                'purchases_count' => $purchasesCount,
                'total_purchased' => round($totalPurchased, 2),
                'total_paid' => round($totalPaid, 2),
            ];
        });

        if (strtolower($format ?? '') === 'csv') {
            $headers = ['Supplier Code', 'Supplier Name', 'Phone', 'Opening Balance', 'Total Purchases', 'Total Paid', 'Current Payable'];
            $csvRows = $supplierData->map(fn($s) => [
                $s['code'] ?? 'N/A',
                $s['name'],
                $s['phone'] ?? 'N/A',
                number_format($s['opening_balance'], 2),
                number_format($s['total_purchased'], 2),
                number_format($s['total_paid'], 2),
                number_format($s['payable'], 2),
            ])->toArray();

            return $this->exportToCsv($headers, $csvRows, "supplier_report.csv");
        }

        return [
            'summary' => $summary,
            'suppliers' => $supplierData,
        ];
    }

    /**
     * Expense Report.
     */
    public function getExpenseReport(int $businessId, ?int $branchId, string $from, string $to, ?string $format = null)
    {
        $totalExpenses = $this->dashboardService->calculateExpenses($businessId, $branchId, $from, $to);

        $query = Expense::with(['categoryModel', 'branch', 'user', 'financialAccount'])
            ->where('business_id', $businessId)
            ->where('status', 'posted');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $expenses = $query->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('date', 'desc')
            ->get();

        // Group by category
        $byCategory = DB::table('expenses')
            ->where('business_id', $businessId)
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('category', DB::raw('SUM(amount) as category_total'), DB::raw('COUNT(*) as expense_count'))
            ->groupBy('category')
            ->get();

        if (strtolower($format ?? '') === 'csv') {
            $headers = ['Expense ID', 'Date', 'Category', 'Description', 'Branch', 'Account', 'Amount', 'Status'];
            $csvRows = $expenses->map(fn($e) => [
                $e->id,
                $this->formatDateValue($e->date, $e->created_at),
                $e->category,
                $e->description ?? 'N/A',
                $e->branch ? $e->branch->name : 'N/A',
                $e->financialAccount ? $e->financialAccount->name : 'N/A',
                number_format((float)$e->amount, 2),
                $e->status,
            ])->toArray();

            return $this->exportToCsv($headers, $csvRows, "expense_report_{$from}_to_{$to}.csv");
        }

        return [
            'total_expenses' => $totalExpenses,
            'categories_summary' => $byCategory->map(fn($c) => [
                'category' => $c->category,
                'total' => round((float)$c->category_total, 2),
                'count' => (int)$c->expense_count,
            ]),
            'expenses' => $expenses->map(fn($e) => [
                'id' => $e->id,
                'date' => $this->formatDateValue($e->date, $e->created_at),
                'category' => $e->category,
                'description' => $e->description,
                'branch_name' => $e->branch ? $e->branch->name : 'N/A',
                'account_name' => $e->financialAccount ? $e->financialAccount->name : 'N/A',
                'amount' => (float) $e->amount,
                'status' => $e->status,
            ]),
        ];
    }

    /**
     * Financial Accounts Report.
     */
    public function getFinancialAccountReport(int $businessId, ?int $branchId, string $from, string $to, bool $isOwner, ?string $format = null)
    {
        $cashAndBank = $this->dashboardService->calculateCashAndBank($businessId, $branchId, $isOwner);
        $periodCashMovement = $this->dashboardService->calculatePeriodCashMovement($businessId, $branchId, $from, $to);

        $query = FinancialAccount::with('branch')
            ->where('business_id', $businessId);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }
        if (!$isOwner) {
            $query->where('type', 'cash');
        }

        $accountList = $query->get();
        $accountIds = $accountList->pluck('id');

        // Pre-aggregate period movements by account to eliminate N+1 queries
        $movementStats = AccountMovement::whereIn('account_id', $accountIds)
            ->where('status', 'posted')
            ->whereNotIn('movement_category', ['transfer_in', 'transfer_out'])
            ->whereBetween('date', [$from, $to])
            ->select('account_id', 'type', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('account_id', 'type')
            ->get()
            ->groupBy('account_id');

        $accounts = $accountList->map(function ($acc) use ($from, $to, $movementStats) {
            $rawBal = $this->movementService->calculateBalance($acc);
            $accMovements = $movementStats->get($acc->id, collect());
            $inflows = (float) ($accMovements->firstWhere('type', 'inflow')?->total_amount ?? 0.00);
            $outflows = (float) ($accMovements->firstWhere('type', 'outflow')?->total_amount ?? 0.00);

            return [
                'id' => $acc->id,
                'name' => $acc->name,
                'type' => $acc->type,
                'branch_name' => $acc->branch ? $acc->branch->name : 'Central (All Branches)',
                'is_default' => (bool) $acc->is_default,
                'status' => $acc->status,
                'opening_balance' => (float) $acc->opening_balance,
                'current_balance' => round($rawBal, 2),
                'period_inflows' => round($inflows, 2),
                'period_outflows' => round($outflows, 2),
            ];
        });

        if (strtolower($format ?? '') === 'csv') {
            $headers = ['Account Name', 'Type', 'Branch', 'Is Default', 'Status', 'Opening Balance', 'Period Inflows', 'Period Outflows', 'Current Balance'];
            $csvRows = $accounts->map(fn($a) => [
                $a['name'],
                $a['type'],
                $a['branch_name'],
                $a['is_default'] ? 'Yes' : 'No',
                $a['status'],
                number_format($a['opening_balance'], 2),
                number_format($a['period_inflows'], 2),
                number_format($a['period_outflows'], 2),
                number_format($a['current_balance'], 2),
            ])->toArray();

            return $this->exportToCsv($headers, $csvRows, "financial_accounts_report_{$from}_to_{$to}.csv");
        }

        return [
            'cash_and_bank' => $cashAndBank,
            'period_cash_movement' => $periodCashMovement,
            'accounts' => $accounts,
        ];
    }

    /**
     * Branch Comparison Report (Owner Only).
     */
    public function getBranchReport(int $businessId, string $from, string $to, ?string $format = null)
    {
        $comparison = $this->dashboardService->calculateBranchComparison($businessId, $from, $to);

        if (strtolower($format ?? '') === 'csv') {
            $headers = ['Branch Name', 'Transaction Count', 'Net Sales', 'Gross Profit', 'Posted Expenses', 'Cash Drawer Balance'];
            $csvRows = collect($comparison)->map(fn($b) => [
                $b['branch_name'],
                $b['transaction_count'],
                number_format($b['net_sales'], 2),
                $b['is_cogs_complete'] ? number_format($b['gross_profit'], 2) : 'Partial COGS',
                number_format($b['expenses'], 2),
                number_format($b['cash_drawer_balance'], 2),
            ])->toArray();

            return $this->exportToCsv($headers, $csvRows, "branch_comparison_report_{$from}_to_{$to}.csv");
        }

        return [
            'period' => ['from' => $from, 'to' => $to],
            'branches' => $comparison,
        ];
    }

    /**
     * Comprehensive Small-Retail Net Profit Statement (REQ-0027, SRT-GAP-019).
     *
     * Gross Goods Sales (SUM subtotal)
     * - Order Discounts (SUM discount)
     * + Invoiced Tax (SUM tax)
     * = Commercial Invoice Sales (SUM total)
     * - Economic Returns (SUM sale_returns.refund_amount)
     * = Profit Revenue Basis / Net Commercial Sales
     * - Net COGS (Gross COGS from sale_items.cost_price snapshot - Restockable Returned COGS)
     * = Gross Profit
     * - Operating Expenses (posted expenses in period, excluding voided/capital/transfers/adjustments)
     * = Operational Net Profit
     */
    public function getProfitabilityReport(int $businessId, ?int $branchId, string $from, string $to, ?string $format = null)
    {
        // 1. Completed Sales in Date Range
        $salesQuery = Sale::where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }

        $sales = $salesQuery->get(['id', 'subtotal', 'discount', 'tax', 'total']);

        $grossGoodsSales = '0.00';
        $discounts = '0.00';
        $tax = '0.00';
        $commercialInvoiceSales = '0.00';

        foreach ($sales as $s) {
            $grossGoodsSales = bcadd($grossGoodsSales, (string) ($s->subtotal ?? '0.00'), 2);
            $discounts = bcadd($discounts, (string) ($s->discount ?? '0.00'), 2);
            $tax = bcadd($tax, (string) ($s->tax ?? '0.00'), 2);
            $commercialInvoiceSales = bcadd($commercialInvoiceSales, (string) ($s->total ?? '0.00'), 2);
        }

        // 2. Economic Returns in Date Range
        $returnsQuery = SaleReturn::where('business_id', $businessId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        if ($branchId) {
            $returnsQuery->where('branch_id', $branchId);
        }

        $economicReturns = (string) ($returnsQuery->sum('refund_amount') ?? '0.00');
        $economicReturns = bcadd($economicReturns, '0.00', 2);

        // 3. Profit Revenue Basis / Net Commercial Sales
        $netSales = bcsub($commercialInvoiceSales, $economicReturns, 2);
        if (bccomp($netSales, '0.00', 2) < 0) {
            $netSales = '0.00';
        }

        // 4. COGS Calculation from immutable snapshot
        $saleIds = $sales->pluck('id');
        $saleItems = SaleItem::whereIn('sale_id', $saleIds)->get(['id', 'sale_id', 'quantity', 'cost_price']);

        $grossCogs = '0.00';
        $uncostedItemsCount = 0;

        foreach ($saleItems as $item) {
            if ($item->cost_price === null) {
                $uncostedItemsCount++;
            } else {
                $lineCogs = bcmul((string) $item->quantity, (string) $item->cost_price, 2);
                $grossCogs = bcadd($grossCogs, $lineCogs, 2);
            }
        }

        // Restockable Returned COGS in period
        $returnIds = $returnsQuery->pluck('id');
        $returnedCogs = '0.00';

        if ($returnIds->isNotEmpty()) {
            $returnItems = DB::table('sale_return_items')
                ->join('sale_items', 'sale_return_items.sale_item_id', '=', 'sale_items.id')
                ->whereIn('sale_return_items.sale_return_id', $returnIds)
                ->where('sale_return_items.is_restockable', true)
                ->select('sale_return_items.quantity', 'sale_items.cost_price')
                ->get();

            foreach ($returnItems as $ri) {
                if ($ri->cost_price !== null) {
                    $reversedLine = bcmul((string) $ri->quantity, (string) $ri->cost_price, 2);
                    $returnedCogs = bcadd($returnedCogs, $reversedLine, 2);
                }
            }
        }

        $netCogs = bcsub($grossCogs, $returnedCogs, 2);
        if (bccomp($netCogs, '0.00', 2) < 0) {
            $netCogs = '0.00';
        }

        // 5. Gross Profit
        $grossProfit = bcsub($netSales, $netCogs, 2);

        // 6. Operating Expenses in Date Range
        $expensesQuery = Expense::where('business_id', $businessId)
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        if ($branchId) {
            $expensesQuery->where('branch_id', $branchId);
        }

        $operatingExpenses = (string) ($expensesQuery->sum('amount') ?? '0.00');
        $operatingExpenses = bcadd($operatingExpenses, '0.00', 2);

        // 7. Net Profit
        $netProfit = bcsub($grossProfit, $operatingExpenses, 2);

        // 8. Margins
        $grossMarginPercent = bccomp($netSales, '0.00', 2) > 0
            ? round(((float) $grossProfit / (float) $netSales) * 100, 2)
            : 0.00;

        $netMarginPercent = bccomp($netSales, '0.00', 2) > 0
            ? round(((float) $netProfit / (float) $netSales) * 100, 2)
            : 0.00;

        $isComplete = ($uncostedItemsCount === 0);

        if (strtolower($format ?? '') === 'csv') {
            $headers = ['Metric', 'Amount (PKR)'];
            $rows = [
                ['Gross Goods Sales', number_format((float) $grossGoodsSales, 2)],
                ['Discounts', number_format((float) $discounts, 2)],
                ['Invoiced Tax', number_format((float) $tax, 2)],
                ['Commercial Invoice Sales', number_format((float) $commercialInvoiceSales, 2)],
                ['Economic Returns', number_format((float) $economicReturns, 2)],
                ['Net Commercial Sales (Revenue Basis)', number_format((float) $netSales, 2)],
                ['Gross COGS', number_format((float) $grossCogs, 2)],
                ['Returned COGS (Restockable)', number_format((float) $returnedCogs, 2)],
                ['Net COGS', number_format((float) $netCogs, 2)],
                ['Gross Profit', number_format((float) $grossProfit, 2)],
                ['Gross Margin %', $grossMarginPercent . '%'],
                ['Operating Expenses', number_format((float) $operatingExpenses, 2)],
                ['Operational Net Profit', number_format((float) $netProfit, 2)],
                ['Net Margin %', $netMarginPercent . '%'],
            ];
            return $this->exportToCsv($headers, $rows, "profitability_statement_{$from}_to_{$to}.csv");
        }

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'gross_goods_sales' => (float) $grossGoodsSales,
            'discounts' => (float) $discounts,
            'tax' => (float) $tax,
            'commercial_invoice_sales' => (float) $commercialInvoiceSales,
            'economic_returns' => (float) $economicReturns,
            'net_sales' => (float) $netSales,
            'profit_revenue_basis' => (float) $netSales,
            'gross_cogs' => (float) $grossCogs,
            'returned_cogs' => (float) $returnedCogs,
            'net_cogs' => (float) $netCogs,
            'cogs_status' => $isComplete ? 'complete' : 'partial',
            'is_cogs_complete' => $isComplete,
            'uncosted_sales_count' => $uncostedItemsCount,
            'gross_profit' => (float) $grossProfit,
            'gross_margin_percent' => $grossMarginPercent,
            'operating_expenses' => (float) $operatingExpenses,
            'net_profit' => (float) $netProfit,
            'net_margin_percent' => $netMarginPercent,
            'tax_liability_mode' => 'OPERATIONAL_INVOICE_SURCHARGE',
            'accounting_basis' => 'Small-Retail Operational Cash & Inventory Basis (REQ-0027)',
        ];
    }

    /**
     * Sanitize cell value to prevent CSV Formula Injection in spreadsheet applications.
     */
    protected function sanitizeCsvCell($value)
    {
        if (is_string($value) && strlen($value) > 0) {
            $firstChar = $value[0];
            if ($firstChar === '=' || $firstChar === '@') {
                return "'" . $value;
            }
            if (($firstChar === '+' || $firstChar === '-') && !is_numeric($value)) {
                return "'" . $value;
            }
        }
        return $value;
    }

    /**
     * Export array data to streamed CSV response.
     */
    protected function exportToCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_map([$this, 'sanitizeCsvCell'], $headers));
            foreach ($rows as $row) {
                fputcsv($file, array_map([$this, 'sanitizeCsvCell'], $row));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
