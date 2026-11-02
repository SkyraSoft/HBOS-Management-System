<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryMovement;
use App\Models\ActivityLog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RetailWorkforcePrompt2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run canonical role and permission seeder
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Checkpoint 2.1: Business Registration and Staged Onboarding (RETAIL-GAP-001, RETAIL-GAP-002)
     */
    public function test_owner_registration_phone_persistence_and_staged_onboarding_resumption()
    {
        // 1. Owner Registration
        $signupPayload = [
            'name' => 'Tariq Mehmood',
            'email' => 'tariq@hbosretail.pk',
            'phone' => '+923001234567',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'business_name' => 'Mehmood Super Mart',
        ];

        $registerRes = $this->postJson('/api/v1/auth/register', $signupPayload);
        $registerRes->assertStatus(201);
        $registerRes->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email', 'phone', 'business_id'],
            'business' => ['id', 'name', 'onboarding_status', 'onboarding_step'],
            'access_token'
        ]);

        $this->assertEquals('+923001234567', $registerRes->json('user.phone'));
        $this->assertEquals('in_progress', $registerRes->json('business.onboarding_status'));
        $this->assertEquals(2, $registerRes->json('business.onboarding_step'));

        $token = $registerRes->json('access_token');
        $businessId = $registerRes->json('business.id');

        // 2. Duplicate Email rejection
        $dupRes = $this->postJson('/api/v1/auth/register', $signupPayload);
        $dupRes->assertStatus(422);

        // 3. Weak Password rejection
        $weakRes = $this->postJson('/api/v1/auth/register', [
            'name' => 'Weak Pass User',
            'email' => 'weak@hbosretail.pk',
            'password' => 'short',
            'password_confirmation' => 'short',
            'business_name' => 'Weak Mart',
        ]);
        $weakRes->assertStatus(422);

        // 4. Onboarding Status Check (Resumption after session restart)
        $statusRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $businessId,
        ])->getJson('/api/v1/onboarding/status');

        $statusRes->assertStatus(200);
        $this->assertEquals('in_progress', $statusRes->json('onboarding_status'));
        $this->assertEquals(2, $statusRes->json('current_step'));

        // 5. Step 2: Business Identity
        $step2Res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $businessId,
        ])->postJson('/api/v1/onboarding/step', [
            'step' => 2,
            'name' => 'Mehmood Super Mart Flagship',
            'type' => 'Supermarket',
            'phone' => '+923007654321',
            'address' => 'Mall of Lahore, Main Boulevard',
            'city' => 'Lahore',
            'country' => 'Pakistan',
        ]);
        $step2Res->assertStatus(200);
        $this->assertEquals(3, $step2Res->json('business.onboarding_step'));

        // 6. Step 3: Primary Branch
        $step3Res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $businessId,
        ])->postJson('/api/v1/onboarding/step', [
            'step' => 3,
            'branch_name' => 'Lahore Mall Branch',
            'branch_code' => 'LHR-01',
            'branch_address' => 'Shop 102, Ground Floor',
            'branch_city' => 'Lahore',
        ]);
        $step3Res->assertStatus(200);
        $this->assertEquals(4, $step3Res->json('business.onboarding_step'));

        // 7. Step 4: Business Defaults
        $step4Res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $businessId,
        ])->postJson('/api/v1/onboarding/step', [
            'step' => 4,
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'tax_number' => 'NTN-9876543-2',
        ]);
        $step4Res->assertStatus(200);
        $this->assertEquals(5, $step4Res->json('business.onboarding_step'));

        // 8. Step 5: Review & Complete
        $step5Res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $businessId,
        ])->postJson('/api/v1/onboarding/step', [
            'step' => 5,
            'confirm' => true,
        ]);
        $step5Res->assertStatus(200);
        $this->assertEquals('completed', $step5Res->json('business.onboarding_status'));
        $this->assertNotNull($step5Res->json('business.onboarding_completed_at'));

        // Verify database state
        $biz = Business::find($businessId);
        $this->assertEquals('completed', $biz->onboarding_status);
        $this->assertEquals('Mehmood Super Mart Flagship', $biz->name);
        $this->assertEquals('NTN-9876543-2', $biz->tax_number);
    }

    /**
     * Checkpoint 2.2: Branch Profile & Lifecycle (RETAIL-GAP-004)
     */
    public function test_branch_lifecycle_business_scoped_uniqueness_and_membership()
    {
        // Create Tenant A with owner
        $bizA = Business::create(['name' => 'Tenant A Business', 'currency' => 'PKR']);
        setPermissionsTeamId($bizA->id);
        $ownerA = User::create([
            'name' => 'Owner A',
            'email' => 'ownerA@tenanta.com',
            'password' => Hash::make('password123'),
            'business_id' => $bizA->id,
            'is_active' => true,
        ]);
        $ownerA->businesses()->attach($bizA->id);
        $ownerA->assignRole('Business Owner');

        // Create initial primary branch for Tenant A
        $primaryA = Branch::create([
            'business_id' => $bizA->id,
            'name' => 'Tenant A Main',
            'code' => 'MAIN',
            'is_primary' => true,
            'status' => 'active',
        ]);
        $ownerA->branches()->attach($primaryA->id, ['business_id' => $bizA->id]);

        // Create Tenant B with owner
        $bizB = Business::create(['name' => 'Tenant B Business', 'currency' => 'PKR']);
        setPermissionsTeamId($bizB->id);
        $ownerB = User::create([
            'name' => 'Owner B',
            'email' => 'ownerB@tenantb.com',
            'password' => Hash::make('password123'),
            'business_id' => $bizB->id,
            'is_active' => true,
        ]);
        $ownerB->businesses()->attach($bizB->id);
        $ownerB->assignRole('Business Owner');

        // 1. Owner A creates Branch 2 with code 'STORE-01'
        $branchCreateRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerA->createToken('test')->plainTextToken}",
            'X-Business-Id' => $bizA->id,
        ])->postJson('/api/v1/branches', [
            'name' => 'Tenant A North Branch',
            'code' => 'STORE-01',
            'phone' => '+923001112233',
            'city' => 'Islamabad',
            'status' => 'active',
        ]);
        $branchCreateRes->assertStatus(201);
        $newBranchId = $branchCreateRes->json('data.id');

        // Verify creating owner gained membership in Branch 2
        $this->assertTrue($ownerA->fresh()->branches->contains('id', $newBranchId));

        // 2. Duplicate code 'STORE-01' in same business must fail validation
        $dupCodeRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerA->createToken('test')->plainTextToken}",
            'X-Business-Id' => $bizA->id,
        ])->postJson('/api/v1/branches', [
            'name' => 'Tenant A Another Branch',
            'code' => 'STORE-01',
        ]);
        $dupCodeRes->assertStatus(422);

        // 3. Different Tenant B CAN legitimately use the same branch code 'STORE-01'
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $tenantBBranchRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerB->createToken('test')->plainTextToken}",
            'X-Business-Id' => $bizB->id,
        ])->postJson('/api/v1/branches', [
            'name' => 'Tenant B North Branch',
            'code' => 'STORE-01',
            'status' => 'active',
        ]);
        $tenantBBranchRes->assertStatus(201);

        // 4. Primary Branch safety: cannot deactivate primary branch
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $deactPrimaryRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerA->createToken('test')->plainTextToken}",
            'X-Business-Id' => $bizA->id,
        ])->putJson("/api/v1/branches/{$primaryA->id}", [
            'status' => 'inactive',
        ]);
        $deactPrimaryRes->assertStatus(422);

        // 5. Cross-tenant isolation: Owner B cannot alter Tenant A's branch
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $crossTenantRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerB->createToken('test')->plainTextToken}",
            'X-Business-Id' => $bizB->id,
        ])->putJson("/api/v1/branches/{$newBranchId}", [
            'name' => 'Hacked Branch Name',
        ]);
        $crossTenantRes->assertStatus(404);
    }

    /**
     * Checkpoint 2.3: Employee Accounts, Profiles & Role Foundations (RETAIL-GAP-005, 006, 007)
     */
    public function test_employee_creation_invitation_token_password_setup_and_governance()
    {
        $biz = Business::create(['name' => 'Omega Retailers', 'currency' => 'PKR']);
        setPermissionsTeamId($biz->id);

        $branch = Branch::create([
            'business_id' => $biz->id,
            'name' => 'Omega Center',
            'code' => 'OMG-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $owner = User::create([
            'name' => 'Omega Owner',
            'email' => 'owner@omega.pk',
            'password' => Hash::make('password123'),
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $owner->businesses()->attach($biz->id);
        $owner->branches()->attach($branch->id, ['business_id' => $biz->id]);
        $owner->assignRole('Business Owner');

        $ownerToken = $owner->createToken('test')->plainTextToken;

        // 1. Owner creates Employee with invitation token
        $createEmpRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerToken}",
            'X-Business-Id' => $biz->id,
        ])->postJson('/api/v1/users', [
            'name' => 'Usman Cashier',
            'email' => 'usman@omega.pk',
            'phone' => '+923334445566',
            'role' => 'Salesperson',
            'branch_id' => $branch->id,
            'employee_code' => 'EMP-001',
            'job_title' => 'Junior Cashier',
            'send_invitation' => true,
        ]);

        $createEmpRes->assertStatus(201);
        $this->assertNotEmpty($createEmpRes->json('invitation_token'));
        $invitationToken = $createEmpRes->json('invitation_token');
        $employeeId = $createEmpRes->json('data.id');

        // Verify token in database is hashed (not plaintext)
        $employeeUser = User::find($employeeId);
        $this->assertNotEquals($invitationToken, $employeeUser->invitation_token);
        $this->assertTrue(Hash::check($invitationToken, $employeeUser->invitation_token));

        // 2. Invalid invitation token fails
        $invalidTokenRes = $this->postJson('/api/v1/auth/accept-invitation', [
            'token' => 'invalid-bogus-token-123',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $invalidTokenRes->assertStatus(422);

        // 3. Employee sets password via valid single-use invitation token
        $acceptRes = $this->postJson('/api/v1/auth/accept-invitation', [
            'token' => $invitationToken,
            'password' => 'UsmanPass123!',
            'password_confirmation' => 'UsmanPass123!',
        ]);
        $acceptRes->assertStatus(200);
        $this->assertNotEmpty($acceptRes->json('access_token'));

        // 4. Token cannot be reused (Single-Use Token Invariant)
        $reuseRes = $this->postJson('/api/v1/auth/accept-invitation', [
            'token' => $invitationToken,
            'password' => 'AnotherPass123!',
            'password_confirmation' => 'AnotherPass123!',
        ]);
        $reuseRes->assertStatus(422);

        // 5. Employee logs in with newly established password
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'usman@omega.pk',
            'password' => 'UsmanPass123!',
        ]);
        $loginRes->assertStatus(200);
        $empAuthToken = $loginRes->json('access_token');

        // 6. Privilege Escalation Prevention: Salesperson cannot create users or assign roles
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $unauthCreateRes = $this->withHeaders([
            'Authorization' => "Bearer {$empAuthToken}",
            'X-Business-Id' => $biz->id,
        ])->postJson('/api/v1/users', [
            'name' => 'Escalated Admin',
            'email' => 'admin@omega.pk',
            'role' => 'Business Owner',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $unauthCreateRes->assertStatus(403);

        // 7. Deactivation Revokes Active Tokens & Denies Login
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $deactivateRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerToken}",
            'X-Business-Id' => $biz->id,
        ])->postJson("/api/v1/users/{$employeeId}/status", [
            'is_active' => false,
        ]);
        $deactivateRes->assertStatus(200);

        // Deactivated user cannot access with old token (revoked)
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $oldTokenRes = $this->withHeaders([
            'Authorization' => "Bearer {$empAuthToken}",
            'X-Business-Id' => $biz->id,
        ])->getJson('/api/v1/auth/me');
        $oldTokenRes->assertStatus(401);

        // Deactivated user cannot log in
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $deniedLoginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'usman@omega.pk',
            'password' => 'UsmanPass123!',
        ]);
        $deniedLoginRes->assertStatus(403);
    }

    /**
     * Checkpoint 2.3: Canonical Tenant Roles & Permission Verification
     */
    public function test_target_tenant_roles_and_canonical_permissions()
    {
        $expectedRoles = [
            'Business Owner' => 30,
            'Branch Manager' => 23,
            'Salesperson' => 8,
            'Accountant' => 13,
            'Inventory Manager' => 15,
        ];

        foreach ($expectedRoles as $roleName => $expectedPermissionCount) {
            $role = Role::where('name', $roleName)->first();
            $this->assertNotNull($role, "Expected role {$roleName} must exist");
            $this->assertEquals($expectedPermissionCount, $role->permissions()->count(), "Role {$roleName} permission count mismatch");
        }

        // Platform Super Admin must NOT be in tenant roles
        $platformAdmin = Role::where('name', 'Platform Super Admin')->first();
        $this->assertNull($platformAdmin, "Platform Super Admin must NOT be present as an ordinary tenant role");
    }

    /**
     * Checkpoint 2.4: Material Mutation Actor Attribution & Role Preservation (RETAIL-GAP-012, 013)
     */
    public function test_material_mutation_actor_attribution_and_historical_integrity()
    {
        $biz = Business::create(['name' => 'Attribution Retailers', 'currency' => 'PKR']);
        setPermissionsTeamId($biz->id);

        $branch = Branch::create([
            'business_id' => $biz->id,
            'name' => 'Main Outlet',
            'code' => 'MAIN-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $owner = User::create([
            'name' => 'Attribution Owner',
            'email' => 'owner@attribution.pk',
            'password' => Hash::make('password123'),
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $owner->businesses()->attach($biz->id);
        $owner->branches()->attach($branch->id, ['business_id' => $biz->id]);
        $owner->assignRole('Business Owner');

        $clerk = User::create([
            'name' => 'Kareem Clerk',
            'email' => 'kareem@attribution.pk',
            'password' => Hash::make('password123'),
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $clerk->businesses()->attach($biz->id);
        $clerk->branches()->attach($branch->id, ['business_id' => $biz->id]);
        $clerk->assignRole('Salesperson');

        $clerkToken = $clerk->createToken('test')->plainTextToken;

        // Create product
        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Premium Basmati Rice 5kg',
            'sku' => 'RICE-001',
            'selling_price' => 1200,
            'cost_price' => 1000,
            'is_active' => true,
        ]);

        // Create Cash Drawer and Branch Inventory
        \App\Models\FinancialAccount::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'account_number' => 'CASH-MAIN',
            'balance' => 0.00,
            'currency' => 'PKR',
            'is_default' => true,
            'status' => 'active',
        ]);

        \App\Models\BranchInventory::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 20,
        ]);

        // Clerk creates Sale
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$clerkToken}",
            'X-Business-Id' => $biz->id,
            'X-Branch-Id' => $branch->id,
        ])->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'payment_method' => 'cash',
            'amount_paid' => 1200,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 1200,
                ]
            ]
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('id') ?: $saleRes->json('data.id');

        $sale = Sale::find($saleId);
        $this->assertNotNull($sale);
        $this->assertEquals($clerk->id, $sale->created_by);

        // Check activity log attribution
        $log = ActivityLog::where('subject_type', Sale::class)
            ->where('subject_id', $saleId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($clerk->id, $log->user_id);
        $this->assertEquals($biz->id, $log->business_id);
        $this->assertEquals($branch->id, $log->branch_id);

        // Now Owner promotes Clerk to Branch Manager
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $ownerToken = $owner->createToken('test')->plainTextToken;
        $promoteRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerToken}",
            'X-Business-Id' => $biz->id,
        ])->putJson("/api/v1/users/{$clerk->id}", [
            'name' => 'Kareem Clerk',
            'email' => 'kareem@attribution.pk',
            'role' => 'Branch Manager',
        ]);
        $promoteRes->assertStatus(200);

        // Historical sale created_by still points to Kareem Clerk and audit log remains immutable
        $historicalSale = Sale::find($saleId);
        $this->assertEquals($clerk->id, $historicalSale->created_by);
        $historicalLog = ActivityLog::find($log->id);
        $this->assertEquals($clerk->id, $historicalLog->user_id);
    }
}
