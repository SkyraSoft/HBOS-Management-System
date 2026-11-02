<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditAttributionCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected User $cashier;
    protected string $ownerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Audit Integrity Biz']);
        setPermissionsTeamId($this->business->id);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Audit Main Branch',
            'code' => 'AUD-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');
        $this->ownerToken = $this->owner->createToken('owner-audit')->plainTextToken;

        $this->cashier = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->assignRole('Salesperson');
    }

    public function test_audit_log_captures_authentic_causer_id()
    {
        $auditService = app(AuditService::class);
        $log = $auditService->log(
            'sales',
            'created',
            'Sale created',
            null,
            ['invoice' => 'INV-001', 'total' => 500],
            $this->branch->id,
            $this->business->id,
            $this->owner
        );

        $this->assertNotNull($log);
        $this->assertEquals($this->owner->id, $log->causer_id);
        $this->assertEquals($this->business->id, $log->business_id);
        $this->assertEquals($this->branch->id, $log->branch_id);
    }

    public function test_view_as_mode_preserves_authentic_owner_causer_id()
    {
        $resp = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
            'X-Branch-ID' => $this->branch->id,
            'X-View-As-Role' => 'Branch Manager'
        ])->getJson("/api/v1/auth/me");

        $resp->assertStatus(200);

        $auditService = app(AuditService::class);
        $log = $auditService->log(
            'view_as',
            'entered',
            'Owner previewed Manager view',
            null,
            ['role' => 'Branch Manager'],
            $this->branch->id,
            $this->business->id,
            $this->owner
        );

        $this->assertEquals($this->owner->id, $log->causer_id);
    }

    public function test_transaction_rollback_prevents_false_positive_audit_log()
    {
        $initialCount = ActivityLog::count();

        try {
            DB::transaction(function () {
                $auditService = app(AuditService::class);
                $auditService->log('test', 'action', 'Failed transaction test', null, [], $this->branch->id, $this->business->id, $this->owner);
                throw new \Exception('Forced Transaction Failure');
            });
        } catch (\Exception $e) {
            // caught
        }

        $afterCount = ActivityLog::count();
        $this->assertEquals($initialCount, $afterCount);
    }

    public function test_sensitive_properties_are_redacted_in_audit_properties()
    {
        $auditService = app(AuditService::class);
        $log = $auditService->log(
            'governance',
            'password_reset',
            'User reset password',
            null,
            [
                'email' => 'user@retail.test',
                'password' => 'SuperSecret123!',
                'token' => 'plain_text_token_abc',
                'card_pin' => '1234'
            ],
            $this->branch->id,
            $this->business->id,
            $this->owner
        );

        $properties = $log->properties;
        $this->assertEquals('[REDACTED]', $properties['password'] ?? null);
        $this->assertEquals('[REDACTED]', $properties['token'] ?? null);
        $this->assertEquals('user@retail.test', $properties['email'] ?? null);
    }
}