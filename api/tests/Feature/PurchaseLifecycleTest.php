<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Purchase;
use Database\Seeders\RolesAndPermissionsSeeder;

class PurchaseLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA;
    protected $branchB;
    protected $ownerA;
    protected $ownerB;
    protected $supplierA;
    protected $productA;

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

        setPermissionsTeamId($this->businessB->id);
        $this->ownerB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB->id]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole('Business Owner');

        setPermissionsTeamId(null);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_server_calculates_line_totals_subtotal_total_and_due()
    {
        $token = $this->ownerA->createToken('test')->plainTextToken;

        $purchaseData = [
            'supplier_id' => $this->supplierA->id,
            'po_number' => 'PO-SERVER-CALC-01',
            'date' => '2026-09-14',
            'paid_amount' => 100.00,
            'tax' => 20.00,
            'discount' => 10.00,

            // Client attempts to forge wrong totals
            'subtotal' => 1.00,
            'total' => 1.00,
            'due_amount' => 1.00,

            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 5,
                    'unit_cost' => 50.00,
                    'total' => 1.00 // Forged line total
                ]
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/purchases', $purchaseData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('purchases', [
            'po_number' => 'PO-SERVER-CALC-01',
            'subtotal' => 250.00,
            'total' => 260.00,
            'paid_amount' => 100.00,
            'due_amount' => 160.00
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'product_id' => $this->productA->id,
            'quantity' => 5,
            'unit_cost' => 50.00,
            'total' => 250.00
        ]);
    }

    public function test_po_number_is_unique_per_tenant(): void
    {
        setPermissionsTeamId($this->businessA->id);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create PO in Business A
        $responseA = $this->actingAs($this->ownerA)
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplierA->id,
                'po_number' => 'PO-UNIQUE-999',
                'items' => [
                    [
                        'product_id' => $this->productA->id,
                        'quantity' => 10,
                        'unit_cost' => 50,
                    ]
                ]
            ]);

        $responseA->assertStatus(201);

        // Same PO in Business A should fail validation
        $responseA2 = $this->actingAs($this->ownerA)
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplierA->id,
                'po_number' => 'PO-UNIQUE-999',
                'items' => [
                    [
                        'product_id' => $this->productA->id,
                        'quantity' => 5,
                        'unit_cost' => 50,
                    ]
                ]
            ]);

        $responseA2->assertStatus(422)
            ->assertJsonValidationErrors(['po_number']);

        // Same PO in Business B should be allowed
        setPermissionsTeamId($this->businessB->id);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $ownerB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB->id]);
        $ownerB->businesses()->attach($this->businessB->id);
        $ownerB->assignRole('Business Owner');

        $supplierB = Supplier::create(['business_id' => $this->businessB->id, 'name' => 'Supplier B', 'code' => 'SUP-B-99']);
        $productB = Product::create(['business_id' => $this->businessB->id, 'name' => 'Product B', 'cost_price' => 10, 'selling_price' => 20, 'stock' => 0]);

        $responseB = $this->actingAs($ownerB)
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $supplierB->id,
                'po_number' => 'PO-UNIQUE-999',
                'items' => [
                    [
                        'product_id' => $productB->id,
                        'quantity' => 5,
                        'unit_cost' => 100,
                    ]
                ]
            ]);

        $responseB->assertStatus(201);
    }

    public function test_purchase_item_quantity_and_cost_validation()
    {
        $token = $this->ownerA->createToken('test')->plainTextToken;

        // Zero quantity
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->businessA->id])
            ->postJson('/api/v1/purchases', [
                'po_number' => 'PO-ZERO-QTY',
                'items' => [['product_id' => $this->productA->id, 'quantity' => 0, 'unit_cost' => 10]]
            ])->assertStatus(422);

        // Negative unit cost
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->businessA->id])
            ->postJson('/api/v1/purchases', [
                'po_number' => 'PO-NEG-COST',
                'items' => [['product_id' => $this->productA->id, 'quantity' => 5, 'unit_cost' => -10]]
            ])->assertStatus(422);

        // Paid amount exceeding total
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->businessA->id])
            ->postJson('/api/v1/purchases', [
                'po_number' => 'PO-OVERPAID',
                'paid_amount' => 500,
                'items' => [['product_id' => $this->productA->id, 'quantity' => 2, 'unit_cost' => 10]] // Total = 20
            ])->assertStatus(422);
    }

    public function test_hard_deletion_of_posted_purchase_is_prohibited()
    {
        $token = $this->ownerA->createToken('test')->plainTextToken;

        $createRes = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->businessA->id])
            ->postJson('/api/v1/purchases', [
                'po_number' => 'PO-HARD-DEL-TEST',
                'items' => [['product_id' => $this->productA->id, 'quantity' => 5, 'unit_cost' => 10]]
            ]);
        $createRes->assertStatus(201);
        $purchaseId = $createRes->json('id');

        // DELETE /purchases/{id} MUST fail with 422
        $deleteRes = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->businessA->id])
            ->deleteJson("/api/v1/purchases/{$purchaseId}");

        $deleteRes->assertStatus(422);
        $this->assertDatabaseHas('purchases', ['id' => $purchaseId]);
    }
}
