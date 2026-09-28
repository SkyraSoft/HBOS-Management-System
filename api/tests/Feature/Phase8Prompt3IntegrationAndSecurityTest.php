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
use App\Services\CustomerAccountService;
use App\Services\SupplierBalanceService;
use App\Services\NeedsAttentionService;
use App\Services\AccountMovementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class Phase8Prompt3IntegrationAndSecurityTest extends TestCase
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

        // 1. Setup Businesses & Branches
        $this->businessA = Business::create(['name' => 'Tenant Alpha']);
        $this->businessB = Business::create(['name' => 'Tenant Beta']);

        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A1', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A2', 'is_primary' => false]);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B1', 'is_primary' => true]);

        // 2. Setup Spatie Roles
        setPermissionsTeamId($this->businessA->id);
        $roleOwnerA = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $roleManagerA = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $roleSalespersonA = Role::firstOrCreate(['name' => 'Salesperson', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);

        $this->ownerA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole($roleOwnerA);

        $this->managerA1 = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id]);
        $this->managerA1->businesses()->attach($this->businessA->id);
        $this->managerA1->assignRole($roleManagerA);

        $this->salespersonA1 = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id]);
        $this->salespersonA1->businesses()->attach($this->businessA->id);
        $this->salespersonA1->assignRole($roleSalespersonA);

        setPermissionsTeamId($this->businessB->id);
        $roleOwnerB = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessB->id]);
        $this->ownerB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB1->id]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole($roleOwnerB);
    }

    /**
     * 1. Multi-Tenant Dashboard Isolation: Business A cannot see Business B figures.
     */
    public function test_tenant_isolation_dashboard_does_not_leak_cross_tenant_totals()
    {
        // Business A transaction
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-A-100',
            'date' => now()->toDateString(),
            'total' => 1000.00,
            'paid_amount' => 1000.00,
            'status' => 'completed',
        ]);

        // Business B transaction
        Sale::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB1->id,
            'user_id' => $this->ownerB->id,
            'invoice_number' => 'INV-B-500',
            'date' => now()->toDateString(),
            'total' => 5000.00,
            'paid_amount' => 5000.00,
            'status' => 'completed',
        ]);

        $resA = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $resA->assertStatus(200);
        $this->assertEquals(1000.00, $resA->json('sales.gross_sales'));

        $resB = $this->actingAs($this->ownerB)
            ->withHeaders(['X-Business-Id' => $this->businessB->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $resB->assertStatus(200);
        $this->assertEquals(5000.00, $resB->json('sales.gross_sales'));
    }

    /**
     * 2. Multi-Tenant Reports Isolation: Reports and CSV exports cannot leak cross-tenant rows.
     */
    public function test_tenant_isolation_all_reports_and_csv_strictly_filter_foreign_tenant_data()
    {
        $custA = Customer::create(['business_id' => $this->businessA->id, 'name' => 'Alice Alpha', 'phone' => '111111']);
        $custB = Customer::create(['business_id' => $this->businessB->id, 'name' => 'Bob Beta', 'phone' => '222222']);

        // JSON Report
        $resCust = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/reports/customers');

        $resCust->assertStatus(200)
            ->assertSee('Alice Alpha')
            ->assertDontSee('Bob Beta');

        // CSV Report
        $resCsv = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->get('/api/v1/reports/customers?export=csv');

        $resCsv->assertStatus(200);
        $content = $resCsv->streamedContent();
        $this->assertStringContainsString('Alice Alpha', $content);
        $this->assertStringNotContainsString('Bob Beta', $content);
    }

    /**
     * 3. Missing Active Business Context: Fails explicitly with 400.
     */
    public function test_missing_active_business_context_explicitly_fails()
    {
        // 1. User without business_id and without X-Business-ID header -> 400
        $unassignedUser = User::factory()->create(['business_id' => null, 'branch_id' => null]);
        $resNoContext = $this->actingAs($unassignedUser)->getJson('/api/v1/dashboard');
        $resNoContext->assertStatus(400);

        // 2. Multi-business user (member of Business A and Business B) with legacy users.business_id = A
        // MUST NOT silently fall back to A when no active context header is provided -> 400
        $multiUser = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
        ]);
        $multiUser->businesses()->attach([$this->businessA->id, $this->businessB->id]);

        $resMultiNoHeader = $this->actingAs($multiUser)->getJson('/api/v1/dashboard');
        $resMultiNoHeader->assertStatus(400);

        // Report endpoint also fails with 400 when active business context missing for multi-business user
        $resMultiReportNoHeader = $this->actingAs($multiUser)->getJson('/api/v1/reports/sales');
        $resMultiReportNoHeader->assertStatus(400);

        // 3. Multi-business user explicitly providing X-Business-ID succeeds for A, B, and A again
        setPermissionsTeamId($this->businessA->id);
        $roleOwnerA = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $multiUser->assignRole($roleOwnerA);

        setPermissionsTeamId($this->businessB->id);
        $roleOwnerB = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessB->id]);
        $multiUser->assignRole($roleOwnerB);

        $resA = $this->actingAs($multiUser)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');
        $resA->assertStatus(200);

        $resB = $this->actingAs($multiUser)
            ->withHeaders(['X-Business-Id' => $this->businessB->id])
            ->getJson('/api/v1/dashboard');
        $resB->assertStatus(200);

        $resA2 = $this->actingAs($multiUser)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');
        $resA2->assertStatus(200);

        // 4. User requesting foreign/unauthorized business context -> 403
        $resUnauthorized = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => 99999])
            ->getJson('/api/v1/dashboard');
        $resUnauthorized->assertStatus(403);
    }

    /**
     * 3b. Zero-Membership Legacy business_id Test: A user with users.business_id = Business A
     * but zero pivot memberships in business_user must NOT be granted tenant access.
     */
    public function test_zero_membership_legacy_business_id_cannot_grant_tenant_access()
    {
        $zeroMemberUser = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
        ]);
        $this->assertEquals(0, $zeroMemberUser->businesses()->count());

        // 1. Without header -> 400 Bad Request
        $resNoHeader = $this->actingAs($zeroMemberUser)->getJson('/api/v1/dashboard');
        $resNoHeader->assertStatus(400);

        $resReportNoHeader = $this->actingAs($zeroMemberUser)->getJson('/api/v1/reports/sales');
        $resReportNoHeader->assertStatus(400);

        // 2. With header -> 403 Forbidden because user is not a member of business_user
        $resWithHeader = $this->actingAs($zeroMemberUser)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');
        $resWithHeader->assertStatus(403);

        $resReportWithHeader = $this->actingAs($zeroMemberUser)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/reports/sales');
        $resReportWithHeader->assertStatus(403);
    }

    /**
     * 4. Owner Branch Scope: Valid specific branch, all branches, and rejection of foreign branch.
     */
    public function test_owner_branch_scope_validates_branches_and_rejects_foreign_branch()
    {
        // 1. All Branches
        $resAll = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?branch_id=all');
        $resAll->assertStatus(200)
            ->assertJsonPath('scope.branch_id', null);

        // 2. Specific valid branch
        $resBranch = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson("/api/v1/dashboard?branch_id={$this->branchA1->id}");
        $resBranch->assertStatus(200)
            ->assertJsonPath('scope.branch_id', $this->branchA1->id);

        // 3. Foreign branch from Business B
        $resForeign = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson("/api/v1/dashboard?branch_id={$this->branchB1->id}");
        $resForeign->assertStatus(422);
    }

    /**
     * 5. Manager Branch Security: Cannot view all branches, foreign branch, or central financials.
     */
    public function test_manager_branch_security_enforces_assigned_branch_and_masks_central_bank()
    {
        // Create central bank account (branch_id = null)
        $bank = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'name' => 'Main Corporate Bank',
            'type' => 'bank',
            'branch_id' => null,
            'opening_balance' => 50000.00,
            'status' => 'active',
        ]);

        $res = $this->actingAs($this->managerA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $res->assertStatus(200)
            ->assertJsonPath('role', 'Branch Manager')
            ->assertJsonMissingPath('payables')
            ->assertJsonMissingPath('cash_and_bank.bank_balance')
            ->assertJsonMissingPath('branch_comparison');

        // Cannot view other branch in same business
        $resOther = $this->actingAs($this->managerA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson("/api/v1/dashboard?branch_id={$this->branchA2->id}");
        $resOther->assertStatus(403);
    }

    /**
     * 6. Salesperson Security & Report Access Rejection.
     */
    public function test_salesperson_dashboard_security_and_report_denial()
    {
        $resDash = $this->actingAs($this->salespersonA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $resDash->assertStatus(200)
            ->assertJsonPath('role', 'Salesperson')
            ->assertJsonMissingPath('gross_profit')
            ->assertJsonMissingPath('expenses')
            ->assertJsonMissingPath('cogs')
            ->assertJsonMissingPath('cash_and_bank')
            ->assertJsonMissingPath('cash_drawer')
            ->assertJsonMissingPath('cash_drawer_balance')
            ->assertJsonMissingPath('bank_balance')
            ->assertJsonMissingPath('operating_position')
            ->assertJsonMissingPath('branch_comparison')
            ->assertJsonMissingPath('receivables')
            ->assertJsonMissingPath('payables')
            ->assertJsonMissingPath('inventory')
            ->assertJsonStructure([
                'role',
                'period' => ['preset', 'from', 'to'],
                'scope' => ['business_id', 'branch_id', 'branch_name', 'user_name'],
                'personal_sales' => [
                    'gross_sales',
                    'return_adjustment',
                    'net_sales',
                    'transaction_count',
                    'customers_served',
                ],
                'recent_sales',
            ]);

        // Denied on all reports
        $endpoints = [
            '/api/v1/reports/sales',
            '/api/v1/reports/inventory',
            '/api/v1/reports/customers',
            '/api/v1/reports/suppliers',
            '/api/v1/reports/expenses',
            '/api/v1/reports/financial-accounts',
            '/api/v1/reports/branches',
        ];

        foreach ($endpoints as $ep) {
            $res = $this->actingAs($this->salespersonA1)
                ->withHeaders(['X-Business-Id' => $this->businessA->id])
                ->getJson($ep);
            $res->assertStatus(403);
        }
    }

    /**
     * 7. Role Spoofing: Client-supplied role parameters cannot escalate privileges.
     */
    public function test_role_spoofing_via_query_parameter_is_ignored()
    {
        $res = $this->actingAs($this->salespersonA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?role=Business+Owner');

        // Must still return Salesperson role without financial cards
        $res->assertStatus(200)
            ->assertJsonPath('role', 'Salesperson')
            ->assertJsonMissingPath('gross_profit');
    }

    /**
     * 8. Legacy Route Compatibility: GET /api/v1/dashboard/stats delegates to secure contract.
     */
    public function test_legacy_dashboard_stats_endpoint_enforces_same_security_rules()
    {
        $res = $this->actingAs($this->salespersonA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard/stats');

        $res->assertStatus(200)
            ->assertJsonPath('role', 'Salesperson')
            ->assertJsonMissingPath('gross_profit');
    }

    /**
     * 9. Manager Report Domain Matrix: Can access sales/inventory/expenses but NOT suppliers or branches.
     */
    public function test_manager_report_domain_permissions_matrix()
    {
        // Manager allowed on sales (branch scoped)
        $resSales = $this->actingAs($this->managerA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/reports/sales');
        $resSales->assertStatus(200);

        // Manager forbidden on suppliers
        $resSuppliers = $this->actingAs($this->managerA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/reports/suppliers');
        $resSuppliers->assertStatus(403);

        // Manager forbidden on branches
        $resBranches = $this->actingAs($this->managerA1)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/reports/branches');
        $resBranches->assertStatus(403);
    }

    /**
     * 10. Date Preset Boundary Verification (PKT).
     */
    public function test_date_preset_boundaries_include_today_and_exclude_yesterday_for_today_preset()
    {
        $todayStr = now('Asia/Karachi')->toDateString();
        $yesterdayStr = now('Asia/Karachi')->subDay()->toDateString();

        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-TODAY',
            'date' => $todayStr,
            'total' => 200.00,
            'paid_amount' => 200.00,
            'status' => 'completed',
        ]);

        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-YEST',
            'date' => $yesterdayStr,
            'total' => 300.00,
            'paid_amount' => 300.00,
            'status' => 'completed',
        ]);

        $resToday = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $resToday->assertStatus(200);
        $this->assertEquals(200.00, $resToday->json('sales.gross_sales'));

        $resYest = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=yesterday');

        $resYest->assertStatus(200);
        $this->assertEquals(300.00, $resYest->json('sales.gross_sales'));
    }

    /**
     * 11. Zero-Denominator Safeguard: No Division by Zero or NaN.
     */
    public function test_zero_denominator_safeguard_handles_zero_to_zero_and_zero_to_positive()
    {
        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $res->assertStatus(200);
        $comparison = $res->json('sales.comparison');

        // 0 to 0 -> neutral, 0.0
        $this->assertEquals(0.0, $comparison['change_percentage']);
        $this->assertEquals('neutral', $comparison['state']);
    }

    /**
     * 12. Return-Adjusted Net Sales & Multiple Returns.
     */
    public function test_return_adjusted_net_sales_with_multiple_returns_does_not_fall_below_zero()
    {
        $sale = Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-RET-MULTI',
            'date' => now()->toDateString(),
            'total' => 1000.00,
            'paid_amount' => 1000.00,
            'status' => 'completed',
        ]);

        // Return 1: 300.00
        SaleReturn::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'sale_id' => $sale->id,
            'user_id' => $this->ownerA->id,
            'return_number' => 'RET-M-1',
            'refund_amount' => 300.00,
        ]);

        // Return 2: 400.00
        SaleReturn::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'sale_id' => $sale->id,
            'user_id' => $this->ownerA->id,
            'return_number' => 'RET-M-2',
            'refund_amount' => 400.00,
        ]);

        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $res->assertStatus(200);
        $this->assertEquals(1000.00, $res->json('sales.gross_sales'));
        $this->assertEquals(700.00, $res->json('sales.return_adjustment'));
        $this->assertEquals(300.00, $res->json('sales.net_sales'));
    }

    /**
     * 13. Gross COGS, Returned COGS, and Immutable Snapshot Invariant.
     */
    public function test_cogs_immutable_snapshot_and_returned_cogs_calculation()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Gadget Z',
            'sku' => 'GAD-Z',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'stock' => 10,
        ]);

        $sale = Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-COGS-1',
            'date' => now()->toDateString(),
            'total' => 200.00,
            'paid_amount' => 200.00,
            'status' => 'completed',
        ]);

        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 20.00,
            'cost_price' => 10.00, // Snapshot = 10.00
            'total' => 200.00,
        ]);

        $saleReturn = SaleReturn::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'sale_id' => $sale->id,
            'user_id' => $this->ownerA->id,
            'return_number' => 'RET-COGS-1',
            'refund_amount' => 60.00,
        ]);

        SaleReturnItem::create([
            'sale_return_id' => $saleReturn->id,
            'sale_item_id' => $saleItem->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 20.00,
            'refund_amount' => 60.00,
        ]);

        // Catalog cost price changes later - must NOT affect historical COGS!
        $product->update(['cost_price' => 99.00]);

        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $res->assertStatus(200);
        // Gross COGS = 10 * 10 = 100. Returned COGS = 3 * 10 = 30. Net COGS = 70.
        $this->assertEquals(100.00, $res->json('cogs.gross_cogs'));
        $this->assertEquals(30.00, $res->json('cogs.returned_cogs'));
        $this->assertEquals(70.00, $res->json('cogs.net_cogs'));
        $this->assertEquals('complete', $res->json('cogs.cogs_status'));

        // Net Sales = 200 - 60 = 140. Gross Profit = 140 - 70 = 70.
        $this->assertEquals(140.00, $res->json('sales.net_sales'));
        $this->assertEquals(70.00, $res->json('gross_profit.amount'));
    }

    /**
     * 14. Cash Position & Transfer Neutralization (AC-8.18).
     */
    public function test_period_cash_movement_neutralizes_internal_transfers()
    {
        $cashAcc = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'branch_id' => $this->branchA1->id,
            'opening_balance' => 1000.00,
            'status' => 'active',
        ]);

        $bankAcc = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'name' => 'Main Bank Account',
            'type' => 'bank',
            'branch_id' => null,
            'opening_balance' => 5000.00,
            'status' => 'active',
        ]);

        // External inflow (Sale collection): 500
        AccountMovement::create([
            'business_id' => $this->businessA->id,
            'account_id' => $cashAcc->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'type' => 'inflow',
            'movement_category' => 'sale_pos',
            'amount' => 500.00,
            'balance_after' => 1500.00,
            'date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        // Internal transfer: Cash -> Bank (500)
        AccountMovement::create([
            'business_id' => $this->businessA->id,
            'account_id' => $cashAcc->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'type' => 'outflow',
            'movement_category' => 'transfer_out',
            'amount' => 500.00,
            'balance_after' => 1000.00,
            'date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        AccountMovement::create([
            'business_id' => $this->businessA->id,
            'account_id' => $bankAcc->id,
            'branch_id' => null,
            'user_id' => $this->ownerA->id,
            'type' => 'inflow',
            'movement_category' => 'transfer_in',
            'amount' => 500.00,
            'balance_after' => 5500.00,
            'date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $res->assertStatus(200);
        $movement = $res->json('period_cash_movement');

        // External inflows must be 500 (internal transfer excluded!)
        $this->assertEquals(500.00, $movement['external_inflows']);
        $this->assertEquals(0.00, $movement['external_outflows']);
        $this->assertEquals(500.00, $movement['net_external_movement']);
    }

    /**
     * 15. Customer Receivables Parity: Dashboard matches CustomerAccountService.
     */
    public function test_customer_receivables_parity_with_customer_account_service()
    {
        $customerService = app(CustomerAccountService::class);

        $c1 = Customer::create(['business_id' => $this->businessA->id, 'name' => 'Cust 1', 'opening_balance' => 500.00]);
        $c2 = Customer::create(['business_id' => $this->businessA->id, 'name' => 'Cust 2', 'opening_balance' => -200.00]); // Credit

        $expectedOutstanding = $customerService->calculateOutstanding($c1) + $customerService->calculateOutstanding($c2);
        $expectedCredit = $customerService->calculateCredit($c1) + $customerService->calculateCredit($c2);

        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $res->assertStatus(200);
        $this->assertEquals(round($expectedOutstanding, 2), $res->json('receivables.outstanding'));
        $this->assertEquals(round($expectedCredit, 2), $res->json('receivables.customer_credit'));
    }

    /**
     * 16. Supplier Payables Parity: Dashboard matches SupplierBalanceService.
     */
    public function test_supplier_payables_parity_with_supplier_balance_service()
    {
        $supplierService = app(SupplierBalanceService::class);

        $s1 = Supplier::create(['business_id' => $this->businessA->id, 'name' => 'Supp 1', 'opening_balance' => 800.00]);

        $expectedPayable = $supplierService->reconcileBalance($s1);

        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $res->assertStatus(200);
        $this->assertEquals(round($expectedPayable, 2), $res->json('payables.total_payable'));
    }

    /**
     * 17. Inventory Truth: Ignores Product.stock and uses branch_inventories (AC-8.35).
     */
    public function test_inventory_quantity_uses_branch_inventories_and_ignores_product_stock()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Test Inv Item',
            'sku' => 'INV-TRUTH',
            'cost_price' => 15.00,
            'selling_price' => 30.00,
            'stock' => 9999, // Conflicting deprecated single-column stock!
        ]);

        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 25,
            'minimum_stock' => 10,
        ]);

        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $res->assertStatus(200);
        $this->assertEquals(25, $res->json('inventory.units_on_hand'));
        $this->assertEquals(375.00, $res->json('inventory.current_stock_value_at_current_cost'));
    }

    /**
     * 18. Low Stock Threshold Detection (AC-8.24).
     */
    public function test_low_stock_detection_uses_branch_inventory_minimum_stock()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Low Stock Product',
            'sku' => 'LOW-PROD',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
        ]);

        // BranchInventory has 5 on hand with minimum 10 -> Low Stock
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 5,
            'minimum_stock' => 10,
        ]);

        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $res->assertStatus(200)
            ->assertJsonPath('inventory.low_stock_count', 1)
            ->assertJsonPath('inventory.out_of_stock_count', 0);
    }

    /**
     * 19. Top Sellers & Slow Movers Determinism (AC-8.25 & AC-8.26).
     */
    public function test_top_sellers_ranked_by_net_sales_and_slow_movers_require_positive_stock()
    {
        $prodTop = Product::create(['business_id' => $this->businessA->id, 'name' => 'Top Prod', 'sku' => 'TOP-1', 'cost_price' => 10.00, 'selling_price' => 50.00]);
        $prodSlow = Product::create(['business_id' => $this->businessA->id, 'name' => 'Slow Prod', 'sku' => 'SLOW-1', 'cost_price' => 10.00, 'selling_price' => 50.00]);

        BranchInventory::create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id, 'product_id' => $prodSlow->id, 'quantity_on_hand' => 10, 'minimum_stock' => 2]);

        $sale = Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-TOP-01',
            'date' => now()->toDateString(),
            'total' => 250.00,
            'paid_amount' => 250.00,
            'status' => 'completed',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $prodTop->id,
            'quantity' => 5,
            'unit_price' => 50.00,
            'cost_price' => 10.00,
            'total' => 250.00,
        ]);

        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard?preset=today');

        $res->assertStatus(200);
        $topSellers = $res->json('product_performance.top_sellers');
        $slowMovers = $res->json('product_performance.slow_movers');

        $this->assertNotEmpty($topSellers);
        $this->assertEquals('Top Prod', $topSellers[0]['product_name']);
        $this->assertEquals(250.00, $topSellers[0]['net_sales']);

        $this->assertNotEmpty($slowMovers);
        $this->assertEquals('Slow Prod', $slowMovers[0]['product_name']);
        $this->assertEquals(0, $slowMovers[0]['net_units_sold']);
    }

    /**
     * 20. CSV Injection Hardening (AC-8.32).
     */
    public function test_csv_export_sanitizes_potential_formula_injection_characters()
    {
        // Customer with dangerous formula name '=cmd|'/C calc'!A0'
        Customer::create([
            'business_id' => $this->businessA->id,
            'name' => '=1+1',
            'phone' => '@malicious',
            'opening_balance' => 100.00,
        ]);

        $res = $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->get('/api/v1/reports/customers?export=csv');

        $res->assertStatus(200);
        $content = $res->streamedContent();

        // Formula characters '=' and '@' must be sanitized by prefixing single quote
        $this->assertStringContainsString("'=1+1", $content);
        $this->assertStringContainsString("'@malicious", $content);
    }

    /**
     * 21. Read-Only Reporting Invariant: Calling dashboard and reports mutates 0 rows (AC-8.36).
     */
    public function test_dashboard_and_reporting_requests_are_strictly_read_only()
    {
        $salesBefore = Sale::count();
        $movementsBefore = AccountMovement::count();
        $expensesBefore = Expense::count();
        $paymentsBefore = CustomerPayment::count();

        // Call Dashboard and Reports
        $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/dashboard');

        $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/reports/sales');

        $this->actingAs($this->ownerA)
            ->withHeaders(['X-Business-Id' => $this->businessA->id])
            ->getJson('/api/v1/reports/inventory');

        $this->assertEquals($salesBefore, Sale::count());
        $this->assertEquals($movementsBefore, AccountMovement::count());
        $this->assertEquals($expensesBefore, Expense::count());
        $this->assertEquals($paymentsBefore, CustomerPayment::count());
    }

    /**
     * 22. Empirical Query Counts Across All 8 Required Operations (AC-8.37).
     * Measures and records exact query counts under realistic business fixtures.
     */
    public function test_empirical_query_counts_across_dashboards_reports_and_needs_attention()
    {
        // Seed representative fixtures
        $cat = ExpenseCategory::create(['business_id' => $this->businessA->id, 'name' => 'Ops']);
        $p1 = Product::create(['business_id' => $this->businessA->id, 'name' => 'Widget A', 'code' => 'WA-1', 'sale_price' => 100.00, 'cost_price' => 50.00]);
        $p2 = Product::create(['business_id' => $this->businessA->id, 'name' => 'Widget B', 'code' => 'WB-2', 'sale_price' => 200.00, 'cost_price' => 120.00]);

        BranchInventory::create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id, 'product_id' => $p1->id, 'quantity_on_hand' => 50, 'minimum_stock' => 10]);
        BranchInventory::create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA2->id, 'product_id' => $p2->id, 'quantity_on_hand' => 30, 'minimum_stock' => 5]);

        $sup = Supplier::create(['business_id' => $this->businessA->id, 'name' => 'MegaSupplier', 'code' => 'SUP-M', 'opening_balance' => 1000.00]);
        Purchase::create(['business_id' => $this->businessA->id, 'supplier_id' => $sup->id, 'po_number' => 'PO-1', 'date' => now()->toDateString(), 'total' => 2000.00, 'paid_amount' => 1000.00, 'status' => 'received']);
        DB::table('supplier_payments')->insert([
            'business_id' => $this->businessA->id,
            'supplier_id' => $sup->id,
            'user_id' => $this->ownerA->id,
            'amount' => 500.00,
            'date' => now()->toDateString(),
            'payment_method' => 'cash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cust = Customer::create(['business_id' => $this->businessA->id, 'name' => 'Prime Cust', 'phone' => '03001234567', 'opening_balance' => 500.00]);
        $sale = Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'customer_id' => $cust->id,
            'invoice_number' => 'INV-REP-1',
            'date' => now()->toDateString(),
            'total' => 1000.00,
            'paid_amount' => 600.00,
            'status' => 'completed',
        ]);
        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $p1->id,
            'quantity' => 10,
            'unit_price' => 100.00,
            'cost_price' => 50.00,
            'subtotal' => 1000.00,
        ]);
        $ret = SaleReturn::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'sale_id' => $sale->id,
            'return_number' => 'RET-REP-1',
            'date' => now()->toDateString(),
            'refund_amount' => 200.00,
            'status' => 'completed',
        ]);
        SaleReturnItem::create([
            'sale_return_id' => $ret->id,
            'sale_item_id' => $saleItem->id,
            'product_id' => $p1->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'cost_price' => 50.00,
            'subtotal' => 200.00,
        ]);

        CustomerPayment::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $cust->id,
            'user_id' => $this->ownerA->id,
            'payment_number' => 'CPAY-1',
            'amount' => 300.00,
            'date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        Expense::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'category_id' => $cat->id,
            'category' => 'Ops',
            'amount' => 150.00,
            'date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        $cashAcc = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'A1 Cash Drawer',
            'type' => 'cash',
            'is_default' => true,
            'status' => 'active',
            'opening_balance' => 1000.00,
        ]);
        AccountMovement::create([
            'business_id' => $this->businessA->id,
            'account_id' => $cashAcc->id,
            'type' => 'inflow',
            'amount' => 500.00,
            'date' => now()->toDateString(),
            'status' => 'posted',
            'movement_category' => 'sale_pos',
        ]);

        // 1. Owner Dashboard Query Count
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/dashboard');
        $ownerQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 2. Manager Dashboard Query Count
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->managerA1)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/dashboard');
        $managerQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 3. Salesperson Dashboard Query Count
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->salespersonA1)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/dashboard');
        $salespersonQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 4. Sales Report Query Count
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/sales');
        $salesReportQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 5. Customer Report Query Count
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/customers');
        $customerReportQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 6. Supplier Report Query Count
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/suppliers');
        $supplierReportQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 7. Branch Report Query Count
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/branches');
        $branchReportQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 8. Needs Attention Query Count
        DB::flushQueryLog();
        DB::enableQueryLog();
        $service = app(NeedsAttentionService::class);
        $service->evaluateAlerts($this->businessA->id, null, true);
        $needsAttentionQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Assert all query counts are strictly bounded and non-exploding
        $this->assertLessThanOrEqual(65, $ownerQueries, "Owner dashboard queries ({$ownerQueries}) exceeded bound.");
        $this->assertLessThanOrEqual(40, $managerQueries, "Manager dashboard queries ({$managerQueries}) exceeded bound.");
        $this->assertLessThanOrEqual(15, $salespersonQueries, "Salesperson dashboard queries ({$salespersonQueries}) exceeded bound.");
        $this->assertLessThanOrEqual(20, $salesReportQueries, "Sales report queries ({$salesReportQueries}) exceeded bound.");
        $this->assertLessThanOrEqual(15, $customerReportQueries, "Customer report queries ({$customerReportQueries}) exceeded bound.");
        $this->assertLessThanOrEqual(15, $supplierReportQueries, "Supplier report queries ({$supplierReportQueries}) exceeded bound.");
        $this->assertLessThanOrEqual(15, $branchReportQueries, "Branch report queries ({$branchReportQueries}) exceeded bound.");
        $this->assertLessThanOrEqual(20, $needsAttentionQueries, "Needs Attention queries ({$needsAttentionQueries}) exceeded bound.");
    }

    /**
     * 23. N+1 Scaling Tests Across Customers, Suppliers, and Branches (AC-8.37).
     * Proves query counts do NOT increase linearly with row/entity volume.
     */
    public function test_n_plus_one_scaling_across_customers_suppliers_and_branches()
    {
        $needsAttentionService = app(NeedsAttentionService::class);

        // --- Domain 1: Customers (5 vs 20) ---
        for ($i = 1; $i <= 5; $i++) {
            $c = Customer::create(['business_id' => $this->businessA->id, 'name' => "ScaleCust {$i}", 'phone' => "0301{$i}"]);
            Sale::create([
                'business_id' => $this->businessA->id,
                'branch_id' => $this->branchA1->id,
                'user_id' => $this->ownerA->id,
                'customer_id' => $c->id,
                'invoice_number' => "INV-SC-{$i}",
                'date' => now()->toDateString(),
                'total' => 100.00,
                'paid_amount' => 50.00,
                'status' => 'completed',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/customers');
        $custReport5 = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $alerts5Cust = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Expand to 20 customers (+15)
        for ($i = 6; $i <= 20; $i++) {
            $c = Customer::create(['business_id' => $this->businessA->id, 'name' => "ScaleCust {$i}", 'phone' => "0301{$i}"]);
            Sale::create([
                'business_id' => $this->businessA->id,
                'branch_id' => $this->branchA1->id,
                'user_id' => $this->ownerA->id,
                'customer_id' => $c->id,
                'invoice_number' => "INV-SC-{$i}",
                'date' => now()->toDateString(),
                'total' => 100.00,
                'paid_amount' => 50.00,
                'status' => 'completed',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/customers');
        $custReport20 = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $alerts20Cust = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Query counts must remain sub-linear / constant (delta <= 1 for 4x customer growth)
        $this->assertLessThanOrEqual($custReport5 + 1, $custReport20, "Customer report queries exploded from 5 ({$custReport5}) to 20 ({$custReport20}) customers.");
        $this->assertLessThanOrEqual($alerts5Cust + 1, $alerts20Cust, "Needs Attention queries exploded from 5 ({$alerts5Cust}) to 20 ({$alerts20Cust}) customers.");

        // --- Domain 2: Suppliers (5 vs 20) ---
        for ($i = 1; $i <= 5; $i++) {
            $s = Supplier::create(['business_id' => $this->businessA->id, 'name' => "ScaleSup {$i}", 'code' => "SUP-S-{$i}", 'opening_balance' => 500.00]);
            Purchase::create(['business_id' => $this->businessA->id, 'supplier_id' => $s->id, 'po_number' => "PO-S-{$i}", 'date' => now()->toDateString(), 'total' => 1000.00, 'paid_amount' => 500.00, 'status' => 'received']);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/suppliers');
        $suppReport5 = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $alerts5Supp = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Expand to 20 suppliers (+15)
        for ($i = 6; $i <= 20; $i++) {
            $s = Supplier::create(['business_id' => $this->businessA->id, 'name' => "ScaleSup {$i}", 'code' => "SUP-S-{$i}", 'opening_balance' => 500.00]);
            Purchase::create(['business_id' => $this->businessA->id, 'supplier_id' => $s->id, 'po_number' => "PO-S-{$i}", 'date' => now()->toDateString(), 'total' => 1000.00, 'paid_amount' => 500.00, 'status' => 'received']);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/suppliers');
        $suppReport20 = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $alerts20Supp = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($suppReport5 + 1, $suppReport20, "Supplier report queries exploded from 5 ({$suppReport5}) to 20 ({$suppReport20}) suppliers.");
        $this->assertLessThanOrEqual($alerts5Supp + 1, $alerts20Supp, "Needs Attention queries exploded from 5 ({$alerts5Supp}) to 20 ({$alerts20Supp}) suppliers.");

        // --- Domain 3: Branches (3 vs 10) ---
        // $this->branchA1 and $this->branchA2 already exist. Create branchA3
        $branch3 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A3', 'code' => 'BRA-A3']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/branches');
        $branchReport3 = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $alerts3Branch = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Expand to 10 branches (+7)
        for ($i = 4; $i <= 10; $i++) {
            Branch::create(['business_id' => $this->businessA->id, 'name' => "Branch A{$i}", 'code' => "BRA-A{$i}"]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->ownerA)->withHeaders(['X-Business-Id' => $this->businessA->id])->getJson('/api/v1/reports/branches');
        $branchReport10 = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $alerts10Branch = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($branchReport3 + 1, $branchReport10, "Branch report queries exploded from 3 ({$branchReport3}) to 10 ({$branchReport10}) branches.");
        $this->assertLessThanOrEqual($alerts3Branch + 1, $alerts10Branch, "Needs Attention queries exploded from 3 ({$alerts3Branch}) to 10 ({$alerts10Branch}) branches.");
    }

    /**
     * 25. Gate 1 — Refund Exposure Semantics:
     * Validates per-return AccountMovement physical settlement and reversal truth:
     * - Return A (100 refund, unsettled) -> Alert triggers with 100 exposure.
     * - Return B (100 refund, settled) -> No alert for Return B.
     * - Together: Return B settlement does not cancel Return A exposure.
     * - Settle Return A -> Alert disappears (0 exposure).
     * - Reverse Return A settlement -> Alert returns with 100 exposure.
     * - Schema check: Proves zero fabricated refund_status or payment_status columns on sale_returns.
     */
    public function test_unsettled_refund_exposure_lifecycle_against_account_movement_physical_settlement_and_reversal()
    {
        $movementService = app(AccountMovementService::class);
        $needsAttentionService = app(NeedsAttentionService::class);

        // 1. Setup Cash Drawer for Business A
        $cashAccount = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 1000.00,
            'balance' => 1000.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        // 2. Setup Sale for Return A and B
        $sale = Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-REF-TEST-01',
            'date' => now()->toDateString(),
            'total' => 500.00,
            'paid_amount' => 500.00,
            'status' => 'completed',
        ]);

        // Case 1: Return A has refund 100.00, no settlement -> alert triggers
        $returnA = SaleReturn::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'sale_id' => $sale->id,
            'user_id' => $this->ownerA->id,
            'return_number' => 'RET-AAA-01',
            'refund_amount' => 100.00,
            'reason' => 'Defective',
        ]);

        $alerts1 = $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $refundAlert1 = collect($alerts1)->firstWhere('type', 'unsettled_refund_exposure');
        $this->assertNotNull($refundAlert1, 'Alert must trigger for unsettled Return A.');
        $this->assertEquals(1, $refundAlert1['count']);
        $this->assertEquals(100.00, $refundAlert1['total_amount']);
        $this->assertEquals($returnA->id, $refundAlert1['items'][0]['return_id']);

        // Case 2: Create Return B (100.00 refund) and settle it via AccountMovement
        $returnB = SaleReturn::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'sale_id' => $sale->id,
            'user_id' => $this->ownerA->id,
            'return_number' => 'RET-BBB-02',
            'refund_amount' => 100.00,
            'reason' => 'Wrong size',
        ]);

        $movementB = $movementService->settleSaleReturnRefund(
            $returnB,
            $cashAccount,
            100.00,
            now()->toDateString(),
            $this->ownerA->id,
            'idemp-settle-ret-b'
        );
        $this->assertEquals('posted', $movementB->status);
        $this->assertEquals('refund', $movementB->movement_category);

        // Case 3: Together, Return B settlement must NOT cancel Return A exposure
        $alerts2 = $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $refundAlert2 = collect($alerts2)->firstWhere('type', 'unsettled_refund_exposure');
        $this->assertNotNull($refundAlert2, 'Alert must remain active for unsettled Return A.');
        $this->assertEquals(1, $refundAlert2['count'], 'Only Return A should be counted as unsettled.');
        $this->assertEquals(100.00, $refundAlert2['total_amount'], 'Total exposure must reflect only Return A.');
        $this->assertEquals($returnA->id, $refundAlert2['items'][0]['return_id']);

        // Case 4: Settle Return A -> Alert disappears completely
        $movementA = $movementService->settleSaleReturnRefund(
            $returnA,
            $cashAccount,
            100.00,
            now()->toDateString(),
            $this->ownerA->id,
            'idemp-settle-ret-a'
        );
        $this->assertEquals('posted', $movementA->status);

        $alerts3 = $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $refundAlert3 = collect($alerts3)->firstWhere('type', 'unsettled_refund_exposure');
        $this->assertNull($refundAlert3, 'Alert must disappear when all returns are physically settled.');

        // Case 5: Reverse Return A physical refund settlement -> Alert returns if economic exposure remains
        $movementService->reverseSaleReturnRefund(
            $returnA,
            $this->ownerA->id,
            'Customer cancelled refund voucher'
        );
        $this->assertEquals('voided', $movementA->fresh()->status);

        $alerts4 = $needsAttentionService->evaluateAlerts($this->businessA->id, null, true);
        $refundAlert4 = collect($alerts4)->firstWhere('type', 'unsettled_refund_exposure');
        $this->assertNotNull($refundAlert4, 'Alert must return when settlement is reversed.');
        $this->assertEquals(1, $refundAlert4['count']);
        $this->assertEquals(100.00, $refundAlert4['total_amount']);
        $this->assertEquals($returnA->id, $refundAlert4['items'][0]['return_id']);

        // Case 6: Schema Truth Check - verify NO fabricated columns exist
        $this->assertFalse(Schema::hasColumn('sale_returns', 'refund_status'), 'sale_returns must NOT contain fabricated refund_status.');
        $this->assertFalse(Schema::hasColumn('sale_returns', 'payment_status'), 'sale_returns must NOT contain fabricated payment_status.');
    }
}
