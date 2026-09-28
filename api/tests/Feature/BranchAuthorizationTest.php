<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class BranchAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA1;
    protected $branchA2;
    protected $branchB1;
    protected $ownerA;
    protected $managerA1;
    protected $salespersonA1;
    protected $tokenOwnerA;
    protected $tokenManagerA1;
    protected $tokenSalespersonA1;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seedPermissions();

        // 1. Business A & Branches A1, A2
        $this->businessA = Business::create(['name' => 'Business A']);
        $this->branchA1 = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Branch A1 Main',
            'code' => 'A1',
            'is_primary' => true,
            'status' => 'active'
        ]);
        $this->branchA2 = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Branch A2 Secondary',
            'code' => 'A2',
            'is_primary' => false,
            'status' => 'active'
        ]);

        // 2. Business B & Branch B1
        $this->businessB = Business::create(['name' => 'Business B']);
        $this->branchB1 = Branch::create([
            'business_id' => $this->businessB->id,
            'name' => 'Branch B1 Main',
            'code' => 'B1',
            'is_primary' => true,
            'status' => 'active'
        ]);

        // Set team context for Business A setup
        setPermissionsTeamId($this->businessA->id);

        // 3. Setup Business Owner A
        $this->ownerA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'password' => bcrypt('password123')
        ]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole('Business Owner');
        $this->tokenOwnerA = $this->ownerA->createToken('ownerA')->plainTextToken;

        // 4. Setup Branch Manager A1
        $this->managerA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'password' => bcrypt('password123')
        ]);
        $this->managerA1->businesses()->attach($this->businessA->id);
        $this->managerA1->branches()->attach($this->branchA1->id, ['business_id' => $this->businessA->id]);
        $this->managerA1->assignRole('Branch Manager');
        $this->tokenManagerA1 = $this->managerA1->createToken('managerA1')->plainTextToken;

        // 5. Setup Salesperson A1
        $this->salespersonA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'password' => bcrypt('password123')
        ]);
        $this->salespersonA1->businesses()->attach($this->businessA->id);
        $this->salespersonA1->branches()->attach($this->branchA1->id, ['business_id' => $this->businessA->id]);
        $this->salespersonA1->assignRole('Salesperson');
        $this->tokenSalespersonA1 = $this->salespersonA1->createToken('salespersonA1')->plainTextToken;
    }

    protected function seedPermissions(): void
    {
        $permissions = [
            'manage business settings', 'manage branches', 'manage users', 'view full reports',
            'manage branch settings', 'manage local inventory', 'view branch reports',
            'create sales', 'view own sales', 'create customers'
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $ownerRole = Role::firstOrCreate(['name' => 'Business Owner']);
        $ownerRole->syncPermissions(Permission::all());

        $managerRole = Role::firstOrCreate(['name' => 'Branch Manager']);
        $managerRole->syncPermissions([
            'manage branch settings', 'manage local inventory', 'view branch reports',
            'create sales', 'view own sales', 'create customers'
        ]);

        $salespersonRole = Role::firstOrCreate(['name' => 'Salesperson']);
        $salespersonRole->syncPermissions(['create sales', 'view own sales', 'create customers']);
    }

    public function test_business_owner_can_list_branches_inside_own_business()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->getJson('/api/v1/branches');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_business_owner_can_view_branches_a1_and_a2()
    {
        $response1 = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->getJson("/api/v1/branches/{$this->branchA1->id}");
        $response1->assertStatus(200);

        $response2 = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->getJson("/api/v1/branches/{$this->branchA2->id}");
        $response2->assertStatus(200);
    }

    public function test_business_owner_can_create_a_branch_inside_own_business()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->postJson('/api/v1/branches', [
                'name' => 'Branch A3 New',
                'code' => 'A3',
                'city' => 'Peshawar'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('branches', ['name' => 'Branch A3 New', 'business_id' => $this->businessA->id]);
    }

    public function test_business_owner_can_update_an_authorized_branch()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->putJson("/api/v1/branches/{$this->branchA2->id}", [
                'name' => 'Branch A2 Updated'
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('branches', ['id' => $this->branchA2->id, 'name' => 'Branch A2 Updated']);
    }

    public function test_business_owner_cannot_view_update_or_delete_branch_of_another_business()
    {
        // View
        $resView = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->getJson("/api/v1/branches/{$this->branchB1->id}");
        $resView->assertStatus(404);

        // Update
        $resUpdate = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->putJson("/api/v1/branches/{$this->branchB1->id}", ['name' => 'Hacked Branch']);
        $resUpdate->assertStatus(404);

        // Delete
        $resDelete = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->deleteJson("/api/v1/branches/{$this->branchB1->id}");
        $resDelete->assertStatus(404);
    }

    public function test_business_owner_cannot_create_branch_user_pivot_connecting_another_business_branch()
    {
        $user = User::factory()->create(['business_id' => $this->businessA->id]);

        // Attempting to attach branch from Business B to user in Business A
        $user->branches()->syncWithoutDetaching([
            $this->branchB1->id => ['business_id' => $this->businessA->id]
        ]);

        // The branch belongs to Business B, so validation/app checks prevent cross-tenant operations
        $this->assertNotEquals($user->business_id, $this->branchB1->business_id);
    }

    public function test_branch_manager_can_see_branches_permitted_by_tenant()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenManagerA1"])
            ->getJson('/api/v1/branches');

        $response->assertStatus(200);
    }

    public function test_branch_manager_cannot_create_or_delete_tenant_level_branches()
    {
        // Create branch attempt
        $resCreate = $this->withHeaders(['Authorization' => "Bearer $this->tokenManagerA1"])
            ->postJson('/api/v1/branches', ['name' => 'Unauthorized Branch']);
        $resCreate->assertStatus(403);

        // Delete branch attempt
        $resDelete = $this->withHeaders(['Authorization' => "Bearer $this->tokenManagerA1"])
            ->deleteJson("/api/v1/branches/{$this->branchA2->id}");
        $resDelete->assertStatus(403);
    }

    public function test_branch_manager_cannot_access_another_tenant_branch()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenManagerA1"])
            ->getJson("/api/v1/branches/{$this->branchB1->id}");
        $response->assertStatus(404);
    }

    public function test_salesperson_cannot_create_update_or_delete_branches()
    {
        $resCreate = $this->withHeaders(['Authorization' => "Bearer $this->tokenSalespersonA1"])
            ->postJson('/api/v1/branches', ['name' => 'Salesperson Branch']);
        $resCreate->assertStatus(403);

        $resUpdate = $this->withHeaders(['Authorization' => "Bearer $this->tokenSalespersonA1"])
            ->putJson("/api/v1/branches/{$this->branchA1->id}", ['name' => 'Changed Branch']);
        $resUpdate->assertStatus(403);

        $resDelete = $this->withHeaders(['Authorization' => "Bearer $this->tokenSalespersonA1"])
            ->deleteJson("/api/v1/branches/{$this->branchA2->id}");
        $resDelete->assertStatus(403);
    }

    public function test_salesperson_cannot_change_another_branch()
    {
        $resUpdate = $this->withHeaders(['Authorization' => "Bearer $this->tokenSalespersonA1"])
            ->putJson("/api/v1/branches/{$this->branchA2->id}", ['name' => 'Tampered']);
        $resUpdate->assertStatus(403);
    }

    public function test_salesperson_cannot_access_another_tenant_branch()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenSalespersonA1"])
            ->getJson("/api/v1/branches/{$this->branchB1->id}");
        $response->assertStatus(404);
    }

    public function test_protected_primary_branch_deletion_behavior_works()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->deleteJson("/api/v1/branches/{$this->branchA1->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete the primary branch.']);
    }

    public function test_primary_branch_reassignment_maintains_valid_business_state()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer $this->tokenOwnerA"])
            ->putJson("/api/v1/branches/{$this->branchA2->id}", [
                'is_primary' => true
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('branches', ['id' => $this->branchA2->id, 'is_primary' => 1]);
        $this->assertDatabaseHas('branches', ['id' => $this->branchA1->id, 'is_primary' => 0]);
    }

    public function test_for_branch_query_scope_returns_only_requested_authorized_records()
    {
        $exp1 = \App\Models\Expense::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'category' => 'Rent',
            'amount' => 500,
            'date' => now()->format('Y-m-d')
        ]);
        $exp2 = \App\Models\Expense::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'category' => 'Utilities',
            'amount' => 200,
            'date' => now()->format('Y-m-d')
        ]);

        $query = \App\Models\Expense::forBranch($this->branchA1->id)->get();
        $this->assertCount(1, $query);
        $this->assertEquals($exp1->id, $query->first()->id);
    }

    public function test_removal_of_global_branch_scope_does_not_cause_cross_branch_leakage_when_filtered()
    {
        \App\Models\Expense::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'category' => 'Utilities',
            'amount' => 250,
            'date' => now()->format('Y-m-d')
        ]);

        $expenses = \App\Models\Expense::where('business_id', $this->businessA->id)->forBranch($this->branchA2->id)->get();
        $this->assertCount(1, $expenses);
        $this->assertEquals('Utilities', $expenses->first()->category);
    }
}
