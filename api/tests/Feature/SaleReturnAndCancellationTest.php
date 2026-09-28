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

class SaleReturnAndCancellationTest extends TestCase
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

        $this->business = Business::create(['name' => 'Return Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Return Product',
            'cost_price' => 20,
            'selling_price' => 50,
            'stock' => 50,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50
        ]);

        $this->customer = Customer::create(['business_id' => $this->business->id, 'name' => 'Return Customer']);
    }

    public function test_partial_sale_return_restores_stock_and_updates_status()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Sale qty 4
        $createRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 200,
            'items' => [['product_id' => $this->product->id, 'quantity' => 4]]
        ]);

        $createRes->assertStatus(201);
        $saleId = $createRes->json('id');
        $itemId = $createRes->json('items.0.id');
        $this->assertDatabaseHas('branch_inventories', ['quantity_on_hand' => 46]);

        // Return qty 2
        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'reason' => 'Customer changed mind for 2 items',
            'items' => [['sale_item_id' => $itemId, 'quantity' => 2]]
        ]);

        $returnRes->assertStatus(201);
        $this->assertEquals(100, $returnRes->json('refund_amount'));
        $this->assertDatabaseHas('branch_inventories', ['quantity_on_hand' => 48]);
        $this->assertDatabaseHas('sales', ['id' => $saleId, 'status' => 'partially_returned']);

        // Attempt returning 3 more items (only 2 remain) -> 422
        $excessRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'items' => [['sale_item_id' => $itemId, 'quantity' => 3]]
        ]);

        $excessRes->assertStatus(422);

        // Return remaining 2 items -> full return
        $fullRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'items' => [['sale_item_id' => $itemId, 'quantity' => 2]]
        ]);

        $fullRes->assertStatus(201);
        $this->assertDatabaseHas('branch_inventories', ['quantity_on_hand' => 50]);
        $this->assertDatabaseHas('sales', ['id' => $saleId, 'status' => 'returned']);
    }

    public function test_sale_level_discount_and_tax_allocation_across_returns()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Sale 4 units @ 50 = subtotal 200. Discount = 20 (10%), Tax = 18 (10% of 180). Total = 198.
        $createRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 198,
            'discount' => 20,
            'tax' => 18,
            'items' => [['product_id' => $this->product->id, 'quantity' => 4]]
        ]);

        $createRes->assertStatus(201);
        $saleId = $createRes->json('id');
        $itemId = $createRes->json('items.0.id');
        $this->assertEquals(200, $createRes->json('subtotal'));
        $this->assertEquals(198, $createRes->json('total'));

        // Return 2 of 4 units -> proportional refund = (200 / 4 * 0.99) * 2 = 99.00
        $returnRes1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'reason' => 'Proportional return test 1',
            'items' => [['sale_item_id' => $itemId, 'quantity' => 2]]
        ]);

        $returnRes1->assertStatus(201);
        $this->assertEquals(99.00, $returnRes1->json('refund_amount'));

        // Return remaining 2 units -> proportional refund = 99.00
        $returnRes2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'reason' => 'Proportional return test 2',
            'items' => [['sale_item_id' => $itemId, 'quantity' => 2]]
        ]);

        $returnRes2->assertStatus(201);
        $this->assertEquals(99.00, $returnRes2->json('refund_amount'));
    }
}
