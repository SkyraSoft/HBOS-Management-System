<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessOnboardingDeepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_01_successful_business_registration_creates_owner_and_primary_branch(): void
    {
        $payload = [
            'name' => 'Jan Muhammad',
            'business_name' => 'Khyber Retail Goods',
            'email' => 'jan@khybergoods.pk',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'phone' => '03001234567',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user',
            'business',
            'branch',
            'access_token'
        ]);

        $business = Business::where('name', 'Khyber Retail Goods')->first();
        $this->assertNotNull($business);

        $primaryBranch = Branch::where('business_id', $business->id)->where('is_primary', true)->first();
        $this->assertNotNull($primaryBranch);
        $this->assertEquals('active', $primaryBranch->status);

        $user = User::where('email', 'jan@khybergoods.pk')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Business Owner'));
    }

    public function test_02_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicate@retail.pk']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ali Khan',
            'business_name' => 'Second Store',
            'email' => 'duplicate@retail.pk',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'phone' => '03009999999',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_03_registration_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password', 'business_name']);
    }

    public function test_04_owner_can_operate_pos_directly_without_dummy_cashier(): void
    {
        $business = Business::create(['name' => 'Solo Owner Shop']);
        setPermissionsTeamId($business->id);
        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Store',
            'code' => 'SOLO-01',
            'is_primary' => true,
            'status' => 'active'
        ]);
        $owner = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
        ]);
        $owner->businesses()->attach($business->id);
        $owner->assignRole('Business Owner');

        // Owner can access POS products and checkout directly
        $response = $this->actingAs($owner)->getJson('/api/v1/products');
        $response->assertStatus(200);

        $sessionResponse = $this->actingAs($owner)->getJson('/api/v1/cash-register-sessions/current');
        $this->assertContains($sessionResponse->status(), [200, 400, 404, 422]);
    }

    public function test_05_owner_can_add_secondary_branch_without_mutating_primary_branch(): void
    {
        $business = Business::create(['name' => 'Expanding Retail Corp']);
        setPermissionsTeamId($business->id);
        $primary = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Store',
            'code' => 'EXP-HQ',
            'is_primary' => true,
            'status' => 'active'
        ]);
        $owner = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $primary->id,
        ]);
        $owner->businesses()->attach($business->id);
        $owner->assignRole('Business Owner');

        $response = $this->actingAs($owner)->postJson('/api/v1/branches', [
            'name' => 'Gulberg Express Branch',
            'code' => 'EXP-GUL',
            'address' => 'Main Boulevard Gulberg',
            'is_primary' => false,
        ]);

        $response->assertStatus(201);

        // Verify primary branch remains untouched
        $primaryAfter = Branch::find($primary->id);
        $this->assertTrue((bool) $primaryAfter->is_primary);
        $this->assertEquals('EXP-HQ', $primaryAfter->code);

        $newBranch = Branch::where('code', 'EXP-GUL')->first();
        $this->assertNotNull($newBranch);
        $this->assertFalse((bool) $newBranch->is_primary);
    }

    public function test_06_deactivated_branch_readiness_shows_inactive(): void
    {
        $business = Business::create(['name' => 'Branch Lifecycle Retail']);
        setPermissionsTeamId($business->id);
        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Temporary Outlet',
            'code' => 'TMP-01',
            'is_primary' => false,
            'status' => 'inactive'
        ]);
        $staff = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
        ]);
        $staff->businesses()->attach($business->id);
        $staff->assignRole('Salesperson');

        $response = $this->actingAs($staff)->getJson('/api/v1/branches/' . $branch->id . '/readiness');
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'inactive']);
    }

    public function test_07_owner_can_invite_staff_with_salesperson_role(): void
    {
        $business = Business::create(['name' => 'Staff Hierarchy Retail']);
        setPermissionsTeamId($business->id);
        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Counter',
            'code' => 'SHR-01',
            'is_primary' => true,
            'status' => 'active'
        ]);
        $owner = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
        ]);
        $owner->businesses()->attach($business->id);
        $owner->assignRole('Business Owner');

        $response = $this->actingAs($owner)->postJson('/api/v1/users', [
            'name' => 'Tariq Cashier',
            'email' => 'tariq@retail.pk',
            'password' => 'Password123!',
            'role' => 'Salesperson',
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(201);
        $invited = User::where('email', 'tariq@retail.pk')->first();
        $this->assertNotNull($invited);
        $this->assertTrue($invited->hasRole('Salesperson'));
        $this->assertFalse($invited->hasRole('Business Owner'));
    }

    public function test_08_salesperson_cannot_invite_other_staff(): void
    {
        $business = Business::create(['name' => 'Unauthorized Staff Retail']);
        setPermissionsTeamId($business->id);
        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Counter',
            'code' => 'USR-01',
            'is_primary' => true,
            'status' => 'active'
        ]);
        $salesperson = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
        ]);
        $salesperson->businesses()->attach($business->id);
        $salesperson->assignRole('Salesperson');

        $response = $this->actingAs($salesperson)->postJson('/api/v1/users', [
            'name' => 'Malicious Invite',
            'email' => 'malicious@retail.pk',
            'password' => 'Password123!',
            'role' => 'Salesperson',
            'branch_id' => $branch->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_09_tenant_isolation_during_user_query(): void
    {
        $bizA = Business::create(['name' => 'Business Alpha']);
        setPermissionsTeamId($bizA->id);
        $branchA = Branch::create(['business_id' => $bizA->id, 'name' => 'Branch A', 'code' => 'A-01', 'is_primary' => true]);
        $userA = User::factory()->create(['business_id' => $bizA->id, 'branch_id' => $branchA->id]);
        $userA->businesses()->attach($bizA->id);
        $userA->assignRole('Business Owner');

        $bizB = Business::create(['name' => 'Business Beta']);
        setPermissionsTeamId($bizB->id);
        $branchB = Branch::create(['business_id' => $bizB->id, 'name' => 'Branch B', 'code' => 'B-01', 'is_primary' => true]);
        $userB = User::factory()->create(['business_id' => $bizB->id, 'branch_id' => $branchB->id]);
        $userB->businesses()->attach($bizB->id);
        $userB->assignRole('Business Owner');

        $response = $this->actingAs($userA)->getJson('/api/v1/users');
        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => $userA->email]);
        $response->assertJsonMissing(['email' => $userB->email]);
    }

    public function test_10_business_settings_can_be_retrieved_and_updated_by_owner(): void
    {
        $business = Business::create(['name' => 'Settings Retail']);
        setPermissionsTeamId($business->id);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Store', 'code' => 'SET-01', 'is_primary' => true]);
        $owner = User::factory()->create(['business_id' => $business->id, 'branch_id' => $branch->id]);
        $owner->businesses()->attach($business->id);
        $owner->assignRole('Business Owner');

        $response = $this->actingAs($owner)->postJson('/api/v1/settings', [
            'settings' => [
                'receipt_footer' => 'Thank you for shopping at Settings Retail!',
            ]
        ]);

        $response->assertStatus(200);

        $getResp = $this->actingAs($owner)->getJson('/api/v1/settings');
        $getResp->assertStatus(200);
        $getResp->assertJsonFragment(['receipt_footer' => 'Thank you for shopping at Settings Retail!']);
    }

    public function test_11_salesperson_cannot_update_business_settings(): void
    {
        $business = Business::create(['name' => 'Settings Protection Retail']);
        setPermissionsTeamId($business->id);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Store', 'code' => 'SPR-01', 'is_primary' => true]);
        $salesperson = User::factory()->create(['business_id' => $business->id, 'branch_id' => $branch->id]);
        $salesperson->businesses()->attach($business->id);
        $salesperson->assignRole('Salesperson');

        $response = $this->actingAs($salesperson)->postJson('/api/v1/settings', [
            'settings' => [
                'receipt_footer' => 'Hacked footer message',
            ]
        ]);

        $response->assertStatus(403);
    }

    public function test_12_onboarding_status_endpoint_returns_completion_state(): void
    {
        $business = Business::create(['name' => 'Onboarding Status Retail']);
        setPermissionsTeamId($business->id);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Store', 'code' => 'OSR-01', 'is_primary' => true]);
        $owner = User::factory()->create(['business_id' => $business->id, 'branch_id' => $branch->id]);
        $owner->businesses()->attach($business->id);
        $owner->assignRole('Business Owner');

        $response = $this->actingAs($owner)->getJson('/api/v1/onboarding/status');
        $response->assertStatus(200);
        $response->assertJsonStructure(['current_step', 'onboarding_status', 'business', 'primary_branch']);
    }
}
