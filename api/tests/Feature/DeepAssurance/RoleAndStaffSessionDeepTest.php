<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndStaffSessionDeepTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected User $manager;
    protected User $salesperson;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Role Integrity Store']);
        setPermissionsTeamId($this->business->id);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'HQ Counter',
            'code' => 'RIS-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->manager = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->assignRole('Branch Manager');

        $this->salesperson = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->assignRole('Salesperson');
    }

    public function test_01_salesperson_cannot_access_profitability_reports(): void
    {
        $response = $this->actingAs($this->salesperson)->getJson('/api/v1/reports/profitability');
        $response->assertStatus(403);
    }

    public function test_02_owner_can_access_profitability_reports(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/profitability');
        $response->assertStatus(200);
    }

    public function test_03_salesperson_cannot_create_expenses(): void
    {
        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/expenses', [
            'amount' => 500.00,
            'expense_category_id' => 1,
            'description' => 'Unauthorized tea expense',
            'expense_date' => date('Y-m-d'),
        ]);

        $response->assertStatus(403);
    }

    public function test_04_salesperson_cannot_modify_business_settings(): void
    {
        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/settings', [
            'settings' => ['receipt_footer' => 'Hacked']
        ]);

        $response->assertStatus(403);
    }

    public function test_05_salesperson_cost_price_is_masked_in_catalog(): void
    {
        Product::create([
            'business_id' => $this->business->id,
            'name' => 'Basmati Rice Premium',
            'cost_price' => 250.00,
            'retail_price' => 320.00,
            'sku' => 'RICE-001',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->salesperson)->getJson('/api/v1/products');
        $response->assertStatus(200);
        $data = $response->json('data.0') ?? $response->json('0');
        $this->assertNotNull($data);
        // Cost price must either be null or excluded from cashier view
        $costPrice = $data['cost_price'] ?? null;
        $this->assertTrue($costPrice === null || $costPrice === 0 || $costPrice === '0.00');
    }

    public function test_06_owner_can_view_product_cost_price(): void
    {
        Product::create([
            'business_id' => $this->business->id,
            'name' => 'Cooking Oil 5L',
            'cost_price' => 1800.00,
            'retail_price' => 2200.00,
            'sku' => 'OIL-005',
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/products');
        $response->assertStatus(200);
        $data = $response->json('data.0') ?? $response->json('0');
        $this->assertNotNull($data);
        $this->assertNotNull($data['cost_price'] ?? null);
    }

    public function test_07_view_as_mode_blocks_mutating_actions_with_403(): void
    {
        // Enter View-As mode
        $enterResp = $this->actingAs($this->owner)->postJson('/api/v1/view-as/enter', [
            'branch_id' => $this->branch->id,
            'role' => 'Cashier'
        ]);
        $enterResp->assertStatus(200);

        // Attempt mutating creation while in View-As mode
        $postResp = $this->actingAs($this->owner)
            ->withHeader('X-View-As-Role', 'Cashier')
            ->postJson('/api/v1/products', [
                'name' => 'Illegal View-As Product',
                'cost_price' => 50.00,
                'selling_price' => 100.00,
                'stock' => 10,
                'sku' => 'ILLEGAL-01'
            ]);
        $postResp->assertStatus(403);

        // Exit View-As mode
        $exitResp = $this->actingAs($this->owner)->postJson('/api/v1/view-as/exit');
        $exitResp->assertStatus(200);
    }

    public function test_08_deactivated_user_is_denied_api_access(): void
    {
        $this->salesperson->is_active = false;
        $this->salesperson->save();

        $response = $this->actingAs($this->salesperson)->getJson('/api/v1/products');
        $response->assertStatus(403);
    }

    public function test_09_explicit_logout_revokes_bearer_token(): void
    {
        $tokenObj = $this->salesperson->createToken('session_test');
        $token = $tokenObj->plainTextToken;

        $logoutResp = $this->withToken($token)->postJson('/api/v1/auth/logout');
        $logoutResp->assertStatus(200);

        // Verify token deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenObj->accessToken->id
        ]);
    }

    public function test_10_manager_can_access_branch_reports_but_not_delete_business(): void
    {
        $reportResp = $this->actingAs($this->manager)->getJson('/api/v1/reports/sales');
        $reportResp->assertStatus(200);

        $deleteResp = $this->actingAs($this->manager)->deleteJson('/api/v1/businesses/' . $this->business->id);
        $deleteResp->assertStatus(403);
    }

    public function test_11_salesperson_cannot_trigger_user_deactivation(): void
    {
        $response = $this->actingAs($this->salesperson)->postJson('/api/v1/users/' . $this->manager->id . '/status', [
            'is_active' => false
        ]);

        $response->assertStatus(403);
    }

    public function test_12_authenticated_me_endpoint_returns_accurate_role_and_business_context(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/auth/me');
        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => $this->owner->email]);
        $this->assertNotEmpty($response->json('user.roles'));
    }
}
