<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;

class RoleExposureTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $ownerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Bismillah Cloth House']);
        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'code' => 'MAIN-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create([
            'name' => 'Chaudhry Riaz',
            'email' => 'riaz@bismillah.pk',
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->branches()->attach($this->branch->id, ['business_id' => $this->business->id]);
        $this->owner->assignRole('Business Owner');

        $this->ownerToken = $this->owner->createToken('OwnerToken')->plainTextToken;
    }

    /**
     * Test that all 5 canonical roles exist in Spatie database.
     */
    public function test_canonical_roles_exist_in_spatie_system()
    {
        $expectedRoles = [
            'Business Owner',
            'Branch Manager',
            'Salesperson',
            'Accountant',
            'Inventory Manager',
        ];

        foreach ($expectedRoles as $roleName) {
            $this->assertDatabaseHas('roles', [
                'name' => $roleName,
            ]);
        }
    }

    /**
     * Test backend continues to accept compatibility roles (Accountant, Inventory Manager) under POLICY-B1.
     */
    public function test_backend_preserves_compatibility_roles_on_user_creation()
    {
        // 1. Create Accountant via authorized backend API
        $resAccountant = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->postJson('/api/v1/users', [
            'name' => 'Farhan Accountant',
            'email' => 'farhan@bismillah.pk',
            'role' => 'Accountant',
            'branch_id' => $this->branch->id,
            'password' => 'SecurePass123!',
        ]);

        $resAccountant->assertStatus(201);
        $accountantUser = User::where('email', 'farhan@bismillah.pk')->first();
        $this->assertNotNull($accountantUser);
        $this->assertTrue($accountantUser->hasRole('Accountant'));

        // 2. Create Inventory Manager via authorized backend API
        $resInvMgr = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->postJson('/api/v1/users', [
            'name' => 'Saeed Inventory',
            'email' => 'saeed@bismillah.pk',
            'role' => 'Inventory Manager',
            'branch_id' => $this->branch->id,
            'password' => 'SecurePass123!',
        ]);

        $resInvMgr->assertStatus(201);
        $invUser = User::where('email', 'saeed@bismillah.pk')->first();
        $this->assertNotNull($invUser);
        $this->assertTrue($invUser->hasRole('Inventory Manager'));
    }

    /**
     * Test that standard staff creation with Salesperson persists role as 'Salesperson'.
     */
    public function test_salesperson_role_persisted_correctly()
    {
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->postJson('/api/v1/users', [
            'name' => 'Bilal Cashier',
            'email' => 'bilal@bismillah.pk',
            'role' => 'Salesperson',
            'branch_id' => $this->branch->id,
            'password' => 'SecurePass123!',
        ]);

        $res->assertStatus(201);
        $salesperson = User::where('email', 'bilal@bismillah.pk')->first();
        $this->assertNotNull($salesperson);
        $this->assertTrue($salesperson->hasRole('Salesperson'));
        $this->assertEquals('Salesperson', $salesperson->roles->first()->name);
    }

    /**
     * Test that updating an existing user preserves their compatibility role.
     */
    public function test_compatibility_role_user_permissions_preserved()
    {
        $accountant = User::factory()->create([
            'name' => 'Legacy Accountant',
            'email' => 'legacy.acc@bismillah.pk',
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $accountant->businesses()->attach($this->business->id);
        $accountant->branches()->attach($this->branch->id, ['business_id' => $this->business->id]);
        $accountant->assignRole('Accountant');

        $this->assertTrue($accountant->hasRole('Accountant'));

        // Update profile while maintaining Accountant role
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->putJson("/api/v1/users/{$accountant->id}", [
            'name' => 'Legacy Accountant Updated',
            'email' => 'legacy.acc@bismillah.pk',
            'role' => 'Accountant',
            'branch_id' => $this->branch->id,
        ]);

        $res->assertStatus(200);
        $accountant->refresh();
        $this->assertEquals('Legacy Accountant Updated', $accountant->name);
        $this->assertTrue($accountant->hasRole('Accountant'));
    }
}
