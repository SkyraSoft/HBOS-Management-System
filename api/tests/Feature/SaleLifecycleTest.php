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

class SaleLifecycleTest extends TestCase
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

        $this->business = Business::create(['name' => 'Lifecycle Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Lifecycle Product',
            'cost_price' => 20,
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

        $this->customer = Customer::create(['business_id' => $this->business->id, 'name' => 'Lifecycle Customer']);
    }

    public function test_posted_sale_cannot_be_hard_deleted()
    {
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-LIFE-01',
            'date' => date('Y-m-d'),
            'subtotal' => 100,
            'total' => 100,
            'paid_amount' => 100,
            'status' => 'completed'
        ]);

        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->deleteJson("/api/v1/sales/{$sale->id}");

        $res->assertStatus(422);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }

    public function test_paid_sale_direct_cancellation_is_blocked()
    {
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-LIFE-02',
            'date' => date('Y-m-d'),
            'subtotal' => 100,
            'total' => 100,
            'paid_amount' => 100,
            'due_amount' => 0,
            'status' => 'completed'
        ]);

        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$sale->id}/cancel", ['reason' => 'Direct cancel attempt']);

        $res->assertStatus(422);
        $this->assertEquals('completed', $sale->fresh()->status);
    }

    public function test_unpaid_credit_sale_cancellation_succeeds_and_restores_stock()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $createRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'customer_id' => $this->customer->id,
            'paid_amount' => 0,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5]]
        ]);

        $createRes->assertStatus(201);
        $saleId = $createRes->json('id');
        $this->assertDatabaseHas('branch_inventories', ['quantity_on_hand' => 45]);

        $cancelRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/cancel", ['reason' => 'Customer order cancelled before dispatch']);

        $cancelRes->assertStatus(200);
        $this->assertEquals('cancelled', $cancelRes->json('status'));
        $this->assertDatabaseHas('branch_inventories', ['quantity_on_hand' => 50]);
    }
}
