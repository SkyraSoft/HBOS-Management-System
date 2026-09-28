<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\Customer;
use App\Models\Sale;

class SalePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $manager;
    protected $salesperson;
    protected $product;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'RBAC Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->manager = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->assignRole('Branch Manager');

        $this->salesperson = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->assignRole('Salesperson');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'RBAC Product',
            'cost_price' => 10,
            'selling_price' => 50,
            'stock' => 100,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 100
        ]);

        $this->customer = Customer::create(['business_id' => $this->business->id, 'name' => 'RBAC Customer']);
    }

    public function test_salesperson_cannot_cancel_sale()
    {
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->salesperson->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-SP-01',
            'date' => date('Y-m-d'),
            'subtotal' => 50,
            'total' => 50,
            'paid_amount' => 0,
            'due_amount' => 50,
            'status' => 'completed'
        ]);

        $token = $this->salesperson->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$sale->id}/cancel", ['reason' => 'Salesperson cancel attempt']);

        $res->assertStatus(403);
    }

    public function test_salesperson_cannot_process_sale_return()
    {
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->salesperson->id,
            'invoice_number' => 'INV-SP-02',
            'date' => date('Y-m-d'),
            'subtotal' => 50,
            'total' => 50,
            'paid_amount' => 50,
            'status' => 'completed'
        ]);

        $item = $sale->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 50,
            'total' => 50
        ]);

        $token = $this->salesperson->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$sale->id}/returns", [
            'items' => [['sale_item_id' => $item->id, 'quantity' => 1]]
        ]);

        $res->assertStatus(403);
    }

    public function test_salesperson_cannot_override_product_selling_price()
    {
        $token = $this->salesperson->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10] // Attempting to lower selling price from 50 to 10
            ]
        ]);

        $res->assertStatus(422);
    }

    public function test_owner_can_override_product_selling_price()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 45]
            ]
        ]);

        $res->assertStatus(201);
        $this->assertEquals(45, $res->json('items.0.unit_price'));
    }
}
