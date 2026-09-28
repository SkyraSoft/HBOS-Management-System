<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\ActivityLog;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class Phase9UserGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;
    protected User $ownerA;
    protected User $managerA1;
    protected User $salespersonA1;
    protected User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessA = Business::create(['name' => 'Tenant Alpha', 'currency' => 'PKR']);
        $this->businessB = Business::create(['name' => 'Tenant Beta', 'currency' => 'PKR']);

        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Alpha HQ', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Alpha Branch 2', 'is_primary' => false]);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Beta HQ', 'is_primary' => true]);

        // Permissions
        $permManageUsers = Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
        $permViewAudit = Permission::firstOrCreate(['name' => 'view audit logs', 'guard_name' => 'web']);
        $permManageSettings = Permission::firstOrCreate(['name' => 'manage business settings', 'guard_name' => 'web']);

        // Roles in Business A
        setPermissionsTeamId($this->businessA->id);
        $roleOwnerA = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessA->id, 'business_id' => $this->businessA->id]);
        $roleManagerA = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web', 'team_id' => $this->businessA->id, 'business_id' => $this->businessA->id]);
        $roleSalespersonA = Role::firstOrCreate(['name' => 'Salesperson', 'guard_name' => 'web', 'team_id' => $this->businessA->id, 'business_id' => $this->businessA->id]);

        $roleOwnerA->syncPermissions([$permManageUsers, $permViewAudit, $permManageSettings]);
        $roleManagerA->syncPermissions([$permViewAudit]);

        // Roles in Business B
        setPermissionsTeamId($this->businessB->id);
        $roleOwnerB = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessB->id, 'business_id' => $this->businessB->id]);
        $roleManagerB = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web', 'team_id' => $this->businessB->id, 'business_id' => $this->businessB->id]);
        $roleSalespersonB = Role::firstOrCreate(['name' => 'Salesperson', 'guard_name' => 'web', 'team_id' => $this->businessB->id, 'business_id' => $this->businessB->id]);

        $roleOwnerB->syncPermissions([$permManageUsers, $permViewAudit, $permManageSettings]);
        $roleManagerB->syncPermissions([$permViewAudit]);

        // Users in Business A
        setPermissionsTeamId($this->businessA->id);
        $this->ownerA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'role' => 'Business Owner',
            'is_active' => true,
        ]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole($roleOwnerA);

        $this->managerA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'role' => 'Branch Manager',
            'is_active' => true,
        ]);
        $this->managerA1->businesses()->attach($this->businessA->id);
        $this->managerA1->assignRole($roleManagerA);

        $this->salespersonA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'role' => 'Salesperson',
            'is_active' => true,
        ]);
        $this->salespersonA1->businesses()->attach($this->businessA->id);
        $this->salespersonA1->assignRole($roleSalespersonA);

        // Users in Business B
        setPermissionsTeamId($this->businessB->id);
        $this->ownerB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB1->id,
            'role' => 'Business Owner',
            'is_active' => true,
        ]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole($roleOwnerB);
    }

    /**
     * AE. User List Tenant Security
     */
    public function test_user_list_tenant_security()
    {
        // Owner A queries /api/v1/users
        $resA = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/users');

        $resA->assertStatus(200);
        $userIdsA = collect($resA->json('data'))->pluck('id');
        $this->assertTrue($userIdsA->contains($this->ownerA->id));
        $this->assertTrue($userIdsA->contains($this->managerA1->id));
        $this->assertTrue($userIdsA->contains($this->salespersonA1->id));
        $this->assertFalse($userIdsA->contains($this->ownerB->id), 'Owner A must not see Business B users');

        // Multi-business user switching contexts
        $sharedUser = User::factory()->create(['is_active' => true]);
        $sharedUser->businesses()->attach([$this->businessA->id, $this->businessB->id]);

        $resSwitchA = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/users');
        $this->assertTrue(collect($resSwitchA->json('data'))->pluck('id')->contains($sharedUser->id));

        $resSwitchB = $this->actingAs($this->ownerB)
            ->withHeader('X-Business-ID', $this->businessB->id)
            ->getJson('/api/v1/users');
        $this->assertTrue(collect($resSwitchB->json('data'))->pluck('id')->contains($sharedUser->id));
        $this->assertFalse(collect($resSwitchB->json('data'))->pluck('id')->contains($this->managerA1->id));
    }

    /**
     * AF. Staff Creation Tenant Security
     */
    public function test_staff_creation_tenant_security()
    {
        // 1. Successful staff creation in active business
        $res = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/users', [
                'name' => 'Valid Staff User',
                'email' => 'staff@alpha.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'role' => 'Branch Manager',
                'branch_id' => $this->branchA2->id,
            ]);

        $res->assertStatus(201);
        $userId = $res->json('data.id');
        $createdUser = User::findOrFail($userId);

        $this->assertTrue($createdUser->businesses()->where('businesses.id', $this->businessA->id)->exists());
        $this->assertFalse($createdUser->businesses()->where('businesses.id', $this->businessB->id)->exists());
        $this->assertEquals($this->branchA2->id, $createdUser->branch_id);
        $this->assertTrue(Hash::check('SecurePass123!', $createdUser->password));

        // 2. Reject foreign branch ID
        $resForeignBranch = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/users', [
                'name' => 'Foreign Branch Staff',
                'email' => 'foreignbranch@alpha.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'role' => 'Salesperson',
                'branch_id' => $this->branchB1->id, // Branch B1 belongs to Business B
            ]);

        $resForeignBranch->assertStatus(422);

        // 3. Spoofed business_id payload is ignored; user is created in active header business
        $resSpoof = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/users', [
                'business_id' => $this->businessB->id,
                'name' => 'Spoof Test User',
                'email' => 'spoof@alpha.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'role' => 'Salesperson',
                'branch_id' => $this->branchA1->id,
            ]);

        $resSpoof->assertStatus(201);
        $spoofedUser = User::findOrFail($resSpoof->json('data.id'));
        $this->assertTrue($spoofedUser->businesses()->where('businesses.id', $this->businessA->id)->exists());
        $this->assertFalse($spoofedUser->businesses()->where('businesses.id', $this->businessB->id)->exists());
    }

    /**
     * AG. Canonical Roles Only
     */
    public function test_canonical_roles_enforcement()
    {
        $invalidRoles = ['Super Admin', 'Admin', 'Cashier', 'Custom Role', 'Auditor'];

        foreach ($invalidRoles as $invalidRole) {
            $res = $this->actingAs($this->ownerA)
                ->withHeader('X-Business-ID', $this->businessA->id)
                ->postJson('/api/v1/users', [
                    'name' => "Invalid Role {$invalidRole}",
                    'email' => "invalid_{$invalidRole}@alpha.com",
                    'password' => 'SecurePass123!',
                    'password_confirmation' => 'SecurePass123!',
                    'role' => $invalidRole,
                    'branch_id' => $this->branchA1->id,
                ]);

            $res->assertStatus(422);
        }
    }

    /**
     * AH. Role Team Isolation
     */
    public function test_role_team_isolation_across_businesses()
    {
        // Create user participating in both businesses, assigned to Branch A1 in Business A
        $multiUser = User::factory()->create([
            'is_active' => true,
            'branch_id' => $this->branchA1->id,
        ]);
        $multiUser->businesses()->attach([$this->businessA->id, $this->businessB->id]);
        $multiUser->branches()->attach($this->branchA1->id, ['business_id' => $this->businessA->id]);

        // In Business A, role is Branch Manager
        setPermissionsTeamId($this->businessA->id);
        $roleManagerA = Role::where('name', 'Branch Manager')->where('business_id', $this->businessA->id)->first();
        $multiUser->assignRole($roleManagerA);

        // In Business B, role is Salesperson
        setPermissionsTeamId($this->businessB->id);
        $roleSalespersonB = Role::where('name', 'Salesperson')->where('business_id', $this->businessB->id)->first();
        $multiUser->assignRole($roleSalespersonB);

        // In Business A: multiUser has view audit logs permission
        setPermissionsTeamId($this->businessA->id);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $multiUser->unsetRelation('roles')->unsetRelation('permissions');
        $this->assertTrue($multiUser->hasPermissionTo('view audit logs'));

        // In Business B: multiUser DOES NOT have view audit logs permission
        setPermissionsTeamId($this->businessB->id);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $multiUser->unsetRelation('roles')->unsetRelation('permissions');
        $this->assertFalse($multiUser->hasPermissionTo('view audit logs'));

        // Test through API routes with headers
        $resA = $this->actingAs($multiUser)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs');
        $resA->assertStatus(200);

        $resB = $this->actingAs($multiUser)
            ->withHeader('X-Business-ID', $this->businessB->id)
            ->getJson('/api/v1/audit-logs');
        $resB->assertStatus(403);
    }

    /**
     * AI & AJ. Last Owner Safety & Transactional Locking
     */
    public function test_last_owner_safety_and_locking()
    {
        // 1. Single active owner cannot be demoted
        $resDemoteSingle = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/users/{$this->ownerA->id}", [
                'role' => 'Branch Manager',
            ]);
        $resDemoteSingle->assertStatus(422);

        // 2. Single active owner cannot be deactivated
        $resDeactivateSingle = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson("/api/v1/users/{$this->ownerA->id}/status", [
                'is_active' => false,
            ]);
        $resDeactivateSingle->assertStatus(422);

        // 3. Add a second Business Owner
        setPermissionsTeamId($this->businessA->id);
        $secondOwner = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'role' => 'Business Owner',
            'is_active' => true,
        ]);
        $secondOwner->businesses()->attach($this->businessA->id);
        $roleOwnerA = Role::where('name', 'Business Owner')->where('business_id', $this->businessA->id)->first();
        $secondOwner->assignRole($roleOwnerA);

        // Now first owner demoting the second owner succeeds
        $resDemoteSecond = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/users/{$secondOwner->id}", [
                'role' => 'Branch Manager',
            ]);
        $resDemoteSecond->assertStatus(200);
        $this->assertEquals('Branch Manager', $secondOwner->fresh()->role);

        // Now attempting to demote ownerA again fails because ownerA is once again the sole owner
        $resDemoteLastAgain = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/users/{$this->ownerA->id}", [
                'role' => 'Salesperson',
            ]);
        $resDemoteLastAgain->assertStatus(422);
    }

    /**
     * AK. Self-Governance Safety
     */
    public function test_self_governance_rules()
    {
        // Add second owner so last-owner rule is not what blocks self-demotion
        setPermissionsTeamId($this->businessA->id);
        $secondOwner = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'role' => 'Business Owner',
            'is_active' => true,
        ]);
        $secondOwner->businesses()->attach($this->businessA->id);
        $roleOwnerA = Role::where('name', 'Business Owner')->where('business_id', $this->businessA->id)->first();
        $secondOwner->assignRole($roleOwnerA);

        // 1. Owner cannot demote self even with 2 owners present
        $resSelfDemote = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/users/{$this->ownerA->id}", [
                'role' => 'Branch Manager',
            ]);
        $resSelfDemote->assertStatus(422);

        // 2. Owner cannot deactivate self even with 2 owners present
        $resSelfDeactivate = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson("/api/v1/users/{$this->ownerA->id}/status", [
                'is_active' => false,
            ]);
        $resSelfDeactivate->assertStatus(422);

        // 3. Owner CAN update own profile name and email via ProfileController
        $resProfile = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson('/api/v1/profile', [
                'name' => 'Renamed Owner Alpha',
                'email' => 'newowner@alpha.com',
            ]);
        $resProfile->assertStatus(200);
        $this->assertEquals('Renamed Owner Alpha', $this->ownerA->fresh()->name);
    }

    /**
     * AL & AM. Inactive User Enforcement and Token Revocation
     */
    public function test_inactive_user_enforcement_and_token_revocation()
    {
        // 1. Issue Sanctum token for staff member
        $token = $this->salespersonA1->createToken('test-token')->plainTextToken;

        // Verify token works while active
        $resActive = $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/profile');
        $resActive->assertStatus(200);

        // 2. Deactivate staff member via User Governance endpoint
        $resDeactivate = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson("/api/v1/users/{$this->salespersonA1->id}/status", [
                'is_active' => false,
            ]);
        $resDeactivate->assertStatus(200);

        // 3. Attempting requests with previously valid token must be denied (401 due to token revocation or 403)
        $this->app['auth']->forgetGuards();
        $resInactive = $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/profile');

        $this->assertTrue(in_array($resInactive->status(), [401, 403]));

        // Direct actingAs inactive user also returns 403 via EnsureUserIsActive middleware
        $resActingAs = $this->actingAs($this->salespersonA1->fresh())
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/products');
        $resActingAs->assertStatus(403);

        // 4. Public auth routes (login/register) remain unaffected
        $resLogin = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@hbos.com',
            'password' => 'wrongpassword',
        ]);
        $this->assertTrue(in_array($resLogin->status(), [401, 422]));
    }

    /**
     * M & N. Password Update Redaction and Audit Deduplication
     */
    public function test_password_update_redaction_and_audit_deduplication()
    {
        // 1. Password update via profile
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
            'is_active' => true,
        ]);
        $user->businesses()->attach($this->businessA->id);

        $resPass = $this->actingAs($user)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'OldPassword123!',
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ]);
        $resPass->assertStatus(200);

        // Verify password hash or plaintext is NEVER in activity_log
        $recentLogs = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->get();

        foreach ($recentLogs as $log) {
            $props = json_encode($log->properties);
            $this->assertStringNotContainsString('NewPassword123!', $props);
            $this->assertStringNotContainsString('OldPassword123!', $props);
            $this->assertStringNotContainsString($user->fresh()->password, $props);
        }

        // 2. Audit Deduplication: User Governance update creates exactly ONE audit record
        $initialLogCount = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $this->salespersonA1->id)
            ->count();

        $resUpdate = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/users/{$this->salespersonA1->id}", [
                'name' => 'Updated Salesperson Name',
                'branch_id' => $this->branchA2->id,
            ]);
        $resUpdate->assertStatus(200);

        $afterLogCount = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $this->salespersonA1->id)
            ->count();

        $this->assertEquals($initialLogCount + 1, $afterLogCount, 'Exactly ONE coherent audit record must be created, no duplicate auto-logs');
    }
}
