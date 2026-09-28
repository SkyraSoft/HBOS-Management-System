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

class CatalogTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $ownerA;
    protected $ownerB;
    protected $tokenA;
    protected $tokenB;
    protected $categoryA;
    protected $categoryB;
    protected $brandA;
    protected $brandB;
    protected $subcategoryA;
    protected $subcategoryB;
    protected $productA;
    protected $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Business A
        $this->businessA = Business::create(['name' => 'Business A']);
        $branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A', 'is_primary' => true]);
        $this->ownerA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $branchA->id]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        setPermissionsTeamId($this->businessA->id);
        $this->ownerA->assignRole('Business Owner');
        $this->tokenA = $this->ownerA->createToken('tokenA')->plainTextToken;

        // 2. Business B
        $this->businessB = Business::create(['name' => 'Business B']);
        $branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);
        $this->ownerB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $branchB->id]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        setPermissionsTeamId($this->businessB->id);
        $this->ownerB->assignRole('Business Owner');
        $this->tokenB = $this->ownerB->createToken('tokenB')->plainTextToken;

        // Create Master Data for A
        $this->categoryA = Category::create(['business_id' => $this->businessA->id, 'name' => 'Category A']);
        $this->brandA = Brand::create(['business_id' => $this->businessA->id, 'name' => 'Brand A']);
        $this->subcategoryA = Subcategory::create(['business_id' => $this->businessA->id, 'category_id' => $this->categoryA->id, 'name' => 'Subcategory A']);
        $this->productA = Product::create([
            'business_id' => $this->businessA->id,
            'category_id' => $this->categoryA->id,
            'brand_id' => $this->brandA->id,
            'subcategory_id' => $this->subcategoryA->id,
            'name' => 'Product A',
            'sku' => 'SKU-A',
            'barcode' => 'BAR-A',
            'cost_price' => 100,
            'selling_price' => 150,
            'stock' => 10
        ]);

        // Create Master Data for B
        $this->categoryB = Category::create(['business_id' => $this->businessB->id, 'name' => 'Category B']);
        $this->brandB = Brand::create(['business_id' => $this->businessB->id, 'name' => 'Brand B']);
        $this->subcategoryB = Subcategory::create(['business_id' => $this->businessB->id, 'category_id' => $this->categoryB->id, 'name' => 'Subcategory B']);
        $this->productB = Product::create([
            'business_id' => $this->businessB->id,
            'category_id' => $this->categoryB->id,
            'brand_id' => $this->brandB->id,
            'subcategory_id' => $this->subcategoryB->id,
            'name' => 'Product B',
            'sku' => 'SKU-B',
            'barcode' => 'BAR-B',
            'cost_price' => 200,
            'selling_price' => 250,
            'stock' => 20
        ]);
    }

    public function test_business_a_cannot_list_business_b_products()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->getJson('/api/v1/products');

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonPath('0.id', $this->productA->id);
    }

    public function test_business_a_cannot_show_business_b_product_by_id()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->getJson("/api/v1/products/{$this->productB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_list_business_b_categories()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->getJson('/api/v1/categories');

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonPath('0.id', $this->categoryA->id);
    }

    public function test_business_a_cannot_show_business_b_category_by_id()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->getJson("/api/v1/categories/{$this->categoryB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_list_business_b_brands()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->getJson('/api/v1/brands');

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonPath('0.id', $this->brandA->id);
    }

    public function test_business_a_cannot_show_business_b_brand_by_id()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->getJson("/api/v1/brands/{$this->brandB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_list_business_b_subcategories()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->getJson('/api/v1/subcategories');

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonPath('0.id', $this->subcategoryA->id);
    }

    public function test_business_a_cannot_show_business_b_subcategory_by_id()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->getJson("/api/v1/subcategories/{$this->subcategoryB->id}");

        $response->assertStatus(404);
    }

    public function test_business_a_cannot_update_or_delete_business_b_product()
    {
        $updateResp = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                           ->putJson("/api/v1/products/{$this->productB->id}", [
                               'name' => 'Hacked Name',
                               'cost_price' => 10,
                               'selling_price' => 20,
                               'stock' => 5
                           ]);
        $updateResp->assertStatus(404);

        $deleteResp = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                           ->deleteJson("/api/v1/products/{$this->productB->id}");
        $deleteResp->assertStatus(404);
    }

    public function test_business_a_cannot_update_or_delete_business_b_category()
    {
        $updateResp = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                           ->putJson("/api/v1/categories/{$this->categoryB->id}", [
                               'name' => 'Hacked Category'
                           ]);
        $updateResp->assertStatus(404);

        $deleteResp = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                           ->deleteJson("/api/v1/categories/{$this->categoryB->id}");
        $deleteResp->assertStatus(404);
    }

    public function test_empty_business_catalog_stays_empty_without_fallback_leak()
    {
        $emptyBusiness = Business::create(['name' => 'Empty Business']);
        $branch = Branch::create(['business_id' => $emptyBusiness->id, 'name' => 'Empty Branch', 'is_primary' => true]);
        $emptyOwner = User::factory()->create(['business_id' => $emptyBusiness->id, 'branch_id' => $branch->id]);
        $emptyOwner->businesses()->attach($emptyBusiness->id);
        setPermissionsTeamId($emptyBusiness->id);
        $emptyOwner->assignRole('Business Owner');
        $emptyToken = $emptyOwner->createToken('empty')->plainTextToken;

        $catResp = $this->withHeaders(['Authorization' => "Bearer {$emptyToken}"])->getJson('/api/v1/categories');
        $catResp->assertStatus(200)->assertJsonCount(0);

        $brandResp = $this->withHeaders(['Authorization' => "Bearer {$emptyToken}"])->getJson('/api/v1/brands');
        $brandResp->assertStatus(200)->assertJsonCount(0);

        $subResp = $this->withHeaders(['Authorization' => "Bearer {$emptyToken}"])->getJson('/api/v1/subcategories');
        $subResp->assertStatus(200)->assertJsonCount(0);
    }
}
