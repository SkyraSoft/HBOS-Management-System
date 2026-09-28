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

class SalespersonPOSAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $salesperson1;
    protected $salesperson2;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'POS Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->salesperson1 = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->salesperson1->businesses()->attach($this->business->id);
        $this->salesperson1->assignRole('Salesperson');

        $this->salesperson2 = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->salesperson2->businesses()->attach($this->business->id);
        $this->salesperson2->assignRole('Salesperson');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'POS Product',
            'cost_price' => 10,
            'selling_price' => 20,
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

    public function test_salesperson_can_checkout_and_only_views_own_sales()
    {
        $token1 = $this->salesperson1->createToken('test1')->plainTextToken;
        $token2 = $this->salesperson2->createToken('test2')->plainTextToken;

        // Salesperson 1 creates sale
        $res1 = $this->actingAs($this->salesperson1, 'sanctum')->withHeaders([
            'Authorization' => "Bearer {$token1}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 40,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]]
        ]);
        $res1->assertStatus(201);
        $saleId1 = $res1->json('id');

        // Salesperson 2 creates sale
        $res2 = $this->actingAs($this->salesperson2, 'sanctum')->withHeaders([
            'Authorization' => "Bearer {$token2}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 20,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]]
        ]);
        $res2->assertStatus(201);
        $saleId2 = $res2->json('id');

        // Salesperson 1 index only shows sale 1
        $listRes1 = $this->actingAs($this->salesperson1, 'sanctum')->withHeaders([
            'Authorization' => "Bearer {$token1}",
            'X-Business-ID' => $this->business->id
        ])->getJson('/api/v1/sales');

        $listRes1->assertStatus(200);
        $this->assertCount(1, $listRes1->json());
        $this->assertEquals($saleId1, $listRes1->json('0.id'));

        // Salesperson 1 cannot show sale 2 of Salesperson 2
        $showRes2 = $this->actingAs($this->salesperson1, 'sanctum')->withHeaders([
            'Authorization' => "Bearer {$token1}",
            'X-Business-ID' => $this->business->id
        ])->getJson("/api/v1/sales/{$saleId2}");

        $showRes2->assertStatus(403);
    }
}
