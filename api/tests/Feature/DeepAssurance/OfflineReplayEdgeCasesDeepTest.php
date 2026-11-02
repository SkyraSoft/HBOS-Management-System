<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Deep Assurance: Offline POS Replay, Bulk Transactions Ingest, Price/Stock Conflicts, and Durability
 */
class OfflineReplayEdgeCasesDeepTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $businessB;
    protected $branch;
    protected $cashier;
    protected $otherUser;
    protected $cashAccount;
    protected $product1;
    protected $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->business = Business::create([
            'name' => 'Offline Superstore',
            'legal_name' => 'Offline Superstore Pvt Ltd',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->businessB = Business::create([
            'name' => 'Competitor Shop',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Counter',
            'code' => 'CTR-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->cashier = User::create([
            'name' => 'Offline Cashier 1',
            'email' => 'cashier1@offline.test',
            'password' => bcrypt('Secret123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->cashier->businesses()->attach($this->business->id);

        setPermissionsTeamId($this->business->id);
        $this->cashier->assignRole('Salesperson');

        $this->otherUser = User::create([
            'name' => 'Other Cashier',
            'email' => 'cashier2@offline.test',
            'password' => bcrypt('Secret123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->otherUser->businesses()->attach($this->business->id);
        $this->otherUser->assignRole('Salesperson');

        $this->cashAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Cash Till',
            'type' => 'cash',
            'account_number' => 'CSH-OFF-1',
            'opening_balance' => 10000.00,
            'balance' => 10000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->product1 = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Lipton Yellow Label 400g',
            'sku' => 'LIP-400',
            'selling_price' => 600.00,
            'cost_price' => 480.00,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Olpers Milk 1 Litre',
            'sku' => 'OLP-1L',
            'selling_price' => 280.00,
            'cost_price' => 240.00,
            'status' => 'active',
            'is_active' => true,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product1->id,
            'quantity_on_hand' => 15.000,
            'minimum_stock' => 2.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product2->id,
            'quantity_on_hand' => 5.000,
            'minimum_stock' => 1.000,
        ]);
    }

    public function test_offline_bulk_sync_single_sale_success(): void
    {
        $idempotencyKey = 'SYNC-SALE-' . Str::uuid()->toString();

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $idempotencyKey,
                    'branch_id' => $this->branch->id,
                    'user_id' => $this->cashier->id,
                    'occurred_at' => now()->subMinutes(30)->toDateTimeString(),
                    'items' => [
                        [
                            'product_id' => $this->product1->id,
                            'quantity' => 2,
                            'unit_price' => 600.00,
                        ]
                    ],
                    'payments' => [
                        [
                            'payment_method' => 'cash',
                            'amount' => 1200.00,
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data['sales']);
        $this->assertEquals('synced', $data['sales'][0]['status']);
        $this->assertEquals(1200.00, (float)$data['sales'][0]['total']);

        // Stock deduction check
        $inv = BranchInventory::where('branch_id', $this->branch->id)
            ->where('product_id', $this->product1->id)
            ->first();
        // 15 - 2 = 13
        $this->assertEquals(13.000, (float)$inv->quantity_on_hand);
    }

    public function test_offline_bulk_sync_multiple_sales_atomicity(): void
    {
        $key1 = 'SYNC-MULTI-1-' . Str::uuid()->toString();
        $key2 = 'SYNC-MULTI-2-' . Str::uuid()->toString();

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key1,
                    'branch_id' => $this->branch->id,
                    'items' => [
                        ['product_id' => $this->product1->id, 'quantity' => 1, 'unit_price' => 600.00]
                    ],
                    'payments' => [['payment_method' => 'cash', 'amount' => 600.00]]
                ],
                [
                    'idempotency_key' => $key2,
                    'branch_id' => $this->branch->id,
                    'items' => [
                        ['product_id' => $this->product2->id, 'quantity' => 2, 'unit_price' => 280.00]
                    ],
                    'payments' => [['payment_method' => 'cash', 'amount' => 560.00]]
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('sales')));
    }

    public function test_offline_bulk_sync_preserves_historical_occurred_at(): void
    {
        $historicalTime = now()->subHours(2);
        $key = 'SYNC-TIME-' . Str::uuid()->toString();

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key,
                    'branch_id' => $this->branch->id,
                    'occurred_at' => $historicalTime->toDateTimeString(),
                    'items' => [
                        ['product_id' => $this->product1->id, 'quantity' => 1, 'unit_price' => 600.00]
                    ],
                    'payments' => [['payment_method' => 'cash', 'amount' => 600.00]]
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(200);
        $saleId = $response->json('sales.0.server_id');
        $sale = Sale::find($saleId);
        $this->assertEquals($historicalTime->toDateString(), (string)$sale->date);
    }

    public function test_offline_bulk_sync_price_conflict_rejected(): void
    {
        $key = 'SYNC-PRC-CNFLCT-' . Str::uuid()->toString();

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key,
                    'branch_id' => $this->branch->id,
                    'items' => [
                        [
                            'product_id' => $this->product1->id,
                            'quantity' => 1,
                            'unit_price' => 450.00, // Client cached price is 450, but authoritative is 600
                        ]
                    ],
                    'payments' => [['payment_method' => 'cash', 'amount' => 450.00]]
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
        $this->assertEquals('PRICE_CONFLICT', $response->json('error_type'));
    }

    public function test_offline_bulk_sync_stock_conflict_rolls_back_entire_batch(): void
    {
        $key1 = 'SYNC-STK-OK-' . Str::uuid()->toString();
        $key2 = 'SYNC-STK-FAIL-' . Str::uuid()->toString();

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key1,
                    'branch_id' => $this->branch->id,
                    'items' => [
                        ['product_id' => $this->product1->id, 'quantity' => 1, 'unit_price' => 600.00]
                    ],
                    'payments' => [['payment_method' => 'cash', 'amount' => 600.00]]
                ],
                [
                    'idempotency_key' => $key2,
                    'branch_id' => $this->branch->id,
                    'items' => [
                        // Product 2 only has 5 in stock; requesting 99 units
                        ['product_id' => $this->product2->id, 'quantity' => 99, 'unit_price' => 280.00]
                    ],
                    'payments' => [['payment_method' => 'cash', 'amount' => 99 * 280]]
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
        $this->assertEquals('STOCK_CONFLICT', $response->json('error_type'));

        // Verify entire batch rolled back: sale 1 must NOT exist
        $this->assertDatabaseMissing('sales', ['idempotency_key' => $key1]);
    }

    public function test_offline_bulk_sync_cross_tenant_replay_rejected(): void
    {
        $payload = [
            'business_id' => $this->businessB->id, // Forged foreign business ID in payload
            'sales' => [
                [
                    'idempotency_key' => 'SYNC-TENT-' . Str::uuid()->toString(),
                    'branch_id' => $this->branch->id,
                    'items' => [
                        ['product_id' => $this->product1->id, 'quantity' => 1, 'unit_price' => 600.00]
                    ],
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
    }

    public function test_offline_bulk_sync_spoofed_user_id_rejected(): void
    {
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => 'SYNC-USER-SPOOF-' . Str::uuid()->toString(),
                    'user_id' => $this->otherUser->id, // Attempting to spoof another user
                    'branch_id' => $this->branch->id,
                    'items' => [
                        ['product_id' => $this->product1->id, 'quantity' => 1, 'unit_price' => 600.00]
                    ],
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
        $this->assertEquals('FORBIDDEN', $response->json('error_type'));
    }

    public function test_offline_bulk_sync_invalid_branch_id_rejected(): void
    {
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => 'SYNC-BR-INV-' . Str::uuid()->toString(),
                    'branch_id' => 999999, // Non-existent branch
                    'items' => [
                        ['product_id' => $this->product1->id, 'quantity' => 1, 'unit_price' => 600.00]
                    ],
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(422);
    }

    public function test_sync_catalog_delta_returns_fresh_records(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/sync/catalog-delta?branch_id=' . $this->branch->id);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('products', $data);
        $this->assertNotEmpty($data['products']);
    }

    public function test_sync_status_returns_engine_metadata(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->withHeader('X-HBOS-Device-ID', 'TERM-WIN-DESKTOP-01')
            ->getJson('/api/v1/sync/status');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('online', $data['status']);
        $this->assertEquals('HBOS-Sync-Core-v1.0', $data['engine']);
        $this->assertEquals($this->business->id, $data['business_id']);
    }
}
