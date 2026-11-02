<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use Database\Seeders\RolesAndPermissionsSeeder;

class PrimaryBranchPreservationTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $primaryBranch;
    protected $secondaryBranch;
    protected $owner;
    protected $ownerToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Create Business & Primary Branch
        $this->business = Business::create(['name' => 'Al-Madina General Store']);
        
        $this->primaryBranch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Store',
            'code' => 'MAIN-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->secondaryBranch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Gulberg Express Branch',
            'code' => 'GLB-02',
            'is_primary' => false,
            'status' => 'active',
        ]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create([
            'name' => 'Haji Muhammad Aslam',
            'email' => 'owner@almadina.pk',
            'business_id' => $this->business->id,
            'branch_id' => $this->primaryBranch->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->branches()->attach($this->primaryBranch->id, ['business_id' => $this->business->id]);
        $this->owner->assignRole('Business Owner');

        $this->ownerToken = $this->owner->createToken('OwnerToken')->plainTextToken;
    }

    /**
     * Test primary branch cannot be deleted.
     */
    public function test_primary_branch_cannot_be_deleted()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->deleteJson("/api/v1/branches/{$this->primaryBranch->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Cannot delete the primary branch.'
        ]);

        $this->assertDatabaseHas('branches', [
            'id' => $this->primaryBranch->id,
            'is_primary' => true,
        ]);
    }

    /**
     * Test primary branch cannot be deactivated.
     */
    public function test_primary_branch_cannot_be_deactivated()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->putJson("/api/v1/branches/{$this->primaryBranch->id}", [
            'status' => 'inactive',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Cannot deactivate the primary branch.'
        ]);

        $this->assertDatabaseHas('branches', [
            'id' => $this->primaryBranch->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test secondary branch can be created, updated, and deleted when no history.
     */
    public function test_secondary_branch_can_be_created_and_deleted()
    {
        // 1. Create a 3rd branch
        $createRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->postJson('/api/v1/branches', [
            'name' => 'Faisal Town Outlet',
            'code' => 'FT-03',
            'is_primary' => false,
            'status' => 'active',
        ]);

        $createRes->assertStatus(201);
        $newBranchId = $createRes->json('data.id');

        // 2. Delete the new branch (no sales/history)
        $deleteRes = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->deleteJson("/api/v1/branches/{$newBranchId}");

        $deleteRes->assertStatus(200);
        $deleteRes->assertJsonFragment([
            'message' => 'Branch deleted successfully.'
        ]);

        $this->assertDatabaseMissing('branches', [
            'id' => $newBranchId,
        ]);
    }

    /**
     * Test main store does not require a dedicated manager or cashier.
     */
    public function test_main_store_does_not_require_manager_or_cashier()
    {
        // Primary branch has is_primary = true
        $this->assertTrue((bool) $this->primaryBranch->is_primary);

        // The business has no manager or cashier users
        $staffCount = User::where('business_id', $this->business->id)
            ->where('id', '!=', $this->owner->id)
            ->count();
        $this->assertEquals(0, $staffCount);

        // Owner can query and view branch details directly
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->getJson("/api/v1/branches/{$this->primaryBranch->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_primary', 1);
    }

    /**
     * Test no hardcoded branch ID = 1 dependency.
     */
    public function test_no_hardcoded_branch_id_one_dependency()
    {
        // Create another business with a non-1 primary branch ID
        $biz2 = Business::create(['name' => 'Modern Superstore']);
        $branch2 = Branch::create([
            'business_id' => $biz2->id,
            'name' => 'Main Outlet',
            'code' => 'MOD-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->assertNotEquals(1, $branch2->id);
        $this->assertTrue((bool) $branch2->is_primary);

        setPermissionsTeamId($biz2->id);
        $owner2 = User::factory()->create([
            'business_id' => $biz2->id,
            'branch_id' => $branch2->id,
        ]);
        $owner2->businesses()->attach($biz2->id);
        $owner2->assignRole('Business Owner');
        $token2 = $owner2->createToken('Owner2')->plainTextToken;

        // Try deleting branch2 -> must be protected because is_primary is true, not because ID is 1
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token2}",
            'X-Business-ID' => $biz2->id,
        ])->deleteJson("/api/v1/branches/{$branch2->id}");

        $res->assertStatus(422);
        $res->assertJsonFragment([
            'message' => 'Cannot delete the primary branch.'
        ]);
    }
}
