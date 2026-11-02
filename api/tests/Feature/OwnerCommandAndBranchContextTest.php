<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerCommandAndBranchContextTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $owner;
    protected User $manager1;
    protected User $salesperson;
    protected string $ownerToken;
    protected string $managerToken;
    protected string $salespersonToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Metro Retail Enterprise']);
        setPermissionsTeamId($this->business->id);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->branch1 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'North Nazimabad Main',
            'code' => 'NN-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->branch2 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Clifton Outlet',
            'code' => 'CLF-02',
            'is_primary' => false,
            'status' => 'active',
        ]);

        // Owner
        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->branches()->attach([$this->branch1->id, $this->branch2->id], ['business_id' => $this->business->id]);
        $this->owner->assignRole('Business Owner');
        $this->ownerToken = $this->owner->createToken('Owner')->plainTextToken;

        // Manager assigned only to Branch 1
        $this->manager1 = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
        ]);
        $this->manager1->businesses()->attach($this->business->id);
        $this->manager1->branches()->attach($this->branch1->id, ['business_id' => $this->business->id]);
        $this->manager1->assignRole('Branch Manager');
        $this->managerToken = $this->manager1->createToken('Manager')->plainTextToken;

        // Salesperson (Cashier) assigned to Branch 1
        $this->salesperson = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
        ]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->branches()->attach($this->branch1->id, ['business_id' => $this->business->id]);
        $this->salesperson->assignRole('Salesperson');
        $this->salespersonToken = $this->salesperson->createToken('Salesperson')->plainTextToken;
    }

    protected function authHeaders(string $token, array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $this->business->id,
        ], $extra);
    }

    public function test_single_store_progressive_disclosure_detection()
    {
        $singleBiz = Business::create(['name' => 'Single Store Corner']);
        setPermissionsTeamId($singleBiz->id);
        $this->seed(RolesAndPermissionsSeeder::class);
        $singleBranch = Branch::create([
            'business_id' => $singleBiz->id,
            'name' => 'Solo Branch',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $singleOwner = User::factory()->create([
            'business_id' => $singleBiz->id,
            'branch_id' => $singleBranch->id,
        ]);
        $singleOwner->businesses()->attach($singleBiz->id);
        $singleOwner->assignRole('Business Owner');
        $singleToken = $singleOwner->createToken('SoloOwner')->plainTextToken;

        $res = $this->getJson('/api/v1/branches', [
            'Authorization' => "Bearer {$singleToken}",
            'X-Business-ID' => (string) $singleBiz->id,
        ]);
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    public function test_multi_branch_progressive_disclosure_detection()
    {
        $resMulti = $this->getJson('/api/v1/branches', $this->authHeaders($this->ownerToken));
        $resMulti->assertStatus(200);
        $this->assertCount(2, $resMulti->json('data'));
    }

    public function test_owner_branch_switcher_and_all_stores_aggregation_context()
    {
        // Query reports for Branch 1 specifically
        $resBranch1 = $this->getJson('/api/v1/reports/sales?branch_id=' . $this->branch1->id, $this->authHeaders($this->ownerToken));
        $resBranch1->assertStatus(200);

        // Query reports for All Stores (omitted branch_id or branch_id=all)
        $resAllOmitted = $this->getJson('/api/v1/reports/sales', $this->authHeaders($this->ownerToken));
        $resAllOmitted->assertStatus(200);

        $resAllExplicit = $this->getJson('/api/v1/reports/sales?branch_id=all', $this->authHeaders($this->ownerToken));
        $resAllExplicit->assertStatus(200);
    }

    public function test_all_stores_is_never_persisted_as_a_branch_id_in_database()
    {
        $allStoresBranch = Branch::where('name', 'like', '%All Stores%')
            ->orWhere('name', 'like', '%all%')
            ->where('business_id', $this->business->id)
            ->first();

        $this->assertNull($allStoresBranch);

        // Ensure database branches only consist of actual physical branches
        $count = Branch::where('business_id', $this->business->id)->count();
        $this->assertEquals(2, $count);
    }

    public function test_manager_cannot_request_all_branches_reports()
    {
        $res = $this->getJson('/api/v1/reports/sales?branch_id=all', $this->authHeaders($this->managerToken));
        $res->assertStatus(403);
        $this->assertStringContainsString('Branch Managers cannot request All Branches reports', $res->json('message'));
    }

    public function test_manager_cannot_access_reports_for_another_branch()
    {
        $res = $this->getJson('/api/v1/reports/sales?branch_id=' . $this->branch2->id, $this->authHeaders($this->managerToken));
        $res->assertStatus(403);
        $this->assertStringContainsString('Branch Managers cannot access reports for another branch', $res->json('message'));
    }

    public function test_salesperson_cannot_access_executive_reports()
    {
        $res = $this->getJson('/api/v1/reports/sales', $this->authHeaders($this->salespersonToken));
        $res->assertStatus(403);
        $this->assertStringContainsString('Salespersons are not authorized to view executive reports', $res->json('message'));
    }

    public function test_owner_branch_comparison_matrix_endpoint()
    {
        // Owner accessing branch comparative matrix
        $res = $this->getJson('/api/v1/reports/branches', $this->authHeaders($this->ownerToken));
        $res->assertStatus(200);
        $res->assertJsonStructure([
            'period' => ['from', 'to'],
            'branches' => [
                '*' => [
                    'branch_id',
                    'branch_name',
                    'transaction_count',
                    'net_sales',
                    'gross_profit',
                    'expenses',
                    'cash_drawer_balance',
                    'is_cogs_complete',
                ]
            ]
        ]);

        $this->assertCount(2, $res->json('branches'));
    }

    public function test_non_owner_forbidden_from_branch_comparison_report()
    {
        $resManager = $this->getJson('/api/v1/reports/branches', $this->authHeaders($this->managerToken));
        $resManager->assertStatus(403);

        $resSalesperson = $this->getJson('/api/v1/reports/branches', $this->authHeaders($this->salespersonToken));
        $resSalesperson->assertStatus(403);
    }
}
