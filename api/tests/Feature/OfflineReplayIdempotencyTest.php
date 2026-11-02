<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Sale;
use Database\Seeders\RolesAndPermissionsSeeder;

class OfflineReplayIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $cashierA;
    protected User $cashierB;
    protected Product $productA;
    protected FinancialAccount $cashDrawer;
    protected ExpenseCategory $expenseCat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Business A
        $this->businessA = Business::create(['name' => 'Metro Retail A']);
        setPermissionsTeamId($this->businessA->id);

        $this->branchA = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Branch Main A',
            'code' => 'BMA-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->cashierA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $this->cashierA->businesses()->attach($this->businessA->id);
        $this->cashierA->branches()->attach($this->branchA->id, ['business_id' => $this->businessA->id]);
        $this->cashierA->assignRole('Salesperson');

        $this->cashDrawer = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'status' => 'active',
            'is_default' => true,
            'current_balance' => 5000.00,
        ]);
        app(\App\Services\AccountMovementService::class)->postInflow($this->cashDrawer, [
            'amount' => 5000.00,
            'movement_category' => 'capital_in',
            'description' => 'Opening drawer balance',
        ]);

        $this->expenseCat = ExpenseCategory::create([
            'business_id' => $this->businessA->id,
            'name' => 'General Operations',
        ]);

        $this->productA = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Basmati Rice 1kg',
            'cost_price' => 150.00,
            'selling_price' => 200.00,
            'wholesale_price' => 180.00,
            'allow_fractional' => false,
            'stock' => 5.000,
            'is_active' => true,
        ]);

        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => '5.000',
        ]);

        // Business B
        $this->businessB = Business::create(['name' => 'Metro Retail B']);
        setPermissionsTeamId($this->businessB->id);

        $this->branchB = Branch::create([
            'business_id' => $this->businessB->id,
            'name' => 'Branch Main B',
            'code' => 'BMB-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->cashierB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'is_active' => true,
        ]);
        $this->cashierB->businesses()->attach($this->businessB->id);
        $this->cashierB->branches()->attach($this->branchB->id, ['business_id' => $this->businessB->id]);
        $this->cashierB->assignRole('Salesperson');
    }

    public function test_sale_replay_exactly_once(): void
    {
        $idempotencyKey = 'sale-uuid-replay-001';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $idempotencyKey,
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 200.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 200.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders([
                'X-Business-ID' => $this->businessA->id,
                'X-Client-Version' => '1.0.0',
            ])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('sales', [
            'business_id' => $this->businessA->id,
            'idempotency_key' => $idempotencyKey,
            'total' => '200.00',
        ]);

        // Stock reduced from 5.000 to 4.000
        $inv = BranchInventory::where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productA->id)
            ->first();
        $this->assertEquals('4.000', (string) $inv->quantity_on_hand);
    }

    public function test_sale_response_loss_retry_returns_same_sale_without_duplicate(): void
    {
        $idempotencyKey = 'sale-uuid-retry-002';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $idempotencyKey,
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 200.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 200.00,
                        ],
                    ],
                ],
            ],
        ];

        // First attempt
        $res1 = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);
        $res1->assertStatus(200);

        // Immediate identical retry
        $res2 = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);
        $res2->assertStatus(200);

        // Exactly one sale in database
        $this->assertEquals(1, Sale::where('idempotency_key', $idempotencyKey)->count());

        // Stock decreased only once: 5 - 1 = 4
        $inv = BranchInventory::where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productA->id)
            ->first();
        $this->assertEquals('4.000', (string) $inv->quantity_on_hand);
    }

    public function test_cross_tenant_sale_replay_is_denied(): void
    {
        $payload = [
            'business_id' => $this->businessB->id, // Mismatch with authenticated business A
            'sales' => [
                [
                    'idempotency_key' => 'sale-cross-tenant-003',
                    'branch_id' => $this->branchA->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 200.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
        $this->assertEquals(0, Sale::where('idempotency_key', 'sale-cross-tenant-003')->count());
    }

    public function test_stock_conflict_fails_cleanly_and_denies_negative_stock(): void
    {
        // 1. Consume all 5 units on the server directly
        BranchInventory::where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productA->id)
            ->update(['quantity_on_hand' => '0.000']);

        // 2. Offline queued sale tries to sell 3 units
        $idempotencyKey = 'sale-stock-conflict-004';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $idempotencyKey,
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 600.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => '3.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 200.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'error_type' => 'STOCK_CONFLICT',
            'failed_item_key' => $idempotencyKey,
        ]);

        // Verify stock remains 0.000 (NOT negative) and sale was not committed
        $inv = BranchInventory::where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productA->id)
            ->first();
        $this->assertEquals('0.000', (string) $inv->quantity_on_hand);
        $this->assertEquals(0, Sale::where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_price_conflict_fails_cleanly_when_catalog_price_changed(): void
    {
        // Change authoritative catalog price on server from 200 to 250
        $this->productA->update(['selling_price' => 250.00]);

        // Offline queued sale cached at old price 200.00
        $idempotencyKey = 'sale-price-conflict-005';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $idempotencyKey,
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 200.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 200.00, // Stale cached price!
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'error_type' => 'PRICE_CONFLICT',
            'failed_item_key' => $idempotencyKey,
        ]);

        $this->assertEquals(0, Sale::where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_whole_batch_atomicity_rolls_back_all_when_one_fails(): void
    {
        $validKey = 'sale-valid-batch-006';
        $invalidKey = 'sale-invalid-batch-007';

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $validKey,
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 200.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 200.00,
                        ],
                    ],
                ],
                [
                    'idempotency_key' => $invalidKey,
                    'branch_id' => 999999, // Invalid branch triggers failure
                    'payment_method' => 'cash',
                    'paid_amount' => 200.00,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 200.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);

        // Whole-batch atomicity: zero sales committed, valid sale rolled back!
        $this->assertEquals(0, Sale::where('idempotency_key', $validKey)->count());
        $this->assertEquals(0, Sale::where('idempotency_key', $invalidKey)->count());
        $inv = BranchInventory::where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productA->id)
            ->first();
        $this->assertEquals('5.000', (string) $inv->quantity_on_hand);
    }

    public function test_khata_payment_replay_idempotency(): void
    {
        setPermissionsTeamId($this->businessA->id);
        $manager = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $manager->businesses()->attach($this->businessA->id);
        $manager->branches()->attach($this->branchA->id, ['business_id' => $this->businessA->id]);
        $manager->assignRole('Business Owner');

        $customer = Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Tariq Wholesale',
            'opening_balance' => 5000.00,
        ]);
        app(\App\Services\CustomerAccountService::class)->recalculateBalance($customer);

        $key = 'khata-pay-idemp-008';
        $payload = [
            'payments' => [
                [
                    'idempotency_key' => $key,
                    'customer_id' => $customer->id,
                    'branch_id' => $this->branchA->id,
                    'amount' => 1000.00,
                    'payment_method' => 'cash',
                    'financial_account_id' => $this->cashDrawer->id,
                ],
            ],
        ];

        $res1 = $this->actingAs($manager, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);
        $res1->assertStatus(200);

        // Replay retry with same key
        $res2 = $this->actingAs($manager, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);
        $res2->assertStatus(200);

        // Exactly 1 payment created
        $this->assertEquals(1, CustomerPayment::where('idempotency_key', $key)->count());
    }

    public function test_expense_replay_idempotency(): void
    {
        $key = 'exp-idemp-009';
        $payload = [
            'expenses' => [
                [
                    'idempotency_key' => $key,
                    'branch_id' => $this->branchA->id,
                    'category_id' => $this->expenseCat->id,
                    'financial_account_id' => $this->cashDrawer->id,
                    'amount' => 250.00,
                    'description' => 'Packaging tape and stationery',
                ],
            ],
        ];

        $res1 = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);
        $res1->assertStatus(200);

        // Replay
        $res2 = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);
        $res2->assertStatus(200);

        $this->assertEquals(1, Expense::where('idempotency_key', $key)->count());
    }

    public function test_client_spoofed_user_id_is_strictly_rejected(): void
    {
        $key = 'sale-spoof-user-010';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key,
                    'user_id' => 99999, // Attempt to spoof another user
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 500.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 500.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'error_type' => 'FORBIDDEN',
            'failed_item_key' => $key,
        ]);

        $this->assertEquals(0, Sale::where('idempotency_key', $key)->count());
    }

    public function test_inactive_user_cannot_replay_transactions(): void
    {
        $this->cashierA->update(['is_active' => false]);

        $key = 'sale-inactive-user-011';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key,
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 500.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 500.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        // Inactive user is rejected by auth/governance guard
        $this->assertTrue(in_array($response->status(), [401, 403]));
        $this->assertEquals(0, Sale::where('idempotency_key', $key)->count());
    }

    public function test_client_spoofed_user_id_in_payment_is_strictly_rejected(): void
    {
        $customer = \App\Models\Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Ali Khan',
            'phone' => '03001234567',
        ]);

        $key = 'payment-spoof-user-012';
        $payload = [
            'payments' => [
                [
                    'idempotency_key' => $key,
                    'customer_id' => $customer->id,
                    'amount' => 500.00,
                    'payment_method' => 'cash',
                    'financial_account_id' => $this->cashDrawer->id,
                    'user_id' => $this->cashierB->id, // Mismatch with acting cashierA
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'error_type' => 'FORBIDDEN',
            'failed_item_key' => $key,
        ]);
    }

    public function test_client_spoofed_user_id_in_expense_is_strictly_rejected(): void
    {
        $category = \App\Models\ExpenseCategory::create([
            'business_id' => $this->businessA->id,
            'name' => 'Store Utilities',
            'status' => 'active',
        ]);

        $key = 'expense-spoof-user-013';
        $payload = [
            'expenses' => [
                [
                    'idempotency_key' => $key,
                    'category_id' => $category->id,
                    'amount' => 300.00,
                    'payment_method' => 'cash',
                    'financial_account_id' => $this->cashDrawer->id,
                    'user_id' => $this->cashierB->id, // Mismatch with acting cashierA
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'error_type' => 'FORBIDDEN',
            'failed_item_key' => $key,
        ]);
    }
}

