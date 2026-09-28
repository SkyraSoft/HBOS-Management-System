<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Subcategory;

class CatalogRelationshipIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $ownerA;
    protected $tokenA;
    protected $categoryA;
    protected $categoryB;
    protected $brandA;
    protected $brandB;
    protected $subcategoryA;
    protected $subcategoryB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Business A
        $this->businessA = Business::create(['name' => 'Business A']);
        $branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A', 'is_primary' => true]);
        $this->ownerA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $branchA->id]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        setPermissionsTeamId($this->businessA->id);
        $this->ownerA->assignRole('Business Owner');
        $this->tokenA = $this->ownerA->createToken('tokenA')->plainTextToken;

        // Business B
        $this->businessB = Business::create(['name' => 'Business B']);
        $branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);

        // Master Data A
        $this->categoryA = Category::create(['business_id' => $this->businessA->id, 'name' => 'Cat A']);
        $this->brandA = Brand::create(['business_id' => $this->businessA->id, 'name' => 'Brand A']);
        $this->subcategoryA = Subcategory::create(['business_id' => $this->businessA->id, 'category_id' => $this->categoryA->id, 'name' => 'Sub A']);

        // Master Data B
        $this->categoryB = Category::create(['business_id' => $this->businessB->id, 'name' => 'Cat B']);
        $this->brandB = Brand::create(['business_id' => $this->businessB->id, 'name' => 'Brand B']);
        $this->subcategoryB = Subcategory::create(['business_id' => $this->businessB->id, 'category_id' => $this->categoryB->id, 'name' => 'Sub B']);
    }

    public function test_product_in_business_a_can_use_category_brand_subcategory_from_business_a()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Product Valid',
                             'category_id' => $this->categoryA->id,
                             'brand_id' => $this->brandA->id,
                             'subcategory_id' => $this->subcategoryA->id,
                             'cost_price' => 50,
                             'selling_price' => 100,
                             'stock' => 5
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('name', 'Product Valid');

        $this->assertDatabaseHas('products', [
            'name' => 'Product Valid',
            'business_id' => $this->businessA->id,
            'category_id' => $this->categoryA->id,
            'brand_id' => $this->brandA->id,
            'subcategory_id' => $this->subcategoryA->id
        ]);
    }

    public function test_product_in_business_a_cannot_use_category_from_business_b()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Product Invalid Cat',
                             'category_id' => $this->categoryB->id,
                             'cost_price' => 50,
                             'selling_price' => 100,
                             'stock' => 5
                         ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('products', ['name' => 'Product Invalid Cat']);
    }

    public function test_product_in_business_a_cannot_use_brand_from_business_b()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Product Invalid Brand',
                             'brand_id' => $this->brandB->id,
                             'cost_price' => 50,
                             'selling_price' => 100,
                             'stock' => 5
                         ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('products', ['name' => 'Product Invalid Brand']);
    }

    public function test_product_in_business_a_cannot_use_subcategory_from_business_b()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Product Invalid Sub',
                             'subcategory_id' => $this->subcategoryB->id,
                             'cost_price' => 50,
                             'selling_price' => 100,
                             'stock' => 5
                         ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('products', ['name' => 'Product Invalid Sub']);
    }

    public function test_product_update_rejects_foreign_tenant_relationships()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Original Product',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 5
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->putJson("/api/v1/products/{$product->id}", [
                             'name' => 'Original Product',
                             'category_id' => $this->categoryB->id,
                             'cost_price' => 50,
                             'selling_price' => 100,
                             'stock' => 5
                         ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => null]);
    }

    public function test_subcategory_store_rejects_parent_category_from_business_b()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/subcategories', [
                             'name' => 'Subcategory Hack',
                             'category_id' => $this->categoryB->id
                         ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('subcategories', ['name' => 'Subcategory Hack']);
    }

    public function test_dynamic_category_creation_binds_to_active_business()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Dynamic Product',
                             'category' => 'New Dynamic Category',
                             'cost_price' => 50,
                             'selling_price' => 100,
                             'stock' => 5
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', [
            'name' => 'New Dynamic Category',
            'business_id' => $this->businessA->id
        ]);
    }

    public function test_dynamic_brand_creation_binds_to_active_business()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Dynamic Brand Product',
                             'brand' => 'New Dynamic Brand',
                             'cost_price' => 50,
                             'selling_price' => 100,
                             'stock' => 5
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('brands', [
            'name' => 'New Dynamic Brand',
            'business_id' => $this->businessA->id
        ]);
    }

    public function test_spoofed_request_business_id_payload_cannot_change_product_ownership()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Spoof Test Product',
                             'business_id' => $this->businessB->id,
                             'cost_price' => 50,
                             'selling_price' => 100,
                             'stock' => 5
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'name' => 'Spoof Test Product',
            'business_id' => $this->businessA->id
        ]);
        $this->assertDatabaseMissing('products', [
            'name' => 'Spoof Test Product',
            'business_id' => $this->businessB->id
        ]);
    }
}
