<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;

class CatalogTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $business;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Test Business']);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'password' => bcrypt('password123')
        ]);
        $this->user->businesses()->attach($this->business->id);

        setPermissionsTeamId($this->business->id);
        $this->user->assignRole('Business Owner');
        
        $this->token = $this->user->createToken('test_token')->plainTextToken;
    }

    public function test_user_can_create_and_view_categories()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->postJson('/api/v1/categories', [
                             'name' => 'Electronics'
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('name', 'Electronics');

        $this->assertDatabaseHas('categories', ['name' => 'Electronics', 'business_id' => $this->business->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->getJson('/api/v1/categories');
                         
        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonPath('0.name', 'Electronics');
    }

    public function test_user_can_create_and_manage_products()
    {
        $category = Category::create([
            'name' => 'Electronics',
            'business_id' => $this->business->id
        ]);

        $productData = [
            'name' => 'Smartphone',
            'category_id' => $category->id,
            'sku' => 'PHONE-001',
            'cost_price' => 400,
            'selling_price' => 500,
            'stock' => 10,
            'min_stock' => 2,
            'unit' => 'pcs'
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
                 ->assertJsonPath('name', 'Smartphone');

        $this->assertDatabaseHas('products', ['name' => 'Smartphone', 'business_id' => $this->business->id, 'stock' => 10]);

        $productId = $response->json('id');

        $updateResponse = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                               ->putJson("/api/v1/products/{$productId}", [
                                   'name' => 'Smartphone',
                                   'category_id' => $category->id,
                                   'sku' => 'PHONE-001',
                                   'cost_price' => 400,
                                   'selling_price' => 550,
                                   'stock' => 10,
                                   'unit' => 'pcs'
                               ]);
                               
        $updateResponse->assertStatus(200)
                       ->assertJsonPath('selling_price', 550);
    }

    public function test_products_are_isolated_by_business_id()
    {
        $otherBusiness = Business::create(['name' => 'Other Business']);
        
        Product::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Product',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 5,
            'unit' => 'pcs'
        ]);

        Product::create([
            'business_id' => $this->business->id,
            'name' => 'Our Product',
            'cost_price' => 150,
            'selling_price' => 200,
            'stock' => 10,
            'unit' => 'pcs'
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->getJson('/api/v1/products');

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonPath('0.name', 'Our Product');
    }
}
