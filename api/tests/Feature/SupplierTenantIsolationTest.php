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

class SupplierTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $userA;
    protected $userB;
    protected $supplierA;
    protected $supplierB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Business A
        $this->businessA = Business::create(['name' => 'Business A']);
        $branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A', 'is_primary' => true]);
        
        setPermissionsTeamId($this->businessA->id);
        $this->userA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $branchA->id,
            'password' => bcrypt('password123')
        ]);
        $this->userA->businesses()->attach($this->businessA->id);
        $this->userA->assignRole('Business Owner');

        $this->supplierA = Supplier::create([
            'business_id' => $this->businessA->id,
            'name' => 'Supplier A',
            'code' => 'SUP-A'
        ]);

        // Business B
        $this->businessB = Business::create(['name' => 'Business B']);
        $branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);

        setPermissionsTeamId($this->businessB->id);
        $this->userB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'branch_id' => $branchB->id,
            'password' => bcrypt('password123')
        ]);
        $this->userB->businesses()->attach($this->businessB->id);
        $this->userB->assignRole('Business Owner');

        $this->supplierB = Supplier::create([
            'business_id' => $this->businessB->id,
            'name' => 'Supplier B',
            'code' => 'SUP-B'
        ]);
    }

    public function test_user_in_business_a_cannot_read_business_b_supplier()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->businessA->id
        ])->getJson("/api/v1/suppliers/{$this->supplierB->id}");

        $response->assertStatus(404);
    }

    public function test_user_in_business_a_cannot_update_business_b_supplier()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->businessA->id
        ])->putJson("/api/v1/suppliers/{$this->supplierB->id}", [
            'name' => 'Hacked Supplier'
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplierB->id, 'name' => 'Supplier B']);
    }

    public function test_user_in_business_a_cannot_delete_business_b_supplier()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->businessA->id
        ])->deleteJson("/api/v1/suppliers/{$this->supplierB->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplierB->id]);
    }

    public function test_supplier_creation_with_spoofed_business_id_remains_scoped_to_active_business()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/suppliers', [
            'name' => 'New Supplier',
            'business_id' => $this->businessB->id // Malicious spoof attempt
        ]);

        $response->assertStatus(201);
        $supplierId = $response->json('id');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplierId,
            'business_id' => $this->businessA->id // Must be forced to Business A
        ]);
    }

    public function test_purchase_creation_with_foreign_tenant_supplier_fails()
    {
        $token = $this->userA->createToken('test')->plainTextToken;

        $productA = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Product A',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 0
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $this->supplierB->id, // Foreign supplier from Business B
            'po_number' => 'PO-FOREIGN-SUP-01',
            'date' => '2026-09-14',
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 10,
                    'unit_cost' => 10
                ]
            ]
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('purchases', ['po_number' => 'PO-FOREIGN-SUP-01']);
    }
}
