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

class CatalogPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $owner;
    protected $manager;
    protected $salesperson;
    protected $tokenOwner;
    protected $tokenManager;
    protected $tokenSalesperson;
    protected $category;
    protected $brand;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'RBAC Business']);
        $branch = Branch::create(['business_id' => $this->business->id, 'name' => 'RBAC Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        // Owner
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');
        $this->tokenOwner = $this->owner->createToken('owner')->plainTextToken;

        // Manager
        $this->manager = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $branch->id]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->assignRole('Branch Manager');
        $this->tokenManager = $this->manager->createToken('manager')->plainTextToken;

        // Salesperson
        $this->salesperson = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $branch->id]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->assignRole('Salesperson');
        $this->tokenSalesperson = $this->salesperson->createToken('sales')->plainTextToken;

        // Create initial catalog data
        $this->category = Category::create(['business_id' => $this->business->id, 'name' => 'Initial Cat']);
        $this->brand = Brand::create(['business_id' => $this->business->id, 'name' => 'Initial Brand']);
        $this->product = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Initial Product',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 10
        ]);
    }

    public function test_business_owner_can_manage_all_catalog_entities()
    {
        // View
        $this->withHeaders(['Authorization' => "Bearer {$this->tokenOwner}"])
             ->getJson('/api/v1/products')->assertStatus(200);

        // Create
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenOwner}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Owner Product',
                             'cost_price' => 10,
                             'selling_price' => 20,
                             'stock' => 5
                         ]);
        $response->assertStatus(201);

        // Update
        $this->withHeaders(['Authorization' => "Bearer {$this->tokenOwner}"])
             ->putJson("/api/v1/products/{$this->product->id}", [
                 'name' => 'Updated Product',
                 'cost_price' => 50,
                 'selling_price' => 100,
                 'stock' => 10
             ])->assertStatus(200);

        // Delete
        $this->withHeaders(['Authorization' => "Bearer {$this->tokenOwner}"])
             ->deleteJson("/api/v1/products/{$this->product->id}")->assertStatus(200);
    }

    public function test_branch_manager_can_manage_catalog_entities_as_permitted()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->tokenManager}"])
             ->getJson('/api/v1/products')->assertStatus(200);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenManager}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Manager Product',
                             'cost_price' => 10,
                             'selling_price' => 20,
                             'stock' => 5
                         ]);
        $response->assertStatus(201);
    }

    public function test_salesperson_can_view_products_categories_brands()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
             ->getJson('/api/v1/products')->assertStatus(200);

        $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
             ->getJson('/api/v1/categories')->assertStatus(200);

        $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
             ->getJson('/api/v1/brands')->assertStatus(200);
    }

    public function test_salesperson_cannot_create_products()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Unauthorized Product',
                             'cost_price' => 10,
                             'selling_price' => 20,
                             'stock' => 5
                         ]);
        $response->assertStatus(403);
    }

    public function test_salesperson_cannot_update_products()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
                         ->putJson("/api/v1/products/{$this->product->id}", [
                             'name' => 'Hacked Product',
                             'cost_price' => 10,
                             'selling_price' => 20,
                             'stock' => 5
                         ]);
        $response->assertStatus(403);
    }

    public function test_salesperson_cannot_delete_products()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
                         ->deleteJson("/api/v1/products/{$this->product->id}");
        $response->assertStatus(403);
    }

    public function test_salesperson_cannot_create_categories()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
                         ->postJson('/api/v1/categories', [
                             'name' => 'Unauthorized Category'
                         ]);
        $response->assertStatus(403);
    }

    public function test_salesperson_cannot_update_or_delete_categories()
    {
        $updateResp = $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
                           ->putJson("/api/v1/categories/{$this->category->id}", [
                               'name' => 'Hacked Category'
                           ]);
        $updateResp->assertStatus(403);

        $deleteResp = $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
                           ->deleteJson("/api/v1/categories/{$this->category->id}");
        $deleteResp->assertStatus(403);
    }

    public function test_salesperson_cannot_create_brands()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
                         ->postJson('/api/v1/brands', [
                             'name' => 'Unauthorized Brand'
                         ]);
        $response->assertStatus(403);
    }

    public function test_salesperson_cannot_update_or_delete_brands()
    {
        $updateResp = $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
                           ->putJson("/api/v1/brands/{$this->brand->id}", [
                               'name' => 'Hacked Brand'
                           ]);
        $updateResp->assertStatus(403);

        $deleteResp = $this->withHeaders(['Authorization' => "Bearer {$this->tokenSalesperson}"])
                           ->deleteJson("/api/v1/brands/{$this->brand->id}");
        $deleteResp->assertStatus(403);
    }
}
