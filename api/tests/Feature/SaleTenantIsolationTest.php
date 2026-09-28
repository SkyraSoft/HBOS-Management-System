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
use App\Models\SaleItem;

class SaleTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA;
    protected $branchB;
    protected $userA;
    protected $userB;
    protected $productA;
    protected $productB;
    protected $customerA;
    protected $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Business A Setup
        $this->businessA = Business::create(['name' => 'Business A']);
        $this->branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A', 'is_primary' => true]);
        
        setPermissionsTeamId($this->businessA->id);
        $this->userA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA->id]);
        $this->userA->businesses()->attach($this->businessA->id);
        $this->userA->assignRole('Business Owner');

        $this->productA = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Product A',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 50,
            'unit' => 'pcs'
        ]);
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 50
        ]);
        $this->customerA = Customer::create(['business_id' => $this->businessA->id, 'name' => 'Customer A']);
        \App\Models\FinancialAccount::create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA->id, 'name' => 'Drawer A', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        // Business B Setup
        $this->businessB = Business::create(['name' => 'Business B']);
        $this->branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);
        
        setPermissionsTeamId($this->businessB->id);
        $this->userB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB->id]);
        $this->userB->businesses()->attach($this->businessB->id);
        $this->userB->assignRole('Business Owner');

        $this->productB = Product::create([
            'business_id' => $this->businessB->id,
            'name' => 'Product B',
            'cost_price' => 15,
            'selling_price' => 30,
            'stock' => 50,
            'unit' => 'pcs'
        ]);
        BranchInventory::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'product_id' => $this->productB->id,
            'quantity_on_hand' => 50
        ]);
        $this->customerB = Customer::create(['business_id' => $this->businessB->id, 'name' => 'Customer B']);
        \App\Models\FinancialAccount::create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB->id, 'name' => 'Drawer B', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);
    }

    public function test_user_in_business_a_cannot_view_business_b_sales()
    {
        $saleB = Sale::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'user_id' => $this->userB->id,
            'invoice_number' => 'INV-B-001',
            'date' => date('Y-m-d'),
            'subtotal' => 30,
            'total' => 30,
            'paid_amount' => 30,
            'status' => 'completed'
        ]);

        $tokenA = $this->userA->createToken('test')->plainTextToken;

        // Index
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/sales');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());

        // Show
        $showRes = $this->withHeaders([
            'Authorization' => "Bearer {$tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/sales/{$saleB->id}");

        $showRes->assertStatus(404);
    }

    public function test_sale_creation_rejects_foreign_tenant_product()
    {
        $tokenA = $this->userA->createToken('test')->plainTextToken;

        $payload = [
            'items' => [
                ['product_id' => $this->productB->id, 'quantity' => 1]
            ]
        ];

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->postJson('/api/v1/sales', $payload);

        $res->assertStatus(422);
    }

    public function test_sale_creation_rejects_foreign_tenant_customer()
    {
        $tokenA = $this->userA->createToken('test')->plainTextToken;

        $payload = [
            'customer_id' => $this->customerB->id,
            'paid_amount' => 0,
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 1]
            ]
        ];

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$tokenA}",
            'X-Business-ID' => $this->businessA->id,
        ])->postJson('/api/v1/sales', $payload);

        $res->assertStatus(422);
    }

    public function test_multi_business_user_context_switch_enforces_active_tenant()
    {
        // Give userA membership in Business B as Branch Manager
        $this->userA->businesses()->attach($this->businessB->id);
        setPermissionsTeamId($this->businessB->id);
        $this->userA->assignRole('Branch Manager');

        $tokenA = $this->userA->createToken('test')->plainTextToken;

        // Switch context to Business B
        $resB = $this->withHeaders([
            'Authorization' => "Bearer {$tokenA}",
            'X-Business-ID' => $this->businessB->id,
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branchB->id,
            'items' => [
                ['product_id' => $this->productB->id, 'quantity' => 2]
            ]
        ]);

        $resB->assertStatus(201);
        $this->assertEquals($this->businessB->id, $resB->json('business_id'));

        // Attempt using Product A while in Business B context
        $resFail = $this->withHeaders([
            'Authorization' => "Bearer {$tokenA}",
            'X-Business-ID' => $this->businessB->id,
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branchB->id,
            'items' => [
                ['product_id' => $this->productA->id, 'quantity' => 2]
            ]
        ]);

        $resFail->assertStatus(422);
    }

    public function test_tenant_scoped_invoice_uniqueness_allows_same_invoice_number_across_businesses()
    {
        // Business A creates sale with invoice number INV-SAME-001
        $saleA = Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->userA->id,
            'invoice_number' => 'INV-SAME-001',
            'date' => date('Y-m-d'),
            'subtotal' => 20,
            'total' => 20,
            'paid_amount' => 20,
            'status' => 'completed'
        ]);

        // Business B creates sale with identical invoice number INV-SAME-001
        $saleB = Sale::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'user_id' => $this->userB->id,
            'invoice_number' => 'INV-SAME-001',
            'date' => date('Y-m-d'),
            'subtotal' => 30,
            'total' => 30,
            'paid_amount' => 30,
            'status' => 'completed'
        ]);

        $this->assertEquals('INV-SAME-001', $saleA->invoice_number);
        $this->assertEquals('INV-SAME-001', $saleB->invoice_number);
        $this->assertNotEquals($saleA->business_id, $saleB->business_id);
    }

    public function test_same_business_duplicate_invoice_number_is_rejected()
    {
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->userA->id,
            'invoice_number' => 'INV-DUPLICATE-001',
            'date' => date('Y-m-d'),
            'subtotal' => 20,
            'total' => 20,
            'paid_amount' => 20,
            'status' => 'completed'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Direct DB attempt to insert duplicate invoice_number inside same Business A
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->userA->id,
            'invoice_number' => 'INV-DUPLICATE-001',
            'date' => date('Y-m-d'),
            'subtotal' => 20,
            'total' => 20,
            'paid_amount' => 20,
            'status' => 'completed'
        ]);
    }
}

