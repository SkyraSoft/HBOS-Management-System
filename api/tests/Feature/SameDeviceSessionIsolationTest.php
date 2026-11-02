<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SameDeviceSessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA;
    protected User $owner;
    protected User $cashier;
    protected string $ownerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->businessA = Business::create(['name' => 'Session Isolation Biz A']);
        $this->businessB = Business::create(['name' => 'Session Isolation Biz B']);

        setPermissionsTeamId($this->businessA->id);
        $this->branchA = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Main Store',
            'code' => 'MAIN-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'password' => Hash::make('password123'),
        ]);
        $this->owner->businesses()->attach($this->businessA->id);
        $this->owner->assignRole('Business Owner');
        $this->ownerToken = $this->owner->createToken('owner-token')->plainTextToken;

        $this->cashier = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'password' => Hash::make('password123'),
        ]);
        $this->cashier->businesses()->attach($this->businessA->id);
        $this->cashier->assignRole('Salesperson');
    }

    public function test_logout_revokes_token_completely()
    {
        $resp = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/auth/me");
        $resp->assertStatus(200);

        $logoutResp = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->postJson("/api/v1/auth/logout");
        $logoutResp->assertStatus(200);

        // Reset auth guards to simulate fresh request context
        $this->app['auth']->forgetGuards();

        $afterResp = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/auth/me");
        $afterResp->assertStatus(401);
        $this->assertEquals(0, $this->owner->fresh()->tokens()->count());
    }

    public function test_cashier_login_after_owner_does_not_inherit_owner_roles_or_privileges()
    {
        $loginResp = $this->postJson("/api/v1/auth/login", [
            'email' => $this->cashier->email,
            'password' => 'password123',
        ]);
        $loginResp->assertStatus(200);
        $cashierToken = $loginResp->json('access_token');

        $this->assertNotEmpty($cashierToken);

        $ownerResp = $this->withHeaders([
            'Authorization' => "Bearer {$cashierToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson("/api/v1/reports/profitability");

        $ownerResp->assertStatus(403);
    }

    public function test_user_cannot_switch_to_unauthorized_business()
    {
        $resp = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->businessB->id,
        ])->getJson("/api/v1/branches");

        $resp->assertStatus(403);
    }
}