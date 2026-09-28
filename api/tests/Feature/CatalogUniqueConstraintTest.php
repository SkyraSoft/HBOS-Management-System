<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\QueryException;

class CatalogUniqueConstraintTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $tokenA;
    protected $tokenB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Business A
        $this->businessA = Business::create(['name' => 'Business A']);
        $branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A', 'is_primary' => true]);
        $ownerA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $branchA->id]);
        $ownerA->businesses()->attach($this->businessA->id);
        setPermissionsTeamId($this->businessA->id);
        $ownerA->assignRole('Business Owner');
        $this->tokenA = $ownerA->createToken('tokenA')->plainTextToken;

        // Business B
        $this->businessB = Business::create(['name' => 'Business B']);
        $branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);
        $ownerB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $branchB->id]);
        $ownerB->businesses()->attach($this->businessB->id);
        setPermissionsTeamId($this->businessB->id);
        $ownerB->assignRole('Business Owner');
        $this->tokenB = $ownerB->createToken('tokenB')->plainTextToken;
    }

    public function test_same_business_duplicate_sku_fails_api_validation()
    {
        Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Existing Product',
            'sku' => 'UNIQUE-SKU-001',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Duplicate SKU Product',
                             'sku' => 'UNIQUE-SKU-001',
                             'cost_price' => 10,
                             'selling_price' => 20,
                             'stock' => 5
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('sku');
    }

    public function test_cross_business_same_sku_succeeds()
    {
        Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Business A Product',
            'sku' => 'SHARED-SKU-001',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenB}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Business B Product',
                             'sku' => 'SHARED-SKU-001',
                             'cost_price' => 15,
                             'selling_price' => 25,
                             'stock' => 10
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'business_id' => $this->businessB->id,
            'sku' => 'SHARED-SKU-001'
        ]);
    }

    public function test_same_business_duplicate_barcode_fails_api_validation()
    {
        Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Existing Barcode Product',
            'barcode' => '7890123456',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Duplicate Barcode Product',
                             'barcode' => '7890123456',
                             'cost_price' => 10,
                             'selling_price' => 20,
                             'stock' => 5
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('barcode');
    }

    public function test_cross_business_same_barcode_succeeds()
    {
        Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Business A Barcode Product',
            'barcode' => '1122334455',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenB}"])
                         ->postJson('/api/v1/products', [
                             'name' => 'Business B Barcode Product',
                             'barcode' => '1122334455',
                             'cost_price' => 15,
                             'selling_price' => 25,
                             'stock' => 10
                         ]);

        $response->assertStatus(201);
    }

    public function test_updating_product_retaining_own_sku_and_barcode_succeeds()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'My Product',
            'sku' => 'MY-SKU-99',
            'barcode' => 'MY-BAR-99',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->putJson("/api/v1/products/{$product->id}", [
                             'name' => 'My Product Updated',
                             'sku' => 'MY-SKU-99',
                             'barcode' => 'MY-BAR-99',
                             'cost_price' => 12,
                             'selling_price' => 22,
                             'stock' => 5
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('name', 'My Product Updated');
    }

    public function test_updating_product_to_conflicting_same_business_sku_fails()
    {
        Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Product One',
            'sku' => 'SKU-ONE',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5
        ]);

        $productTwo = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Product Two',
            'sku' => 'SKU-TWO',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                         ->putJson("/api/v1/products/{$productTwo->id}", [
                             'name' => 'Product Two',
                             'sku' => 'SKU-ONE',
                             'cost_price' => 10,
                             'selling_price' => 20,
                             'stock' => 5
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('sku');
    }

    public function test_multiple_products_with_null_sku_and_barcode_succeed()
    {
        $resp1 = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                      ->postJson('/api/v1/products', [
                          'name' => 'Null Product 1',
                          'cost_price' => 10,
                          'selling_price' => 20,
                          'stock' => 5
                      ]);
        $resp1->assertStatus(201);

        $resp2 = $this->withHeaders(['Authorization' => "Bearer {$this->tokenA}"])
                      ->postJson('/api/v1/products', [
                          'name' => 'Null Product 2',
                          'cost_price' => 10,
                          'selling_price' => 20,
                          'stock' => 5
                      ]);
        $resp2->assertStatus(201);
    }

    public function test_database_level_sku_unique_constraint_rejects_duplicate()
    {
        Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'DB Test 1',
            'sku' => 'HARD-DB-SKU',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5
        ]);

        $this->expectException(QueryException::class);

        // Direct DB insertion bypassing Eloquent / API validation
        Product::withoutGlobalScopes()->insert([
            'business_id' => $this->businessA->id,
            'name' => 'DB Test 2',
            'sku' => 'HARD-DB-SKU',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function test_database_level_barcode_unique_constraint_rejects_duplicate()
    {
        Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'DB Barcode 1',
            'barcode' => 'HARD-DB-BARCODE',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5
        ]);

        $this->expectException(QueryException::class);

        // Direct DB insertion bypassing Eloquent / API validation
        Product::withoutGlobalScopes()->insert([
            'business_id' => $this->businessA->id,
            'name' => 'DB Barcode 2',
            'barcode' => 'HARD-DB-BARCODE',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 5,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
