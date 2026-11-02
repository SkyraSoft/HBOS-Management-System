<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Customer;
use Database\Seeders\RolesAndPermissionsSeeder;

class SessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA;
    protected $branchB;
    protected $ownerA;
    protected $ownerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Business A setup
        $this->businessA = Business::create(['name' => 'Khyber General Store']);
        $this->branchA = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Khyber Main',
            'code' => 'KHY-01',
            'is_primary' => true,
            'status' => 'active',
        ]);
        setPermissionsTeamId($this->businessA->id);
        $this->ownerA = User::factory()->create([
            'name' => 'Owner Khyber',
            'email' => 'owner@khyber.pk',
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
        ]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole('Business Owner');

        // Business B setup
        $this->businessB = Business::create(['name' => 'Mehran Mart']);
        $this->branchB = Branch::create([
            'business_id' => $this->businessB->id,
            'name' => 'Mehran Main',
            'code' => 'MHR-01',
            'is_primary' => true,
            'status' => 'active',
        ]);
        setPermissionsTeamId($this->businessB->id);
        $this->ownerB = User::factory()->create([
            'name' => 'Owner Mehran',
            'email' => 'owner@mehran.pk',
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
        ]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole('Business Owner');
    }

    /**
     * Test server-side token revocation on logout.
     */
    public function test_server_token_revocation_on_logout()
    {
        $token = $this->ownerA->createToken('DeviceSessionToken')->plainTextToken;

        // Verify token works
        $checkRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/auth/me');
        $checkRes->assertStatus(200);

        // Logout
        $logoutRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->businessA->id,
        ])->postJson('/api/v1/auth/logout');
        $logoutRes->assertStatus(200);

        // Verify token deleted from database
        $this->assertEquals(0, $this->ownerA->tokens()->count());

        // Clear in-memory test guard cache
        $this->app['auth']->forgetGuards();

        // Subsequent call with revoked token must fail with 401
        $subsequentRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/auth/me');
        $subsequentRes->assertStatus(401);
    }

    /**
     * Test logout-all-devices invalidates all active tokens for that user.
     */
    public function test_logout_all_devices_revokes_all_tokens()
    {
        $token1 = $this->ownerA->createToken('PC-Terminal-1')->plainTextToken;
        $token2 = $this->ownerA->createToken('Mobile-App-2')->plainTextToken;

        $this->assertEquals(2, $this->ownerA->tokens()->count());

        $logoutRes = $this->withHeaders([
            'Authorization' => "Bearer {$token1}",
            'X-Business-ID' => $this->businessA->id,
        ])->postJson('/api/v1/auth/logout-all-devices');
        $logoutRes->assertStatus(200);

        // All tokens must be wiped
        $this->assertEquals(0, $this->ownerA->tokens()->count());

        // Clear in-memory test guard cache
        $this->app['auth']->forgetGuards();

        // Both tokens now fail
        $this->withHeaders(['Authorization' => "Bearer {$token1}"])->getJson('/api/v1/auth/me')->assertStatus(401);
        $this->withHeaders(['Authorization' => "Bearer {$token2}"])->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    /**
     * Test logout preserves all underlying business records.
     */
    public function test_logout_preserves_business_records()
    {
        // Create business assets for Business A
        $customer = Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Shahid Khan',
            'phone' => '03001234567',
        ]);

        $token = $this->ownerA->createToken('CounterToken')->plainTextToken;

        // Perform logout
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->businessA->id,
        ])->postJson('/api/v1/auth/logout')->assertStatus(200);

        // Verify customer, branch, and business are not touched or deleted
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Shahid Khan',
            'business_id' => $this->businessA->id,
        ]);
        $this->assertDatabaseHas('branches', [
            'id' => $this->branchA->id,
            'business_id' => $this->businessA->id,
        ]);
        $this->assertDatabaseHas('businesses', [
            'id' => $this->businessA->id,
        ]);
    }

    /**
     * Test unauthenticated access is strictly rejected.
     */
    public function test_unauthenticated_request_cannot_access_tenant_data()
    {
        $res = $this->getJson('/api/v1/users');
        $res->assertStatus(401);

        $resBranches = $this->getJson('/api/v1/branches');
        $resBranches->assertStatus(401);
    }

    /**
     * Test same-business user switch re-evaluates branch context and role from new session.
     */
    public function test_same_business_user_switch_branch_context_rebuilt()
    {
        // 1. Owner A logs in
        $ownerToken = $this->ownerA->createToken('OwnerSession')->plainTextToken;
        $ownerRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/auth/me');
        $ownerRes->assertStatus(200);
        $this->assertEquals('Business Owner', $ownerRes->json('user.roles.0.name'));

        // 2. Owner logs out
        $this->withHeaders([
            'Authorization' => "Bearer {$ownerToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->postJson('/api/v1/auth/logout')->assertStatus(200);

        $this->app['auth']->forgetGuards();

        // 3. Create Cashier on secondary branch
        $branchA2 = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Khyber Secondary',
            'code' => 'KHY-02',
            'is_primary' => false,
            'status' => 'active',
        ]);
        $cashier = User::factory()->create([
            'name' => 'Cashier Gul',
            'email' => 'gul@khyber.pk',
            'business_id' => $this->businessA->id,
            'branch_id' => $branchA2->id,
        ]);
        $cashier->businesses()->attach($this->businessA->id);
        $cashier->assignRole('Salesperson');

        $cashierToken = $cashier->createToken('CashierSession')->plainTextToken;

        // 4. Cashier requests session
        $cashierRes = $this->withHeaders([
            'Authorization' => "Bearer {$cashierToken}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/auth/me');
        $cashierRes->assertStatus(200);

        // Assert Cashier does NOT inherit Owner role or branch
        $this->assertEquals('Salesperson', $cashierRes->json('user.roles.0.name'));
        $this->assertNotEquals($this->branchA->id, $cashierRes->json('user.branch_id'));
        $this->assertEquals($branchA2->id, $cashierRes->json('user.branch_id'));
    }

    /**
     * Test cross-business user switch isolation.
     */
    public function test_cross_business_user_switch_isolation()
    {
        $tokenB = $this->ownerB->createToken('BizBToken')->plainTextToken;

        // Owner B tries to access Business A using token B
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$tokenB}",
            'X-Business-ID' => $this->businessA->id,
        ])->getJson('/api/v1/users');

        // Middleware or controller rejects unauthorized business access
        $this->assertTrue(in_array($res->status(), [403, 401]));
    }
}
