<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\AccountTransfer;
use App\Services\AccountMovementService;
use App\Services\FinancialAccountService;
use App\Services\ExpenseService;
use Spatie\Permission\Models\Role;

class Phase7IntegrationAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;

    protected User $ownerA;
    protected User $managerA1;
    protected User $salespersonA1;
    protected User $ownerB;

    protected FinancialAccount $drawerA1;
    protected FinancialAccount $bankA;

    protected AccountMovementService $movementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->movementService = app(AccountMovementService::class);

        // Setup Businesses
        $this->businessA = Business::create(['name' => 'Business Alpha']);
        $this->businessB = Business::create(['name' => 'Business Beta']);

        // Setup Branches
        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A1', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A2', 'is_primary' => false]);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B1', 'is_primary' => true]);

        // Setup Owner A
        setPermissionsTeamId($this->businessA->id);
        $this->ownerA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id]);
        $this->ownerA->businesses()->attach([$this->businessA->id, $this->businessB->id]);
        $this->ownerA->assignRole('Business Owner');

        // Setup Manager A1 (Branch A1 only)
        $this->managerA1 = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id]);
        $this->managerA1->businesses()->attach($this->businessA->id);
        $this->managerA1->branches()->attach($this->branchA1->id, ['business_id' => $this->businessA->id]);
        $this->managerA1->assignRole('Branch Manager');

        // Setup Salesperson A1
        $this->salespersonA1 = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id]);
        $this->salespersonA1->businesses()->attach($this->businessA->id);
        $this->salespersonA1->assignRole('Salesperson');

        // Setup Owner B
        setPermissionsTeamId($this->businessB->id);
        $this->ownerB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB1->id]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole('Business Owner');

        // Reset default team context to Business A
        setPermissionsTeamId($this->businessA->id);

        // Accounts for Business A
        $this->drawerA1 = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Drawer A1 Cash',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->bankA = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => null,
            'name' => 'Alpha Central Bank',
            'type' => 'bank',
            'account_number' => 'PK1234567890',
            'bank_name' => 'HBL',
            'opening_balance' => 5000,
            'balance' => 5000,
            'is_default' => false,
            'status' => 'active',
        ]);
    }

    protected function authHeaders(User $user, ?int $businessId = null): array
    {
        $this->app['auth']->forgetGuards();
        $bizId = $businessId ?? $user->business_id;
        $token = $user->createToken('test_token')->plainTextToken;
        return [
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $bizId,
            'Accept' => 'application/json',
        ];
    }

    /**
     * B. Opening Balance Invariant Verification
     */
    public function test_opening_balance_invariant_is_strictly_preserved()
    {
        $account = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Invariant Test Account',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'status' => 'active',
        ]);

        $this->assertEquals(1000, $account->opening_balance);
        $this->assertEquals(1000, $this->movementService->calculateBalance($account));

        // Inflow 200
        $inflow = $this->movementService->postInflow($account, [
            'movement_category' => 'capital_in',
            'amount' => 200,
            'date' => now()->toDateString(),
        ]);

        $this->assertEquals(1000, $account->fresh()->opening_balance);
        $this->assertEquals(1200, $this->movementService->calculateBalance($account));
        $this->assertEquals(1200, $account->fresh()->balance);

        // Outflow 300
        $outflow = $this->movementService->postOutflow($account, [
            'movement_category' => 'capital_out',
            'amount' => 300,
            'date' => now()->toDateString(),
        ]);

        $this->assertEquals(1000, $account->fresh()->opening_balance);
        $this->assertEquals(900, $this->movementService->calculateBalance($account));
        $this->assertEquals(900, $account->fresh()->balance);

        // Void the 300 outflow
        $this->movementService->voidMovement($outflow, $this->ownerA->id, 'Testing void');

        $this->assertEquals(1000, $account->fresh()->opening_balance);
        $this->assertEquals(1200, $this->movementService->calculateBalance($account));
        $this->assertEquals(1200, $account->fresh()->balance);
    }

    /**
     * C. Account Cache Reconciliation Verification
     */
    public function test_cached_balance_reconciles_with_movement_formula_across_all_financial_events()
    {
        $account = $this->drawerA1;

        // 1. Capital In 500
        $this->movementService->postInflow($account, ['movement_category' => 'capital_in', 'amount' => 500]);
        $this->assertEquals($this->movementService->calculateBalance($account), $account->fresh()->balance);

        // 2. Capital Out 200
        $this->movementService->postOutflow($account, ['movement_category' => 'capital_out', 'amount' => 200]);
        $this->assertEquals($this->movementService->calculateBalance($account), $account->fresh()->balance);

        // 3. Expense 150 & Void
        $category = ExpenseCategory::create(['business_id' => $this->businessA->id, 'name' => 'Utilities']);
        $expenseService = app(ExpenseService::class);
        $expense = $expenseService->createExpense([
            'branch_id' => $this->branchA1->id,
            'financial_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 150,
            'date' => now()->toDateString(),
        ], $this->businessA->id, $this->ownerA->id);
        $this->assertEquals($this->movementService->calculateBalance($account), $account->fresh()->balance);

        $expenseService->voidExpense($expense, $this->businessA->id, $this->ownerA->id, 'Void test');
        $this->assertEquals($this->movementService->calculateBalance($account), $account->fresh()->balance);
    }

    /**
     * D & E. Tenant Isolation & Financial Account IDOR
     */
    public function test_tenant_isolation_and_idor_protection_on_financial_accounts()
    {
        // Owner B attempts to view Business A account
        $res = $this->withHeaders($this->authHeaders($this->ownerB))
            ->getJson("/api/v1/financial-accounts/{$this->drawerA1->id}");
        $res->assertStatus(404);

        // Owner B attempts to update Business A account
        $res = $this->withHeaders($this->authHeaders($this->ownerB))
            ->putJson("/api/v1/financial-accounts/{$this->drawerA1->id}", ['name' => 'Hacked Name']);
        $res->assertStatus(404);

        // Owner B attempts to view Business A movements
        $res = $this->withHeaders($this->authHeaders($this->ownerB))
            ->getJson("/api/v1/financial-accounts/{$this->drawerA1->id}/movements");
        $res->assertStatus(404);

        // Owner B attempts capital in on Business A account
        $res = $this->withHeaders($this->authHeaders($this->ownerB))
            ->postJson("/api/v1/financial-accounts/{$this->drawerA1->id}/capital-in", ['amount' => 500]);
        $res->assertStatus(404);
    }

    /**
     * F. Active Business Switching
     */
    public function test_active_business_switching_scopes_financial_accounts_correctly()
    {
        // Owner A active on Business A
        $resA = $this->withHeaders($this->authHeaders($this->ownerA, $this->businessA->id))
            ->getJson('/api/v1/financial-accounts');
        $resA->assertStatus(200);
        $idsA = collect($resA->json())->pluck('id')->toArray();
        $this->assertContains($this->drawerA1->id, $idsA);

        // Owner A switch to Business B
        $resB = $this->withHeaders($this->authHeaders($this->ownerA, $this->businessB->id))
            ->getJson('/api/v1/financial-accounts');
        $resB->assertStatus(200);
        $idsB = collect($resB->json())->pluck('id')->toArray();
        $this->assertNotContains($this->drawerA1->id, $idsB);
    }

    /**
     * G & H. Cash Branch Security & Central Bank Null-Branch Security
     */
    public function test_null_branch_central_bank_account_is_denied_to_branch_manager_and_salesperson()
    {
        // Owner A can view central bank account
        $resOwner = $this->withHeaders($this->authHeaders($this->ownerA))
            ->getJson("/api/v1/financial-accounts/{$this->bankA->id}");
        $resOwner->assertStatus(200);

        // Branch Manager A1 CANNOT view central bank account
        $resMgr = $this->withHeaders($this->authHeaders($this->managerA1))
            ->getJson("/api/v1/financial-accounts/{$this->bankA->id}");
        $resMgr->assertStatus(403);

        // Salesperson A1 CANNOT view central bank account
        $resSp = $this->withHeaders($this->authHeaders($this->salespersonA1))
            ->getJson("/api/v1/financial-accounts/{$this->bankA->id}");
        $resSp->assertStatus(403);
    }

    public function test_branch_manager_cannot_access_unassigned_branch_cash_drawer()
    {
        $drawerA2 = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'name' => 'Drawer A2 Cash',
            'type' => 'cash',
            'opening_balance' => 500,
            'balance' => 500,
            'status' => 'active',
        ]);

        // Manager A1 (assigned to Branch A1) attempts to view Drawer A2
        $res = $this->withHeaders($this->authHeaders($this->managerA1))
            ->getJson("/api/v1/financial-accounts/{$drawerA2->id}");
        $res->assertStatus(403);
    }

    /**
     * J & K. Default Cash Drawer Uniqueness
     */
    public function test_default_cash_drawer_uniqueness_enforced_per_business_and_branch()
    {
        // First default for Business A + Branch A1 exists ($this->drawerA1)

        // Attempt second default cash account for Business A + Branch A1
        $res = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/financial-accounts', [
                'name' => 'Duplicate Default Drawer',
                'type' => 'cash',
                'branch_id' => $this->branchA1->id,
                'is_default' => true,
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['is_default']);

        // Different Branch (Branch A2) CAN have its own default
        $resA2 = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/financial-accounts', [
                'name' => 'Branch A2 Default Drawer',
                'type' => 'cash',
                'branch_id' => $this->branchA2->id,
                'is_default' => true,
            ]);
        $resA2->assertStatus(201);
    }

    /**
     * L. POS Zero-Account / Inactive / Wrong-Branch Gates
     */
    public function test_pos_sale_rejected_when_no_active_cash_drawer_configured_for_branch()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Test Product',
            'sku' => 'PROD-A2',
            'selling_price' => 100,
            'cost_price' => 50,
            'stock' => 50,
        ]);
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 50,
        ]);

        // Branch A2 has no cash account configured. Attempt POS sale on Branch A2.
        $saleData = [
            'branch_id' => $this->branchA2->id,
            'total' => 100,
            'subtotal' => 100,
            'paid_amount' => 100,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'total' => 100],
            ],
        ];

        $res = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/sales', $saleData);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['branch_id']);
        $this->assertDatabaseMissing('sales', ['branch_id' => $this->branchA2->id, 'total' => 100]);
        $this->assertEquals(50, BranchInventory::where('branch_id', $this->branchA2->id)->where('product_id', $product->id)->first()->quantity_on_hand);
    }

    /**
     * M. Payment Method Compatibility
     */
    public function test_payment_method_compatibility_rules()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Compat Product',
            'sku' => 'COMP-1',
            'selling_price' => 100,
            'cost_price' => 50,
            'stock' => 50,
        ]);
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 50,
        ]);

        // Cash payment specifying a bank account is rejected
        $saleData = [
            'branch_id' => $this->branchA1->id,
            'financial_account_id' => $this->bankA->id,
            'total' => 100,
            'subtotal' => 100,
            'paid_amount' => 100,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'total' => 100],
            ],
        ];

        $res = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/sales', $saleData);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['financial_account_id']);
    }

    /**
     * N, O & P. Sale Financial Idempotency & Movement Uniqueness & Atomicity
     */
    public function test_sale_idempotency_key_conflicting_account_id_is_rejected()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Idem Product',
            'sku' => 'IDEM-1',
            'selling_price' => 100,
            'cost_price' => 50,
            'stock' => 50,
        ]);
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 50,
        ]);

        $account2 = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Drawer A1 Second',
            'type' => 'cash',
            'opening_balance' => 500,
            'balance' => 500,
            'status' => 'active',
        ]);

        $key = 'SALE-IDEM-KEY-999';

        $payload1 = [
            'branch_id' => $this->branchA1->id,
            'financial_account_id' => $this->drawerA1->id,
            'total' => 100,
            'subtotal' => 100,
            'paid_amount' => 100,
            'payment_method' => 'cash',
            'idempotency_key' => $key,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'total' => 100],
            ],
        ];

        // First attempt succeeds
        $res1 = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/sales', $payload1);
        $res1->assertStatus(201);
        $saleId1 = $res1->json('id');

        // Exact retry returns same sale
        $resRetry = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/sales', $payload1);
        $this->assertTrue(in_array($resRetry->status(), [200, 201]));
        $this->assertEquals($saleId1, $resRetry->json('id'));

        // Conflicting payload with different financial_account_id returns 422
        $payloadConflict = $payload1;
        $payloadConflict['financial_account_id'] = $account2->id;

        $resConflict = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/sales', $payloadConflict);
        $resConflict->assertStatus(422);

        // Verify single AccountMovement created
        $this->assertEquals(1, AccountMovement::where('reference_type', Sale::class)->where('reference_id', $saleId1)->count());
    }

    /**
     * Q, R, S, T. CustomerPayment Idempotency, Account Security & Reversal Sync
     */
    public function test_customer_payment_reversal_synchronizes_receivable_and_account_movement()
    {
        $customer = Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Debt Customer',
            'opening_balance' => 1000,
            'balance' => 1000,
        ]);

        $account = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Dedicated Customer Payment Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'status' => 'active',
        ]);
        $startBal = (float) $account->fresh()->balance;

        // Post customer payment 400
        $payRes = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/customers/{$customer->id}/payments", [
                'branch_id' => $this->branchA1->id,
                'financial_account_id' => $account->id,
                'amount' => 400,
                'payment_method' => 'cash',
                'date' => now()->toDateString(),
            ]);

        $payRes->assertStatus(201);
        $paymentId = $payRes->json('id');

        $this->assertEquals(600, (float) $customer->fresh()->balance);
        $this->assertEquals($startBal + 400, (float) $account->fresh()->balance);

        // Void/reverse customer payment
        $voidRes = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/customer-payments/{$paymentId}/reverse", [
                'reason' => 'Bounced check',
            ]);

        $voidRes->assertStatus(200);

        // Receivable restored, account balance restored
        $this->assertEquals(1000, (float) $customer->fresh()->balance);
        $this->assertEquals($startBal, (float) $account->fresh()->balance);

        // Double void returns 422
        $voidRes2 = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/customer-payments/{$paymentId}/reverse", [
                'reason' => 'Repeat void',
            ]);
        $voidRes2->assertStatus(422);
    }

    /**
     * U, V, W. SupplierPayment Security & Double-Effect Prevention
     */
    public function test_supplier_payment_updates_payable_once_and_outflow_once()
    {
        $supplier = Supplier::create([
            'business_id' => $this->businessA->id,
            'name' => 'Vender Corp',
            'opening_balance' => 2000,
            'balance' => 2000,
        ]);

        $account = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => null,
            'name' => 'Dedicated Supplier Payment Bank Account',
            'type' => 'bank',
            'opening_balance' => 5000,
            'balance' => 5000,
            'status' => 'active',
        ]);
        $startBal = (float) $account->fresh()->balance;

        $payRes = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
                'financial_account_id' => $account->id,
                'amount' => 500,
                'payment_method' => 'bank_transfer',
                'payment_date' => now()->toDateString(),
                'reference_number' => 'REF-SUPP-101',
            ]);

        $payRes->assertStatus(201);

        $this->assertEquals(1500, (float) $supplier->fresh()->balance);
        $this->assertEquals($startBal - 500, (float) $account->fresh()->balance);

        // Verify AccountMovement recorded once
        $this->assertDatabaseHas('account_movements', [
            'account_id' => $account->id,
            'movement_category' => 'supplier_payment',
            'amount' => 500,
            'status' => 'posted',
        ]);
    }

    /**
     * X, Y, Z. Expense Idempotency, Category Normalization & Voiding
     */
    public function test_expense_category_normalization_duplicate_prevention()
    {
        $cat1 = ExpenseCategory::create([
            'business_id' => $this->businessA->id,
            'name' => 'Utilities',
            'normalized_name' => 'utilities',
        ]);

        // Attempting to create duplicate category with extra whitespace and uppercase returns existing or 422
        $res = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/expense-categories', [
                'name' => '  UTILITIES  ',
            ]);

        // Should return existing category or handle gracefully
        if ($res->status() === 200 || $res->status() === 201) {
            $this->assertEquals($cat1->id, $res->json('id'));
        } else {
            $res->assertStatus(422);
        }
    }

    /**
     * AB, AC, AD. Refund Settlement Security & Controlled Reversal Workflow
     */
    public function test_refund_settlement_and_controlled_reversal_workflow()
    {
        $customer = Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Refund Customer',
        ]);

        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Refundable Product',
            'sku' => 'REF-PROD-1',
            'selling_price' => 200,
            'cost_price' => 100,
            'stock' => 20,
        ]);
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 20,
        ]);

        // Create Sale
        $saleRes = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/sales', [
                'branch_id' => $this->branchA1->id,
                'financial_account_id' => $this->drawerA1->id,
                'total' => 200,
                'subtotal' => 200,
                'paid_amount' => 200,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 200, 'total' => 200],
                ],
            ]);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        // Create Sale Return (refund_amount = 200)
        $returnRes = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/sales/{$saleId}/returns", [
                'reason' => 'Defective',
                'refund_amount' => 200,
                'items' => [
                    ['sale_item_id' => $saleItemId, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 200],
                ],
            ]);
        $returnRes->assertStatus(201);
        $returnId = $returnRes->json('id');

        // Settle Refund
        $account = $this->drawerA1;
        $balBefore = (float) $account->fresh()->balance;

        $settleRes = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/sale-returns/{$returnId}/settle-refund", [
                'financial_account_id' => $account->id,
                'amount' => 200,
            ]);
        $settleRes->assertStatus(201);
        $this->assertEquals($balBefore - 200, (float) $account->fresh()->balance);

        // Reverse Refund
        $reverseRes = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/sale-returns/{$returnId}/reverse-refund", [
                'reason' => 'Customer store credit chosen instead',
            ]);
        $reverseRes->assertStatus(200);
        $this->assertEquals($balBefore, (float) $account->fresh()->balance);

        // Double reversal fails
        $doubleRev = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/sale-returns/{$returnId}/reverse-refund", [
                'reason' => 'Repeat reverse',
            ]);
        $doubleRev->assertStatus(422);
    }

    /**
     * AE, AF, AG, AH. Transfer Conservation, Idempotency & Security
     */
    public function test_account_transfer_conservation_and_idempotency()
    {
        $accA = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Transfer Source Drawer',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'status' => 'active',
        ]);

        $accB = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => null,
            'name' => 'Transfer Dest Bank',
            'type' => 'bank',
            'opening_balance' => 5000,
            'balance' => 5000,
            'status' => 'active',
        ]);

        $totalBefore = (float) $accA->fresh()->balance + (float) $accB->fresh()->balance;

        $key = 'TRANSFER-IDEM-KEY-777';

        // Transfer 300 from accA to accB
        $res = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $accA->id,
                'destination_account_id' => $accB->id,
                'amount' => 300,
                'date' => now()->toDateString(),
                'idempotency_key' => $key,
            ]);

        $res->assertStatus(201);

        $this->assertEquals(700, (float) $accA->fresh()->balance);
        $this->assertEquals(5300, (float) $accB->fresh()->balance);
        $this->assertEquals($totalBefore, (float) $accA->fresh()->balance + (float) $accB->fresh()->balance);

        // Exact retry returns same transfer
        $retryRes = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $accA->id,
                'destination_account_id' => $accB->id,
                'amount' => 300,
                'date' => now()->toDateString(),
                'idempotency_key' => $key,
            ]);
        $this->assertTrue(in_array($retryRes->status(), [200, 201]));

        // Conflicting amount retry returns 422
        $conflictRes = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $accA->id,
                'destination_account_id' => $accB->id,
                'amount' => 400,
                'date' => now()->toDateString(),
                'idempotency_key' => $key,
            ]);
        $conflictRes->assertStatus(422);
    }

    public function test_account_transfer_rejects_insufficient_funds_and_cross_tenant()
    {
        $accA = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Limited Source Drawer',
            'type' => 'cash',
            'opening_balance' => 700,
            'balance' => 700,
            'status' => 'active',
        ]);

        // Attempt transfer exceeding balance
        $resOver = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $accA->id,
                'destination_account_id' => $this->bankA->id,
                'amount' => 99999,
            ]);
        $resOver->assertStatus(422);

        // Attempt cross-tenant transfer to Business B account
        $accB2 = FinancialAccount::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB1->id,
            'name' => 'Beta Cash',
            'type' => 'cash',
            'opening_balance' => 100,
            'balance' => 100,
            'status' => 'active',
        ]);

        $resCross = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $accA->id,
                'destination_account_id' => $accB2->id,
                'amount' => 100,
            ]);
        $resCross->assertStatus(422);
    }

    /**
     * AJ & AK. Capital In & Capital Out
     */
    public function test_capital_in_and_capital_out_rbac_and_balance_effects()
    {
        setPermissionsTeamId($this->businessA->id);
        $account = FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => null,
            'name' => 'Capital Test Central Bank',
            'type' => 'bank',
            'opening_balance' => 5000,
            'balance' => 5000,
            'status' => 'active',
        ]);
        $balBefore = (float) $account->fresh()->balance;

        // Manager A1 cannot inject capital
        $resMgr = $this->withHeaders($this->authHeaders($this->managerA1))
            ->postJson("/api/v1/financial-accounts/{$account->id}/capital-in", ['amount' => 1000]);
        $resMgr->assertStatus(403);

        // Owner A injects capital 1000
        $resCapIn = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/financial-accounts/{$account->id}/capital-in", ['amount' => 1000]);
        $resCapIn->assertStatus(201);
        $this->assertEquals($balBefore + 1000, (float) $account->fresh()->balance);

        // Owner A withdraws capital 500
        $resCapOut = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson("/api/v1/financial-accounts/{$account->id}/capital-out", ['amount' => 500]);
        $resCapOut->assertStatus(201);
        $this->assertEquals($balBefore + 500, (float) $account->fresh()->balance);
    }

    /**
     * AR & AS. Account Statement & Inactivation
     */
    public function test_account_statement_generation_and_hard_delete_prevention()
    {
        setPermissionsTeamId($this->businessA->id);
        $account = $this->drawerA1;

        // Post a movement to ensure movements exist
        $this->movementService->postInflow($account, ['movement_category' => 'capital_in', 'amount' => 100]);

        $resStmt = $this->withHeaders($this->authHeaders($this->ownerA))
            ->getJson("/api/v1/financial-accounts/{$account->id}/movements");

        $resStmt->assertStatus(200);
        $resStmt->assertJsonStructure([
            'account' => ['id', 'name', 'type', 'opening_balance', 'current_balance'],
            'statement_baseline',
            'movements',
        ]);

        // Attempt hard delete on account with movements fails with 422
        $resDel = $this->withHeaders($this->authHeaders($this->ownerA))
            ->deleteJson("/api/v1/financial-accounts/{$account->id}");
        $resDel->assertStatus(422);
    }

    /**
     * D. Mass-Assignment Security Verification
     */
    public function test_client_cannot_mass_assign_or_spoof_account_balance()
    {
        setPermissionsTeamId($this->businessA->id);

        // Client attempts to POST account with malicious balance = 9999999
        $resPost = $this->withHeaders($this->authHeaders($this->ownerA))
            ->postJson('/api/v1/financial-accounts', [
                'name' => 'Mass Assign Guard Account',
                'type' => 'cash',
                'branch_id' => $this->branchA2->id,
                'opening_balance' => 500,
                'balance' => 9999999,
            ]);

        $resPost->assertStatus(201);
        $accountId = $resPost->json('id');
        $account = FinancialAccount::findOrFail($accountId);

        // Balance must equal opening_balance (500), ignoring 9999999
        $this->assertEquals(500, (float) $account->balance);

        // Client attempts to PUT/PATCH account with malicious balance = 9999999
        $resPut = $this->withHeaders($this->authHeaders($this->ownerA))
            ->putJson("/api/v1/financial-accounts/{$accountId}", [
                'name' => 'Renamed Guard Account',
                'balance' => 9999999,
            ]);

        $resPut->assertStatus(200);
        $this->assertEquals(500, (float) $account->fresh()->balance);
    }
}
