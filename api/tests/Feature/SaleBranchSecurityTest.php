<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\Sale;

class SaleBranchSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch1;
    protected $branch2;
    protected $owner;
    protected $manager1;
    protected $salesperson1;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Branch Test Business']);
        $this->branch1 = Branch::create(['business_id' => $this->business->id, 'name' => 'Branch 1', 'is_primary' => true]);
        $this->branch2 = Branch::create(['business_id' => $this->business->id, 'name' => 'Branch 2', 'is_primary' => false]);

        setPermissionsTeamId($this->business->id);

        // Owner
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        // Branch Manager for Branch 1
        $this->manager1 = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->manager1->businesses()->attach($this->business->id);
        $this->manager1->assignRole('Branch Manager');

        // Salesperson for Branch 1
        $this->salesperson1 = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->salesperson1->businesses()->attach($this->business->id);
        $this->salesperson1->assignRole('Salesperson');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id, 'name' => 'Drawer 1', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);
        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch2->id, 'name' => 'Drawer 2', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Multi-Branch Product',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 100,
            'unit' => 'pcs'
        ]);

        BranchInventory::create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id, 'product_id' => $this->product->id, 'quantity_on_hand' => 50]);
        BranchInventory::create(['business_id' => $this->business->id, 'branch_id' => $this->branch2->id, 'product_id' => $this->product->id, 'quantity_on_hand' => 50]);
    }

    public function test_owner_can_create_sale_in_any_authorized_branch()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch2->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]]
        ]);

        $res->assertStatus(201);
        $this->assertEquals($this->branch2->id, $res->json('branch_id'));
    }

    public function test_manager_cannot_create_sale_in_unassigned_branch()
    {
        $token = $this->manager1->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch2->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]]
        ]);

        $res->assertStatus(422);
    }

    public function test_manager_cannot_view_unassigned_branch_sale()
    {
        $sale2 = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-BR2-001',
            'date' => date('Y-m-d'),
            'subtotal' => 40,
            'total' => 40,
            'paid_amount' => 40,
            'status' => 'completed'
        ]);

        $token = $this->manager1->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->getJson("/api/v1/sales/{$sale2->id}");

        $res->assertStatus(403);
    }
}
