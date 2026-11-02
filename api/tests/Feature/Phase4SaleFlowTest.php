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
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\FinancialAccount;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class Phase4SaleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $branch2;
    protected $owner;
    protected $manager;
    protected $managerBranch2;
    protected $cashier;
    protected $product;
    protected $customer;
    protected $cashAccount;
    protected $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Business 1
        $this->business = Business::create(['name' => 'Phase 4 Test Business']);
        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'is_primary' => true
        ]);
        $this->branch2 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Second Branch',
            'is_primary' => false
        ]);

        setPermissionsTeamId($this->business->id);

        // Accounts
        $this->cashAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 50000,
            'balance' => 50000,
            'is_default' => true,
            'status' => 'active'
        ]);

        $this->bankAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'HBL Digital Account',
            'type' => 'bank',
            'opening_balance' => 50000,
            'balance' => 50000,
            'is_default' => false,
            'status' => 'active'
        ]);

        // Users
        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'email' => 'owner@test.com',
            'password' => Hash::make('OwnerSecret123!')
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->manager = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'email' => 'manager1@test.com',
            'password' => Hash::make('ManagerSecret123!')
        ]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->assignRole('Branch Manager');
        $this->manager->givePermissionTo('override sale price');

        $this->managerBranch2 = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'email' => 'manager2@test.com',
            'password' => Hash::make('Manager2Secret123!')
        ]);
        $this->managerBranch2->businesses()->attach($this->business->id);
        $this->managerBranch2->assignRole('Branch Manager');
        $this->managerBranch2->givePermissionTo('override sale price');

        $this->cashier = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'email' => 'cashier@test.com',
            'password' => Hash::make('CashierSecret123!')
        ]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->assignRole('Salesperson');

        // Product
        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Basmati Rice 1kg',
            'cost_price' => 150,
            'selling_price' => 200,
            'stock' => 100,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 100
        ]);

        // Customer
        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Tariq Mehmood',
            'phone' => '03001234567'
        ]);
    }

    // =========================================================================
    // 1. SPLIT MULTI-TENDER TESTS (SRT-GAP-006)
    // =========================================================================

    public function test_cash_only_sale_regression_works_correctly()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 200,
            'payment_method' => 'Cash',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 200]
            ]
        ]);

        $res->assertStatus(201);
        $saleId = $res->json('id');
        $sale = Sale::find($saleId);

        $this->assertEquals(200, $sale->total);
        $this->assertEquals(200, $sale->paid_amount);
        $this->assertEquals(0, $sale->due_amount);
        $this->assertEquals('completed', $sale->status);

        // Inventory reduced
        $this->assertEquals(99, $this->product->fresh()->stock);
    }

    public function test_split_tender_cash_and_digital_exact_reconciliation()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        // Sale of 2 items = Rs 400. Split: Rs 150 cash, Rs 250 card
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'payment_method' => 'Split',
            'paid_amount' => 400,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200]
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 150,
                    'financial_account_id' => $this->cashAccount->id
                ],
                [
                    'payment_method' => 'card',
                    'amount' => 250,
                    'financial_account_id' => $this->bankAccount->id
                ]
            ]
        ]);

        $res->assertStatus(201);
        $sale = Sale::find($res->json('id'));

        $this->assertEquals(400, $sale->total);
        $this->assertEquals(400, $sale->paid_amount);
        $this->assertEquals(0, $sale->due_amount);
        $this->assertEquals('completed', $sale->status);

        // Payments recorded
        $payments = SalePayment::where('sale_id', $sale->id)->get();
        $this->assertCount(2, $payments);
        $this->assertEquals(150, $payments->where('payment_method', 'cash')->first()->amount);
        $this->assertEquals(250, $payments->where('payment_method', 'card')->first()->amount);
    }

    public function test_split_tender_cash_and_khata_receivable()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        // Sale of 2 items = Rs 400. Rs 100 paid in Cash, Rs 300 unpaid on Khata
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'customer_id' => $this->customer->id,
            'payment_method' => 'Split',
            'paid_amount' => 100,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200]
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 100,
                    'financial_account_id' => $this->cashAccount->id
                ]
            ]
        ]);

        $res->assertStatus(201);
        $sale = Sale::find($res->json('id'));

        $this->assertEquals(400, $sale->total);
        $this->assertEquals(100, $sale->paid_amount);
        $this->assertEquals(300, $sale->due_amount);
        $this->assertEquals('completed', $sale->status);

        // Customer debt balance updated
        $this->assertEquals(300, $this->customer->fresh()->balance);

        // Khata does NOT create a fake SalePayment record
        $payments = SalePayment::where('sale_id', $sale->id)->get();
        $this->assertCount(1, $payments);
        $this->assertEquals('cash', $payments->first()->payment_method);
    }

    public function test_khata_without_customer_is_rejected()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        // Sale with unpaid amount but no customer
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'payment_method' => 'Split',
            'paid_amount' => 100, // Due = 100
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 200]
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 100
                ]
            ]
        ]);

        $res->assertStatus(422);
    }

    public function test_split_tender_under_settlement_rejected()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        // Total = 400. Paid amount = 400, but payment items sum to only 250 (unbalanced)
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'payment_method' => 'Split',
            'paid_amount' => 400,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200]
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 150
                ],
                [
                    'payment_method' => 'card',
                    'amount' => 100
                ]
            ]
        ]);

        $res->assertStatus(422);
    }

    public function test_negative_tender_amount_is_rejected()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'payment_method' => 'Split',
            'paid_amount' => 200,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 200]
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => -50
                ]
            ]
        ]);

        $res->assertStatus(422);
    }

    // =========================================================================
    // 2. PRICE OVERRIDE & DUAL-ACTOR SECURITY TESTS (SRT-GAP-008)
    // =========================================================================

    public function test_cashier_self_price_override_rejected()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        // Cashier attempts price override without elevated approver credentials
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 160,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 160] // catalog is 200
            ]
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['items.0.unit_price']);
    }

    public function test_cashier_delegated_override_with_wrong_password_rejected()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 160,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 160]
            ],
            'override_authorization' => [
                'approver_email' => 'manager1@test.com',
                'approver_password' => 'WrongPassword!'
            ]
        ]);

        $res->assertStatus(422);
    }

    public function test_failed_override_attempts_are_rate_limited()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;
        $throttleKey = 'price_override:' . $this->business->id . ':' . $this->cashier->id;
        RateLimiter::clear($throttleKey);

        // Attempt 5 bad passwords
        for ($i = 0; $i < 5; $i++) {
            $res = $this->withHeaders([
                'Authorization' => "Bearer {$token}",
                'X-Business-ID' => $this->business->id
            ])->postJson('/api/v1/sales', [
                'paid_amount' => 160,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 160]
                ],
                'override_authorization' => [
                    'approver_email' => 'manager1@test.com',
                    'approver_password' => 'WrongPassword!'
                ]
            ]);
            $res->assertStatus(422);
        }

        // 6th attempt should be throttled
        $res6 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 160,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 160]
            ],
            'override_authorization' => [
                'approver_email' => 'manager1@test.com',
                'approver_password' => 'ManagerSecret123!'
            ]
        ]);

        $res6->assertStatus(422);
        $res6->assertJsonValidationErrors(['override_authorization']);
        $this->assertStringContainsString('Too many failed override authorization attempts', $res6->json('message'));
        RateLimiter::clear($throttleKey);
    }

    public function test_manager_from_other_branch_cannot_authorize_override()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        // Cashier is on branch 1. Manager 2 is on branch 2.
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 160,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 160]
            ],
            'override_authorization' => [
                'approver_email' => 'manager2@test.com',
                'approver_password' => 'Manager2Secret123!'
            ]
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['override_authorization']);
    }

    public function test_authorized_manager_approval_succeeds_with_dual_actor_audit()
    {
        $token = $this->cashier->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 160,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 160]
            ],
            'override_authorization' => [
                'approver_email' => 'manager1@test.com',
                'approver_password' => 'ManagerSecret123!',
                'reason' => 'Counter customer discount'
            ]
        ]);

        $res->assertStatus(201);
        $sale = Sale::find($res->json('id'));

        // Dual Actor Attribution:
        // 1. Sale user_id remains Cashier
        $this->assertEquals($this->cashier->id, $sale->user_id);

        // 2. Audit record records actual sale actor and approver
        $audit = ActivityLog::where('business_id', $this->business->id)
            ->where('event', 'sale.price_override')
            ->latest()
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals($this->cashier->id, $audit->properties['actual_sale_actor_user_id']);
        $this->assertEquals($this->manager->id, $audit->properties['override_authorized_by_user_id']);
        $this->assertEquals('Branch Manager', $audit->properties['override_authorized_by_role']);
        $this->assertEquals(200, $audit->properties['catalog_price']);
        $this->assertEquals(160, $audit->properties['override_price']);
        
        // Passwords must NEVER be logged
        $this->assertArrayNotHasKey('password', $audit->properties);
        $this->assertArrayNotHasKey('approver_password', $audit->properties);
    }

    public function test_owner_direct_override_succeeds_without_password()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 170,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 170]
            ]
        ]);

        $res->assertStatus(201);
        $sale = Sale::find($res->json('id'));
        $this->assertEquals(170, $sale->total);
        $this->assertEquals($this->owner->id, $sale->user_id);
    }

    // =========================================================================
    // 3. SALES RETURN & RESTOCK CONDITION TESTS (SRT-GAP-010)
    // =========================================================================

    public function test_restockable_return_restores_sellable_inventory()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // 1. Create sale of 5 items
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 1000,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 200]
            ]
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        $this->assertEquals(95, $this->product->fresh()->stock);

        // 2. Return 2 items marked RESTOCKABLE
        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_amount' => 400,
            'is_restockable' => true,
            'reason' => 'Customer changed mind - unopened box',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 2,
                    'is_restockable' => true
                ]
            ]
        ]);

        $returnRes->assertStatus(201);

        // Sellable stock should increase from 95 to 97
        $this->assertEquals(97, $this->product->fresh()->stock);
    }

    public function test_non_restockable_return_does_not_increase_inventory()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // 1. Create sale of 5 items
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 1000,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 200]
            ]
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        $this->assertEquals(95, $this->product->fresh()->stock);

        // 2. Return 2 items marked NON-RESTOCKABLE (damaged)
        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_amount' => 400,
            'is_restockable' => false,
            'reason' => 'Package ripped and contaminated',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 2,
                    'is_restockable' => false
                ]
            ]
        ]);

        $returnRes->assertStatus(201);

        // Stock remains 95 (NOT restored)
        $this->assertEquals(95, $this->product->fresh()->stock);
    }

    public function test_over_return_is_rejected()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // 1. Create sale of 2 items
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 400,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200]
            ]
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        // 2. Try to return 3 items (more than sold)
        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_amount' => 600,
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 3
                ]
            ]
        ]);

        $returnRes->assertStatus(422);
    }

    public function test_khata_return_priority_reduces_receivable_first()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Sale: 2 items = Rs 400. Rs 100 paid in Cash, Rs 300 on Khata credit
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'customer_id' => $this->customer->id,
            'payment_method' => 'Split',
            'paid_amount' => 100,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 200]
            ],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 100]
            ]
        ]);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        $this->assertEquals(300, $this->customer->fresh()->balance);

        // Return 1 item (value 200).
        // Since due_amount is 300, the entire 200 must reduce Khata debt first!
        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'items' => [
                ['sale_item_id' => $saleItemId, 'quantity' => 1]
            ]
        ]);

        $returnRes->assertStatus(201);

        // Customer receivable decreased by 200 (from 300 to 100)
        $this->assertEquals(100, $this->customer->fresh()->balance);

        // Sale due amount reduced
        $sale = Sale::find($saleId);
        $this->assertEquals(100, $sale->due_amount);
    }

    public function test_cash_refund_settlement_creates_single_account_movement_outflow()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // 1. Create completed paid sale (1 item = Rs 200, cash)
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 200,
            'payment_method' => 'Cash',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 200]
            ]
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        // 2. Return 1 item (return value 200, residual physical refund 200)
        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'items' => [
                ['sale_item_id' => $saleItemId, 'quantity' => 1]
            ]
        ]);
        $returnRes->assertStatus(201);
        $saleReturnId = $returnRes->json('id');

        $initialCashBalance = $this->cashAccount->fresh()->balance;

        // 3. Settle physical cash refund
        $settleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sale-returns/{$saleReturnId}/settle-refund", [
            'financial_account_id' => $this->cashAccount->id,
            'amount' => 200
        ]);
        $settleRes->assertStatus(201);

        // Verify single AccountMovement outflow created
        $movements = \App\Models\AccountMovement::where('business_id', $this->business->id)
            ->where('reference_type', \App\Models\SaleReturn::class)
            ->where('reference_id', $saleReturnId)
            ->where('movement_category', 'refund')
            ->get();

        $this->assertCount(1, $movements);
        $movement = $movements->first();
        $this->assertEquals('outflow', $movement->type);
        $this->assertEquals(200, $movement->amount);
        $this->assertEquals($this->cashAccount->id, $movement->account_id);
        $this->assertEquals($this->branch->id, $movement->branch_id);
        $this->assertEquals($this->owner->id, $movement->user_id);

        // Verify account balance decremented by exactly 200
        $this->assertEquals($initialCashBalance - 200, $this->cashAccount->fresh()->balance);
    }

    public function test_bank_account_refund_settlement_and_cross_business_denial()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // 1. Create sale
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 500,
            'payment_method' => 'Card',
            'payments' => [
                ['payment_method' => 'Card', 'amount' => 500, 'financial_account_id' => $this->bankAccount->id]
            ],
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 250]
            ]
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        // 2. Return 2 items
        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'items' => [
                ['sale_item_id' => $saleItemId, 'quantity' => 2]
            ]
        ]);
        $returnRes->assertStatus(201);
        $saleReturnId = $returnRes->json('id');

        // 3. Cross-business account settlement attempt must be denied
        $otherBiz = Business::create(['name' => 'Other Tenant Co']);
        $otherAccount = FinancialAccount::create([
            'business_id' => $otherBiz->id,
            'branch_id' => 1,
            'name' => 'Foreign Bank',
            'type' => 'bank',
            'status' => 'active',
            'opening_balance' => 10000,
            'balance' => 10000
        ]);

        $crossRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sale-returns/{$saleReturnId}/settle-refund", [
            'financial_account_id' => $otherAccount->id,
            'amount' => 500
        ]);
        $crossRes->assertStatus(404);

        // 4. Authorized tenant bank account succeeds
        $initialBankBalance = $this->bankAccount->fresh()->balance;
        $bankRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sale-returns/{$saleReturnId}/settle-refund", [
            'financial_account_id' => $this->bankAccount->id,
            'amount' => 500
        ]);
        $bankRes->assertStatus(201);

        $this->assertEquals($initialBankBalance - 500, $this->bankAccount->fresh()->balance);
    }

    public function test_duplicate_refund_settlement_is_denied()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // 1. Create sale & return
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 200,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 200]
            ]
        ]);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'items' => [
                ['sale_item_id' => $saleItemId, 'quantity' => 1]
            ]
        ]);
        $saleReturnId = $returnRes->json('id');

        // 2. First refund settlement: PASS
        $firstSettle = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sale-returns/{$saleReturnId}/settle-refund", [
            'financial_account_id' => $this->cashAccount->id,
            'amount' => 200
        ]);
        $firstSettle->assertStatus(201);

        // 3. Second refund settlement attempt for same return: DENIED
        $secondSettle = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sale-returns/{$saleReturnId}/settle-refund", [
            'financial_account_id' => $this->cashAccount->id,
            'amount' => 200
        ]);
        $secondSettle->assertStatus(422);

        // Verify exactly one movement exists (0 duplicates)
        $movementCount = \App\Models\AccountMovement::where('business_id', $this->business->id)
            ->where('reference_type', \App\Models\SaleReturn::class)
            ->where('reference_id', $saleReturnId)
            ->where('movement_category', 'refund')
            ->count();
        $this->assertEquals(1, $movementCount);
    }

    public function test_split_tender_return_with_khata_priority_and_residual_physical_refund()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Product setup: 2 items at Rs 500 = Rs 1000 total
        $itemProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Split Tender Test Product',
            'cost_price' => 300,
            'selling_price' => 500,
            'stock' => 20,
            'unit' => 'pcs'
        ]);
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $itemProduct->id,
            'quantity_on_hand' => 20
        ]);

        // Sale: Total = Rs 1000. Cash Paid = 600, Khata = 400
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'customer_id' => $this->customer->id,
            'payment_method' => 'Split',
            'paid_amount' => 600,
            'items' => [
                ['product_id' => $itemProduct->id, 'quantity' => 2, 'unit_price' => 500]
            ],
            'payments' => [
                ['payment_method' => 'cash', 'amount' => 600]
            ]
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        $this->assertEquals(400, $this->customer->fresh()->balance);
        $this->assertEquals(400, Sale::find($saleId)->due_amount);

        // Return 1 item: return value = 500
        // Khata reduction absorbs min(500, 400) = 400
        // Sale due amount becomes 0, customer receivable becomes 0
        // Remaining physical refund obligation = 100
        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'items' => [
                ['sale_item_id' => $saleItemId, 'quantity' => 1]
            ]
        ]);
        $returnRes->assertStatus(201);
        $saleReturnId = $returnRes->json('id');

        $this->assertEquals(0, $this->customer->fresh()->balance);
        $this->assertEquals(0, Sale::find($saleId)->due_amount);

        // Settle remaining physical refund obligation of Rs 100
        $initialCashBalance = $this->cashAccount->fresh()->balance;
        $settleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sale-returns/{$saleReturnId}/settle-refund", [
            'financial_account_id' => $this->cashAccount->id,
            'amount' => 100
        ]);
        $settleRes->assertStatus(201);

        // Verify one physical refund outflow of Rs 100
        $this->assertEquals($initialCashBalance - 100, $this->cashAccount->fresh()->balance);

        // Verify customer receivable remains 0 (no double deduction)
        $this->assertEquals(0, $this->customer->fresh()->balance);
        $this->assertEquals(0, Sale::find($saleId)->due_amount);
    }
}
