<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Customer;
use Database\Seeders\RolesAndPermissionsSeeder;

class DesktopFoundationSessionModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Checkpoint 1.2: Unauthenticated Welcome check returns lightweight ping/health
     * without exposing internal secrets or merchant data.
     */
    public function test_unauthenticated_ping_and_health_endpoints_are_accessible_without_revealing_business_data()
    {
        $pingResp = $this->getJson('/api/v1/ping');
        $pingResp->assertStatus(200)
            ->assertJson(['status' => 'pong']);

        $healthResp = $this->getJson('/api/v1/health');
        $healthResp->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    /**
     * Checkpoint 1.3: Protected business endpoints reject unauthenticated requests
     * with 401 Unauthorized, never falling back to sample or mock data.
     */
    public function test_unauthenticated_request_to_protected_routes_returns_401()
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
        $this->getJson('/api/v1/sales')->assertStatus(401);
        $this->getJson('/api/v1/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/purchases')->assertStatus(401);
        $this->getJson('/api/v1/customers')->assertStatus(401);
    }

    /**
     * Checkpoint 1.3: Clean registration creates legitimate business identity,
     * exactly one primary Main Store, but ZERO sample products, sales, purchases, or debts.
     */
    public function test_new_business_onboarding_registers_clean_tenant_with_zero_sample_operational_records()
    {
        $registrationData = [
            'name' => 'Tariq Mehmood',
            'business_name' => 'Tariq Provision Store',
            'email' => 'tariq@tariqstore.pk',
            'phone' => '03001234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $resp = $this->postJson('/api/v1/auth/register', $registrationData);
        $resp->assertStatus(201);

        $business = Business::where('name', 'Tariq Provision Store')->first();
        $this->assertNotNull($business, 'Business must be created');

        // Verify exactly one primary Main Store exists
        $branches = Branch::where('business_id', $business->id)->get();
        $this->assertCount(1, $branches, 'Exactly 1 initial primary branch must exist');
        $this->assertTrue((bool)$branches->first()->is_primary, 'Initial branch must be marked primary');

        // Verify zero sample operational records exist
        $this->assertEquals(0, Product::where('business_id', $business->id)->count(), 'Must have 0 products initially');
        $this->assertEquals(0, Sale::where('business_id', $business->id)->count(), 'Must have 0 sales initially');
        $this->assertEquals(0, Purchase::where('business_id', $business->id)->count(), 'Must have 0 purchases initially');
        $this->assertEquals(0, Customer::where('business_id', $business->id)->count(), 'Must have 0 customers initially');
    }

    /**
     * Checkpoint 1.3: Shared device tenant switching prevents cross-tenant data leakage.
     */
    public function test_same_device_user_switching_enforces_strict_tenant_data_isolation()
    {
        // Business 1
        $biz1 = Business::create(['name' => 'Shop One']);
        $branch1 = Branch::create([
            'business_id' => $biz1->id,
            'name' => 'Shop 1 Main',
            'code' => 'S1-01',
            'is_primary' => true,
            'status' => 'active',
        ]);
        setPermissionsTeamId($biz1->id);
        $owner1 = User::factory()->create([
            'name' => 'Owner One',
            'email' => 'owner1@shop.pk',
            'business_id' => $biz1->id,
            'branch_id' => $branch1->id,
        ]);
        $owner1->businesses()->attach($biz1->id);
        $owner1->assignRole('Business Owner');

        Product::create([
            'business_id' => $biz1->id,
            'branch_id' => $branch1->id,
            'name' => 'Confidential Product Biz 1',
            'sku' => 'BIZ1-CONF',
            'selling_price' => 500,
            'cost_price' => 300,
            'status' => 'active',
        ]);

        // Business 2
        $biz2 = Business::create(['name' => 'Shop Two']);
        $branch2 = Branch::create([
            'business_id' => $biz2->id,
            'name' => 'Shop 2 Main',
            'code' => 'S2-01',
            'is_primary' => true,
            'status' => 'active',
        ]);
        setPermissionsTeamId($biz2->id);
        $owner2 = User::factory()->create([
            'name' => 'Owner Two',
            'email' => 'owner2@shop.pk',
            'business_id' => $biz2->id,
            'branch_id' => $branch2->id,
        ]);
        $owner2->businesses()->attach($biz2->id);
        $owner2->assignRole('Business Owner');

        // Owner 2 queries products
        $token2 = $owner2->createToken('test')->plainTextToken;
        $resp = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->withHeader('X-Active-Business', $biz2->id)
            ->getJson('/api/v1/products');

        $resp->assertStatus(200);
        $data = $resp->json('data') ?? $resp->json();
        $names = collect($data)->pluck('name')->toArray();
        $this->assertNotContains('Confidential Product Biz 1', $names, 'Owner 2 must not see Business 1 products');
    }

    /**
     * Checkpoint 1.3: Cashier/Salesperson role is prevented from accessing owner-only financial endpoints.
     */
    public function test_salesperson_cannot_access_owner_financial_dashboards()
    {
        $biz = Business::create(['name' => 'Metro Mart']);
        $branch = Branch::create([
            'business_id' => $biz->id,
            'name' => 'Metro Main',
            'code' => 'MM-01',
            'is_primary' => true,
            'status' => 'active',
        ]);
        setPermissionsTeamId($biz->id);
        $cashier = User::factory()->create([
            'name' => 'Cashier Ali',
            'email' => 'ali@metro.pk',
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
        ]);
        $cashier->businesses()->attach($biz->id);
        $cashier->assignRole('Salesperson');

        $token = $cashier->createToken('test')->plainTextToken;

        // Cashier accessing financial reports should be forbidden
        $resp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Active-Business', $biz->id)
            ->getJson('/api/v1/reports/profitability');

        $resp->assertStatus(403);
    }
}
