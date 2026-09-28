<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\InventoryMovement;
use Database\Seeders\RolesAndPermissionsSeeder;

class InventoryLedgerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $owner;
    protected $business;
    protected $branch;
    protected $token;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'Ledger Test Business']);
        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'is_primary' => true
        ]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'password' => bcrypt('password123')
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');
        $this->token = $this->owner->createToken('test_token')->plainTextToken;

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Ledger Product',
            'cost_price' => 15,
            'selling_price' => 30,
            'stock' => 50,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50,
            'minimum_stock' => 5,
        ]);
    }

    public function test_inventory_movement_created_on_purchase_receipt()
    {
        $purchaseData = [
            'po_number' => 'PO-LEDGER-01',
            'date' => '2026-09-14',
            'status' => 'received',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 25,
                    'unit_cost' => 15,
                    'total' => 375
                ]
            ],
            'subtotal' => 375,
            'total' => 375,
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/purchases', $purchaseData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'purchase',
            'quantity' => 25,
            'performed_by' => $this->owner->id
        ]);
    }

    public function test_inventory_movement_created_on_sale_issue()
    {
        $saleData = [
            'invoice_number' => 'INV-LEDGER-01',
            'date' => '2026-09-14',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 30,
                    'total' => 300
                ]
            ],
            'subtotal' => 300,
            'total' => 300,
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', $saleData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'sale',
            'quantity' => -10,
            'performed_by' => $this->owner->id
        ]);
    }

    public function test_sale_deletion_creates_compensating_reversal_movement()
    {
        $customer = \App\Models\Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Credit Customer',
        ]);

        $saleData = [
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DEL-01',
            'date' => '2026-09-14',
            'paid_amount' => 0, // Unpaid credit sale eligible for cancellation
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 30,
                    'total' => 150
                ]
            ],
            'subtotal' => 150,
            'total' => 150,
        ];

        $createRes = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', $saleData);

        $createRes->assertStatus(201);
        $saleId = $createRes->json('id');

        // 1. Hard DELETE operation must be blocked (HTTP 422)
        $deleteRes = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->deleteJson("/api/v1/sales/{$saleId}");

        $deleteRes->assertStatus(422);

        // 2. Cancellation via POST /sales/{id}/cancel
        $cancelRes = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/cancel", ['reason' => 'Test cancellation']);

        $cancelRes->assertStatus(200);

        // Compensating movement (sale_return) should exist
        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'sale_return',
            'quantity' => 5
        ]);

        // Branch quantity on hand restored to 50
        $this->assertDatabaseHas('branch_inventories', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50
        ]);
    }

    public function test_authorized_user_can_view_movement_history()
    {
        InventoryMovement::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'opening',
            'quantity' => 50,
            'performed_by' => $this->owner->id,
            'notes' => 'Initial test stock'
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->getJson('/api/v1/inventory/movements');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_purchase_deletion_creates_compensating_reversal_movement_and_fails_if_insufficient_stock()
    {
        $purchaseData = [
            'po_number' => 'PO-REV-TEST-01',
            'date' => '2026-09-14',
            'status' => 'received',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 20,
                    'unit_cost' => 15,
                    'total' => 300
                ]
            ],
            'subtotal' => 300,
            'total' => 300,
        ];

        // Receive stock of 20 via Purchase. Initial 50 + 20 = 70 on hand.
        $createRes = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/purchases', $purchaseData);

        $createRes->assertStatus(201);
        $purchaseId = $createRes->json('id');

        $this->assertDatabaseHas('branch_inventories', [
            'product_id' => $this->product->id,
            'quantity_on_hand' => 70
        ]);

        // Reversal 1: Cancelling purchase reverses 20 stock. Restores on hand to 50.
        $deleteRes = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/purchases/{$purchaseId}/cancel");

        $deleteRes->assertStatus(200);

        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'purchase_return',
            'quantity' => -20
        ]);

        $this->assertDatabaseHas('branch_inventories', [
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50
        ]);

        // Reversal 2: Test reversal failure when stock is insufficient.
        // Reduce stock down to 5 via damage adjustment.
        $inventoryService = app(\App\Services\InventoryService::class);
        $inventoryService->issueStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity' => 45,
            'type' => 'damage',
            'performed_by' => $this->owner->id
        ]);

        $this->assertEquals(5, $inventoryService->getBranchBalance($this->product->id, $this->branch->id));

        // Create another purchase of 10 items (branch on hand = 15).
        $purchase2Res = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/purchases', [
            'po_number' => 'PO-REV-TEST-02',
            'date' => '2026-09-14',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_cost' => 15,
                    'total' => 150
                ]
            ],
            'subtotal' => 150,
            'total' => 150,
        ]);
        $purchase2Id = $purchase2Res->json('id');
        $this->assertEquals(15, $inventoryService->getBranchBalance($this->product->id, $this->branch->id));

        // Issue 12 items (branch on hand becomes 3).
        $inventoryService->issueStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity' => 12,
            'type' => 'sale',
            'performed_by' => $this->owner->id
        ]);
        $this->assertEquals(3, $inventoryService->getBranchBalance($this->product->id, $this->branch->id));

        // Attempting to cancel PO-REV-TEST-02 (which requires returning 10 items when only 3 are available) MUST fail safely!
        $failDeleteRes = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/purchases/{$purchase2Id}/cancel");

        $failDeleteRes->assertStatus(422);

        // Branch balance remains untouched at 3, purchase record remains intact
        $this->assertEquals(3, $inventoryService->getBranchBalance($this->product->id, $this->branch->id));
        $this->assertDatabaseHas('purchases', ['id' => $purchase2Id]);
    }

    public function test_sale_creation_with_insufficient_stock_rolls_back_sale_and_items_atomically()
    {
        // Branch on hand is 50. Attempting to sell 100 items.
        $saleData = [
            'invoice_number' => 'INV-FAIL-STOCK-01',
            'date' => '2026-09-14',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 100,
                    'unit_price' => 30,
                    'total' => 3000
                ]
            ],
            'subtotal' => 3000,
            'total' => 3000,
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', $saleData);

        // Controller catches Exception and returns 400 error
        $response->assertStatus(400);

        // DB Transaction rollback assertion: No sale created, no sale items created, branch inventory remains 50
        $this->assertDatabaseMissing('sales', ['invoice_number' => 'INV-FAIL-STOCK-01']);
        $this->assertDatabaseHas('branch_inventories', [
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50
        ]);
        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'sale'
        ]);
    }
}


