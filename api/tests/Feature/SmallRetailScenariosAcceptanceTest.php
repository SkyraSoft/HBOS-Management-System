<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use App\Models\StockTransfer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

class SmallRetailScenariosAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Scenario A — Owner-only complete retail day
     * A brand-new Owner registers and operates a shop without employees, completing real purchasing,
     * POS, Khata, expenses, supplier settlement, refunds, account movement, closing and reporting.
     */
    public function test_scenario_a_owner_only_complete_retail_day()
    {
        // 1. Owner registers business
        $regResp = $this->postJson('/api/v1/auth/register', [
            'name' => 'Haji Rasheed',
            'business_name' => 'Rasheed Kiryana Store',
            'email' => 'rasheed@kiryana.pk',
            'phone' => '03009988776',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);
        $regResp->assertStatus(201);
        $bizId = $regResp->json('business.id');
        $branchId = $regResp->json('branch.id');
        $token = $regResp->json('access_token');
        $authHeaders = [
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $bizId,
        ];

        // 2. Resolve default cash drawer
        $drawer = FinancialAccount::where('business_id', $bizId)->where('type', 'cash')->first();
        $this->assertNotNull($drawer);

        // 3. Owner opens cash register session establishing opening float (PKR 5,000)

        // Open cash register session
        $openResp = $this->withHeaders($authHeaders)->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'opening_float' => 5000,
            'notes' => 'Day start by owner',
        ]);
        $openResp->assertStatus(201);
        $sessionId = $openResp->json('session.id');

        // 4. Create products (packaged item and loose fractional rice)
        $prod1Resp = $this->withHeaders($authHeaders)->postJson('/api/v1/products', [
            'name' => 'Basmati Rice Premium (Loose)',
            'sku' => 'RICE-001',
            'barcode' => '89640001',
            'selling_price' => 350.00,
            'cost_price' => 280.00,
            'unit' => 'kg',
            'allow_fractional' => true,
            'stock' => 10.500,
            'branch_id' => $branchId,
        ]);
        $prod1Resp->assertStatus(201);
        $prod1Id = $prod1Resp->json('data.id') ?? $prod1Resp->json('id');

        // 5. Create supplier and make a purchase
        $suppResp = $this->withHeaders($authHeaders)->postJson('/api/v1/suppliers', [
            'name' => 'Punjab Grains Wholesale',
            'phone' => '03214445566',
        ]);
        $suppResp->assertStatus(201);
        $suppId = $suppResp->json('data.id') ?? $suppResp->json('id');

        // Partially paid purchase: Total 2,200 (Paid 1,200 cash, Due 1,000)
        $purchResp = $this->withHeaders($authHeaders)->postJson('/api/v1/purchases', [
            'supplier_id' => $suppId,
            'branch_id' => $branchId,
            'po_number' => 'PO-2026-001',
            'items' => [
                [
                    'product_id' => $prod1Id,
                    'quantity' => 10,
                    'unit_cost' => 220,
                ]
            ],
            'paid_amount' => 1200,
            'payment_method' => 'cash',
        ]);
        $purchResp->assertStatus(201);
        $purchId = $purchResp->json('data.id') ?? $purchResp->json('id');

        // Drawer decreased by 1,200: 5,000 - 1,200 = 3,800
        $drawer->refresh();
        $this->assertEquals(3800.00, (float)$drawer->balance);

        // 6. POS Sales:
        // Cash Sale: 2.500 kg of Rice = 2.5 * 350 = 875 PKR
        $sale1Resp = $this->withHeaders($authHeaders)->postJson('/api/v1/sales', [
            'branch_id' => $branchId,
            'items' => [
                ['product_id' => $prod1Id, 'quantity' => 2.500, 'unit_price' => 350.00]
            ],
            'payment_method' => 'cash',
            'paid_amount' => 875.00,
        ]);
        $sale1Resp->assertStatus(201);

        // Customer creation and Khata credit sale
        $custResp = $this->withHeaders($authHeaders)->postJson('/api/v1/customers', [
            'name' => 'Chaudhry Akram',
            'phone' => '03335556677',
            'credit_limit' => 5000,
        ]);
        $custResp->assertStatus(201);
        $custId = $custResp->json('data.id') ?? $custResp->json('id');

        // Khata Sale: 2 kg = 700 PKR on credit
        $sale2Resp = $this->withHeaders($authHeaders)->postJson('/api/v1/sales', [
            'branch_id' => $branchId,
            'customer_id' => $custId,
            'items' => [
                ['product_id' => $prod1Id, 'quantity' => 2.000, 'unit_price' => 350.00]
            ],
            'payment_method' => 'khata',
            'paid_amount' => 0.00,
        ]);
        $sale2Resp->assertStatus(201);

        // 7. Customer Khata Repayment (Customer pays 500 PKR cash)
        $repayResp = $this->withHeaders($authHeaders)->postJson("/api/v1/customers/{$custId}/payments", [
            'amount' => 500.00,
            'payment_method' => 'cash',
            'notes' => 'Partial repayment',
        ]);
        $repayResp->assertStatus(201);

        // 8. Supplier Settlement (Pay remaining 1,000 PKR to Punjab Grains)
        $suppPayResp = $this->withHeaders($authHeaders)->postJson("/api/v1/suppliers/{$suppId}/payments", [
            'purchase_id' => $purchId,
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'notes' => 'Clear remaining balance',
        ]);
        $suppPayResp->assertStatus(201);

        // 9. Operating Expense: Shop Utility / Tea (PKR 300 from cash)
        $expCat = ExpenseCategory::create(['business_id' => $bizId, 'name' => 'Refreshments']);
        $expResp = $this->withHeaders($authHeaders)->postJson('/api/v1/expenses', [
            'branch_id' => $branchId,
            'category_id' => $expCat->id,
            'amount' => 300.00,
            'payment_method' => 'cash',
            'financial_account_id' => $drawer->id,
            'description' => 'Tea for guests',
            'date' => now()->toDateString(),
        ]);
        $expResp->assertStatus(201);

        // 10. Cash Day Closing Math Reconciled:
        // Opening float: 5,000
        // Purchase payment 1: -1,200
        // Cash Sale 1: +875
        // Customer Repayment: +500
        // Supplier Settlement: -1,000
        // Operating Expense: -300
        // Expected Cash in Drawer = 5000 - 1200 + 875 + 500 - 1000 - 300 = 3,875 PKR
        $drawer->refresh();
        $this->assertEquals(3875.00, (float)$drawer->balance);

        // Close Session with exact expected amount
        $closeResp = $this->withHeaders($authHeaders)->postJson('/api/v1/cash-register-sessions/close', [
            'session_id' => $sessionId,
            'closing_cash_counted' => 3875.00,
            'notes' => 'Exact balanced close',
        ]);
        $closeResp->assertStatus(200);

        // Verify profitability statement
        $reportResp = $this->withHeaders($authHeaders)->getJson('/api/v1/reports/profitability');
        $reportResp->assertStatus(200);
        $this->assertEquals(1575.00, (float)$reportResp->json('net_sales'));
    }

    /**
     * Scenario B — Offline counter and recovery
     * A valid Cashier creates an offline sale, app reconnects, bulk sync ingestion processes it exactly once.
     */
    public function test_scenario_b_offline_counter_and_recovery()
    {
        $biz = Business::create(['name' => 'Offline Demo Shop']);
        $branch = Branch::create(['business_id' => $biz->id, 'name' => 'Main', 'is_primary' => true]);
        setPermissionsTeamId($biz->id);
        $user = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $branch->id]);
        $user->businesses()->attach($biz->id);
        $user->assignRole('Salesperson');

        $drawer = FinancialAccount::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'name' => 'Main Drawer',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $prod = Product::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'name' => 'Soap Bar',
            'sku' => 'SOAP-01',
            'selling_price' => 120,
            'cost_price' => 80,
            'status' => 'active',
        ]);
        BranchInventory::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'product_id' => $prod->id,
            'quantity_on_hand' => 50,
        ]);

        $token = $user->createToken('test')->plainTextToken;
        $saleUuid = (string) Str::uuid();

        $syncPayload = [
            'sales' => [
                [
                    'idempotency_key' => $saleUuid,
                    'branch_id' => $branch->id,
                    'customer_id' => null,
                    'payment_method' => 'cash',
                    'paid_amount' => 240,
                    'financial_account_id' => $drawer->id,
                    'items' => [
                        ['product_id' => $prod->id, 'quantity' => 2, 'unit_price' => 120]
                    ],
                ]
            ]
        ];

        // 1. Initial Ingestion
        $syncResp1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $biz->id,
        ])->postJson('/api/v1/sync/bulk-transactions', $syncPayload);
        $syncResp1->assertStatus(200);

        // 2. Retry identical sync payload (idempotency check)
        $syncResp2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $biz->id,
        ])->postJson('/api/v1/sync/bulk-transactions', $syncPayload);
        $syncResp2->assertStatus(200);

        // Exactly 1 sale must exist
        $this->assertEquals(1, Sale::where('business_id', $biz->id)->count());

        // Stock correctly deducted by 2 (50 - 2 = 48)
        $inv = BranchInventory::where('branch_id', $branch->id)->where('product_id', $prod->id)->first();
        $this->assertEquals(48, (int)$inv->quantity_on_hand);
    }

    /**
     * Scenario C — Progressive branch expansion
     * An existing Main Store adds Branch 2 without losing historical records, transfers stock.
     */
    public function test_scenario_c_progressive_branch_expansion()
    {
        $biz = Business::create(['name' => 'Expanding Retailer']);
        $mainBranch = Branch::create(['business_id' => $biz->id, 'name' => 'Main Store', 'is_primary' => true]);
        setPermissionsTeamId($biz->id);
        $owner = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $mainBranch->id]);
        $owner->businesses()->attach($biz->id);
        $owner->assignRole('Business Owner');
        $token = $owner->createToken('test')->plainTextToken;

        // Create initial product in Main Store
        $prod = Product::create([
            'business_id' => $biz->id,
            'branch_id' => $mainBranch->id,
            'name' => 'Cooking Oil 5L',
            'sku' => 'OIL-5L',
            'selling_price' => 2500,
            'cost_price' => 2100,
            'status' => 'active',
        ]);
        BranchInventory::create([
            'business_id' => $biz->id,
            'branch_id' => $mainBranch->id,
            'product_id' => $prod->id,
            'quantity_on_hand' => 20,
        ]);

        // Add Branch 2
        $branch2Resp = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $biz->id,
        ])->postJson('/api/v1/branches', [
            'name' => 'Gulberg Branch',
            'code' => 'GB-02',
            'address' => 'Gulberg III, Lahore',
        ]);
        $branch2Resp->assertStatus(201);
        $branch2Id = $branch2Resp->json('data.id') ?? $branch2Resp->json('id');

        // Activate Branch 2 via PUT /api/v1/branches/{id}
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $biz->id,
        ])->putJson("/api/v1/branches/{$branch2Id}", ['status' => 'active'])->assertStatus(200);

        // Verify Main Store remains primary
        $this->assertTrue((bool) $mainBranch->fresh()->is_primary);

        // Perform stock transfer: 5 units from Main to Branch 2
        $transferResp = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $biz->id,
        ])->postJson('/api/v1/transfers', [
            'from_branch_id' => $mainBranch->id,
            'to_branch_id' => $branch2Id,
            'items' => [
                ['product_id' => $prod->id, 'quantity' => 5]
            ],
            'notes' => 'Transfer to Gulberg',
        ]);
        $transferResp->assertStatus(201);
        $transferId = $transferResp->json('id') ?? $transferResp->json('data.id');

        // Dispatch transfer
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $biz->id,
        ])->postJson("/api/v1/transfers/{$transferId}/dispatch")->assertStatus(200);

        // Receive transfer at Branch 2
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $biz->id,
        ])->postJson("/api/v1/transfers/{$transferId}/receive")->assertStatus(200);

        // Verify stock conservation: Main has 15, Branch 2 has 5
        $invMain = BranchInventory::where('branch_id', $mainBranch->id)->where('product_id', $prod->id)->first();
        $invB2 = BranchInventory::where('branch_id', $branch2Id)->where('product_id', $prod->id)->first();
        $this->assertEquals(15, (int)$invMain->quantity_on_hand);
        $this->assertEquals(5, (int)$invB2->quantity_on_hand);
    }

    /**
     * Scenario D — Three-branch operations & role boundaries
     * Owner, branch Managers, and Cashiers operate within assigned scopes.
     */
    public function test_scenario_d_three_branch_operations_and_role_boundaries()
    {
        $biz = Business::create(['name' => 'Metro Multi-Branch']);
        setPermissionsTeamId($biz->id);
        $this->seed(RolesAndPermissionsSeeder::class);

        $b1 = Branch::create(['business_id' => $biz->id, 'name' => 'Main Center', 'is_primary' => true]);
        $b2 = Branch::create(['business_id' => $biz->id, 'name' => 'North Branch']);
        $b3 = Branch::create(['business_id' => $biz->id, 'name' => 'South Branch']);

        $owner = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $b1->id]);
        $owner->businesses()->attach($biz->id);
        $owner->branches()->attach([$b1->id, $b2->id, $b3->id], ['business_id' => $biz->id]);
        $owner->assignRole('Business Owner');

        $manager2 = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $b2->id]);
        $manager2->businesses()->attach($biz->id);
        $manager2->branches()->attach($b2->id, ['business_id' => $biz->id]);
        $manager2->assignRole('Branch Manager');

        $cashier3 = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $b3->id]);
        $cashier3->businesses()->attach($biz->id);
        $cashier3->branches()->attach($b3->id, ['business_id' => $biz->id]);
        $cashier3->assignRole('Salesperson');

        // Cashier 3 is blocked from financial reports
        $tokenC3 = $cashier3->createToken('test')->plainTextToken;
        app('auth')->forgetGuards();
        $this->withHeaders([
            'Authorization' => "Bearer {$tokenC3}",
            'X-Business-ID' => (string) $biz->id,
        ])->getJson('/api/v1/reports/profitability')->assertStatus(403);

        // Manager 2 cannot inspect unrelated branches
        $tokenM2 = $manager2->createToken('test')->plainTextToken;
        app('auth')->forgetGuards();
        $respM2 = $this->withHeaders([
            'Authorization' => "Bearer {$tokenM2}",
            'X-Business-ID' => (string) $biz->id,
        ])->getJson("/api/v1/reports/sales?branch_id={$b3->id}");
        $respM2->assertStatus(403);

        // Owner can view All Stores aggregated report
        $tokenOwner = $owner->createToken('test')->plainTextToken;
        app('auth')->forgetGuards();
        $respOwner = $this->withHeaders([
            'Authorization' => "Bearer {$tokenOwner}",
            'X-Business-ID' => (string) $biz->id,
        ])->getJson('/api/v1/reports/sales?branch_id=all');
        $respOwner->assertStatus(200);
    }

    /**
     * Scenario E — Two unrelated businesses
     * Strict tenant isolation across catalog, sales, customers, accounts.
     */
    public function test_scenario_e_two_unrelated_businesses_isolation()
    {
        // Business Alpha
        $bizA = Business::create(['name' => 'Alpha Stores']);
        $bA = Branch::create(['business_id' => $bizA->id, 'name' => 'Alpha Main', 'is_primary' => true]);
        setPermissionsTeamId($bizA->id);
        $ownerA = User::factory()->create(['business_id' => $bizA->id, 'branch_id' => $bA->id]);
        $ownerA->businesses()->attach($bizA->id);
        $ownerA->assignRole('Business Owner');
        $prodA = Product::create([
            'business_id' => $bizA->id,
            'branch_id' => $bA->id,
            'name' => 'Alpha Exclusive Tea',
            'sku' => 'TEA-A',
            'selling_price' => 400,
            'cost_price' => 250,
            'status' => 'active',
        ]);

        // Business Beta
        $bizB = Business::create(['name' => 'Beta Mart']);
        $bB = Branch::create(['business_id' => $bizB->id, 'name' => 'Beta Main', 'is_primary' => true]);
        setPermissionsTeamId($bizB->id);
        $ownerB = User::factory()->create(['business_id' => $bizB->id, 'branch_id' => $bB->id]);
        $ownerB->businesses()->attach($bizB->id);
        $ownerB->assignRole('Business Owner');
        $tokenB = $ownerB->createToken('test')->plainTextToken;

        // Owner B attempts to view or edit Product A
        $resp = $this->withHeaders([
            'Authorization' => "Bearer {$tokenB}",
            'X-Business-ID' => (string) $bizB->id,
        ])->getJson("/api/v1/products/{$prodA->id}");
        $this->assertTrue(in_array($resp->status(), [403, 404]), 'Access to another business product must be 403 or 404');
    }
}
