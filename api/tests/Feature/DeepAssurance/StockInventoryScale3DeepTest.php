<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Deep Assurance: Inventory, Stock Movements, Scale-3 Precision, and Inter-Branch Transfers
 */
class StockInventoryScale3DeepTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch1;
    protected $branch2;
    protected $owner;
    protected $discreteProduct;
    protected $fractionalProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->business = Business::create([
            'name' => 'Scale3 Retailer',
            'legal_name' => 'Scale3 Retailer Legal',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->branch1 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'code' => 'MAIN-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->branch2 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Sub Branch',
            'code' => 'SUB-02',
            'is_primary' => false,
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Inventory Owner',
            'email' => 'inv_owner@scale3.test',
            'password' => bcrypt('Secret123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);

        setPermissionsTeamId($this->business->id);
        $this->owner->assignRole('Business Owner');

        // Discrete Product (e.g. shampoo bottle)
        $this->discreteProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Discrete Shampoo 200ml',
            'sku' => 'SHP-200',
            'barcode' => '896400011111',
            'retail_price' => 350.00,
            'cost_price' => 250.00,
            'allow_fractional' => false,
            'status' => 'active',
        ]);

        // Fractional Product (e.g. sugar in kg)
        $this->fractionalProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Loose Sugar Premium (KG)',
            'sku' => 'SGR-KG',
            'barcode' => '896400022222',
            'retail_price' => 150.00,
            'cost_price' => 120.00,
            'allow_fractional' => true,
            'unit_type' => 'kg',
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->discreteProduct->id,
            'quantity_on_hand' => 20.000,
            'minimum_stock' => 5.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->fractionalProduct->id,
            'quantity_on_hand' => 50.250,
            'minimum_stock' => 10.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'product_id' => $this->discreteProduct->id,
            'quantity_on_hand' => 5.000,
            'minimum_stock' => 2.000,
        ]);
    }

    public function test_inventory_listing_scoped_to_active_tenant(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/inventory');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data);
        foreach ($data as $inv) {
            $this->assertEquals($this->business->id, $inv['business_id']);
        }
    }

    public function test_inventory_listing_branch_filter(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/inventory?branch_id=' . $this->branch2->id);

        $response->assertStatus(200);
        $data = $response->json();
        foreach ($data as $inv) {
            $this->assertEquals($this->branch2->id, $inv['branch_id']);
        }
    }

    public function test_inventory_stock_adjustment_in_increases_quantity(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/adjustments', [
                'branch_id' => $this->branch1->id,
                'product_id' => $this->discreteProduct->id,
                'type' => 'adjustment_in',
                'quantity' => 10,
                'notes' => 'Found unopened box in backroom',
            ]);

        $response->assertStatus(201);
        $inv = BranchInventory::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertEquals(30.000, (float)$inv->quantity_on_hand);
    }

    public function test_inventory_stock_adjustment_out_decreases_quantity(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/adjustments', [
                'branch_id' => $this->branch1->id,
                'product_id' => $this->discreteProduct->id,
                'type' => 'adjustment_out',
                'quantity' => 4,
                'notes' => 'Inventory count audit correction',
            ]);

        $response->assertStatus(201);
        $inv = BranchInventory::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertEquals(16.000, (float)$inv->quantity_on_hand);
    }

    public function test_inventory_stock_adjustment_damage_decreases_quantity(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/adjustments', [
                'branch_id' => $this->branch1->id,
                'product_id' => $this->discreteProduct->id,
                'type' => 'damage',
                'quantity' => 2,
                'notes' => 'Broken bottle during shelf stocking',
            ]);

        $response->assertStatus(201);
        $inv = BranchInventory::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertEquals(18.000, (float)$inv->quantity_on_hand);
    }

    public function test_inventory_discrete_product_fractional_adjustment_rejected(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/adjustments', [
                'branch_id' => $this->branch1->id,
                'product_id' => $this->discreteProduct->id,
                'type' => 'adjustment_in',
                'quantity' => 1.5,
                'notes' => 'Attempting half bottle',
            ]);

        $response->assertStatus(422);
    }

    public function test_inventory_fractional_allowed_product_scale_3_adjustment_success(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/adjustments', [
                'branch_id' => $this->branch1->id,
                'product_id' => $this->fractionalProduct->id,
                'type' => 'adjustment_in',
                'quantity' => 2.375, // 2kg 375 grams
                'notes' => 'Scale-3 accurate loose bag intake',
            ]);

        $response->assertStatus(201);
        $inv = BranchInventory::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->fractionalProduct->id)
            ->first();
        // 50.250 + 2.375 = 52.625
        $this->assertEquals(52.625, (float)$inv->quantity_on_hand);
    }

    public function test_inventory_adjustment_out_exceeding_on_hand_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/adjustments', [
                'branch_id' => $this->branch1->id,
                'product_id' => $this->discreteProduct->id,
                'type' => 'adjustment_out',
                'quantity' => 999,
                'notes' => 'Excessive writeoff beyond stock',
            ]);

        $response->assertStatus(422);
    }

    public function test_inventory_transfer_between_branches_conserves_total_stock(): void
    {
        $initialTotal = 20.000 + 5.000;

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/transfers', [
                'from_branch_id' => $this->branch1->id,
                'to_branch_id' => $this->branch2->id,
                'product_id' => $this->discreteProduct->id,
                'quantity' => 7,
                'notes' => 'Inter-branch replenishment transfer',
            ]);

        $response->assertStatus(201);

        $fromInv = BranchInventory::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $toInv = BranchInventory::where('branch_id', $this->branch2->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();

        $this->assertEquals(13.000, (float)$fromInv->quantity_on_hand);
        $this->assertEquals(12.000, (float)$toInv->quantity_on_hand);
        $this->assertEquals($initialTotal, (float)$fromInv->quantity_on_hand + (float)$toInv->quantity_on_hand);
    }

    public function test_inventory_transfer_to_same_branch_rejected(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/transfers', [
                'from_branch_id' => $this->branch1->id,
                'to_branch_id' => $this->branch1->id,
                'product_id' => $this->discreteProduct->id,
                'quantity' => 5,
                'notes' => 'Circular transfer to self',
            ]);

        $response->assertStatus(422);
    }

    public function test_inventory_transfer_exceeding_source_stock_rejected(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/transfers', [
                'from_branch_id' => $this->branch1->id,
                'to_branch_id' => $this->branch2->id,
                'product_id' => $this->discreteProduct->id,
                'quantity' => 500,
                'notes' => 'Over-transfer',
            ]);

        $response->assertStatus(422);
    }

    public function test_inventory_movements_history_recorded_with_audit(): void
    {
        $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/inventory/adjustments', [
                'branch_id' => $this->branch1->id,
                'product_id' => $this->discreteProduct->id,
                'type' => 'adjustment_in',
                'quantity' => 3,
                'notes' => 'Audit check movement verification',
            ]);

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/inventory/movements?product_id=' . $this->discreteProduct->id);

        $response->assertStatus(200);
        $movements = $response->json();
        // Since it paginates with 50, response contains data
        $items = isset($movements['data']) ? $movements['data'] : $movements;
        $this->assertNotEmpty($items);
        $this->assertEquals($this->business->id, $items[0]['business_id']);
    }
}
