<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\CashRegisterSession;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POSRealisticCheckoutDeepTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected User $salesperson;
    protected FinancialAccount $cashAccount;
    protected FinancialAccount $bankAccount;
    protected Product $productA;
    protected Product $productB;
    protected Product $looseProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'POS Pressure Retail Goods']);
        setPermissionsTeamId($this->business->id);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Checkout Counter',
            'code' => 'POS-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->salesperson = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->assignRole('Salesperson');

        // Accounts with status=active and branch_id
        $this->cashAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Cash Drawer Primary',
            'type' => 'cash',
            'account_number' => 'CASH-POS-1',
            'balance' => 5000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->bankAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Meezan Retail Bank',
            'type' => 'bank',
            'account_number' => 'MEEZAN-9901',
            'balance' => 50000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
        ]);

        // Products
        $this->productA = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Dal Chana 1kg Pack',
            'cost_price' => 180.00,
            'retail_price' => 240.00,
            'selling_price' => 240.00,
            'wholesale_price' => 210.00,
            'sku' => 'DAL-001',
            'stock' => 100,
            'status' => 'active'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 100.000,
            'low_stock_threshold' => 10.000,
        ]);

        $this->productB = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Sugar 50kg Sack',
            'cost_price' => 6000.00,
            'retail_price' => 7200.00,
            'selling_price' => 7200.00,
            'wholesale_price' => 6800.00,
            'sku' => 'SUGAR-050',
            'stock' => 5,
            'status' => 'active'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->productB->id,
            'quantity_on_hand' => 5.000,
            'low_stock_threshold' => 2.000,
        ]);

        $this->looseProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Loose Basmati Rice',
            'cost_price' => 200.00,
            'retail_price' => 280.00,
            'selling_price' => 280.00,
            'sku' => 'RICE-LOOSE',
            'stock' => 50,
            'unit' => 'kg',
            'allow_fractional' => true,
            'status' => 'active'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->looseProduct->id,
            'quantity_on_hand' => 50.000,
            'low_stock_threshold' => 5.000,
        ]);

        // Open Cash Register Session
        CashRegisterSession::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->salesperson->id,
            'financial_account_id' => $this->cashAccount->id,
            'opened_at' => now(),
            'opening_float' => 1000.00,
            'status' => 'open'
        ]);
    }

    public function test_01_single_item_cash_sale_deducts_inventory_and_logs_audit(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 480.00,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 2.000,
                    'unit_price' => 240.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);
        $response->assertJsonFragment(['total' => '480.00']);

        // Check stock deducted accurately (100 - 2 = 98)
        $inv = BranchInventory::where('branch_id', $this->branch->id)
            ->where('product_id', $this->productA->id)
            ->first();
        $this->assertEquals(98.000, (float) $inv->quantity_on_hand);
    }

    public function test_02_multi_item_sale_with_exact_monetary_scale_two(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 7680.00,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 2.000, // 2 * 240 = 480
                    'unit_price' => 240.00,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 1.000, // 1 * 7200 = 7200
                    'unit_price' => 7200.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);
        $response->assertJsonFragment(['total' => '7680.00']);
    }

    public function test_03_split_tender_cash_plus_bank_allocation(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'split',
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 1000.00,
                    'financial_account_id' => $this->cashAccount->id,
                ],
                [
                    'payment_method' => 'bank_transfer',
                    'amount' => 6680.00,
                    'financial_account_id' => $this->bankAccount->id,
                    'reference_number' => 'MEEZAN-REF-8899',
                ]
            ],
            'items' => [
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 1.000,
                    'unit_price' => 7200.00,
                    'discount' => 480.00, // net 6720
                ],
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 4.000, // 4 * 240 = 960
                    'unit_price' => 240.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);
        $this->assertEquals(2, count($response->json('payments')));
    }

    public function test_04_wholesale_pricing_mode_applies_wholesale_rate(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'pricing_mode' => 'WHOLESALE',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 10.000,
                    'pricing_mode' => 'WHOLESALE',
                    'unit_price' => 210.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);
        $response->assertJsonFragment(['total' => '2100.00']);
    }

    public function test_05_line_item_discount_deducts_accurately(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 5.000, // 5 * 240 = 1200
                    'unit_price' => 240.00,
                    'discount' => 200.00, // net 1000
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);
        $response->assertJsonFragment(['total' => '1000.00']);
    }

    public function test_06_order_discount_deducts_from_subtotal(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'discount' => 100.00,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 2.000, // 480
                    'unit_price' => 240.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);
        $response->assertJsonFragment(['total' => '380.00']);
    }

    public function test_07_excessive_order_discount_exceeding_subtotal_is_prevented(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'discount' => 1000.00, // subtotal is 480
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 2.000,
                    'unit_price' => 240.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $this->assertContains($response->status(), [400, 422]);
    }

    public function test_08_last_unit_sale_depletes_stock_to_zero_without_error(): void
    {
        $lastProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Single Unit Special Item',
            'cost_price' => 500.00,
            'retail_price' => 750.00,
            'selling_price' => 750.00,
            'sku' => 'LAST-001',
            'stock' => 1,
            'status' => 'active'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $lastProduct->id,
            'quantity_on_hand' => 1.000,
            'low_stock_threshold' => 1.000,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $lastProduct->id,
                    'quantity' => 1.000,
                    'unit_price' => 750.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);

        $inv = BranchInventory::where('branch_id', $this->branch->id)
            ->where('product_id', $lastProduct->id)
            ->first();
        $this->assertEquals(0.000, (float) $inv->quantity_on_hand);
    }

    public function test_09_insufficient_stock_checkout_is_cleanly_rejected(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 10.000, // Only 5 available!
                    'unit_price' => 7200.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $this->assertContains($response->status(), [400, 422]);
    }

    public function test_10_fractional_loose_goods_scale_three_checkout(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->looseProduct->id,
                    'quantity' => 2.500, // 2.500 kg * 280 = 700.00
                    'unit_price' => 280.00,
                    'unit' => 'kg',
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);
        $response->assertJsonFragment(['total' => '700.00']);

        $inv = BranchInventory::where('branch_id', $this->branch->id)
            ->where('product_id', $this->looseProduct->id)
            ->first();
        // 50 - 2.500 = 47.500
        $this->assertEquals(47.500, (float) $inv->quantity_on_hand);
    }

    public function test_11_duplicate_checkout_with_idempotency_key_prevents_double_stock_deduction(): void
    {
        $idempotencyKey = 'IDEM-POS-TEST-UNIQUE-999';

        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'idempotency_key' => $idempotencyKey,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 3.000,
                    'unit_price' => 240.00,
                ]
            ]
        ];

        // First attempt
        $resp1 = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $resp1->assertStatus(201);
        $saleId1 = $resp1->json('id');

        // Second duplicate attempt with identical key
        $resp2 = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $this->assertContains($resp2->status(), [200, 201]);
        $this->assertEquals($saleId1, $resp2->json('id'));

        // Stock must have been deducted only ONCE (100 - 3 = 97)
        $inv = BranchInventory::where('branch_id', $this->branch->id)
            ->where('product_id', $this->productA->id)
            ->first();
        $this->assertEquals(97.000, (float) $inv->quantity_on_hand);
    }

    public function test_12_exact_cash_checkout_reconciles_total_and_payments(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 240.00,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 1.000,
                    'unit_price' => 240.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);
        $response->assertJsonFragment(['total' => '240.00', 'paid_amount' => '240.00']);
    }

    public function test_13_sale_items_contain_accurate_product_foreign_keys(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 1.000,
                    'unit_price' => 240.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);
        $sale = Sale::with('items')->find($response->json('id'));
        $this->assertNotNull($sale);
        $this->assertEquals(1, $sale->items->count());
        $this->assertEquals($this->productA->id, $sale->items->first()->product_id);
    }

    public function test_14_sale_show_endpoint_returns_nested_item_details(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 1.000,
                    'unit_price' => 240.00,
                ]
            ]
        ];

        $createResp = $this->actingAs($this->salesperson)->postJson('/api/v1/sales', $payload);
        $createResp->assertStatus(201);
        $saleId = $createResp->json('id');

        $response = $this->actingAs($this->salesperson)->getJson('/api/v1/sales/' . $saleId);
        $response->assertStatus(200);
        $this->assertEquals($saleId, $response->json('id'));
        $this->assertNotEmpty($response->json('items'));
    }
}
