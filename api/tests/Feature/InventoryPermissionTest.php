<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use Database\Seeders\RolesAndPermissionsSeeder;

class InventoryPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch1;
    protected $branch2;
    protected $owner;
    protected $manager;
    protected $salesperson;
    protected $tokenOwner;
    protected $tokenManager;
    protected $tokenSalesperson;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'RBAC Inventory Business']);
        $this->branch1 = Branch::create(['business_id' => $this->business->id, 'name' => 'Branch One', 'is_primary' => true]);
        $this->branch2 = Branch::create(['business_id' => $this->business->id, 'name' => 'Branch Two', 'is_primary' => false]);

        setPermissionsTeamId($this->business->id);

        // Owner
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');
        $this->tokenOwner = $this->owner->createToken('owner')->plainTextToken;

        // Branch Manager (Assigned to Branch 1)
        $this->manager = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->assignRole('Branch Manager');
        $this->tokenManager = $this->manager->createToken('manager')->plainTextToken;

        // Salesperson (Assigned to Branch 1)
        $this->salesperson = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->assignRole('Salesperson');
        $this->tokenSalesperson = $this->salesperson->createToken('sales')->plainTextToken;

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'RBAC Item',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 50,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 30,
            'minimum_stock' => 5
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 20,
            'minimum_stock' => 5
        ]);
    }

    public function test_salesperson_can_view_inventory_balances_but_cannot_view_stock_movements()
    {
        $resBal = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenSalesperson}",
            'X-Business-ID' => $this->business->id
        ])->getJson('/api/v1/inventory');

        $resBal->assertStatus(200);

        // Salesperson does not have view stock movements permission
        $resMovSales = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenSalesperson}",
            'X-Business-ID' => $this->business->id
        ])->getJson('/api/v1/inventory/movements');

        $resMovSales->assertStatus(403);
    }

    public function test_owner_and_manager_can_view_stock_movements()
    {
        // Business Owner has view stock movements permission
        $resMovOwner = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenOwner}",
            'X-Business-ID' => $this->business->id
        ])->getJson('/api/v1/inventory/movements');

        $resMovOwner->assertStatus(200);

        // Branch Manager has view stock movements permission
        $resMovMgr = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenManager}",
            'X-Business-ID' => $this->business->id
        ])->getJson('/api/v1/inventory/movements');

        $resMovMgr->assertStatus(200);
    }

    public function test_salesperson_cannot_perform_manual_stock_adjustment()
    {
        $payload = [
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment_in',
            'quantity' => 10,
            'notes' => 'Salesperson attempt'
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenSalesperson}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/inventory/adjustments', $payload);

        $response->assertStatus(403);
    }

    public function test_salesperson_cannot_perform_stock_transfer()
    {
        $payload = [
            'from_branch_id' => $this->branch1->id,
            'to_branch_id' => $this->branch2->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'notes' => 'Salesperson transfer attempt'
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenSalesperson}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/inventory/transfers', $payload);

        $response->assertStatus(403);
    }

    public function test_branch_manager_cannot_adjust_stock_for_unassigned_branch()
    {
        // Manager belongs to Branch 1. Attempting to adjust Branch 2 stock should fail
        $payload = [
            'branch_id' => $this->branch2->id,
            'product_id' => $this->product->id,
            'type' => 'adjustment_in',
            'quantity' => 10,
            'notes' => 'Cross-branch manager adjustment'
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenManager}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/inventory/adjustments', $payload);

        $response->assertStatus(403);
    }
}
