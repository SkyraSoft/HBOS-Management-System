<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;

class SalePricingAndTotalsTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Pricing Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Pricing Product',
            'cost_price' => 19.99,
            'selling_price' => 49.99,
            'stock' => 100,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 100
        ]);
    }

    public function test_server_calculates_totals_and_ignores_forged_client_subtotal()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'subtotal' => 1.00, // Forged
            'total' => 1.00,    // Forged
            'paid_amount' => 149.97,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 3] // 3 * 49.99 = 149.97
            ]
        ]);

        $res->assertStatus(201);
        $this->assertEquals(149.97, $res->json('subtotal'));
        $this->assertEquals(149.97, $res->json('total'));
        $this->assertEquals(19.99, $res->json('items.0.cost_price')); // Historical COGS snapshot
    }

    public function test_cogs_snapshot_remains_immutable_when_catalog_cost_price_changes()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]]
        ]);

        $res->assertStatus(201);
        $this->assertEquals(19.99, $res->json('items.0.cost_price'));

        // Update catalog product cost price
        $this->product->update(['cost_price' => 29.99]);

        // Re-fetch sale and verify snapshot is unchanged
        $saleId = $res->json('id');
        $showRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->getJson("/api/v1/sales/{$saleId}");

        $showRes->assertStatus(200);
        $this->assertEquals(19.99, $showRes->json('items.0.cost_price'));
    }
}
