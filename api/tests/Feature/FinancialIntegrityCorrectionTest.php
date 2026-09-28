<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\SaleReturn;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\SaleService;
use App\Services\CustomerPaymentService;
use App\Services\AccountMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialIntegrityCorrectionTest extends TestCase
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

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Correction Business']);
        $this->branchA = Branch::create(['business_id' => $this->business->id, 'name' => 'Branch A', 'is_primary' => true]);
        $this->branchB = Branch::create(['business_id' => $this->business->id, 'name' => 'Branch B', 'is_primary' => false]);

        setPermissionsTeamId($this->business->id);
        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Test Product',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 100,
            'unit' => 'pcs',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 100,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchB->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 100,
        ]);
    }

    public function test_1_zero_financial_accounts_cash_sale_returns_422()
    {
        $this->assertEquals(0, FinancialAccount::where('business_id', $this->business->id)->count());

        $saleService = app(SaleService::class);

        $this->expectException(ValidationException::class);
        $saleService->createSale([
            'branch_id' => $this->branchA->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1]
            ],
            'paid_amount' => 100,
            'payment_method' => 'cash',
        ], $this->owner);
    }

    public function test_2_inactive_cash_drawer_cash_sale_returns_422()
    {
        $account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Inactive Drawer',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'is_default' => true,
            'status' => 'inactive',
        ]);
        $this->assertEquals(1, FinancialAccount::where('business_id', $this->business->id)->where('status', 'inactive')->count());

        $saleService = app(SaleService::class);

        $this->expectException(ValidationException::class);
        $saleService->createSale([
            'branch_id' => $this->branchA->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1]
            ],
            'paid_amount' => 100,
            'payment_method' => 'cash',
        ], $this->owner);
    }

    public function test_3_wrong_branch_drawer_cash_sale_returns_422()
    {
        // Default cash drawer created ONLY for Branch B
        FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Branch B Drawer',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $saleService = app(SaleService::class);

        // Attempt Sale at Branch A (which has no active cash drawer)
        $this->expectException(ValidationException::class);
        $saleService->createSale([
            'branch_id' => $this->branchA->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1]
            ],
            'paid_amount' => 100,
            'payment_method' => 'cash',
        ], $this->owner);
    }

    public function test_4_and_5_zero_balance_account_expense_returns_422_and_opening_balance_unchanged()
    {
        $account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Zero Drawer',
            'type' => 'cash',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => true,
            'status' => 'active',
        ]);

        $expenseService = app(ExpenseService::class);

        try {
            $expenseService->createExpense([
                'branch_id' => $this->branchA->id,
                'financial_account_id' => $account->id,
                'amount' => 100,
                'category' => 'Rent',
            ], $this->business->id, $this->owner->id);

            $this->fail('Expected ValidationException for insufficient balance');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Verify opening balance and balance remain exactly 0
        $freshAccount = $account->fresh();
        $this->assertEquals(0.00, (float)$freshAccount->opening_balance);
        $this->assertEquals(0.00, (float)$freshAccount->balance);
        $this->assertEquals(0, \App\Models\Expense::where('business_id', $this->business->id)->count());
        $this->assertEquals(0, \App\Models\AccountMovement::where('business_id', $this->business->id)->count());
    }

    public function test_6_and_7_funded_account_expense_posts_outflow_without_altering_opening_balance()
    {
        $account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Funded Drawer',
            'type' => 'cash',
            'opening_balance' => 500,
            'balance' => 500,
            'is_default' => true,
            'status' => 'active',
        ]);

        $expenseService = app(ExpenseService::class);

        $expense = $expenseService->createExpense([
            'branch_id' => $this->branchA->id,
            'financial_account_id' => $account->id,
            'amount' => 100,
            'category' => 'Utilities',
            'date' => '2026-09-17',
        ], $this->business->id, $this->owner->id);

        $this->assertEquals('posted', $expense->status);

        $freshAccount = $account->fresh();
        $this->assertEquals(500.00, (float)$freshAccount->opening_balance);
        $this->assertEquals(400.00, (float)$freshAccount->balance);

        $this->assertDatabaseHas('account_movements', [
            'account_id' => $account->id,
            'type' => 'outflow',
            'movement_category' => 'expense',
            'amount' => 100,
            'status' => 'posted',
        ]);
    }

    public function test_8_customer_payment_cannot_commit_without_resolvable_account()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Unfunded Customer',
            'opening_balance' => 500,
        ]);
        app(\App\Services\CustomerAccountService::class)->recalculateBalance($customer);

        // No financial accounts exist
        $this->assertEquals(0, FinancialAccount::where('business_id', $this->business->id)->count());

        $paymentService = app(CustomerPaymentService::class);

        $this->expectException(ValidationException::class);
        $paymentService->recordPayment([
            'branch_id' => $this->branchA->id,
            'customer_id' => $customer->id,
            'amount' => 100,
            'payment_method' => 'cash',
        ], $this->owner);
    }

    public function test_9_supplier_payment_cannot_commit_without_resolvable_account()
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Test Supplier',
            'opening_balance' => 500,
            'balance' => 500,
        ]);

        // No financial accounts exist
        $this->assertEquals(0, FinancialAccount::where('business_id', $this->business->id)->count());

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
                'amount' => 100,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422);
        $this->assertEquals(500, $supplier->fresh()->balance);
        $this->assertEquals(0, \App\Models\SupplierPayment::where('business_id', $this->business->id)->count());
    }

    public function test_10_refund_settlement_insufficient_balance_rejects_without_opening_balance_mutation()
    {
        $account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Low Drawer',
            'type' => 'cash',
            'opening_balance' => 50,
            'balance' => 50,
            'is_default' => true,
            'status' => 'active',
        ]);

        $sale = app(SaleService::class)->createSale([
            'branch_id' => $this->branchA->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2] // 200 total
            ],
            'paid_amount' => 200,
            'payment_method' => 'cash',
            'financial_account_id' => $account->id,
        ], $this->owner);

        // Account balance after sale inflow = 50 + 200 = 250.
        // Let's create a return for 200
        $saleReturn = app(SaleService::class)->processReturn($sale, [
            ['sale_item_id' => $sale->items->first()->id, 'quantity' => 2]
        ], 'Defective', $this->owner);

        $this->assertEquals(200, $saleReturn->refund_amount);

        // Drain account balance with an expense of 200 -> balance becomes 50
        app(ExpenseService::class)->createExpense([
            'branch_id' => $this->branchA->id,
            'financial_account_id' => $account->id,
            'amount' => 200,
            'category' => 'Operational',
        ], $this->business->id, $this->owner->id);

        $this->assertEquals(50, $account->fresh()->balance);

        // Now attempt refund settlement of 200 when available balance is only 50
        $movementService = app(AccountMovementService::class);

        try {
            $movementService->settleSaleReturnRefund($saleReturn, $account, 200, date('Y-m-d'), $this->owner->id);
            $this->fail('Expected ValidationException for insufficient balance on refund settlement');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Account opening_balance must remain 50 and balance remain 50
        $freshAccount = $account->fresh();
        $this->assertEquals(50.00, (float)$freshAccount->opening_balance);
        $this->assertEquals(50.00, (float)$freshAccount->balance);
        $this->assertEquals(200.00, (float)$saleReturn->fresh()->refund_amount);
    }
}
