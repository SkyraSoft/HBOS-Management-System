<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\BulkSyncService;
use App\Services\InventoryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossSystemConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected Product $product;
    protected FinancialAccount $cashDrawer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Concurrency Retail Shop']);
        setPermissionsTeamId($this->business->id);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Concurrency Store',
            'code' => 'CONC-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->cashDrawer = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 0.00,
            'balance' => 0.00,
            'is_default' => true,
            'status' => 'active'
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Limited Stock Widget',
            'sku' => 'WIDGET-001',
            'cost_price' => 50,
            'retail_price' => 100,
            'selling_price' => 100,
            'is_active' => true
        ]);
    }

    public function test_duplicate_sale_replay_with_identical_idempotency_key_is_strictly_idempotent()
    {
        $syncService = app(BulkSyncService::class);

        // Seed initial stock of 10
        app(InventoryService::class)->receiveStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_cost' => 50.00,
            'type' => 'purchase',
            'performed_by' => $this->owner->id,
        ]);

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => 'CONCURRENT-IDEMPOTENT-KEY-001',
                    'branch_id' => $this->branch->id,
                    'invoice_number' => 'INV-CONC-001',
                    'date' => now()->toDateString(),
                    'subtotal' => 100,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => 100,
                    'paid_amount' => 100,
                    'payment_method' => 'cash',
                    'payment_type' => 'cash',
                    'payment_status' => 'paid',
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => 1,
                            'unit_price' => 100,
                            'total' => 100
                        ]
                    ]
                ]
            ]
        ];

        // First ingestion
        $res1 = $syncService->ingestBulkTransactions($payload, $this->owner, $this->business->id);
        $this->assertEquals(1, $res1['total_processed']);
        $this->assertCount(1, $res1['sales']);

        $invAfter1 = BranchInventory::where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(9.000, floatval($invAfter1->quantity_on_hand));

        // Second duplicate ingestion
        $res2 = $syncService->ingestBulkTransactions($payload, $this->owner, $this->business->id);
        
        // Stock MUST remain 9, not decrease to 8!
        $invAfter2 = BranchInventory::where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(9.000, floatval($invAfter2->quantity_on_hand));

        // Total sales in database must remain 1
        $salesCount = Sale::where('business_id', $this->business->id)->where('idempotency_key', 'CONCURRENT-IDEMPOTENT-KEY-001')->count();
        $this->assertEquals(1, $salesCount);
    }

    public function test_negative_stock_rejection_under_insufficient_inventory()
    {
        $invService = app(InventoryService::class);

        // Receive 2 units
        $invService->receiveStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_cost' => 50.00,
            'type' => 'purchase',
            'performed_by' => $this->owner->id,
        ]);

        // Attempt to issue 5 units
        $this->expectException(\Exception::class);
        $invService->issueStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'type' => 'sale',
            'performed_by' => $this->owner->id,
        ]);
    }
}