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

class InventoryTransferTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA1;
    protected $branchA2;
    protected $branchB1;
    protected $ownerA;
    protected $tokenA;
    protected $productA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Business A with two branches
        $this->businessA = Business::create(['name' => 'Transfer Business A']);
        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Source Branch', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Dest Branch', 'is_primary' => false]);

        setPermissionsTeamId($this->businessA->id);

        $this->ownerA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id
        ]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole('Business Owner');
        $this->tokenA = $this->ownerA->createToken('tokenA')->plainTextToken;

        // Business B with foreign branch
        $this->businessB = Business::create(['name' => 'Transfer Business B']);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Foreign Branch', 'is_primary' => true]);

        // Product in Business A
        $this->productA = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Transferrable Goods',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 80,
            'unit' => 'pcs'
        ]);

        // Source branch has 80 pcs, destination branch has 0 pcs
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 80,
            'minimum_stock' => 10
        ]);

        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 0,
            'minimum_stock' => 5
        ]);
    }

    public function test_authorized_user_can_transfer_stock_between_branches()
    {
        $payload = [
            'from_branch_id' => $this->branchA1->id,
            'to_branch_id' => $this->branchA2->id,
            'product_id' => $this->productA->id,
            'quantity' => 20,
            'notes' => 'Weekly stock rebalancing'
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/inventory/transfers', $payload);

        $response->assertStatus(201);

        // Source branch updated to 60
        $this->assertDatabaseHas('branch_inventories', [
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 60
        ]);

        // Destination branch updated to 20
        $this->assertDatabaseHas('branch_inventories', [
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 20
        ]);

        // Paired movements created
        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'type' => 'transfer_out',
            'quantity' => -20
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'product_id' => $this->productA->id,
            'type' => 'transfer_in',
            'quantity' => 20
        ]);
    }

    public function test_transfer_with_insufficient_source_stock_fails()
    {
        // Attempt to transfer 150 pcs when source branch only has 80
        $payload = [
            'from_branch_id' => $this->branchA1->id,
            'to_branch_id' => $this->branchA2->id,
            'product_id' => $this->productA->id,
            'quantity' => 150,
            'notes' => 'Excessive transfer'
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/inventory/transfers', $payload);

        $response->assertStatus(422);

        // Balances remain intact
        $this->assertDatabaseHas('branch_inventories', [
            'branch_id' => $this->branchA1->id,
            'quantity_on_hand' => 80
        ]);
        $this->assertDatabaseHas('branch_inventories', [
            'branch_id' => $this->branchA2->id,
            'quantity_on_hand' => 0
        ]);
    }

    public function test_transfer_to_foreign_tenant_branch_fails()
    {
        // Attempting to transfer to Branch B1 belonging to Business B
        $payload = [
            'from_branch_id' => $this->branchA1->id,
            'to_branch_id' => $this->branchB1->id,
            'product_id' => $this->productA->id,
            'quantity' => 10,
            'notes' => 'Cross tenant transfer attack'
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/inventory/transfers', $payload);

        // Should return HTTP 422 or 403 error
        $this->assertTrue(in_array($response->status(), [422, 403]));

        // Source branch balance untouched
        $this->assertDatabaseHas('branch_inventories', [
            'branch_id' => $this->branchA1->id,
            'quantity_on_hand' => 80
        ]);
    }
}
