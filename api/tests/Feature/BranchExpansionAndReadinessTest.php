<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\CashRegisterSession;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchExpansionAndReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $mainBranch;
    protected User $owner;
    protected string $ownerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Metro Retail Enterprise']);
        setPermissionsTeamId($this->business->id);

        $this->mainBranch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Store',
            'code' => 'MAIN-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->mainBranch->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->branches()->attach($this->mainBranch->id, ['business_id' => $this->business->id]);
        $this->owner->assignRole('Business Owner');

        $this->ownerToken = $this->owner->createToken('OwnerToken')->plainTextToken;
    }

    protected function authHeaders(array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$this->ownerToken}",
            'X-Business-ID' => (string) $this->business->id,
        ], $extra);
    }

    public function test_owner_creates_new_branch_in_staged_inactive_state_by_default()
    {
        $response = $this->postJson('/api/v1/branches', [
            'name' => 'North Nazimabad Outlet',
            'code' => 'NN-02',
            'city' => 'Karachi',
            'address' => 'Block D, North Nazimabad',
            'phone' => '+92 300 9876543',
        ], $this->authHeaders());

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'inactive');
        $this->assertFalse((bool) $response->json('data.is_primary'));

        // Verify default cash drawer is provisioned automatically with 0 balance
        $branchId = $response->json('data.id');
        $drawer = FinancialAccount::where('business_id', $this->business->id)
            ->where('branch_id', $branchId)
            ->where('type', 'cash')
            ->where('is_default', true)
            ->first();

        $this->assertNotNull($drawer);
        $this->assertEquals('0.00', (string) $drawer->balance);
    }

    public function test_primary_main_store_cannot_be_deactivated()
    {
        $response = $this->putJson("/api/v1/branches/{$this->mainBranch->id}", [
            'status' => 'inactive',
        ], $this->authHeaders());

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Cannot deactivate the primary branch.']);
    }

    public function test_branch_readiness_audit_endpoint()
    {
        $branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'DHA Phase 6 Outlet',
            'code' => 'DHA-03',
            'status' => 'inactive',
        ]);

        // Default drawer
        FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $branch->id,
            'type' => 'cash',
            'is_default' => true,
            'name' => 'DHA Drawer',
            'account_number' => 'DR-DHA',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}/readiness", $this->authHeaders());

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_ready', true);
        $response->assertJsonPath('data.checks.profile_complete.status', true);
        $response->assertJsonPath('data.checks.default_cash_drawer.status', true);
        $response->assertJsonPath('data.checks.assigned_staff.status', false); // Optional warning for Owner direct operation
        $this->assertEmpty($response->json('data.blockers'));
        $this->assertNotEmpty($response->json('data.warnings'));
    }

    public function test_branch_activation_and_reactivation()
    {
        $branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Gulshan Outlet',
            'code' => 'GLS-04',
            'status' => 'inactive',
        ]);

        // Activate
        $response = $this->putJson("/api/v1/branches/{$branch->id}", [
            'status' => 'active',
        ], $this->authHeaders());

        $response->assertStatus(200);
        $this->assertEquals('active', $branch->fresh()->status);

        // Deactivate (when clean)
        $response = $this->putJson("/api/v1/branches/{$branch->id}", [
            'status' => 'inactive',
        ], $this->authHeaders());
        $response->assertStatus(200);
        $this->assertEquals('inactive', $branch->fresh()->status);

        // Reactivate
        $response = $this->putJson("/api/v1/branches/{$branch->id}", [
            'status' => 'active',
        ], $this->authHeaders());
        $response->assertStatus(200);
        $this->assertEquals('active', $branch->fresh()->status);
    }

    public function test_deactivation_blocked_by_open_cash_register_session()
    {
        $branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Clifton Outlet',
            'code' => 'CLF-05',
            'status' => 'active',
        ]);

        $account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $branch->id,
            'type' => 'cash',
            'is_default' => true,
            'name' => 'Clifton Drawer',
            'account_number' => 'DR-CLF',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        CashRegisterSession::create([
            'business_id' => $this->business->id,
            'branch_id' => $branch->id,
            'financial_account_id' => $account->id,
            'user_id' => $this->owner->id,
            'opening_float' => 1000.00,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $response = $this->putJson("/api/v1/branches/{$branch->id}", [
            'status' => 'inactive',
        ], $this->authHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('active open cash drawer session', $response->json('message'));
    }

    public function test_deactivation_blocked_by_positive_stock_on_hand()
    {
        $branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Tariq Road Outlet',
            'code' => 'TR-06',
            'status' => 'active',
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Basmati Rice 5kg',
            'code' => 'RICE-01',
            'cost_price' => 1000.00,
            'price' => 1200.00,
            'stock' => 10.000,
            'allow_fractional' => false,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 5.000,
            'minimum_stock' => 0.000,
        ]);

        $response = $this->putJson("/api/v1/branches/{$branch->id}", [
            'status' => 'inactive',
        ], $this->authHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('remaining stock', $response->json('message'));
    }

    public function test_deactivation_blocked_by_in_transit_stock_transfer()
    {
        $branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Bahria Outlet',
            'code' => 'BHR-07',
            'status' => 'active',
        ]);

        StockTransfer::create([
            'business_id' => $this->business->id,
            'transfer_number' => 'TRF-TEST-001',
            'from_branch_id' => $this->mainBranch->id,
            'to_branch_id' => $branch->id,
            'status' => 'dispatched',
        ]);

        $response = $this->putJson("/api/v1/branches/{$branch->id}", [
            'status' => 'inactive',
        ], $this->authHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('in-transit or pending stock transfers', $response->json('message'));
    }

    public function test_deactivation_blocked_by_client_outbox_guard_header()
    {
        $branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Korangi Outlet',
            'code' => 'KRG-08',
            'status' => 'active',
        ]);

        $response = $this->putJson("/api/v1/branches/{$branch->id}", [
            'status' => 'inactive',
        ], $this->authHeaders([
            'X-Unsynced-Branch-Count' => '3',
        ]));

        $response->assertStatus(422);
        $this->assertStringContainsString('unsynced offline transactions exist in local outbox', $response->json('message'));
    }

    public function test_cannot_delete_branch_with_historical_operational_data()
    {
        $branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Historical Outlet',
            'code' => 'HIST-09',
            'status' => 'active',
        ]);

        FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $branch->id,
            'type' => 'cash',
            'is_default' => true,
            'name' => 'Historical Drawer',
            'account_number' => 'HIST-DR',
            'opening_balance' => 500.00,
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/branches/{$branch->id}", [], $this->authHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('Cannot delete branch with existing operational history', $response->json('message'));
    }

    public function test_cross_tenant_branch_isolation()
    {
        $foreignBiz = Business::create(['name' => 'Foreign Competitor Ltd']);
        $foreignBranch = Branch::create([
            'business_id' => $foreignBiz->id,
            'name' => 'Foreign Branch',
            'code' => 'FOR-01',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/branches/{$foreignBranch->id}", $this->authHeaders());
        $response->assertStatus(404);

        $response = $this->getJson("/api/v1/branches/{$foreignBranch->id}/readiness", $this->authHeaders());
        $response->assertStatus(404);
    }

    public function test_manager_mn_branch_membership_authorizes_secondary_branch()
    {
        // Branch A (primary for manager)
        $branchA = $this->mainBranch;

        // Branch B (secondary membership in branch_user)
        $branchB = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Branch B Secondary',
            'code' => 'BR-B-02',
            'status' => 'active',
        ]);

        // Branch C (unassigned)
        $branchC = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Branch C Unassigned',
            'code' => 'BR-C-03',
            'status' => 'active',
        ]);

        $manager = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $branchA->id,
        ]);
        $manager->businesses()->attach($this->business->id);
        $manager->branches()->attach([$branchA->id, $branchB->id], ['business_id' => $this->business->id]);
        $manager->assignRole('Branch Manager');
        $manager->givePermissionTo('manage branch settings');
        $token = $manager->createToken('MgrToken')->plainTextToken;

        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $this->business->id,
        ];

        // Manager can update Branch A (primary)
        $resA = $this->putJson("/api/v1/branches/{$branchA->id}", ['phone' => '111-222-3333'], $headers);
        $resA->assertStatus(200);

        // Manager can update Branch B (via branch_user M:N)
        $resB = $this->putJson("/api/v1/branches/{$branchB->id}", ['phone' => '444-555-6666'], $headers);
        $resB->assertStatus(200);

        // Manager cannot update Branch C (unassigned)
        $resC = $this->putJson("/api/v1/branches/{$branchC->id}", ['phone' => '777-888-9999'], $headers);
        $resC->assertStatus(403);
        $this->assertStringContainsString('Cannot alter an unassigned branch', $resC->json('message'));
    }

    public function test_unsynced_branch_outbox_guard_allows_zero_and_blocks_positive()
    {
        $branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Deactivation Outbox Outlet',
            'code' => 'DEACT-OUTBOX',
            'status' => 'active',
        ]);

        // Case 1: Positive outbox count blocks deactivation
        $blockedRes = $this->putJson("/api/v1/branches/{$branch->id}", [
            'status' => 'inactive',
        ], $this->authHeaders([
            'X-Unsynced-Branch-Count' => '4',
        ]));
        $blockedRes->assertStatus(422);
        $this->assertStringContainsString('unsynced offline transactions exist in local outbox', $blockedRes->json('message'));

        // Case 2: Zero outbox count allows deactivation (no other blockers present)
        $allowedRes = $this->putJson("/api/v1/branches/{$branch->id}", [
            'status' => 'inactive',
        ], $this->authHeaders([
            'X-Unsynced-Branch-Count' => '0',
        ]));
        $allowedRes->assertStatus(200);
        $this->assertEquals('inactive', $allowedRes->json('data.status'));
    }

    public function test_preview_decommission_and_consolidate_secondary_branch()
    {
        $branchB = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Gulshan',
            'code' => 'GUL-02',
            'is_primary' => false,
            'status' => 'active',
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Basmati Rice 5kg',
            'cost_price' => 500,
            'selling_price' => 700,
            'stock' => 25,
            'unit' => 'bag',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $branchB->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 25.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->mainBranch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10.000,
        ]);

        $drawerB = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $branchB->id,
            'name' => 'Drawer Gulshan',
            'type' => 'cash',
            'opening_balance' => 5000,
            'balance' => 5000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $drawerMain = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->mainBranch->id,
            'name' => 'Drawer Main',
            'type' => 'cash',
            'opening_balance' => 10000,
            'balance' => 10000,
            'is_default' => true,
            'status' => 'active',
        ]);

        CashRegisterSession::create([
            'business_id' => $this->business->id,
            'branch_id' => $branchB->id,
            'financial_account_id' => $drawerB->id,
            'user_id' => $this->owner->id,
            'opening_float' => 5000.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        // 1. Preview
        $previewRes = $this->getJson("/api/v1/branches/{$branchB->id}/decommission-preview", $this->authHeaders());
        $previewRes->assertStatus(200);
        $this->assertEquals(1, $previewRes->json('data.unique_stock_items'));
        $this->assertEquals(25.000, $previewRes->json('data.total_stock_quantity'));
        $this->assertEquals(5000.00, $previewRes->json('data.drawer_cash_balance'));
        $this->assertTrue($previewRes->json('data.has_open_cash_session'));

        // 2. Consolidate and decommission
        $consolidateRes = $this->postJson("/api/v1/branches/{$branchB->id}/consolidate-and-decommission", [], $this->authHeaders());
        $consolidateRes->assertStatus(200);
        $this->assertEquals('inactive', $consolidateRes->json('data.status'));
        $this->assertEquals(1, $consolidateRes->json('data.items_swept'));

        // Assert Branch B stock swept to 0
        $this->assertEquals(0.000, (float) BranchInventory::where('branch_id', $branchB->id)->where('product_id', $product->id)->value('quantity_on_hand'));
        // Assert Main Store stock increased to 35
        $this->assertEquals(35.000, (float) BranchInventory::where('branch_id', $this->mainBranch->id)->where('product_id', $product->id)->value('quantity_on_hand'));
        // Assert session closed
        $this->assertEquals('closed', CashRegisterSession::where('branch_id', $branchB->id)->value('status'));
        // Assert branch status is inactive
        $this->assertEquals('inactive', $branchB->fresh()->status);
    }

    public function test_purge_test_branch_without_sales()
    {
        $testBranch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Accidental Test Outlet',
            'code' => 'TEST-99',
            'is_primary' => false,
            'status' => 'inactive',
        ]);

        $res = $this->postJson("/api/v1/branches/{$testBranch->id}/purge-test-branch", [], $this->authHeaders());
        $res->assertStatus(200);
        $this->assertDatabaseMissing('branches', ['id' => $testBranch->id]);
    }
}
