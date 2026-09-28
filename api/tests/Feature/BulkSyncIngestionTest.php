<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Customer;
use App\Models\BranchInventory;
use App\Models\FinancialAccount;
use App\Models\Sale;
use App\Models\CustomerPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BulkSyncIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected User $cashier;
    protected Product $productA;
    protected Product $productB;
    protected Customer $customer;
    protected FinancialAccount $cashDrawer;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Setup Business and Branch
        $this->business = Business::create([
            'name' => 'Al-Madina Supermarket',
            'email' => 'owner@almadina.test',
            'phone' => '03001234567',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_primary' => true,
        ]);

        setPermissionsTeamId($this->business->id);

        // Create Owner
        $this->owner = User::create([
            'name' => 'Muhammad Shoaib',
            'email' => 'owner@almadina.test',
            'password' => bcrypt('Password123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        // Create Cashier
        $this->cashier = User::create([
            'name' => 'Ali Hassan',
            'email' => 'ali@almadina.test',
            'password' => bcrypt('Password123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->assignRole('Salesperson');

        // Create Financial Account
        $this->cashDrawer = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Counter Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 5000.00,
            'balance' => 5000.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        // Create Category & Brand
        $category = Category::create([
            'business_id' => $this->business->id,
            'name' => 'Beverages',
            'color' => '#3b82f6',
        ]);

        $brand = Brand::create([
            'business_id' => $this->business->id,
            'name' => 'Nestle',
        ]);

        // Create Products with Initial Stock
        $this->productA = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Nestle MilkPak 1L',
            'sku' => 'MP-1L',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'cost_price' => 240.00,
            'selling_price' => 270.00,
            'unit' => 'pc',
            'low_stock_alert' => 5,
            'is_active' => true,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 50,
            'reorder_level' => 5,
        ]);

        $this->productB = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Nestle Juice 1L',
            'sku' => 'NJ-1L',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'cost_price' => 180.00,
            'selling_price' => 220.00,
            'unit' => 'pc',
            'low_stock_alert' => 5,
            'is_active' => true,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->productB->id,
            'quantity_on_hand' => 30,
            'reorder_level' => 5,
        ]);

        // Create Customer
        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Dr. Kamran Ahmed',
            'phone' => '03339876543',
            'opening_balance' => 0.00,
        ]);
        $this->customer->refresh();
        app(\App\Services\CustomerAccountService::class)->recalculateBalance($this->customer);
    }

    public function test_sync_status_endpoint_returns_online_and_server_time()
    {
        $response = $this->actingAs($this->cashier)
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->getJson('/api/v1/sync/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'engine',
                'business_id',
                'server_timestamp',
                'server_timezone',
            ])
            ->assertJson([
                'status' => 'online',
                'business_id' => $this->business->id,
            ]);
    }

    public function test_catalog_delta_sync_returns_products_with_branch_inventory()
    {
        $response = $this->actingAs($this->cashier)
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->getJson('/api/v1/sync/catalog-delta?branch_id=' . $this->branch->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'server_timestamp',
                'products',
                'categories',
                'brands',
                'customers',
            ]);

        $products = $response->json('products');
        $this->assertCount(2, $products);
        $this->assertEquals(50, $products[0]['stock']);
        $this->assertEquals(270.00, $products[0]['selling_price']);
    }

    public function test_atomic_bulk_transaction_ingestion_processes_sales_and_khata_payments()
    {
        $saleUuid1 = (string) Str::uuid();
        $saleUuid2 = (string) Str::uuid();
        $paymentUuid = (string) Str::uuid();

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $saleUuid1,
                    'branch_id' => $this->branch->id,
                    'customer_id' => null,
                    'paid_amount' => 540.00,
                    'payment_method' => 'cash',
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => 2,
                            'unit_price' => 270.00,
                        ]
                    ]
                ],
                [
                    'idempotency_key' => $saleUuid2,
                    'branch_id' => $this->branch->id,
                    'customer_id' => $this->customer->id,
                    'paid_amount' => 0.00,
                    'payment_method' => 'khata',
                    'items' => [
                        [
                            'product_id' => $this->productB->id,
                            'quantity' => 1,
                            'unit_price' => 220.00,
                        ]
                    ]
                ]
            ],
            'khata_payments' => [
                [
                    'idempotency_key' => $paymentUuid,
                    'branch_id' => $this->branch->id,
                    'customer_id' => $this->customer->id,
                    'amount' => 200.00,
                    'payment_method' => 'cash',
                ]
            ]
        ];

        $response = $this->actingAs($this->owner)
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'total_processed' => 3,
            ]);

        // Verify Database State
        // 1. Stock decreased: Product A from 50 -> 48, Product B from 30 -> 29
        $invA = BranchInventory::where('product_id', $this->productA->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEquals(48, $invA->quantity_on_hand);

        $invB = BranchInventory::where('product_id', $this->productB->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEquals(29, $invB->quantity_on_hand);

        // 2. Customer Khata balance: +220 (credit sale), -200 (payment) => 20
        $this->customer->refresh();
        $this->assertEquals(20.00, (float) $this->customer->balance);

        // 3. Sales records created
        $this->assertDatabaseHas('sales', [
            'idempotency_key' => $saleUuid1,
            'business_id' => $this->business->id,
            'total' => 540.00,
        ]);

        $this->assertDatabaseHas('sales', [
            'idempotency_key' => $saleUuid2,
            'business_id' => $this->business->id,
            'total' => 220.00,
        ]);
    }

    public function test_bulk_sync_idempotency_deduplication_prevents_duplicate_sales()
    {
        $saleUuid = (string) Str::uuid();

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $saleUuid,
                    'branch_id' => $this->branch->id,
                    'paid_amount' => 270.00,
                    'payment_method' => 'cash',
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->productA->id,
                            'quantity' => 1,
                            'unit_price' => 270.00,
                        ]
                    ]
                ]
            ]
        ];

        // First Ingestion Request
        $res1 = $this->actingAs($this->cashier)
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $res1->assertStatus(200);

        // Check stock after first call
        $inv = BranchInventory::where('product_id', $this->productA->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEquals(49, $inv->quantity_on_hand);

        // Replay Second Ingestion Request (Identical Payload)
        $res2 = $this->actingAs($this->cashier)
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $res2->assertStatus(200);

        // Verify stock is still 49 (NO duplicate deduction!)
        $inv->refresh();
        $this->assertEquals(49, $inv->quantity_on_hand);

        // Verify only 1 sale exists in database
        $salesCount = Sale::where('idempotency_key', $saleUuid)->count();
        $this->assertEquals(1, $salesCount);
    }
}
