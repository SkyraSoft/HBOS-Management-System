<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use Spatie\Permission\Models\Role;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $userA;
    protected $userB;
    protected $multiUser;
    protected $tokenA;
    protected $tokenB;
    protected $tokenMulti;
    protected $productA;
    protected $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Setup Business A & Owner A
        $this->businessA = Business::create(['name' => 'Business A']);
        $branchA = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Main Branch A',
            'is_primary' => true
        ]);
        $this->userA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $branchA->id,
            'password' => bcrypt('password123')
        ]);
        $this->userA->businesses()->attach($this->businessA->id);
        
        setPermissionsTeamId($this->businessA->id);
        $roleOwner = Role::firstOrCreate(['name' => 'Business Owner']);
        $this->userA->assignRole($roleOwner);
        $this->tokenA = $this->userA->createToken('tokenA')->plainTextToken;

        // 2. Setup Business B & Owner B
        $this->businessB = Business::create(['name' => 'Business B']);
        $branchB = Branch::create([
            'business_id' => $this->businessB->id,
            'name' => 'Main Branch B',
            'is_primary' => true
        ]);
        $this->userB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'branch_id' => $branchB->id,
            'password' => bcrypt('password123')
        ]);
        $this->userB->businesses()->attach($this->businessB->id);
        
        setPermissionsTeamId($this->businessB->id);
        $this->userB->assignRole($roleOwner);
        $this->tokenB = $this->userB->createToken('tokenB')->plainTextToken;

        // 3. Multi-tenant User belonging to both Business A and B
        $this->multiUser = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $branchA->id,
            'password' => bcrypt('password123')
        ]);
        $this->multiUser->businesses()->attach([$this->businessA->id, $this->businessB->id]);
        
        setPermissionsTeamId($this->businessA->id);
        $this->multiUser->assignRole($roleOwner);

        setPermissionsTeamId($this->businessB->id);
        $this->multiUser->assignRole($roleOwner);

        $this->tokenMulti = $this->multiUser->createToken('tokenMulti')->plainTextToken;

        // 4. Sample Products
        $catA = Category::create(['name' => 'Cat A', 'business_id' => $this->businessA->id]);
        $this->productA = Product::create([
            'business_id' => $this->businessA->id,
            'category_id' => $catA->id,
            'name' => 'Product A',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 100,
            'unit' => 'pcs'
        ]);

        $catB = Category::create(['name' => 'Cat B', 'business_id' => $this->businessB->id]);
        $this->productB = Product::create([
            'business_id' => $this->businessB->id,
            'category_id' => $catB->id,
            'name' => 'Product B',
            'cost_price' => 15,
            'selling_price' => 30,
            'stock' => 50,
            'unit' => 'pcs'
        ]);
    }

    public function test_user_in_business_a_cannot_read_business_b_resources()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenA"])
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Product A', $data[0]['name']);
    }

    public function test_user_in_business_a_cannot_update_business_b_resources()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenA"])
            ->putJson("/api/v1/products/{$this->productB->id}", [
                'name' => 'Malicious Product Edit',
                'selling_price' => 999
            ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('products', ['id' => $this->productB->id, 'name' => 'Product B']);
    }

    public function test_user_in_business_a_cannot_delete_business_b_resources()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenA"])
            ->deleteJson("/api/v1/products/{$this->productB->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('products', ['id' => $this->productB->id]);
    }

    public function test_spoofed_x_business_id_header_is_rejected_for_non_member()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenA",
            'X-Business-ID' => $this->businessB->id
        ])->getJson('/api/v1/products');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized business context provided.']);
    }

    public function test_unauthorized_x_business_id_returns_403()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenA",
            'X-Business-ID' => 99999
        ])->getJson('/api/v1/products');

        $response->assertStatus(403);
    }

    public function test_valid_x_business_id_succeeds_for_legitimate_multi_tenant_member()
    {
        $responseA = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessA->id
        ])->getJson('/api/v1/products');

        $responseA->assertStatus(200);
        $dataA = $responseA->json('data') ?? $responseA->json();
        $this->assertEquals('Product A', $dataA[0]['name']);

        $responseB = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessB->id
        ])->getJson('/api/v1/products');

        $responseB->assertStatus(200);
        $dataB = $responseB->json('data') ?? $responseB->json();
        $this->assertEquals('Product B', $dataB[0]['name']);
    }

    public function test_business_listings_expose_only_authorized_businesses()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenA"])
            ->getJson('/api/v1/businesses');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Business A', $data[0]['name']);
    }

    public function test_guessed_resource_id_from_another_tenant_does_not_cause_idor()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenA"])
            ->getJson("/api/v1/products/{$this->productB->id}");

        $response->assertStatus(404);
    }

    public function test_tenant_scope_correctly_restricts_model_queries()
    {
        $this->actingAs($this->userA);
        app()->instance('active_business_id', $this->businessA->id);

        $products = Product::all();
        $this->assertCount(1, $products);
        $this->assertEquals('Product A', $products->first()->name);
    }

    public function test_creating_tenantable_record_assigns_active_business_id_correctly()
    {
        $this->actingAs($this->userA);
        app()->instance('active_business_id', $this->businessA->id);

        $product = Product::create([
            'name' => 'New Product A',
            'cost_price' => 5,
            'selling_price' => 10,
            'stock' => 20,
            'unit' => 'pcs'
        ]);

        $this->assertEquals($this->businessA->id, $product->business_id);
    }

    public function test_request_without_x_business_id_resolves_valid_default_business()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenA"])
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        $this->assertEquals('Product A', $data[0]['name']);
    }

    public function test_missing_or_invalid_tenant_context_does_not_expose_all_rows()
    {
        // Unauthenticated request
        $response = $this->getJson('/api/v1/products');
        $response->assertStatus(401);
    }

    public function test_cross_tenant_branch_ids_are_rejected()
    {
        $branchB = Branch::where('business_id', $this->businessB->id)->first();

        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenA"])
            ->getJson("/api/v1/branches/{$branchB->id}");

        $response->assertStatus(404);
    }

    public function test_multi_tenant_user_can_select_either_authorized_business_independently()
    {
        $responseA = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessA->id
        ])->getJson('/api/v1/products');

        $responseA->assertStatus(200);

        $responseB = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessB->id
        ])->getJson('/api/v1/products');

        $responseB->assertStatus(200);
    }

    public function test_switching_active_business_does_not_retain_stale_tenant_context()
    {
        $responseA = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessA->id
        ])->getJson('/api/v1/products');
        $this->assertEquals('Product A', $responseA->json('0.name'));

        $responseB = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessB->id
        ])->getJson('/api/v1/products');
        $this->assertEquals('Product B', $responseB->json('0.name'));
    }
}
