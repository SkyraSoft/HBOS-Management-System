<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic Spatie permissions
        $permissions = [
            'view products', 'manage products',
            'view sales', 'manage sales',
            'view purchases', 'manage purchases',
            'view financial accounts', 'manage financial accounts',
            'view business settings', 'manage business settings',
            'view brands', 'manage brands',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }
    }

    /**
     * Test Public Registration Atomicity, Email Normalization, and Default Seeding (PLATFORM-GAP-004, PLATFORM-GAP-008).
     */
    public function test_public_registration_creates_isolated_tenant_with_defaults_and_normalized_email()
    {
        $payload = [
            'name' => 'John Owner',
            'email' => '  John.Owner@Example.COM  ',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Super Retail Shop',
            'currency' => 'PKR'
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role'],
                'business' => ['id', 'name', 'currency'],
                'branch' => ['id', 'name', 'code', 'is_primary'],
                'access_token',
                'token_type'
            ]);

        $this->assertEquals('john.owner@example.com', $response->json('user.email'));

        $businessId = $response->json('business.id');
        $branchId = $response->json('branch.id');

        // Verify default cash account was seeded
        $cashAccount = FinancialAccount::where('business_id', $businessId)->first();
        $this->assertNotNull($cashAccount);
        $this->assertEquals('Main Cash', $cashAccount->name);
        $this->assertEquals('CASH-MAIN', $cashAccount->account_number);
        $this->assertEquals($branchId, $cashAccount->branch_id);

        // Verify default settings were seeded
        $setting = Setting::where('business_id', $businessId)->where('key', 'receipt_header')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('Super Retail Shop', $setting->value);

        // Verify duplicate registration with different casing fails
        $duplicatePayload = [
            'name' => 'Another John',
            'email' => 'john.owner@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Another Shop'
        ];

        $dupResponse = $this->postJson('/api/v1/auth/register', $duplicatePayload);
        $dupResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test Login with Case-Insensitive Email and Inactive Status Check.
     */
    public function test_login_normalizes_email_and_checks_active_status()
    {
        $business = Business::create(['name' => 'Login Test Biz']);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Main', 'code' => 'MAIN', 'is_primary' => true]);
        $user = User::create([
            'name' => 'Active User',
            'email' => 'active.user@shop.com',
            'password' => Hash::make('Secret123!'),
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => 'Business Owner',
            'is_active' => true,
        ]);
        $user->businesses()->sync([$business->id]);

        // Login with uppercase email variation
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ACTIVE.USER@SHOP.COM',
            'password' => 'Secret123!'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'user']);

        // Deactivate user and verify rejection
        $user->update(['is_active' => false]);

        $deactivatedResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'active.user@shop.com',
            'password' => 'Secret123!'
        ]);

        $deactivatedResponse->assertStatus(403)
            ->assertJson(['message' => 'Your account has been deactivated. Please contact your business administrator.']);
    }

    /**
     * Test Password Recovery & Multi-Device Session Invalidation (PLATFORM-GAP-005).
     */
    public function test_password_recovery_and_multi_device_session_invalidation()
    {
        $business = Business::create(['name' => 'Recovery Biz']);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Main', 'code' => 'MAIN', 'is_primary' => true]);
        $user = User::create([
            'name' => 'Recovery User',
            'email' => 'recovery@shop.com',
            'password' => Hash::make('OldPassword123!'),
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->businesses()->sync([$business->id]);

        // Issue 2 active Sanctum tokens
        $token1 = $user->createToken('device1')->plainTextToken;
        $token2 = $user->createToken('device2')->plainTextToken;
        $this->assertEquals(2, $user->tokens()->count());

        // 1. Request Password Reset
        $forgotResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'RECOVERY@SHOP.COM'
        ]);

        $forgotResponse->assertStatus(200);
        $resetToken = $forgotResponse->json('reset_token');
        $this->assertNotEmpty($resetToken);

        // 2. Execute Password Reset
        $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'recovery@shop.com',
            'token' => $resetToken,
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!'
        ]);

        $resetResponse->assertStatus(200);

        // Verify password updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));

        // Verify all active tokens were invalidated on password reset
        $this->assertEquals(0, $user->tokens()->count());

        // Verify old reset token is deleted
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'recovery@shop.com']);
    }

    /**
     * Test Logout All Devices Endpoint (PLATFORM-GAP-005).
     */
    public function test_logout_all_devices_revokes_all_tokens()
    {
        $business = Business::create(['name' => 'Multi Device Biz']);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Main', 'code' => 'MAIN', 'is_primary' => true]);
        $user = User::create([
            'name' => 'Device User',
            'email' => 'device@shop.com',
            'password' => Hash::make('Password123!'),
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->businesses()->sync([$business->id]);

        $user->createToken('terminal_1');
        $user->createToken('terminal_2');
        $user->createToken('mobile_pos');
        $this->assertEquals(3, $user->tokens()->count());

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/auth/logout-all-devices');
        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out from all devices successfully.']);

        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * Test Cross-Tenant IDOR Protection across SupplierReturn and Product (PLATFORM-GAP-002).
     */
    public function test_cross_tenant_idor_protection_for_supplier_returns_and_products()
    {
        // Business A
        $bizA = Business::create(['name' => 'Business A']);
        $branchA = Branch::create(['business_id' => $bizA->id, 'name' => 'Branch A', 'code' => 'BRA', 'is_primary' => true]);
        $userA = User::create([
            'name' => 'User A',
            'email' => 'usera@biz.com',
            'password' => Hash::make('Pass123!'),
            'business_id' => $bizA->id,
            'branch_id' => $branchA->id,
            'is_active' => true,
        ]);
        $userA->businesses()->sync([$bizA->id]);
        setPermissionsTeamId($bizA->id);
        $roleA = Role::firstOrCreate(['name' => 'Business Owner', 'business_id' => $bizA->id]);
        $roleA->givePermissionTo(['view purchases', 'view products']);
        $userA->assignRole($roleA);

        // Business B
        $bizB = Business::create(['name' => 'Business B']);
        $branchB = Branch::create(['business_id' => $bizB->id, 'name' => 'Branch B', 'code' => 'BRB', 'is_primary' => true]);
        $userB = User::create([
            'name' => 'User B',
            'email' => 'userb@biz.com',
            'password' => Hash::make('Pass123!'),
            'business_id' => $bizB->id,
            'branch_id' => $branchB->id,
            'is_active' => true,
        ]);
        $userB->businesses()->sync([$bizB->id]);

        // Create Supplier and SupplierReturn for Business B
        $supplierB = Supplier::create([
            'business_id' => $bizB->id,
            'name' => 'Supplier B Corp',
            'code' => 'SUP-B'
        ]);

        $supplierReturnB = SupplierReturn::create([
            'business_id' => $bizB->id,
            'branch_id' => $branchB->id,
            'supplier_id' => $supplierB->id,
            'return_number' => 'SR-BIZ-B-001',
            'total_amount' => 500.00,
            'status' => 'completed',
            'created_by' => $userB->id,
        ]);

        // Create Product for Business B
        $productB = Product::create([
            'business_id' => $bizB->id,
            'name' => 'Confidential Product B',
            'cost_price' => 100,
            'selling_price' => 200,
            'stock' => 50,
        ]);

        // User A attempts to read Business B SupplierReturn
        Sanctum::actingAs($userA, ['*']);

        $responseReturn = $this->withHeaders(['X-Business-ID' => (string) $bizA->id])
            ->getJson('/api/v1/supplier-returns/' . $supplierReturnB->id);

        $responseReturn->assertStatus(404);

        // User A attempts to read Business B Product
        $responseProduct = $this->withHeaders(['X-Business-ID' => (string) $bizA->id])
            ->getJson('/api/v1/products/' . $productB->id);

        $responseProduct->assertStatus(404);
    }

    /**
     * Test Client Version Handshake and Update Gating (PLATFORM-GAP-007).
     */
    public function test_client_version_handshake_and_upgrade_required_response()
    {
        // 1. Request with Current Supported Version
        $responseCurrent = $this->withHeaders([
            'X-HBOS-App-Version' => '1.0.0'
        ])->getJson('/api/v1/health');

        $responseCurrent->assertStatus(200)
            ->assertHeader('X-HBOS-Api-Version', 'v1')
            ->assertHeader('X-HBOS-Update-Status', 'CURRENT');

        // 2. Request with Outdated Unsupported Version (< 1.0.0)
        $responseOutdated = $this->withHeaders([
            'X-HBOS-App-Version' => '0.8.5'
        ])->getJson('/api/v1/health');

        $responseOutdated->assertStatus(426)
            ->assertJson([
                'status' => 'UPDATE_REQUIRED',
                'current_version' => '0.8.5',
                'minimum_supported_version' => '1.0.0'
            ])
            ->assertHeader('X-HBOS-Update-Status', 'UPDATE_REQUIRED');
    }
}
