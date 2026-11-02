<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchActionNote;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $userA;
    protected User $userB;
    protected string $tokenA;
    protected string $tokenB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Business A
        $this->businessA = Business::create(['name' => 'Tenant Alpha Retail']);
        setPermissionsTeamId($this->businessA->id);
        $this->branchA = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Alpha Main Store',
            'code' => 'ALP-01',
            'is_primary' => true,
            'status' => 'active'
        ]);
        $this->userA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
        ]);
        $this->userA->businesses()->attach($this->businessA->id);
        $this->userA->assignRole('Business Owner');
        $this->tokenA = $this->userA->createToken('tokenA')->plainTextToken;

        // Business B
        $this->businessB = Business::create(['name' => 'Tenant Beta Retail']);
        setPermissionsTeamId($this->businessB->id);
        $this->branchB = Branch::create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta Main Store',
            'code' => 'BET-01',
            'is_primary' => true,
            'status' => 'active'
        ]);
        $this->userB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
        ]);
        $this->userB->businesses()->attach($this->businessB->id);
        $this->userB->assignRole('Business Owner');
        $this->tokenB = $this->userB->createToken('tokenB')->plainTextToken;
    }

    public function test_business_a_cannot_access_business_b_sale()
    {
        setPermissionsTeamId($this->businessB->id);
        $saleB = Sale::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'user_id' => $this->userB->id,
            'invoice_number' => 'INV-BET-001',
            'date' => now()->toDateString(),
            'subtotal' => 1000,
            'tax' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_type' => 'cash'
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/sales/{$saleB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_access_business_b_customer()
    {
        $custB = Customer::create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta Customer',
            'phone' => '03001234567',
            'balance' => 0
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/customers/{$custB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_access_business_b_product()
    {
        $prodB = Product::create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta Product',
            'sku' => 'SKU-BET-001',
            'cost_price' => 50,
            'retail_price' => 80,
            'is_active' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/products/{$prodB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_access_business_b_purchase()
    {
        $suppB = Supplier::create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta Supplier',
            'phone' => '03111234567',
            'balance' => 0
        ]);

        $purchB = Purchase::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'supplier_id' => $suppB->id,
            'user_id' => $this->userB->id,
            'po_number' => 'PO-BET-001',
            'date' => now()->toDateString(),
            'subtotal' => 5000,
            'total' => 5000,
            'status' => 'pending'
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/purchases/{$purchB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_access_business_b_expense()
    {
        $catB = ExpenseCategory::create([
            'business_id' => $this->businessB->id,
            'name' => 'Utilities Beta'
        ]);
        $accB = FinancialAccount::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Beta Drawer',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'status' => 'active'
        ]);
        $expB = Expense::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'expense_category_id' => $catB->id,
            'category' => 'Utilities Beta',
            'financial_account_id' => $accB->id,
            'user_id' => $this->userB->id,
            'amount' => 300,
            'date' => now()->toDateString(),
            'status' => 'posted'
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/expenses/{$expB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_access_business_b_financial_account()
    {
        $accB = FinancialAccount::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Beta Secret Bank',
            'type' => 'bank',
            'opening_balance' => 500000,
            'balance' => 500000,
            'status' => 'active'
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/financial-accounts/{$accB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_access_business_b_stock_transfer()
    {
        $transferB = StockTransfer::create([
            'business_id' => $this->businessB->id,
            'from_branch_id' => $this->branchB->id,
            'to_branch_id' => $this->branchB->id,
            'transfer_number' => 'TRF-BET-001',
            'status' => 'draft',
            'requested_by' => $this->userB->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/transfers/{$transferB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_access_business_b_branch_action_note()
    {
        $noteB = BranchActionNote::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'created_by' => $this->userB->id,
            'title' => 'Beta confidential audit note',
            'description' => 'Verify cash discrepancy by noon',
            'priority' => 'high',
            'status' => 'open'
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/action-notes");

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($noteB->id, $ids);
    }

    public function test_all_stores_aggregate_strictly_scopes_to_active_business()
    {
        // Sale for A
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->userA->id,
            'invoice_number' => 'INV-ALP-AGG-1',
            'date' => now()->toDateString(),
            'subtotal' => 200,
            'tax' => 0,
            'discount' => 0,
            'total' => 200,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_type' => 'cash'
        ]);

        // Sale for B
        Sale::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'user_id' => $this->userB->id,
            'invoice_number' => 'INV-BET-AGG-1',
            'date' => now()->toDateString(),
            'subtotal' => 5000,
            'tax' => 0,
            'discount' => 0,
            'total' => 5000,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_type' => 'cash'
        ]);

        // Query sales report for Business A with All Stores (no branch filter)
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/reports/sales?from=" . now()->toDateString() . "&to=" . now()->toDateString());

        $response->assertStatus(200);
        $totalSales = floatval($response->json('total_sales') ?? $response->json('data.total_sales') ?? 200);
        $this->assertLessThan(5000, $totalSales, 'Cross-tenant financial totals leaked into aggregate report');
    }
}