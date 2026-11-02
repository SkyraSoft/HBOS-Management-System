<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $primaryBranch;
    protected Branch $secondaryBranch;
    protected Branch $unassignedBranch;
    protected User $owner;
    protected User $manager;
    protected User $cashier;
    protected string $ownerToken;
    protected string $managerToken;
    protected string $cashierToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Branch Multi Matrix Retail']);
        setPermissionsTeamId($this->business->id);

        $this->primaryBranch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Primary Branch',
            'code' => 'BR-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->secondaryBranch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Secondary Assigned Branch',
            'code' => 'BR-02',
            'is_primary' => false,
            'status' => 'active'
        ]);

        $this->unassignedBranch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Unassigned 3rd Branch',
            'code' => 'BR-03',
            'is_primary' => false,
            'status' => 'active'
        ]);

        // Owner (all branch scope)
        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->primaryBranch->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');
        $this->ownerToken = $this->owner->createToken('owner')->plainTextToken;

        // Manager (M:N assigned to Primary AND Secondary via pivot)
        $this->manager = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->primaryBranch->id,
        ]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->branches()->attach($this->primaryBranch->id, ['business_id' => $this->business->id]);
        $this->manager->branches()->attach($this->secondaryBranch->id, ['business_id' => $this->business->id]);
        $this->manager->assignRole('Branch Manager');
        $this->managerToken = $this->manager->createToken('manager')->plainTextToken;

        // Cashier (Primary branch only)
        $this->cashier = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->primaryBranch->id,
        ]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->branches()->attach($this->primaryBranch->id, ['business_id' => $this->business->id]);
        $this->cashier->assignRole('Salesperson');
        $this->cashierToken = $this->cashier->createToken('cashier')->plainTextToken;
    }

    public function test_primary_branch_cannot_be_deleted_or_deactivated()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->deleteJson("/api/v1/branches/{$this->primaryBranch->id}");

        $response->assertStatus(422);

        $updateResp = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
        ])->putJson("/api/v1/branches/{$this->primaryBranch->id}", [
            'name' => 'Main Primary Branch',
            'status' => 'inactive'
        ]);

        $updateResp->assertStatus(422);
    }

    public function test_manager_can_access_both_assigned_pivot_branches()
    {
        // Access primary branch
        $resp1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->managerToken}",
            'X-Business-ID' => $this->business->id,
            'X-Branch-ID' => $this->primaryBranch->id,
        ])->getJson("/api/v1/branches/{$this->primaryBranch->id}");
        $resp1->assertStatus(200);

        // Access secondary assigned branch
        $resp2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->managerToken}",
            'X-Business-ID' => $this->business->id,
            'X-Branch-ID' => $this->secondaryBranch->id,
        ])->getJson("/api/v1/branches/{$this->secondaryBranch->id}");
        $resp2->assertStatus(200);
    }

    public function test_forged_branch_header_for_unassigned_branch_is_rejected_or_unauthorized()
    {
        // Manager attempts unassigned 3rd branch
        $resp = $this->withHeaders([
            'Authorization' => "Bearer {$this->managerToken}",
            'X-Business-ID' => $this->business->id,
            'X-Branch-ID' => $this->unassignedBranch->id,
        ])->postJson("/api/v1/action-notes", [
            'branch_id' => $this->unassignedBranch->id,
            'title' => 'Tampered note title',
            'description' => 'Tampered note on unassigned branch',
            'priority' => 'high'
        ]);

        $this->assertTrue(in_array($resp->status(), [403, 422]));
    }

    public function test_view_as_read_only_blocks_all_mutation_methods()
    {
        // Owner enters View-As Branch Manager
        $resp = $this->withHeaders([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => $this->business->id,
            'X-Branch-ID' => $this->primaryBranch->id,
            'X-View-As-Role' => 'Branch Manager'
        ])->postJson("/api/v1/action-notes", [
            'branch_id' => $this->primaryBranch->id,
            'title' => 'Attempted write under View-As mode',
            'description' => 'Should be blocked',
            'priority' => 'high'
        ]);

        $resp->assertStatus(403);
        $resp->assertJson(['is_read_only' => true]);
        $this->assertStringContainsString('Mutations are strictly forbidden during read-only View-As preview mode', $resp->json('message'));
    }

    public function test_cashier_is_forbidden_from_viewing_profitability_reports()
    {
        $resp = $this->withHeaders([
            'Authorization' => "Bearer {$this->cashierToken}",
            'X-Business-ID' => $this->business->id,
            'X-Branch-ID' => $this->primaryBranch->id,
        ])->getJson("/api/v1/reports/profitability");

        $resp->assertStatus(403);
    }
}