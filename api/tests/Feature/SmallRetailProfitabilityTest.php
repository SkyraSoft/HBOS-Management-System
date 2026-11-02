<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmallRetailProfitabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $owner;
    protected User $manager;
    protected User $cashier;
    protected FinancialAccount $drawer;
    protected Product $product1;
    protected Product $product2;
    protected Customer $customer;
    protected Supplier $supplier;
    protected ExpenseCategory $expenseCategory;
    protected ReportService $reportService;
    protected DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Small Retail Biz']);
        $this->branch1 = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Store', 'is_primary' => true]);
        $this->branch2 = Branch::create(['business_id' => $this->business->id, 'name' => 'Second Store', 'is_primary' => false]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->manager = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->assignRole('Branch Manager');

        $this->cashier = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->assignRole('Salesperson');

        $this->drawer = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 10000.00,
            'balance' => 10000.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->product1 = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Rice 5kg',
            'cost_price' => 500.00,
            'selling_price' => 700.00,
            'stock' => 100,
            'unit' => 'bag',
        ]);

        $this->product2 = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Sugar Loose',
            'cost_price' => 120.00,
            'selling_price' => 160.00,
            'stock' => 500,
            'unit' => 'kg',
        ]);

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Retail Customer',
            'opening_balance' => 0.00,
        ]);

        $this->supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Wholesale Distributor',
        ]);

        $this->expenseCategory = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Store Utilities',
        ]);

        $this->reportService = app(ReportService::class);
        $this->dashboardService = app(DashboardService::class);
    }

    public function test_small_retail_profitability_statement_canonical_calculations(): void
    {
        $today = date('Y-m-d');

        // Sale 1: subtotal 1000, discount 100, tax 50 -> total 950
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-P6-001',
            'date' => $today,
            'subtotal' => 1000.00,
            'discount' => 100.00,
            'tax' => 50.00,
            'total' => 950.00,
            'paid_amount' => 950.00,
            'due_amount' => 0.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'unit_price' => 500.00,
            'cost_price' => 300.00, // Gross COGS = 2 * 300 = 600.00
            'discount' => 0.00,
            'total' => 1000.00,
            'returned_quantity' => 0,
        ]);

        // Return 1: refund 200.00, 1 restockable item returned (cost_price 300)
        $return = SaleReturn::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'return_number' => 'RET-P6-001',
            'refund_amount' => 200.00,
            'date' => $today,
        ]);

        SaleReturnItem::create([
            'sale_return_id' => $return->id,
            'sale_item_id' => $saleItem->id,
            'product_id' => $this->product1->id,
            'quantity' => 0.500, // half bag returned restockable
            'unit_price' => 500.00,
            'refund_amount' => 200.00,
            'is_restockable' => true, // Returned COGS = 0.5 * 300 = 150.00
            'return_reason' => 'Customer changed mind',
        ]);

        // Operating Expense: 50.00
        Expense::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'expense_category_id' => $this->expenseCategory->id,
            'category' => 'Utilities',
            'financial_account_id' => $this->drawer->id,
            'user_id' => $this->owner->id,
            'amount' => 50.00,
            'date' => $today,
            'description' => 'Light bulb repair',
            'status' => 'posted',
        ]);

        $report = $this->reportService->getProfitabilityReport($this->business->id, $this->branch1->id, $today, $today);

        // Revenue Basis:
        // gross_goods_sales: 1000.00
        // discounts: 100.00
        // tax: 50.00
        // commercial_invoice_sales: 950.00
        // economic_returns: 200.00
        // net_sales (profit_revenue_basis): 950.00 - 200.00 = 750.00
        $this->assertEquals(1000.00, $report['gross_goods_sales']);
        $this->assertEquals(100.00, $report['discounts']);
        $this->assertEquals(50.00, $report['tax']);
        $this->assertEquals(950.00, $report['commercial_invoice_sales']);
        $this->assertEquals(200.00, $report['economic_returns']);
        $this->assertEquals(750.00, $report['net_sales']);
        $this->assertEquals(750.00, $report['profit_revenue_basis']);

        // COGS Basis:
        // gross_cogs: 2 * 300.00 = 600.00
        // returned_cogs: 0.5 * 300.00 = 150.00
        // net_cogs: 600.00 - 150.00 = 450.00
        $this->assertEquals(600.00, $report['gross_cogs']);
        $this->assertEquals(150.00, $report['returned_cogs']);
        $this->assertEquals(450.00, $report['net_cogs']);

        // Profit Basis:
        // gross_profit = 750.00 - 450.00 = 300.00
        // operating_expenses = 50.00
        // net_profit = 300.00 - 50.00 = 250.00
        $this->assertEquals(300.00, $report['gross_profit']);
        $this->assertEquals(50.00, $report['operating_expenses']);
        $this->assertEquals(250.00, $report['net_profit']);

        // Margins:
        // gross_margin = 300.00 / 750.00 * 100 = 40.00%
        // net_margin = 250.00 / 750.00 * 100 = 33.33%
        $this->assertEquals(40.00, $report['gross_margin_percent']);
        $this->assertEquals(33.33, $report['net_margin_percent']);
    }

    public function test_non_operating_transactions_excluded_from_operating_pnl(): void
    {
        $today = date('Y-m-d');

        // Initial base sale: total 1000, cost 600 -> net profit = 400.00
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-P6-BASE',
            'date' => $today,
            'subtotal' => 1000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => 1000.00,
            'paid_amount' => 400.00,
            'due_amount' => 600.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'cost_price' => 600.00,
            'discount' => 0.00,
            'total' => 1000.00,
            'returned_quantity' => 0,
        ]);

        $initialReport = $this->reportService->getProfitabilityReport($this->business->id, $this->branch1->id, $today, $today);
        $this->assertEquals(400.00, $initialReport['net_profit']);

        // 1. Khata customer debt settlement (NOT new revenue)
        CustomerPayment::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->owner->id,
            'payment_number' => 'CP-P6-001',
            'financial_account_id' => $this->drawer->id,
            'amount' => 300.00,
            'date' => $today,
            'payment_method' => 'cash',
        ]);

        // 2. Inventory Purchase (NOT operating expense, capitalized in inventory)
        $purchase = Purchase::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-P6-001',
            'date' => $today,
            'total_amount' => 2000.00,
            'paid_amount' => 0.00,
            'status' => 'received',
        ]);

        // 3. Supplier debt payment (NOT operating expense)
        SupplierPayment::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->owner->id,
            'financial_account_id' => $this->drawer->id,
            'amount' => 500.00,
            'date' => $today,
            'payment_method' => 'cash',
        ]);

        // 4. Voided Expense (must be excluded)
        Expense::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'expense_category_id' => $this->expenseCategory->id,
            'category' => 'Utilities',
            'financial_account_id' => $this->drawer->id,
            'user_id' => $this->owner->id,
            'amount' => 250.00,
            'date' => $today,
            'description' => 'Accidental entry',
            'status' => 'voided',
        ]);

        // Verify report again: revenue and expenses remain unchanged
        $afterReport = $this->reportService->getProfitabilityReport($this->business->id, $this->branch1->id, $today, $today);
        $this->assertEquals(1000.00, $afterReport['net_sales']);
        $this->assertEquals(0.00, $afterReport['operating_expenses']);
        $this->assertEquals(400.00, $afterReport['net_profit']);
    }

    public function test_fractional_quantity_and_cost_snapshots_exact_decimal(): void
    {
        $today = date('Y-m-d');

        // Sale: 2.750 kg Sugar @ 160.00/kg = 440.00. Cost snapshot = 120.00 -> COGS = 330.00
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-FRAC-001',
            'date' => $today,
            'subtotal' => 440.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => 440.00,
            'paid_amount' => 440.00,
            'due_amount' => 0.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product2->id,
            'quantity' => 2.750,
            'unit_price' => 160.00,
            'cost_price' => 120.00,
            'discount' => 0.00,
            'total' => 440.00,
            'returned_quantity' => 0,
        ]);

        $report = $this->reportService->getProfitabilityReport($this->business->id, $this->branch1->id, $today, $today);

        $this->assertEquals(440.00, $report['net_sales']);
        $this->assertEquals(330.00, $report['gross_cogs']);
        $this->assertEquals(110.00, $report['gross_profit']);
        $this->assertEquals(110.00, $report['net_profit']);
    }

    public function test_damaged_non_restockable_returns_do_not_reverse_cogs(): void
    {
        $today = date('Y-m-d');

        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-DAMAGED-001',
            'date' => $today,
            'subtotal' => 500.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => 500.00,
            'paid_amount' => 500.00,
            'due_amount' => 0.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'cost_price' => 300.00,
            'discount' => 0.00,
            'total' => 500.00,
            'returned_quantity' => 1,
        ]);

        // Return with is_restockable = false (damaged goods kept/discarded)
        $return = SaleReturn::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'return_number' => 'RET-DAMAGED-001',
            'refund_amount' => 500.00,
            'date' => $today,
        ]);

        SaleReturnItem::create([
            'sale_return_id' => $return->id,
            'sale_item_id' => $item->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'refund_amount' => 500.00,
            'is_restockable' => false,
            'return_reason' => 'Damaged during delivery',
        ]);

        $report = $this->reportService->getProfitabilityReport($this->business->id, $this->branch1->id, $today, $today);

        // Economic return reverses sales revenue:
        $this->assertEquals(0.00, $report['net_sales']);
        // But non-restockable return does NOT reverse COGS (economic loss of inventory is realized):
        $this->assertEquals(300.00, $report['gross_cogs']);
        $this->assertEquals(0.00, $report['returned_cogs']);
        $this->assertEquals(300.00, $report['net_cogs']);
        $this->assertEquals(-300.00, $report['gross_profit']);
        $this->assertEquals(-300.00, $report['net_profit']);
    }

    public function test_zero_revenue_denominator_safe_margin(): void
    {
        $today = date('Y-m-d');
        $report = $this->reportService->getProfitabilityReport($this->business->id, $this->branch1->id, $today, $today);

        $this->assertEquals(0.00, $report['net_sales']);
        $this->assertEquals(0.00, $report['gross_margin_percent']);
        $this->assertEquals(0.00, $report['net_margin_percent']);
    }

    public function test_branch_filtering_and_all_stores_aggregation(): void
    {
        $today = date('Y-m-d');

        // Sale in Branch 1: total 1000, cost 600
        $sale1 = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-B1-001',
            'date' => $today,
            'subtotal' => 1000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => 1000.00,
            'paid_amount' => 1000.00,
            'due_amount' => 0.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        SaleItem::create([
            'sale_id' => $sale1->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'cost_price' => 600.00,
            'discount' => 0.00,
            'total' => 1000.00,
            'returned_quantity' => 0,
        ]);

        // Sale in Branch 2: total 2000, cost 1200
        $sale2 = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-B2-001',
            'date' => $today,
            'subtotal' => 2000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => 2000.00,
            'paid_amount' => 2000.00,
            'due_amount' => 0.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        SaleItem::create([
            'sale_id' => $sale2->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'unit_price' => 1000.00,
            'cost_price' => 600.00,
            'discount' => 0.00,
            'total' => 2000.00,
            'returned_quantity' => 0,
        ]);

        // Branch 1 report
        $b1Report = $this->reportService->getProfitabilityReport($this->business->id, $this->branch1->id, $today, $today);
        $this->assertEquals(1000.00, $b1Report['net_sales']);
        $this->assertEquals(400.00, $b1Report['net_profit']);

        // Branch 2 report
        $b2Report = $this->reportService->getProfitabilityReport($this->business->id, $this->branch2->id, $today, $today);
        $this->assertEquals(2000.00, $b2Report['net_sales']);
        $this->assertEquals(800.00, $b2Report['net_profit']);

        // All Stores (branchId = null)
        $allReport = $this->reportService->getProfitabilityReport($this->business->id, null, $today, $today);
        $this->assertEquals(3000.00, $allReport['net_sales']);
        $this->assertEquals(1200.00, $allReport['net_profit']);
    }

    public function test_api_roles_access_and_cashier_denied(): void
    {
        $today = date('Y-m-d');

        // Cashier cannot access profitability report -> 403 Forbidden
        $response = $this->actingAs($this->cashier, 'sanctum')
            ->getJson("/api/v1/reports/profitability?from={$today}&to={$today}");
        $response->assertStatus(403);

        // Branch Manager can access assigned branch
        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson("/api/v1/reports/profitability?branch_id={$this->branch1->id}&from={$today}&to={$today}");
        $response->assertStatus(200);

        // Business Owner can access all stores
        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/reports/profitability?from={$today}&to={$today}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'gross_goods_sales',
                'discounts',
                'tax',
                'commercial_invoice_sales',
                'economic_returns',
                'net_sales',
                'gross_cogs',
                'returned_cogs',
                'net_cogs',
                'gross_profit',
                'operating_expenses',
                'net_profit',
                'gross_margin_percent',
                'net_margin_percent',
            ]);
    }

    public function test_dashboard_report_net_profit_parity(): void
    {
        $today = date('Y-m-d');

        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-PARITY-001',
            'date' => $today,
            'subtotal' => 1500.00,
            'discount' => 100.00,
            'tax' => 0.00,
            'total' => 1400.00,
            'paid_amount' => 1400.00,
            'due_amount' => 0.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 1500.00,
            'cost_price' => 800.00,
            'discount' => 0.00,
            'total' => 1500.00,
            'returned_quantity' => 0,
        ]);

        Expense::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'expense_category_id' => $this->expenseCategory->id,
            'category' => 'Cleaning',
            'financial_account_id' => $this->drawer->id,
            'user_id' => $this->owner->id,
            'amount' => 120.00,
            'date' => $today,
            'description' => 'Cleaning',
            'status' => 'posted',
        ]);

        // Net Sales = 1400, Net COGS = 800, Gross Profit = 600, Operating Expenses = 120, Net Profit = 480.00
        $report = $this->reportService->getProfitabilityReport($this->business->id, $this->branch1->id, $today, $today);
        $dateRange = $this->dashboardService->resolveDateRange(['preset' => 'today', 'from' => $today, 'to' => $today]);
        $dashboard = $this->dashboardService->getOwnerDashboard($this->business->id, $this->branch1->id, $dateRange);

        $this->assertEquals(480.00, $report['net_profit']);
        $this->assertEquals(480.00, $dashboard['net_profit']['amount']);
        $this->assertEquals($report['net_profit'], $dashboard['net_profit']['amount']);
    }
}
