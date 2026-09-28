<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\InventoryMovement;
use Database\Seeders\RolesAndPermissionsSeeder;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $token;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'Adjustment Test Business']);
        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'is_primary' => true
        ]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');
        $this->token = $this->owner->createToken('token')->plainTextToken;

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Adjustment Product',
            'cost_price' => 20,
            'selling_price' => 40,
            'stock' => 30,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 30,
            'minimum_stock' => 5
        ]);
    }

    public function test_positive_stock_adjustment_increases_branch_balance()
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment_in',
            'quantity' => 15,
            'notes' => 'Found uncounted stock in warehouse',
            'unit_cost' => 20
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/inventory/adjustments', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('branch_inventories', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 45
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment_in',
            'quantity' => 15,
            'notes' => 'Found uncounted stock in warehouse'
        ]);
    }

    public function test_negative_damage_stock_adjustment_decreases_branch_balance()
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'damage',
            'quantity' => 5,
            'notes' => 'Damaged during water leak',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/inventory/adjustments', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('branch_inventories', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 25
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'damage',
            'quantity' => -5
        ]);
    }

    public function test_excessive_stock_reduction_violating_non_negative_balance_fails_atomically()
    {
        // Current stock is 30. Attempting to reduce by 100 should fail
        $payload = [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment_out',
            'quantity' => 100,
            'notes' => 'Attempting massive inventory reduction',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/inventory/adjustments', $payload);

        $response->assertStatus(422);

        // Branch inventory balance remains untouched at 30
        $this->assertDatabaseHas('branch_inventories', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 30
        ]);

        // No movement record created
        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'adjustment_out',
            'quantity' => -100
        ]);
    }
}
