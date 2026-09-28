<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Services\AuditService;
use App\Services\UserGovernanceService;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Phase9GovernanceAndAuditTest extends TestCase
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

        // 1. Create Businesses & Branches
        $this->businessA = Business::create(['name' => 'Tenant A', 'currency' => 'PKR']);
        $this->businessB = Business::create(['name' => 'Tenant B', 'currency' => 'PKR']);

        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'HQ Branch A1', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A2', 'is_primary' => false]);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'HQ Branch B1', 'is_primary' => true]);

        // 2. Setup Spatie Roles & Permissions for Tenant A
        setPermissionsTeamId($this->businessA->id);
        $roleOwnerA = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $roleManagerA = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $roleSalespersonA = Role::firstOrCreate(['name' => 'Salesperson', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);

        $permManageSettings = Permission::firstOrCreate(['name' => 'manage business settings', 'guard_name' => 'web']);
        $permManageBranches = Permission::firstOrCreate(['name' => 'manage branches', 'guard_name' => 'web']);
        $permViewAudit = Permission::firstOrCreate(['name' => 'view audit logs', 'guard_name' => 'web']);
        $permManageUsers = Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);

        $roleOwnerA->givePermissionTo([$permManageSettings, $permManageBranches, $permViewAudit, $permManageUsers]);
        $roleManagerA->givePermissionTo([$permViewAudit]);

        // 3. Create Users
        $this->ownerA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'is_active' => true
        ]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole($roleOwnerA);

        $this->managerA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'is_active' => true
        ]);
        $this->managerA1->businesses()->attach($this->businessA->id);
        $this->managerA1->assignRole($roleManagerA);

        $this->salespersonA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'is_active' => true
        ]);
        $this->salespersonA1->businesses()->attach($this->businessA->id);
        $this->salespersonA1->assignRole($roleSalespersonA);

        // Tenant B setup
        setPermissionsTeamId($this->businessB->id);
        $roleOwnerB = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessB->id]);
        $roleOwnerB->givePermissionTo([$permManageSettings, $permManageBranches, $permViewAudit, $permManageUsers]);

        $this->ownerB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB1->id,
            'is_active' => true
        ]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole($roleOwnerB);
    }

    public function test_audit_migration_schema_and_tenant_columns()
    {
        $this->assertTrue(Schema::hasColumn('activity_log', 'business_id'));
        $this->assertTrue(Schema::hasColumn('activity_log', 'branch_id'));
    }

    public function test_audit_sensitive_data_redaction()
    {
        $rawProperties = [
            'username' => 'testuser',
            'password' => 'supersecret123',
            'password_confirmation' => 'supersecret123',
            'api_token' => 'abc123xyz',
            'token' => 'plain_text_token',
            'nested' => [
                'pin' => '1234',
                'secret_key' => 'shhh',
                'safe_field' => 'allowed_value'
            ],
            'key' => 'receipt_footer' // Should NOT be redacted as it is a safe setting key
        ];

        $sanitized = AuditService::sanitizeProperties($rawProperties);

        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['password_confirmation']);
        $this->assertEquals('[REDACTED]', $sanitized['api_token']);
        $this->assertEquals('[REDACTED]', $sanitized['token']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['pin']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['secret_key']);
        $this->assertEquals('allowed_value', $sanitized['nested']['safe_field']);
        $this->assertEquals('receipt_footer', $sanitized['key']);
    }

    public function test_audit_service_records_tenant_and_branch_provenance()
    {
        app()->instance('active_business_id', $this->businessA->id);

        $activity = AuditService::logAction(
            'inventory',
            'updated',
            'Stock adjusted for Branch A1',
            $this->branchA1,
            ['delta' => 10],
            $this->branchA1->id,
            $this->ownerA
        );

        $this->assertNotNull($activity);
        $this->assertEquals($this->businessA->id, $activity->business_id);
        $this->assertEquals($this->branchA1->id, $activity->branch_id);
        $this->assertEquals($this->ownerA->id, $activity->causer_id);
    }

    public function test_audit_log_tenant_and_branch_isolation_api()
    {
        // Tenant A log
        app()->instance('active_business_id', $this->businessA->id);
        $logA = AuditService::logAction('settings', 'updated', 'Tenant A log', null, [], null, $this->ownerA);

        // Tenant B log
        app()->instance('active_business_id', $this->businessB->id);
        $logB = AuditService::logAction('settings', 'updated', 'Tenant B log', null, [], null, $this->ownerB);

        // 1. Owner A queries logs
        $resA = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->getJson('/api/v1/audit-logs');

        $resA->assertStatus(200);
        $idsA = collect($resA->json('data'))->pluck('id');
        $this->assertTrue($idsA->contains($logA->id));
        $this->assertFalse($idsA->contains($logB->id));

        // 2. Owner A cannot access Tenant B log by ID (404 expected)
        $resForeign = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->getJson("/api/v1/audit-logs/{$logB->id}");

        $resForeign->assertStatus(404);

        // 3. Manager A1 sees only Branch A1 operational logs, not central settings logs
        app()->instance('active_business_id', $this->businessA->id);
        $branchLogA1 = AuditService::logAction('inventory', 'created', 'Branch A1 stock intake', null, [], $this->branchA1->id, $this->managerA1);

        $resManager = $this->actingAs($this->managerA1)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->getJson('/api/v1/audit-logs');

        $resManager->assertStatus(200);
        $managerLogIds = collect($resManager->json('data'))->pluck('id');
        $this->assertTrue($managerLogIds->contains($branchLogA1->id));
        $this->assertFalse($managerLogIds->contains($logA->id)); // Settings log excluded for branch manager

        // 4. Salesperson is denied audit log access (403 Forbidden)
        $resSales = $this->actingAs($this->salespersonA1)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->getJson('/api/v1/audit-logs');

        $resSales->assertStatus(403);
    }

    public function test_audit_routes_are_append_only()
    {
        // Mutating audit logs via API must not exist (404 or 405)
        $resPost = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->postJson('/api/v1/audit-logs', ['description' => 'fake']);
        $this->assertTrue(in_array($resPost->status(), [404, 405]));

        $resDelete = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->deleteJson('/api/v1/audit-logs/1');
        $this->assertTrue(in_array($resDelete->status(), [404, 405]));
    }

    public function test_settings_allowlist_and_type_validation()
    {
        // 1. Allowed settings write
        $res = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->postJson('/api/v1/settings', [
                'settings' => [
                    'receipt_header' => 'Welcome to Tenant A',
                    'show_tax_number' => true,
                    'receipt_settings' => ['font' => 'bold', 'copies' => 2]
                ]
            ]);

        $res->assertStatus(200);

        // 2. Disallowed unlisted key
        $resDisallowed = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->postJson('/api/v1/settings', [
                'settings' => [
                    'arbitrary_unauthorized_key' => 'evil_value'
                ]
            ]);

        $resDisallowed->assertStatus(422);

        // 3. Manager without permission cannot modify settings
        $resManager = $this->actingAs($this->managerA1)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->postJson('/api/v1/settings', [
                'settings' => ['receipt_header' => 'Hacked Header']
            ]);

        $resManager->assertStatus(403);
    }

    public function test_business_controller_deletion_and_currency_guards()
    {
        // 1. Create a sale so the business has operational transactional history
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-TEST-001',
            'date' => now()->format('Y-m-d'),
            'total' => 500,
            'subtotal' => 500,
            'status' => 'completed'
        ]);

        // Attempt to delete business with operational history must be blocked
        $resDelete = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->deleteJson("/api/v1/businesses/{$this->businessA->id}");

        $resDelete->assertStatus(422);

        // Attempt to modify currency once transactions exist must be blocked
        $resCurrency = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->putJson("/api/v1/businesses/{$this->businessA->id}", [
                'name' => 'Renamed Tenant A',
                'currency' => 'USD'
            ]);

        $resCurrency->assertStatus(422);
    }

    public function test_branch_primary_and_operational_deletion_guards()
    {
        // 1. Attempt to delete primary branch must be blocked
        $resDeletePrimary = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->deleteJson("/api/v1/branches/{$this->branchA1->id}");

        $resDeletePrimary->assertStatus(422);

        // 2. Attach a sale to Branch A2
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-TEST-002',
            'date' => now()->format('Y-m-d'),
            'total' => 250,
            'subtotal' => 250,
            'status' => 'completed'
        ]);

        // Attempt to delete non-primary Branch A2 with operational history must be rejected
        $resDeleteA2 = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->deleteJson("/api/v1/branches/{$this->branchA2->id}");

        $resDeleteA2->assertStatus(422);
    }

    public function test_user_governance_staff_creation_and_last_owner_safety()
    {
        // 1. Create a staff user (Branch Manager)
        $resCreate = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->postJson('/api/v1/users', [
                'name' => 'New Staff Member',
                'email' => 'newstaff@tenanta.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'role' => 'Branch Manager',
                'branch_id' => $this->branchA2->id
            ]);

        $resCreate->assertStatus(201);
        $newUserId = $resCreate->json('data.id');

        // Verify role and branch assigned correctly
        $newUser = User::find($newUserId);
        $this->assertEquals($this->businessA->id, $newUser->business_id);
        $this->assertEquals($this->branchA2->id, $newUser->branch_id);

        // 2. Last Owner Safety: Attempt to deactivate sole Business Owner (ownerA) must be rejected
        $resDeactivateOwner = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->postJson("/api/v1/users/{$this->ownerA->id}/status", [
                'is_active' => false
            ]);

        // Blocked by self-governance guard or last owner guard (422)
        $resDeactivateOwner->assertStatus(422);

        // 3. Self-governance: Owner cannot demote own role
        $resDemoteSelf = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->putJson("/api/v1/users/{$this->ownerA->id}", [
                'role' => 'Salesperson'
            ]);

        $resDemoteSelf->assertStatus(422);
    }

    public function test_inactive_user_is_denied_api_access()
    {
        // Deactivate salesperson
        $this->salespersonA1->update(['is_active' => false]);

        $res = $this->actingAs($this->salespersonA1)
            ->withHeader('X-Business-Id', $this->businessA->id)
            ->getJson('/api/v1/products');

        $res->assertStatus(403);
        $this->assertStringContainsString('deactivated', strtolower($res->json('message')));
    }
}
