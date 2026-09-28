<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\ActivityLog;
use App\Services\AuditService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Phase9AuditSecurityTest extends TestCase
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

        // 1. Businesses & Branches
        $this->businessA = Business::create(['name' => 'Tenant Alpha', 'currency' => 'PKR']);
        $this->businessB = Business::create(['name' => 'Tenant Beta', 'currency' => 'PKR']);

        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A1', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A2', 'is_primary' => false]);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B1', 'is_primary' => true]);

        // 2. Roles & Permissions for Tenant A
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

        // 3. Users Tenant A
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

        // 4. Setup Tenant B
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

    /**
     * B. Activity Log Schema & Package Compatibility
     */
    public function test_activity_log_schema_and_package_compatibility()
    {
        $this->assertTrue(Schema::hasColumn('activity_log', 'business_id'));
        $this->assertTrue(Schema::hasColumn('activity_log', 'branch_id'));
        $this->assertTrue(Schema::hasColumn('activity_log', 'properties'));
        $this->assertTrue(Schema::hasColumn('activity_log', 'attribute_changes'));
        $this->assertTrue(Schema::hasColumn('activity_log', 'log_name'));
        $this->assertTrue(Schema::hasColumn('activity_log', 'event'));

        // Verify Spatie package can log and retrieve via ActivityLog model
        app()->instance('active_business_id', $this->businessA->id);
        activity('governance')
            ->performedOn($this->branchA1)
            ->causedBy($this->ownerA)
            ->withProperties(['test_key' => 'test_val'])
            ->log('Test Spatie Activity');

        $log = ActivityLog::where('description', 'Test Spatie Activity')->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->businessA->id, $log->business_id);
        $this->assertEquals($this->branchA1->id, $log->branch_id);
        $this->assertEquals($this->ownerA->id, $log->causer_id);
        $this->assertEquals('test_val', $log->getProperty('test_key'));
    }

    /**
     * C. Audit Tenant Isolation
     */
    public function test_audit_tenant_isolation_list_filter_search_and_show()
    {
        ActivityLog::truncate();

        // Create logs for Tenant A
        AuditService::logAction(
            logName: 'sale',
            event: 'cancelled',
            description: 'Tenant A Sale #101 Cancelled',
            subject: $this->branchA1,
            properties: ['amount' => 500],
            branchId: $this->branchA1->id,
            causer: $this->ownerA,
            businessId: $this->businessA->id
        );

        // Create logs for Tenant B
        AuditService::logAction(
            logName: 'sale',
            event: 'cancelled',
            description: 'Tenant B Confidential Sale #999 Cancelled',
            subject: $this->branchB1,
            properties: ['amount' => 99999],
            branchId: $this->branchB1->id,
            causer: $this->ownerB,
            businessId: $this->businessB->id
        );

        $logB = ActivityLog::where('business_id', $this->businessB->id)->first();

        // 1. Owner A list: sees only Tenant A
        $responseA = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs');

        $responseA->assertStatus(200);
        $items = $responseA->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals('Tenant A Sale #101 Cancelled', $items[0]['description']);
        $this->assertStringNotContainsString('Confidential', json_encode($items));

        // 2. Owner A search: searching for Tenant B keywords yields empty result
        $searchRes = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs?search=Confidential');
        $searchRes->assertStatus(200);
        $this->assertCount(0, $searchRes->json('data'));

        // 3. Owner A show: attempting to access Tenant B audit log by ID returns 404 (IDOR prevention)
        $showForeign = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson("/api/v1/audit-logs/{$logB->id}");
        $showForeign->assertStatus(404);
    }

    /**
     * D. Manager Audit Scope
     */
    public function test_manager_audit_scope_and_forbidden_categories()
    {
        ActivityLog::truncate();

        // 1. Branch A1 operational event (allowed for Manager A1)
        AuditService::logAction(
            logName: 'inventory',
            event: 'updated',
            description: 'Branch A1 Inventory Adjusted',
            subject: $this->branchA1,
            properties: ['delta' => 10],
            branchId: $this->branchA1->id,
            causer: $this->ownerA,
            businessId: $this->businessA->id
        );

        // 2. Branch A2 operational event (forbidden for Manager A1)
        AuditService::logAction(
            logName: 'inventory',
            event: 'updated',
            description: 'Branch A2 Inventory Adjusted',
            subject: $this->branchA2,
            properties: ['delta' => 5],
            branchId: $this->branchA2->id,
            causer: $this->ownerA,
            businessId: $this->businessA->id
        );

        // 3. Business settings event (forbidden for Manager A1)
        AuditService::logAction(
            logName: 'settings',
            event: 'updated',
            description: 'Store settings updated',
            subject: null,
            properties: ['key' => 'receipt_header'],
            branchId: null,
            causer: $this->ownerA,
            businessId: $this->businessA->id
        );

        // 4. User governance event (forbidden for Manager A1)
        AuditService::logAction(
            logName: 'governance',
            event: 'created',
            description: 'New Staff Member Invited',
            subject: $this->salespersonA1,
            properties: ['role' => 'Salesperson'],
            branchId: $this->branchA1->id,
            causer: $this->ownerA,
            businessId: $this->businessA->id
        );

        $managerResponse = $this->actingAs($this->managerA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs');

        $managerResponse->assertStatus(200);
        $items = $managerResponse->json('data');

        // Manager must see EXACTLY 1 item: Branch A1 operational inventory
        $this->assertCount(1, $items);
        $this->assertEquals('Branch A1 Inventory Adjusted', $items[0]['description']);

        // Verify Manager cannot show Branch A2 or governance logs by ID
        $logA2 = ActivityLog::where('description', 'Branch A2 Inventory Adjusted')->first();
        $logGov = ActivityLog::where('description', 'New Staff Member Invited')->first();

        $this->actingAs($this->managerA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson("/api/v1/audit-logs/{$logA2->id}")
            ->assertStatus(404);

        $this->actingAs($this->managerA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson("/api/v1/audit-logs/{$logGov->id}")
            ->assertStatus(404);
    }

    /**
     * E. Salesperson Audit Denial
     */
    public function test_salesperson_is_denied_audit_access()
    {
        $log = ActivityLog::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'log_name' => 'sale',
            'description' => 'Sale Recorded',
            'causer_id' => $this->ownerA->id,
            'causer_type' => User::class,
        ]);

        $this->actingAs($this->salespersonA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs')
            ->assertStatus(403);

        $this->actingAs($this->salespersonA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson("/api/v1/audit-logs/{$log->id}")
            ->assertStatus(403);
    }

    /**
     * F. Audit Filter Security
     */
    public function test_audit_filter_security_and_validation()
    {
        // 1. Malformed date fails with 422
        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs?from_date=not-a-date')
            ->assertStatus(422);

        // 2. from_date after to_date fails with 422
        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs?from_date=2026-09-25&to_date=2026-09-10')
            ->assertStatus(422);

        // 3. Manager passing foreign branch_id receives 403
        $this->actingAs($this->managerA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson("/api/v1/audit-logs?branch_id={$this->branchA2->id}")
            ->assertStatus(403);
    }

    /**
     * G. Audit Pagination
     */
    public function test_audit_pagination_bounds_and_ordering()
    {
        ActivityLog::truncate();

        // Create 25 audit rows
        for ($i = 1; $i <= 25; $i++) {
            ActivityLog::create([
                'business_id' => $this->businessA->id,
                'branch_id' => $this->branchA1->id,
                'log_name' => 'inventory',
                'description' => "Inventory Event #{$i}",
                'created_at' => now()->addSeconds($i),
            ]);
        }

        // Default pagination: 20 per page
        $resDefault = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs');
        $resDefault->assertStatus(200);
        $this->assertCount(20, $resDefault->json('data'));
        $this->assertEquals(25, $resDefault->json('total'));

        // Max page size bound: requesting 500 is capped at 100
        $resMax = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs?per_page=500');
        $resMax->assertStatus(422); // Validation requires min:1|max:100

        // Custom valid per_page
        $resCustom = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs?per_page=10');
        $resCustom->assertStatus(200);
        $this->assertCount(10, $resCustom->json('data'));

        // Ordering is created_at DESC, id DESC: latest is first
        $items = $resDefault->json('data');
        $this->assertEquals("Inventory Event #25", $items[0]['description']);
    }

    /**
     * L. Sensitive Redaction
     */
    public function test_audit_sensitive_data_redaction_deep_recursive()
    {
        $dirtyPayload = [
            'store_name' => 'SuperMart',
            'normal_key' => 'HBOS-CODE-100',
            'secret_token' => 'SECRET_XYZ',
            'api_key' => 'LIVE_API_KEY_12345',
            'password' => 'plaintext_pass_secret',
            'nested' => [
                'pin' => '9999',
                'cvv' => '123',
                'authorization' => 'Bearer token_secret',
                'private_key' => 'PRIVATE_RSA_DATA',
                'valid_setting' => 'safe_value'
            ]
        ];

        $sanitized = AuditService::sanitizeProperties($dirtyPayload);

        $this->assertEquals('[REDACTED]', $sanitized['secret_token']);
        $this->assertEquals('[REDACTED]', $sanitized['api_key']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['pin']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['cvv']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['authorization']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['private_key']);

        // Harmless business terms remain unredacted
        $this->assertEquals('SuperMart', $sanitized['store_name']);
        $this->assertEquals('HBOS-CODE-100', $sanitized['normal_key']);
        $this->assertEquals('safe_value', $sanitized['nested']['valid_setting']);
    }

    /**
     * P. Append-Only API
     */
    public function test_audit_api_is_strictly_append_only()
    {
        $log = ActivityLog::create([
            'business_id' => $this->businessA->id,
            'description' => 'Permanent Log Entry',
        ]);

        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/audit-logs', ['description' => 'Forged Log'])
            ->assertStatus(405);

        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/audit-logs/{$log->id}", ['description' => 'Mutated Log'])
            ->assertStatus(405);

        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->deleteJson("/api/v1/audit-logs/{$log->id}")
            ->assertStatus(405);

        $this->assertDatabaseHas('activity_log', ['id' => $log->id, 'description' => 'Permanent Log Entry']);
    }

    /**
     * BH. NullOnDelete FK Semantics
     */
    public function test_null_on_delete_preserves_audit_records()
    {
        $tempBranch = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Temporary Branch',
            'is_primary' => false
        ]);

        $log = ActivityLog::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $tempBranch->id,
            'description' => 'Branch Created Activity',
        ]);

        $tempBranch->delete();

        $log->refresh();
        $this->assertNotNull($log->id);
        $this->assertEquals($this->businessA->id, $log->business_id);
        $this->assertNull($log->branch_id); // set null on delete, row preserved!
    }

    /**
     * BK. N+1 Prevention
     */
    public function test_audit_query_count_remains_bounded_and_eager_loaded()
    {
        for ($i = 1; $i <= 20; $i++) {
            ActivityLog::create([
                'business_id' => $this->businessA->id,
                'branch_id' => $this->branchA1->id,
                'causer_id' => $this->ownerA->id,
                'causer_type' => User::class,
                'description' => "Audit Row #{$i}",
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/audit-logs?per_page=20');

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 1 count query + 1 data query + 1 causer eager load + 1 branch eager load + sanctum/auth queries <= 8
        $this->assertLessThanOrEqual(8, $queryCount);
    }
}
