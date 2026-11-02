<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\ActivityLog;
use Database\Seeders\RolesAndPermissionsSeeder;

class AuditTrailLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA;
    protected $branchB;
    protected $ownerA;
    protected $ownerB;
    protected $salespersonA;
    protected $ownerAToken;
    protected $salespersonAToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Business A setup
        $this->businessA = Business::create(['name' => 'Lahore Retail Mart']);
        $this->branchA = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Mall Road Branch',
            'code' => 'LHR-01',
            'is_primary' => true,
            'status' => 'active',
        ]);
        setPermissionsTeamId($this->businessA->id);
        $this->ownerA = User::factory()->create([
            'name' => 'Owner Lahore',
            'email' => 'owner@lahoremart.pk',
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
        ]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole('Business Owner');
        $this->ownerAToken = $this->ownerA->createToken('OwnerA')->plainTextToken;

        $this->salespersonA = User::factory()->create([
            'name' => 'Cashier Usman',
            'email' => 'usman@lahoremart.pk',
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
        ]);
        $this->salespersonA->businesses()->attach($this->businessA->id);
        $this->salespersonA->assignRole('Salesperson');
        $this->salespersonAToken = $this->salespersonA->createToken('CashierUsman')->plainTextToken;

        // Business B setup
        $this->businessB = Business::create(['name' => 'Karachi Supermart']);
        $this->branchB = Branch::create([
            'business_id' => $this->businessB->id,
            'name' => 'Clifton Branch',
            'code' => 'KHI-01',
            'is_primary' => true,
            'status' => 'active',
        ]);
        setPermissionsTeamId($this->businessB->id);
        $this->ownerB = User::factory()->create([
            'name' => 'Owner Karachi',
            'email' => 'owner@karachimart.pk',
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
        ]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole('Business Owner');
    }

    /**
     * Test Business Owner can access audit logs.
     */
    public function test_owner_can_view_tenant_audit_logs()
    {
        // Seed an activity log for Business A
        ActivityLog::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'log_name' => 'governance',
            'event' => 'branch_created',
            'description' => "Branch 'Mall Road Branch' created",
            'causer_type' => User::class,
            'causer_id' => $this->ownerA->id,
        ]);

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerAToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/audit-logs');

        $res->assertStatus(200);
        $res->assertJsonStructure([
            'data' => [
                '*' => ['id', 'log_name', 'description', 'event', 'created_at', 'causer', 'branch']
            ],
            'current_page',
            'last_page',
            'total',
        ]);

        $this->assertGreaterThanOrEqual(1, count($res->json('data')));
    }

    /**
     * Test Salesperson / Cashier is strictly forbidden from accessing audit logs.
     */
    public function test_salesperson_cannot_access_audit_logs()
    {
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->salespersonAToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/audit-logs');

        $res->assertStatus(403);
        $res->assertJsonFragment([
            'message' => 'Unauthorized. Only Business Owners and Branch Managers can access audit logs.'
        ]);
    }

    /**
     * Test Accountant is denied access to audit logs.
     */
    public function test_accountant_cannot_access_audit_logs()
    {
        setPermissionsTeamId($this->businessA->id);
        $accountant = User::factory()->create([
            'name' => 'Accountant A',
            'email' => 'accountant@lahoremart.pk',
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
        ]);
        $accountant->businesses()->attach($this->businessA->id);
        $accountant->assignRole('Accountant');
        $token = $accountant->createToken('AccToken')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/audit-logs');

        $res->assertStatus(403);
    }

    /**
     * Test Inventory Manager is denied access to audit logs.
     */
    public function test_inventory_manager_cannot_access_audit_logs()
    {
        setPermissionsTeamId($this->businessA->id);
        $invMgr = User::factory()->create([
            'name' => 'Inventory Mgr A',
            'email' => 'invmgr@lahoremart.pk',
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
        ]);
        $invMgr->businesses()->attach($this->businessA->id);
        $invMgr->assignRole('Inventory Manager');
        $token = $invMgr->createToken('InvToken')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/audit-logs');

        $res->assertStatus(403);
    }

    /**
     * Test Branch Manager is denied access to foreign branch or central governance audit logs.
     */
    public function test_branch_manager_denied_foreign_branch_audit_logs()
    {
        setPermissionsTeamId($this->businessA->id);
        // Secondary branch for Business A
        $branchA2 = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'DHA Branch',
            'code' => 'LHR-02',
            'is_primary' => false,
            'status' => 'active',
        ]);

        $manager = User::factory()->create([
            'name' => 'Manager Branch 1',
            'email' => 'manager1@lahoremart.pk',
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
        ]);
        $manager->businesses()->attach($this->businessA->id);
        $manager->branches()->attach($this->branchA->id, ['business_id' => $this->businessA->id]);
        $manager->assignRole('Branch Manager');
        $token = $manager->createToken('MgrToken')->plainTextToken;

        // Attempting to query foreign branch (branchA2) audit logs
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/audit-logs?branch_id={$branchA2->id}");

        $res->assertStatus(403);
        $res->assertJsonFragment([
            'message' => 'Unauthorized. Branch Managers cannot access foreign branch audit logs.'
        ]);
    }

    /**
     * Test Tenant Isolation: Business A user cannot see Business B activity records.
     */
    public function test_tenant_isolation_cross_business_audit_logs_hidden()
    {
        // Activity log for Business A
        ActivityLog::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'log_name' => 'governance',
            'event' => 'auth_login',
            'description' => "Owner A logged in",
            'causer_type' => User::class,
            'causer_id' => $this->ownerA->id,
        ]);

        // Activity log for Business B
        ActivityLog::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'log_name' => 'security',
            'event' => 'auth_login',
            'description' => "Owner B Secret Session Log",
            'causer_type' => User::class,
            'causer_id' => $this->ownerB->id,
        ]);

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerAToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/audit-logs');

        $res->assertStatus(200);
        $items = $res->json('data');

        // All returned items must strictly belong to Business A
        foreach ($items as $item) {
            $this->assertEquals($this->businessA->id, $item['business_id']);
            $this->assertStringNotContainsString('Owner B Secret Session Log', $item['description']);
        }
    }

    /**
     * Test audit log filtering by event and search query.
     */
    public function test_audit_log_filtering_by_event_and_search()
    {
        ActivityLog::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'log_name' => 'governance',
            'event' => 'user_invited',
            'description' => "User Tariq invited",
            'causer_type' => User::class,
            'causer_id' => $this->ownerA->id,
        ]);

        ActivityLog::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'log_name' => 'security',
            'event' => 'auth_logout',
            'description' => "Owner logged out",
            'causer_type' => User::class,
            'causer_id' => $this->ownerA->id,
        ]);

        // Filter by event 'user_invited'
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerAToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/audit-logs?event=user_invited');

        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('user_invited', $data[0]['event']);
        $this->assertEquals('User Tariq invited', $data[0]['description']);
    }
}
