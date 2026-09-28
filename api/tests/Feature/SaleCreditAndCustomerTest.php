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

class SaleCreditAndCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $product;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Credit Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Credit Product',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 50,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50
        ]);

        $this->customer = Customer::create(['business_id' => $this->business->id, 'name' => 'Credit Customer']);
    }

    public function test_walk_in_cash_sale_allows_null_customer_when_fully_paid()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'customer_id' => null,
            'paid_amount' => 100,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]]
        ]);

        $res->assertStatus(201);
        $this->assertNull($res->json('customer_id'));
        $this->assertEquals(0, $res->json('due_amount'));
    }

    public function test_credit_sale_without_customer_id_is_rejected()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'customer_id' => null,
            'paid_amount' => 40, // Total = 100, due = 60
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]]
        ]);

        $res->assertStatus(422);
    }

    public function test_credit_sale_updates_customer_balance_correctly()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'customer_id' => $this->customer->id,
            'paid_amount' => 40,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]]
        ]);

        $res->assertStatus(201);
        $this->assertEquals(60, $res->json('due_amount'));

        // Customer balance reflects active credit sale receivable in Phase 6
        $this->assertEquals(60, $this->customer->fresh()->balance);
    }
}
