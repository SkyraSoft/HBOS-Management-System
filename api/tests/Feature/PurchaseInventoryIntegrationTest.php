<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;

class PurchaseInventoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'Inventory Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->product = Product::create(['business_id' => $this->business->id, 'name' => 'Stock Product', 'cost_price' => 10, 'selling_price' => 20, 'stock' => 0]);
    }

    public function test_purchase_creation_updates_branch_inventory_and_logs_movement()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/purchases', [
            'po_number' => 'PO-INV-01',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 25, 'unit_cost' => 10]
            ]
        ]);

        $response->assertStatus(201);

        // BranchInventory on_hand becomes 25
        $this->assertDatabaseHas('branch_inventories', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 25
        ]);

        // Movement appended
        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'purchase',
            'quantity' => 25,
            'unit_cost' => 10
        ]);
    }

    public function test_purchase_creation_rolls_back_atomically_if_item_fails()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // One valid product, one invalid product ID 99999
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/purchases', [
            'po_number' => 'PO-ROLLBACK-01',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 20, 'unit_cost' => 10],
                ['product_id' => 99999, 'quantity' => 10, 'unit_cost' => 10] // Fails
            ]
        ]);

        $response->assertStatus(422);

        // Assert zero stock added to valid product
        $this->assertDatabaseMissing('branch_inventories', [
            'product_id' => $this->product->id
        ]);
        $this->assertDatabaseMissing('purchases', ['po_number' => 'PO-ROLLBACK-01']);
    }
}
