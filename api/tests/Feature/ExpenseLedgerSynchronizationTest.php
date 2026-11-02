<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\User;
use App\Services\AccountMovementService;
use App\Services\ExpenseService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExpenseLedgerSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected FinancialAccount $drawer;
    protected ExpenseCategory $category;
    protected ExpenseService $expenseService;
    protected AccountMovementService $movementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Expense Sync Biz']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Store', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->drawer = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 5000.00,
            'balance' => 5000.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->category = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Store Utilities',
            'monthly_budget' => 15000.00,
        ]);

        $this->expenseService = app(ExpenseService::class);
        $this->movementService = app(AccountMovementService::class);
    }

    public function test_expense_creates_atomic_outflow_movement_with_exact_fixed_decimal(): void
    {
        $this->assertEquals('5000.00', number_format($this->movementService->calculateBalance($this->drawer), 2, '.', ''));

        $expense = $this->expenseService->createExpense([
            'category_id' => $this->category->id,
            'financial_account_id' => $this->drawer->id,
            'amount' => 350.50,
            'date' => date('Y-m-d'),
            'description' => 'Electricity token recharge',
            'idempotency_key' => 'exp_sync_001',
        ], $this->business->id, $this->owner->id);

        $this->assertEquals('posted', $expense->status);
        $this->assertEquals(350.50, $expense->amount);

        // Movement posted
        $movement = AccountMovement::where('reference_type', Expense::class)
            ->where('reference_id', $expense->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals('outflow', $movement->type);
        $this->assertEquals('expense', $movement->movement_category);
        $this->assertEquals('posted', $movement->status);
        $this->assertEquals(350.50, $movement->amount);

        // Check drawer balance
        $currentBal = $this->movementService->calculateBalance($this->drawer);
        $this->assertEquals('4649.50', number_format($currentBal, 2, '.', ''));
    }

    public function test_expense_void_restores_drawer_balance_atomically(): void
    {
        $expense = $this->expenseService->createExpense([
            'category_id' => $this->category->id,
            'financial_account_id' => $this->drawer->id,
            'amount' => 500.00,
            'date' => date('Y-m-d'),
            'description' => 'Packaging supplies',
        ], $this->business->id, $this->owner->id);

        $this->assertEquals('4500.00', number_format($this->movementService->calculateBalance($this->drawer), 2, '.', ''));

        // Void expense
        $voidedExpense = $this->expenseService->voidExpense($expense, $this->owner->id, 'Entered wrong amount');

        $this->assertEquals('voided', $voidedExpense->status);
        $this->assertNotNull($voidedExpense->voided_at);
        $this->assertEquals('Entered wrong amount', $voidedExpense->void_reason);

        // Movement voided
        $movement = AccountMovement::where('reference_type', Expense::class)
            ->where('reference_id', $expense->id)
            ->first();
        $this->assertEquals('voided', $movement->status);

        // Drawer balance restored
        $restoredBal = $this->movementService->calculateBalance($this->drawer);
        $this->assertEquals('5000.00', number_format($restoredBal, 2, '.', ''));
    }

    public function test_expense_overdraft_protection_blocks_excessive_amount(): void
    {
        $this->expectException(ValidationException::class);

        // Attempting to spend 6000.00 when drawer has 5000.00
        $this->expenseService->createExpense([
            'category_id' => $this->category->id,
            'financial_account_id' => $this->drawer->id,
            'amount' => 6000.00,
            'date' => date('Y-m-d'),
            'description' => 'Generator fuel',
        ], $this->business->id, $this->owner->id);
    }

    public function test_expense_tenant_isolation_prevents_cross_business_account_usage(): void
    {
        $otherBiz = Business::create(['name' => 'Foreign Biz']);
        $foreignDrawer = FinancialAccount::create([
            'business_id' => $otherBiz->id,
            'name' => 'Foreign Drawer',
            'type' => 'cash',
            'opening_balance' => 10000.00,
            'balance' => 10000.00,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        // User from $this->business attempts to record expense against $foreignDrawer
        $this->expenseService->createExpense([
            'category_id' => $this->category->id,
            'financial_account_id' => $foreignDrawer->id,
            'amount' => 100.00,
            'date' => date('Y-m-d'),
            'description' => 'Cross-tenant attempt',
        ], $this->business->id, $this->owner->id);
    }

    public function test_monthly_budget_non_regression(): void
    {
        $this->assertEquals(15000.00, $this->category->monthly_budget);

        $this->category->update(['monthly_budget' => 20000.00]);
        $this->assertEquals(20000.00, $this->category->fresh()->monthly_budget);
    }
}
