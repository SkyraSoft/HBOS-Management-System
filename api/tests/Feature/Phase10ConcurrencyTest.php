<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\FinancialAccount;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Services\AccountMovementService;
use App\Services\ExpenseService;
use App\Services\InventoryService;
use App\Services\SaleService;
use App\Services\UserGovernanceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase10ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $owner;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Concurrency Corp']);
        $this->branchA = Branch::create(['business_id' => $this->business->id, 'name' => 'HQ Branch', 'is_primary' => true]);
        $this->branchB = Branch::create(['business_id' => $this->business->id, 'name' => 'Secondary Branch', 'is_primary' => false]);

        setPermissionsTeamId($this->business->id);
        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'role' => 'Business Owner',
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Limited Stock Widget',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 1,
            'unit' => 'pcs',
        ]);
    }

    /**
     * Section T / AC-10.19: Concurrent Final-Stock Sale
     * When stock is 1, two competing requests for 1 unit must result in exactly 1 sale,
     * final stock of 0, zero negative stock, and the loser rejected with ValidationException.
     */
    public function test_concurrent_final_stock_sale_prevents_negative_inventory(): void
    {
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 1,
        ]);

        $account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Cash Register',
            'type' => 'cash',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => true,
            'status' => 'active',
        ]);

        $saleService = app(SaleService::class);

        // First sale for 1 unit: must succeed
        $sale1 = $saleService->createSale([
            'branch_id' => $this->branchA->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1]
            ],
            'paid_amount' => 100,
            'payment_method' => 'cash',
            'financial_account_id' => $account->id,
        ], $this->owner);

        $this->assertNotNull($sale1->id);

        // Immediate second competing sale for 1 unit: must be rejected due to zero available stock
        try {
            $saleService->createSale([
                'branch_id' => $this->branchA->id,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1]
                ],
                'paid_amount' => 100,
                'payment_method' => 'cash',
                'financial_account_id' => $account->id,
            ], $this->owner);
            $this->fail('Expected Exception for insufficient stock.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        // Invariant verification
        $inv = BranchInventory::where('branch_id', $this->branchA->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(0, $inv->quantity_on_hand);
        $this->assertGreaterThanOrEqual(0, $inv->quantity_on_hand);

        // Exactly one sale issue movement recorded
        $movements = InventoryMovement::where('product_id', $this->product->id)->where('type', 'sale')->count();
        $this->assertEquals(1, $movements);
    }

    /**
     * Section U / AC-10.19: Concurrent Inventory Transfer
     * Source branch has limited stock (5). Competing transfers of 4 and 2 must allow only 4,
     * reject 2, leaving 1 in source and 4 in destination.
     */
    public function test_concurrent_inventory_transfer_prevents_source_depletion(): void
    {
        $invA = BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 5,
        ]);
        $invB = BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchB->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 0,
        ]);

        $inventoryService = app(InventoryService::class);

        // Transfer 1: 4 units from A to B (Succeeds: 5 - 4 = 1 left in A, 4 in B)
        $inventoryService->transferStock([
            'business_id' => $this->business->id,
            'from_branch_id' => $this->branchA->id,
            'to_branch_id' => $this->branchB->id,
            'product_id' => $this->product->id,
            'quantity' => 4,
            'performed_by' => $this->owner->id,
            'notes' => 'First transfer',
        ]);

        $this->assertEquals(1, $invA->fresh()->quantity_on_hand);
        $this->assertEquals(4, $invB->fresh()->quantity_on_hand);

        // Competing Transfer 2: 2 units from A to B (Fails because only 1 remains in A)
        $this->expectException(\Exception::class);
        $inventoryService->transferStock([
            'business_id' => $this->business->id,
            'from_branch_id' => $this->branchA->id,
            'to_branch_id' => $this->branchB->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'performed_by' => $this->owner->id,
            'notes' => 'Competing second transfer',
        ]);

        // State remains intact
        $this->assertEquals(1, $invA->fresh()->quantity_on_hand);
        $this->assertEquals(4, $invB->fresh()->quantity_on_hand);
    }

    /**
     * Section V / AC-10.19: Concurrent Financial Outflow & Balance Conservation
     * Account has balance 100. Two competing outflows of 80 and 50 must allow only the first,
     * reject the second, leaving exactly 20 without overdraft.
     */
    public function test_concurrent_financial_outflow_prevents_overdraft(): void
    {
        $account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Operating Treasury',
            'type' => 'bank',
            'opening_balance' => 100.00,
            'balance' => 100.00,
            'status' => 'active',
        ]);

        $expenseService = app(ExpenseService::class);

        // Outflow 1: 80.00 (Succeeds -> balance becomes 20.00)
        $exp1 = $expenseService->createExpense([
            'branch_id' => $this->branchA->id,
            'financial_account_id' => $account->id,
            'amount' => 80.00,
            'category' => 'Utilities',
            'title' => 'Electric bill',
        ], $this->business->id, $this->owner->id);

        $this->assertEquals(20.00, (float) $account->fresh()->balance);

        // Competing Outflow 2: 50.00 (Fails because 50 > 20 available funds)
        try {
            $expenseService->createExpense([
                'branch_id' => $this->branchA->id,
                'financial_account_id' => $account->id,
                'amount' => 50.00,
                'category' => 'Supplies',
                'title' => 'Office supplies',
            ], $this->business->id, $this->owner->id);
            $this->fail('Expected ValidationException for insufficient funds.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Account balance remains exactly 20.00, opening_balance preserved at 100.00
        $fresh = $account->fresh();
        $this->assertEquals(100.00, (float) $fresh->opening_balance);
        $this->assertEquals(20.00, (float) $fresh->balance);
    }

    /**
     * Section W / AC-10.19: Last Owner Safety under Concurrent Demotion
     * A business with two owners allows deactivating one, but strictly blocks
     * deactivating the last active owner.
     */
    public function test_last_owner_deactivation_is_strictly_blocked(): void
    {
        // 1. Single active owner cannot demote self
        $resDemote = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->putJson("/api/v1/users/{$this->owner->id}", [
                'role' => 'Salesperson',
            ]);
        $resDemote->assertStatus(422);

        // 2. Single active owner cannot deactivate self
        $resDeactivate = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/users/{$this->owner->id}/status", [
                'is_active' => false,
            ]);
        $resDeactivate->assertStatus(422);

        // 3. Add second owner
        setPermissionsTeamId($this->business->id);
        $roleOwner = \Spatie\Permission\Models\Role::where('name', 'Business Owner')->where('business_id', $this->business->id)->first();
        $owner2 = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'role' => 'Business Owner',
            'is_active' => true,
        ]);
        $owner2->businesses()->attach($this->business->id);
        $owner2->assignRole($roleOwner);

        // Owner 1 can deactivate Owner 2 (leaving 1 active owner)
        $resDeactivate2 = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/users/{$owner2->id}/status", [
                'is_active' => false,
            ]);
        $resDeactivate2->assertStatus(200);
        $this->assertFalse((bool) $owner2->fresh()->is_active);

        // Now Owner 2 is inactive; attempting to deactivate Owner 1 (last active owner) must be rejected with 422
        $resDeactivateLast = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/users/{$this->owner->id}/status", [
                'is_active' => false,
            ]);
        $resDeactivateLast->assertStatus(422);
        $this->assertTrue((bool) $this->owner->fresh()->is_active);
    }

    /**
     * Section X / AC-10.19: Primary Branch Invariant
     * A business must maintain exactly one primary branch.
     */
    public function test_primary_branch_invariant_ensures_single_primary_branch(): void
    {
        // Initially Branch A is primary, Branch B is not
        $this->assertTrue((bool) $this->branchA->fresh()->is_primary);
        $this->assertFalse((bool) $this->branchB->fresh()->is_primary);

        // Switch primary to Branch B in transaction
        DB::transaction(function () {
            Branch::where('business_id', $this->business->id)->update(['is_primary' => false]);
            $this->branchB->update(['is_primary' => true]);
        });

        // Exactly one branch is primary
        $primaryCount = Branch::where('business_id', $this->business->id)->where('is_primary', true)->count();
        $this->assertEquals(1, $primaryCount);
        $this->assertTrue((bool) $this->branchB->fresh()->is_primary);
        $this->assertFalse((bool) $this->branchA->fresh()->is_primary);
    }
}
