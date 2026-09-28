<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Phase8DashboardAndReportingTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;
    protected User $ownerA;
    protected User $managerA1;
    protected User $salespersonA1;
    protected User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Businesses & Branches
        $this->businessA = Business::create(['name' => 'Tenant A']);
        $this->businessB = Business::create(['name' => 'Tenant B']);

        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'HQ Branch A1', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A2', 'is_primary' => false]);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'HQ Branch B1', 'is_primary' => true]);

        // 2. Setup Spatie Roles & Permissions
        setPermissionsTeamId($this->businessA->id);
        $roleOwner = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $roleManager = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $roleSalesperson = Role::firstOrCreate(['name' => 'Salesperson', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);

        // 3. Create Users
        $this->ownerA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
        ]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole($roleOwner);

        $this->managerA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
        ]);
        $this->managerA1->businesses()->attach($this->businessA->id);
        $this->managerA1->assignRole($roleManager);

        $this->salespersonA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
        ]);
        $this->salespersonA1->businesses()->attach($this->businessA->id);
        $this->salespersonA1->assignRole($roleSalesperson);

        setPermissionsTeamId($this->businessB->id);
        $roleOwnerB = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessB->id]);
        $this->ownerB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB1->id,
        ]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole($roleOwnerB);
    }

    public function test_owner_dashboard_returns_role_aware_full_payload()
    {
        $response = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('role', 'Business Owner')
            ->assertJsonStructure([
                'role',
                'period',
                'scope',
                'sales' => ['gross_sales', 'return_adjustment', 'net_sales', 'transaction_count', 'average_order_value'],
                'cogs' => ['gross_cogs', 'returned_cogs', 'net_cogs', 'cogs_status'],
                'gross_profit',
                'operating_position',
                'expenses',
                'cash_and_bank',
                'period_cash_movement',
                'receivables',
                'payables',
                'inventory',
                'product_performance' => ['top_sellers', 'slow_movers'],
                'branch_comparison',
                'needs_attention',
                'recent_activity',
            ]);
    }

    public function test_manager_dashboard_is_strictly_branch_scoped_and_masks_central_finances()
    {
        $response = $this->actingAs($this->managerA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('role', 'Branch Manager')
            ->assertJsonPath('scope.branch_id', $this->branchA1->id)
            ->assertJsonMissingPath('payables')
            ->assertJsonMissingPath('branch_comparison')
            ->assertJsonMissingPath('cash_and_bank.bank_balance');
    }

    public function test_manager_requesting_all_branches_or_foreign_branch_is_rejected()
    {
        // Manager asking for "all" branches
        $response = $this->actingAs($this->managerA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?branch_id=all');

        $response->assertStatus(403);

        // Manager asking for another branch
        $response2 = $this->actingAs($this->managerA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?branch_id=' . $this->branchA2->id);

        $response2->assertStatus(403);
    }

    public function test_salesperson_dashboard_returns_minimal_payload_without_financials()
    {
        $response = $this->actingAs($this->salespersonA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('role', 'Salesperson')
            ->assertJsonStructure([
                'role',
                'period',
                'scope',
                'personal_sales' => ['gross_sales', 'return_adjustment', 'net_sales', 'transaction_count', 'customers_served'],
                'recent_sales',
            ])
            ->assertJsonMissingPath('gross_profit')
            ->assertJsonMissingPath('expenses')
            ->assertJsonMissingPath('cash_and_bank')
            ->assertJsonMissingPath('receivables')
            ->assertJsonMissingPath('payables')
            ->assertJsonMissingPath('needs_attention');
    }

    public function test_salesperson_attempting_to_access_reports_is_denied()
    {
        $response = $this->actingAs($this->salespersonA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/reports/sales');

        $response->assertStatus(403);
    }

    public function test_sales_and_cogs_calculation_uses_salereturn_refund_amount_and_immutable_cost_price()
    {
        // Setup Product with catalog cost price = 50.00, selling price = 100.00
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Widget A',
            'sku' => 'WID-A-01',
            'cost_price' => 50.00,
            'selling_price' => 100.00,
            'stock' => 10,
        ]);

        // Sale 1: 2 units sold at 100.00 each (Total 200.00), cost_price snapshot = 40.00 (discounted cost)
        $sale = Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-TEST-01',
            'date' => now()->toDateString(),
            'total' => 200.00,
            'paid_amount' => 200.00,
            'due_amount' => 0.00,
            'status' => 'completed',
        ]);

        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'cost_price' => 40.00, // Immutable sale-time cost snapshot!
            'discount' => 0.00,
            'total' => 200.00,
            'returned_quantity' => 1,
        ]);

        // SaleReturn: 1 unit returned, refund_amount = 100.00
        $saleReturn = SaleReturn::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'sale_id' => $sale->id,
            'user_id' => $this->ownerA->id,
            'return_number' => 'RET-TEST-01',
            'refund_amount' => 100.00,
        ]);

        SaleReturnItem::create([
            'sale_return_id' => $saleReturn->id,
            'sale_item_id' => $saleItem->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'refund_amount' => 100.00,
        ]);

        // Catalog price updated later to 80.00 - should NOT affect historical COGS calculation!
        $product->update(['cost_price' => 80.00]);

        $response = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $response->assertStatus(200);

        $salesData = $response->json('sales');
        $cogsData = $response->json('cogs');
        $profitData = $response->json('gross_profit');

        // Gross Sales = 200.00, Return Adjustment = 100.00, Net Sales = 100.00
        $this->assertEquals(200.00, $salesData['gross_sales']);
        $this->assertEquals(100.00, $salesData['return_adjustment']);
        $this->assertEquals(100.00, $salesData['net_sales']);

        // Gross COGS = 2 * 40.00 = 80.00, Returned COGS = 1 * 40.00 = 40.00, Net COGS = 40.00
        $this->assertEquals(80.00, $cogsData['gross_cogs']);
        $this->assertEquals(40.00, $cogsData['returned_cogs']);
        $this->assertEquals(40.00, $cogsData['net_cogs']);
        $this->assertEquals('complete', $cogsData['cogs_status']);

        // Gross Profit = 100.00 - 40.00 = 60.00
        $this->assertEquals(60.00, $profitData['amount']);
        $this->assertFalse($profitData['is_partial']);
    }

    public function test_zero_cost_sale_item_treated_as_valid_zero_cogs_snapshot()
    {
        // Sale item created with cost_price = 0.00 (promotional/zero-cost item)
        $sale = Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-ZERO-COST-01',
            'date' => now()->toDateString(),
            'total' => 150.00,
            'paid_amount' => 150.00,
            'due_amount' => 0.00,
            'status' => 'completed',
        ]);

        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Free Sample Item',
            'sku' => 'FREE-01',
            'cost_price' => 0.00,
            'selling_price' => 150.00,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 150.00,
            'cost_price' => 0.00, // Valid zero-cost snapshot!
            'discount' => 0.00,
            'total' => 150.00,
        ]);

        // Product catalog cost changes later - must NOT alter historical COGS!
        $product->update(['cost_price' => 45.00]);

        $response = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $response->assertStatus(200);

        $cogsData = $response->json('cogs');
        $profitData = $response->json('gross_profit');

        $this->assertEquals('complete', $cogsData['cogs_status']);
        $this->assertFalse($cogsData['is_partial']);
        $this->assertEquals(0.00, $cogsData['net_cogs']);
        $this->assertEquals(150.00, $profitData['amount']);
        $this->assertFalse($profitData['is_partial']);
    }

    public function test_expense_aggregation_uses_posted_status_and_ignores_voided_expenses()
    {
        $cat = ExpenseCategory::create(['business_id' => $this->businessA->id, 'name' => 'Utilities']);

        Expense::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'category_id' => $cat->id,
            'category' => 'Utilities',
            'amount' => 500.00,
            'date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        Expense::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'category_id' => $cat->id,
            'category' => 'Utilities',
            'amount' => 300.00,
            'date' => now()->toDateString(),
            'status' => 'voided', // Voided expense must contribute 0.00!
        ]);

        $response = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $response->assertStatus(200);
        $this->assertEquals(500.00, $response->json('expenses.total'));
    }

    public function test_custom_date_range_validation_rejects_invalid_boundaries_and_future_dates()
    {
        // 1. from > to
        $response = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=custom&from=2026-09-20&to=2026-09-10');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);

        // 2. Future date
        $futureDate = now()->addDays(5)->toDateString();
        $response2 = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson("/api/v1/dashboard?preset=custom&from=2026-09-01&to={$futureDate}");

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    public function test_needs_attention_service_evaluates_deterministic_alerts()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Low Item',
            'sku' => 'LOW-01',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
        ]);

        // BranchInventory quantity_on_hand <= minimum_stock (5 <= 10)
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 5,
            'minimum_stock' => 10,
        ]);

        $response = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200);

        $alerts = $response->json('needs_attention');
        $this->assertNotEmpty($alerts);

        $lowStockAlert = collect($alerts)->firstWhere('type', 'low_stock');
        $this->assertNotNull($lowStockAlert);
        $this->assertEquals('Low Stock Items Detected', $lowStockAlert['title']);
    }

    public function test_csv_export_returns_streamed_attachment()
    {
        $response = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->get('/api/v1/reports/sales?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="sales_report_', $response->headers->get('content-disposition'));
    }

    public function test_multi_business_user_switching_active_business_header_contains_only_active_tenant_data()
    {
        // Setup dual-member user in both Business A and Business B
        setPermissionsTeamId($this->businessB->id);
        $roleOwnerB = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessB->id]);
        $dualUser = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
        ]);
        $dualUser->businesses()->attach([$this->businessA->id, $this->businessB->id]);
        setPermissionsTeamId($this->businessA->id);
        $roleOwnerA = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $dualUser->assignRole($roleOwnerA);
        setPermissionsTeamId($this->businessB->id);
        $dualUser->assignRole($roleOwnerB);

        // Create Sale 1000 in Business A
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $dualUser->id,
            'invoice_number' => 'INV-BIZ-A',
            'date' => now()->toDateString(),
            'total' => 1000.00,
            'paid_amount' => 1000.00,
            'status' => 'completed',
        ]);

        // Create Sale 2000 in Business B
        Sale::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB1->id,
            'user_id' => $dualUser->id,
            'invoice_number' => 'INV-BIZ-B',
            'date' => now()->toDateString(),
            'total' => 2000.00,
            'paid_amount' => 2000.00,
            'status' => 'completed',
        ]);

        // Request 1: Business A
        $resA = $this->actingAs($dualUser->fresh())
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $resA->assertStatus(200)
            ->assertJsonPath('sales.gross_sales', 1000);

        // Request 2: Switch to Business B
        $resB = $this->actingAs($dualUser->fresh())
            ->withHeaders(['X-Business-Id' => $this->businessB->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $resB->assertStatus(200)
            ->assertJsonPath('sales.gross_sales', 2000);

        // Request 3: Switch back to Business A
        $resA2 = $this->actingAs($dualUser->fresh())
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $resA2->assertStatus(200)
            ->assertJsonPath('sales.gross_sales', 1000);
    }
}
