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

class BranchInventoryIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA1;
    protected $branchA2;
    protected $branchB1;
    protected $ownerA;
    protected $ownerB;
    protected $tokenA;
    protected $tokenB;
    protected $productA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Business A with two branches
        $this->businessA = Business::create(['name' => 'Business Alpha']);
        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Alpha Primary', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Alpha Secondary', 'is_primary' => false]);

        setPermissionsTeamId($this->businessA->id);
        $this->ownerA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole('Business Owner');
        $this->tokenA = $this->ownerA->createToken('tokenA')->plainTextToken;

        // Business B with one branch
        $this->businessB = Business::create(['name' => 'Business Beta']);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Beta Primary', 'is_primary' => true]);

        setPermissionsTeamId($this->businessB->id);
        $this->ownerB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB1->id]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole('Business Owner');
        $this->tokenB = $this->ownerB->createToken('tokenB')->plainTextToken;

        \App\Models\FinancialAccount::create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id, 'name' => 'Drawer A1', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);
        \App\Models\FinancialAccount::create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA2->id, 'name' => 'Drawer A2', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);
        \App\Models\FinancialAccount::create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB1->id, 'name' => 'Drawer B1', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        // Product A in Business A
        $this->productA = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Multi-Branch Widget',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 150,
            'unit' => 'pcs'
        ]);

        // Branch A1 has 100, Branch A2 has 50
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 100,
            'minimum_stock' => 10
        ]);

        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 50,
            'minimum_stock' => 5
        ]);
    }

    public function test_product_stock_represents_cumulative_quantity_across_branches()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id
        ])->getJson("/api/v1/inventory?product_id={$this->productA->id}");

        $response->assertStatus(200);
        $data = $response->json();

        // Should return 2 branch inventory records totaling 150
        $totalStock = array_sum(array_column($data, 'quantity_on_hand'));
        $this->assertEquals(150, $totalStock);
    }

    public function test_business_b_cannot_view_business_a_branch_inventory()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenB}",
            'X-Business-ID' => $this->businessB->id
        ])->getJson("/api/v1/inventory?product_id={$this->productA->id}");

        $response->assertStatus(200);
        $data = $response->json();

        // Business B query must return empty array
        $this->assertEmpty($data);
    }

    public function test_business_b_cannot_view_business_a_inventory_movements()
    {
        InventoryMovement::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'type' => 'opening',
            'quantity' => 100,
            'performed_by' => $this->ownerA->id,
            'notes' => 'Alpha Opening'
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenB}",
            'X-Business-ID' => $this->businessB->id
        ])->getJson('/api/v1/inventory/movements');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEmpty($data);
    }

    public function test_ambiguous_branch_request_resolves_to_primary_branch()
    {
        // Sale request without explicit branch_id should use user's branch / primary branch
        $saleData = [
            'invoice_number' => 'INV-AUTO-BRANCH-01',
            'date' => '2026-09-14',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 5,
                    'unit_price' => 20,
                    'total' => 100
                ]
            ],
            'subtotal' => 100,
            'total' => 100,
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/sales', $saleData);

        $response->assertStatus(201);

        // Movement should be attached to Branch A1
        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'type' => 'sale',
            'quantity' => -5
        ]);
    }

    public function test_multibranch_product_stock_cumulative_aggregate_across_transfers()
    {
        $inventoryService = app(\App\Services\InventoryService::class);

        // Create clean test product
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Deterministic Transfer Widget',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 30,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
            'minimum_stock' => 2
        ]);

        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 20,
            'minimum_stock' => 2
        ]);

        // 1. Initial State: A=10, B=20 => Aggregate = 30
        $sumInitial = BranchInventory::where('product_id', $product->id)->sum('quantity_on_hand');
        $this->assertEquals(30, $sumInitial);

        // 2. Issue 3 from Branch A1 -> A=7, B=20 => Aggregate = 27
        $inventoryService->issueStock([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'type' => 'sale',
            'performed_by' => $this->ownerA->id
        ]);

        $this->assertEquals(7, $inventoryService->getBranchBalance($product->id, $this->branchA1->id));
        $this->assertEquals(20, $inventoryService->getBranchBalance($product->id, $this->branchA2->id));
        $sumAfterIssue = BranchInventory::where('product_id', $product->id)->sum('quantity_on_hand');
        $this->assertEquals(27, $sumAfterIssue);
        $this->assertEquals(27, $product->fresh()->stock);

        // 3. Receive 5 into Branch A2 -> A=7, B=25 => Aggregate = 32
        $inventoryService->receiveStock([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'type' => 'purchase',
            'performed_by' => $this->ownerA->id
        ]);

        $this->assertEquals(7, $inventoryService->getBranchBalance($product->id, $this->branchA1->id));
        $this->assertEquals(25, $inventoryService->getBranchBalance($product->id, $this->branchA2->id));
        $sumAfterReceive = BranchInventory::where('product_id', $product->id)->sum('quantity_on_hand');
        $this->assertEquals(32, $sumAfterReceive);
        $this->assertEquals(32, $product->fresh()->stock);

        // 4. Transfer 4 from Branch A2 to Branch A1 -> A=11, B=21 => Aggregate MUST remain 32
        $inventoryService->transferStock([
            'business_id' => $this->businessA->id,
            'from_branch_id' => $this->branchA2->id,
            'to_branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'performed_by' => $this->ownerA->id
        ]);

        $this->assertEquals(11, $inventoryService->getBranchBalance($product->id, $this->branchA1->id));
        $this->assertEquals(21, $inventoryService->getBranchBalance($product->id, $this->branchA2->id));
        $sumAfterTransfer = BranchInventory::where('product_id', $product->id)->sum('quantity_on_hand');
        $this->assertEquals(32, $sumAfterTransfer);
        $this->assertEquals(32, $product->fresh()->stock);
    }

    public function test_product_update_via_api_cannot_mutate_branch_inventory_balances_or_minimum_stock()
    {
        // Malicious PUT /api/v1/products/{id} with stock=9999 and min_stock=9999
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenA}",
            'X-Business-ID' => $this->businessA->id
        ])->putJson("/api/v1/products/{$this->productA->id}", [
            'name' => 'Updated Widget Name',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 9999,
            'min_stock' => 9999
        ]);

        $response->assertStatus(200);

        // Branch A1 quantity on hand MUST remain 100, minimum_stock MUST remain 10
        $this->assertDatabaseHas('branch_inventories', [
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 100,
            'minimum_stock' => 10
        ]);

        // Branch A2 quantity on hand MUST remain 50, minimum_stock MUST remain 5
        $this->assertDatabaseHas('branch_inventories', [
            'branch_id' => $this->branchA2->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 50,
            'minimum_stock' => 5
        ]);
    }
}


