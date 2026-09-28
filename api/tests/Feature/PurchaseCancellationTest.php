<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Product;
use App\Services\InventoryService;
use Database\Seeders\RolesAndPermissionsSeeder;

class PurchaseCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $supplier;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'Cancel Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->supplier = Supplier::create(['business_id' => $this->business->id, 'name' => 'Cancel Supplier', 'code' => 'SUP-CANCEL']);
        $this->product = Product::create(['business_id' => $this->business->id, 'name' => 'Cancel Product', 'cost_price' => 10, 'selling_price' => 20, 'stock' => 0]);
    }

    public function test_unpaid_purchase_cancellation_reverses_stock_and_supplier_balance()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Create unpaid purchase of 10 items at cost 10 -> Total = 100, Due = 100
        $createRes = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'po_number' => 'PO-UNPAID-CANCEL',
                'paid_amount' => 0,
                'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_cost' => 10]]
            ]);

        $createRes->assertStatus(201);
        $purchaseId = $createRes->json('id');

        // Supplier balance is 100
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id, 'balance' => 100.00]);
        // Branch stock is 10
        $this->assertDatabaseHas('branch_inventories', ['product_id' => $this->product->id, 'quantity_on_hand' => 10]);

        // Cancel the purchase
        $cancelRes = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/purchases/{$purchaseId}/cancel", ['reason' => 'Defective goods']);

        $cancelRes->assertStatus(200);

        // Purchase status becomes cancelled
        $this->assertDatabaseHas('purchases', [
            'id' => $purchaseId,
            'status' => 'cancelled',
            'cancellation_reason' => 'Defective goods',
            'cancelled_by' => $this->owner->id
        ]);

        // Compensating stock movement purchase_return appended
        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'type' => 'purchase_return',
            'quantity' => -10
        ]);

        // Branch stock restored to 0
        $this->assertDatabaseHas('branch_inventories', ['product_id' => $this->product->id, 'quantity_on_hand' => 0]);

        // Supplier balance reversed to 0
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id, 'balance' => 0.00]);
    }

    public function test_paid_or_partially_paid_purchase_cancellation_is_rejected()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Create partially paid purchase: Total = 200, Paid = 50, Due = 150
        $createRes = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'po_number' => 'PO-PAID-CANCEL-REJECT',
                'paid_amount' => 50,
                'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_cost' => 20]]
            ]);

        $createRes->assertStatus(201);
        $purchaseId = $createRes->json('id');

        // Supplier balance is 150 (due)
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id, 'balance' => 150.00]);

        // Attempting to cancel a paid/partially-paid purchase MUST fail with 422
        $cancelRes = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/purchases/{$purchaseId}/cancel");

        $cancelRes->assertStatus(422);
        $cancelRes->assertJsonFragment([
            'error' => 'Purchase with recorded payments cannot be cancelled directly. Recorded payments must be resolved first.'
        ]);

        // Status remains received
        $this->assertDatabaseHas('purchases', ['id' => $purchaseId, 'status' => 'received']);
        // Supplier balance remains 150
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id, 'balance' => 150.00]);
    }

    public function test_cancellation_fails_if_available_stock_is_insufficient()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Receive 20 stock via purchase
        $createRes = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'po_number' => 'PO-INSUFFICIENT-STOCK-CANCEL',
                'paid_amount' => 0,
                'items' => [['product_id' => $this->product->id, 'quantity' => 20, 'unit_cost' => 10]]
            ]);

        $createRes->assertStatus(201);
        $purchaseId = $createRes->json('id');

        // Consume 15 stock via damage adjustment (on hand becomes 5)
        $inventoryService = app(InventoryService::class);
        $inventoryService->issueStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
            'type' => 'damage',
            'performed_by' => $this->owner->id
        ]);

        $this->assertEquals(5, $inventoryService->getBranchBalance($this->product->id, $this->branch->id));

        // Attempting to cancel purchase requiring 20 stock when only 5 is available MUST fail
        $cancelRes = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/purchases/{$purchaseId}/cancel");

        $cancelRes->assertStatus(422);

        // Status remains received, stock remains 5, supplier balance remains 200
        $this->assertDatabaseHas('purchases', ['id' => $purchaseId, 'status' => 'received']);
        $this->assertEquals(5, $inventoryService->getBranchBalance($this->product->id, $this->branch->id));
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id, 'balance' => 200.00]);
    }

    public function test_cancellation_is_idempotent()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $createRes = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'po_number' => 'PO-IDEMPOTENT-CANCEL',
                'paid_amount' => 0,
                'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 10]]
            ]);
        $purchaseId = $createRes->json('id');

        // First cancellation succeeds
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/purchases/{$purchaseId}/cancel")
            ->assertStatus(200);

        // Second cancellation fails (422)
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/purchases/{$purchaseId}/cancel")
            ->assertStatus(422);
    }
}
