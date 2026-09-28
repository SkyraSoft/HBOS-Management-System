<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;

class PurchaseRelationshipIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA;
    protected $branchB;
    protected $ownerA;
    protected $supplierA;
    protected $supplierB;
    protected $productA;
    protected $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Business A
        $this->businessA = Business::create(['name' => 'Business A']);
        $this->branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A', 'is_primary' => true]);

        setPermissionsTeamId($this->businessA->id);
        $this->ownerA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA->id]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole('Business Owner');

        $this->supplierA = Supplier::create(['business_id' => $this->businessA->id, 'name' => 'Supplier A', 'code' => 'SUP-A']);
        $this->productA = Product::create(['business_id' => $this->businessA->id, 'name' => 'Product A', 'cost_price' => 10, 'selling_price' => 20, 'stock' => 0]);

        // Business B
        $this->businessB = Business::create(['name' => 'Business B']);
        $this->branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);

        $this->supplierB = Supplier::create(['business_id' => $this->businessB->id, 'name' => 'Supplier B', 'code' => 'SUP-B']);
        $this->productB = Product::create(['business_id' => $this->businessB->id, 'name' => 'Product B', 'cost_price' => 15, 'selling_price' => 30, 'stock' => 0]);
    }

    public function test_purchase_with_foreign_tenant_supplier_fails_atomically()
    {
        $token = $this->ownerA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $this->supplierB->id, // Foreign supplier from Business B
            'po_number' => 'PO-REL-01',
            'items' => [['product_id' => $this->productA->id, 'quantity' => 5, 'unit_cost' => 10]]
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('purchases', ['po_number' => 'PO-REL-01']);
    }

    public function test_purchase_with_foreign_tenant_product_fails_atomically()
    {
        $token = $this->ownerA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $this->supplierA->id,
            'po_number' => 'PO-REL-02',
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 5, 'unit_cost' => 10],
                ['product_id' => $this->productB->id, 'quantity' => 5, 'unit_cost' => 15] // Foreign product from Business B
            ]
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('purchases', ['po_number' => 'PO-REL-02']);
    }

    public function test_purchase_with_foreign_tenant_branch_fails_atomically()
    {
        $token = $this->ownerA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/purchases', [
            'branch_id' => $this->branchB->id, // Foreign branch from Business B
            'supplier_id' => $this->supplierA->id,
            'po_number' => 'PO-REL-03',
            'items' => [['product_id' => $this->productA->id, 'quantity' => 5, 'unit_cost' => 10]]
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('purchases', ['po_number' => 'PO-REL-03']);
    }
}
