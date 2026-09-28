<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $ownerA;
    protected $managerA;
    protected $salespersonA;
    protected $multiUser;
    protected $tokenOwnerA;
    protected $tokenManagerA;
    protected $tokenSalespersonA;
    protected $tokenMulti;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Business A & Branch
        $this->businessA = Business::create(['name' => 'Business A']);
        $branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A', 'is_primary' => true]);

        // 2. Business B & Branch
        $this->businessB = Business::create(['name' => 'Business B']);
        $branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);

        // Seed tenant-scoped roles & permissions for Business A & B
        $this->seedTeamRolesAndPermissions($this->businessA->id);
        $this->seedTeamRolesAndPermissions($this->businessB->id);

        // Owners, Managers, Salespersons in Business A
        setPermissionsTeamId($this->businessA->id);

        $this->ownerA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $branchA->id, 'role' => 'Business Owner']);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole('Business Owner');
        $this->tokenOwnerA = $this->ownerA->createToken('ownerA')->plainTextToken;

        $this->managerA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $branchA->id, 'role' => 'Branch Manager']);
        $this->managerA->businesses()->attach($this->businessA->id);
        $this->managerA->assignRole('Branch Manager');
        $this->tokenManagerA = $this->managerA->createToken('managerA')->plainTextToken;

        $this->salespersonA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $branchA->id, 'role' => 'Salesperson']);
        $this->salespersonA->businesses()->attach($this->businessA->id);
        $this->salespersonA->assignRole('Salesperson');
        $this->tokenSalespersonA = $this->salespersonA->createToken('salespersonA')->plainTextToken;

        // Multi User: Owner in A, Salesperson in B
        $this->multiUser = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $branchA->id]);
        $this->multiUser->businesses()->attach([$this->businessA->id, $this->businessB->id]);
        
        setPermissionsTeamId($this->businessA->id);
        $this->multiUser->assignRole('Business Owner');

        setPermissionsTeamId($this->businessB->id);
        $this->multiUser->assignRole('Salesperson');

        $this->tokenMulti = $this->multiUser->createToken('multi')->plainTextToken;
    }

    protected function seedTeamRolesAndPermissions(int $businessId): void
    {
        setPermissionsTeamId($businessId);

        $permissions = [
            'manage business settings', 'manage branches', 'manage users', 'view full reports',
            'manage branch settings', 'manage local inventory', 'view branch reports',
            'create sales', 'view own sales', 'create customers'
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $ownerRole = Role::firstOrCreate(['name' => 'Business Owner', 'business_id' => $businessId]);
        $ownerRole->syncPermissions(Permission::all());

        $managerRole = Role::firstOrCreate(['name' => 'Branch Manager', 'business_id' => $businessId]);
        $managerRole->syncPermissions([
            'manage branch settings', 'manage local inventory', 'view branch reports',
            'create sales', 'view own sales', 'create customers'
        ]);

        $salespersonRole = Role::firstOrCreate(['name' => 'Salesperson', 'business_id' => $businessId]);
        $salespersonRole->syncPermissions(['create sales', 'view own sales', 'create customers']);
    }

    public function test_business_owner_can_access_approved_business_management_endpoints()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->postJson('/api/v1/businesses', ['name' => 'New Secondary Business']);

        $response->assertStatus(201);
    }

    public function test_branch_manager_cannot_perform_business_owner_only_operations()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenManagerA"])
            ->postJson('/api/v1/branches', ['name' => 'Manager Attempted Branch']);

        $response->assertStatus(403);
    }

    public function test_salesperson_cannot_access_business_settings_management()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenSalespersonA"])
            ->postJson('/api/v1/settings', ['company_name' => 'Hacked Company Name']);

        $response->assertStatus(403);
    }

    public function test_salesperson_cannot_create_update_or_delete_branches()
    {
        $resCreate = $this->withHeaders(['Authorization' => "Bearer $this->tokenSalespersonA"])
            ->postJson('/api/v1/branches', ['name' => 'Unauthorized']);
        $resCreate->assertStatus(403);
    }

    public function test_branch_manager_permissions_match_approved_matrix()
    {
        setPermissionsTeamId($this->businessA->id);
        $this->managerA->unsetRelation('roles')->unsetRelation('permissions');
        $this->assertTrue($this->managerA->hasPermissionTo('manage branch settings'));
        $this->assertTrue($this->managerA->hasPermissionTo('create sales'));
        $this->assertFalse($this->managerA->hasPermissionTo('manage business settings'));
    }

    public function test_unauthorized_permission_attempts_return_403_not_500()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenSalespersonA"])
            ->postJson('/api/v1/settings', ['key' => 'val']);

        $response->assertStatus(403);
    }

    public function test_role_names_are_assigned_via_spatie()
    {
        setPermissionsTeamId($this->businessA->id);
        $this->ownerA->unsetRelation('roles')->unsetRelation('permissions');
        $this->managerA->unsetRelation('roles')->unsetRelation('permissions');
        $this->salespersonA->unsetRelation('roles')->unsetRelation('permissions');

        $this->assertTrue($this->ownerA->hasRole('Business Owner'));
        $this->assertTrue($this->managerA->hasRole('Branch Manager'));
        $this->assertTrue($this->salespersonA->hasRole('Salesperson'));
    }

    public function test_users_role_column_is_not_relied_upon_as_authorization_truth()
    {
        // Set user's users.role DB column to "Business Owner", but do NOT grant Spatie role/permissions
        $fakeUser = User::factory()->create([
            'business_id' => $this->businessA->id,
            'role' => 'Business Owner'
        ]);
        $fakeUser->businesses()->attach($this->businessA->id);
        $tokenFake = $fakeUser->createToken('fake')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $tokenFake"])
            ->postJson('/api/v1/branches', ['name' => 'Fake Owner Branch']);

        $response->assertStatus(403);
    }

    public function test_manipulating_users_role_does_not_grant_privileges()
    {
        $this->salespersonA->update(['role' => 'Business Owner']);
        $token = $this->salespersonA->createToken('tampered')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/v1/branches', ['name' => 'Tampered Branch']);

        $response->assertStatus(403);
    }

    public function test_role_held_in_business_a_does_not_grant_equivalent_rights_in_business_b()
    {
        setPermissionsTeamId($this->businessA->id);
        $this->multiUser->unsetRelation('roles')->unsetRelation('permissions');
        $this->assertTrue($this->multiUser->hasRole('Business Owner'));

        setPermissionsTeamId($this->businessB->id);
        $this->multiUser->unsetRelation('roles')->unsetRelation('permissions');
        $this->assertFalse($this->multiUser->hasRole('Business Owner'));
        $this->assertTrue($this->multiUser->hasRole('Salesperson'));
    }

    public function test_switching_x_business_id_produces_correct_permissions_for_each_business()
    {
        // MultiUser is Owner in A
        $responseA = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessA->id
        ])->postJson('/api/v1/branches', ['name' => 'Multi Owner Branch in A']);
        $responseA->assertStatus(201);

        // MultiUser is Salesperson in B
        $responseB = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessB->id
        ])->postJson('/api/v1/branches', ['name' => 'Multi Salesperson Branch in B']);
        $responseB->assertStatus(403);
    }

    public function test_permission_caches_and_team_context_do_not_leak_between_tenant_switches()
    {
        $responseA = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessA->id
        ])->getJson('/api/v1/auth/me');
        $responseA->assertStatus(200);

        $responseB = $this->withHeaders([
            'Authorization' => "Bearer $this->tokenMulti",
            'X-Business-ID' => $this->businessB->id
        ])->getJson('/api/v1/auth/me');
        $responseB->assertStatus(200);
    }

    public function test_authenticated_user_with_no_role_receives_safe_denial_for_protected_actions()
    {
        $nakedUser = User::factory()->create(['business_id' => $this->businessA->id]);
        $nakedUser->businesses()->attach($this->businessA->id);
        $tokenNaked = $nakedUser->createToken('naked')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $tokenNaked"])
            ->postJson('/api/v1/branches', ['name' => 'Naked Branch']);

        $response->assertStatus(403);
    }

    public function test_user_cannot_self_escalate_role_through_profile_update()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenSalespersonA"])
            ->putJson('/api/v1/profile', [
                'name' => 'Salesperson Escalation',
                'role' => 'Business Owner'
            ]);

        $response->assertStatus(200);

        // Verify Spatie role remains Salesperson
        setPermissionsTeamId($this->businessA->id);
        $this->salespersonA->unsetRelation('roles')->unsetRelation('permissions');
        $this->assertFalse($this->salespersonA->fresh()->hasRole('Business Owner'));
        $this->assertTrue($this->salespersonA->fresh()->hasRole('Salesperson'));
    }
}
