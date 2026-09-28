<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\FinancialAccount;
use App\Models\BranchInventory;
use App\Models\ActivityLog;
use App\Services\AuditService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class Phase9TransactionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $owner;
    protected Product $product;
    protected FinancialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $extraPermissions = [
            'view audit logs', 'manage business settings', 'manage users',
            'manage expenses', 'void expenses', 'view expenses', 'create expenses',
            'manage financial accounts', 'view financial accounts',
            'manage customer payments', 'settle refunds',
        ];

        foreach ($extraPermissions as $pName) {
            Permission::firstOrCreate(['name' => $pName, 'guard_name' => 'web']);
        }

        $this->business = Business::create(['name' => 'Audit Test Corp', 'currency' => 'PKR']);
        $this->branch1 = Branch::create(['business_id' => $this->business->id, 'name' => 'HQ Branch', 'is_primary' => true]);
        $this->branch2 = Branch::create(['business_id' => $this->business->id, 'name' => 'Secondary Branch', 'is_primary' => false]);

        setPermissionsTeamId($this->business->id);
        $roleOwner = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->business->id, 'business_id' => $this->business->id]);
        $roleOwner->syncPermissions(Permission::all());

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'role' => 'Business Owner',
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole($roleOwner);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Widget Alpha',
            'sku' => 'WID-001',
            'cost_price' => 50,
            'selling_price' => 100,
            'is_active' => true,
        ]);

        $this->account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'current_balance' => 10000,
            'is_active' => true,
        ]);

        app(\App\Services\AccountMovementService::class)->postInflow($this->account, [
            'branch_id' => $this->branch1->id,
            'movement_category' => 'capital_in',
            'amount' => 10000,
            'date' => now()->toDateString(),
            'description' => 'Initial Vault Funding',
            'user_id' => $this->owner->id,
        ]);
    }

    /**
     * AN. Inventory Audit: Adjustments and Transfers
     */
    public function test_inventory_adjustment_and_transfer_audit()
    {
        $inv = BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 20,
            'minimum_stock' => 5,
        ]);

        // 1. Adjustment
        $resAdjust = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson('/api/v1/inventory/adjustments', [
                'branch_id' => $this->branch1->id,
                'product_id' => $this->product->id,
                'type' => 'adjustment_in',
                'quantity' => 5,
                'notes' => 'Cycle count correction',
            ]);
        $resAdjust->assertStatus(201);

        $adjustLog = ActivityLog::where('log_name', 'inventory')
            ->where('event', 'updated')
            ->where('business_id', $this->business->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($adjustLog);
        $this->assertEquals($this->branch1->id, $adjustLog->branch_id);
        $this->assertEquals($this->owner->id, $adjustLog->causer_id);
        $this->assertEquals('Cycle count correction', $adjustLog->properties['notes'] ?? null);

        // 2. Transfer
        $resTransfer = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson('/api/v1/inventory/transfers', [
                'from_branch_id' => $this->branch1->id,
                'to_branch_id' => $this->branch2->id,
                'product_id' => $this->product->id,
                'quantity' => 5,
                'notes' => 'Replenish secondary branch',
            ]);
        $resTransfer->assertStatus(201);

        $transferLog = ActivityLog::where('log_name', 'inventory')
            ->where('event', 'updated')
            ->where('business_id', $this->business->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($transferLog);
        $this->assertEquals($this->branch1->id, $transferLog->properties['from_branch_id']);
        $this->assertEquals($this->branch2->id, $transferLog->properties['to_branch_id']);
        $this->assertEquals(5, $transferLog->properties['quantity']);
    }

    /**
     * AP. Sale Audit: Cancellation, Return and Price Override
     */
    public function test_sale_cancellation_return_and_price_override_audit()
    {
        // Setup inventory
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50,
            'minimum_stock' => 5,
        ]);

        // 1. Normal sale WITHOUT price override -> NO price_override audit
        $resNormalSale = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson('/api/v1/sales', [
                'branch_id' => $this->branch1->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 100, // exact catalog price
                    ]
                ],
                'paid_amount' => 100,
                'payment_method' => 'cash',
                'financial_account_id' => $this->account->id,
            ]);
        $resNormalSale->assertStatus(201);
        $normalSaleId = $resNormalSale->json('id');

        $overrideLogsNormal = ActivityLog::where('log_name', 'sale')
            ->where('event', 'updated')
            ->where('properties->action', 'price_override')
            ->where('subject_id', $normalSaleId)
            ->count();
        $this->assertEquals(0, $overrideLogsNormal, 'Normal sale with catalog price must not generate price_override audit');

        // 2. Sale WITH price override -> emits price_override audit
        $resOverrideSale = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson('/api/v1/sales', [
                'branch_id' => $this->branch1->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'unit_price' => 85, // overridden price
                    ]
                ],
                'paid_amount' => 170,
                'payment_method' => 'cash',
                'financial_account_id' => $this->account->id,
            ]);
        $resOverrideSale->assertStatus(201);
        $overrideSaleId = $resOverrideSale->json('id');

        $overrideLog = ActivityLog::where('log_name', 'sale')
            ->where('event', 'updated')
            ->where('properties->action', 'price_override')
            ->where('subject_id', $overrideSaleId)
            ->first();
        $this->assertNotNull($overrideLog);
        $this->assertEquals('price_override', $overrideLog->properties['action']);
        $this->assertEquals(100, $overrideLog->properties['catalog_price']);
        $this->assertEquals(85, $overrideLog->properties['override_price']);

        // 3. Sale Cancellation Audit (unpaid sale can be cancelled)
        $creditCustomer = Customer::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Credit Customer',
            'phone' => '03111222333',
        ]);

        $resUnpaidSale = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson('/api/v1/sales', [
                'branch_id' => $this->branch1->id,
                'customer_id' => $creditCustomer->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'unit_price' => 100,
                    ]
                ],
                'paid_amount' => 0,
                'payment_method' => 'credit',
            ]);
        $resUnpaidSale->assertStatus(201);
        $unpaidSaleId = $resUnpaidSale->json('id');

        $resCancel = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/sales/{$unpaidSaleId}/cancel", [
                'reason' => 'Customer changed mind',
            ]);
        $resCancel->assertStatus(200);

        $cancelLog = ActivityLog::where('log_name', 'sale')
            ->where('event', 'cancelled')
            ->where('subject_id', $unpaidSaleId)
            ->first();
        $this->assertNotNull($cancelLog);
        $this->assertEquals('Customer changed mind', $cancelLog->properties['reason']);

        // 4. Sale Return Audit on paid sale
        $saleItem = \App\Models\SaleItem::where('sale_id', $normalSaleId)->first();
        $resReturn = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/sales/{$normalSaleId}/returns", [
                'items' => [
                    [
                        'sale_item_id' => $saleItem->id,
                        'quantity' => 1,
                    ]
                ],
                'reason' => 'Defective item packaging',
            ]);
        $resReturn->assertStatus(201);

        $returnLog = ActivityLog::where('log_name', 'sale')
            ->where('event', 'returned')
            ->where('subject_id', $resReturn->json('id'))
            ->first();
        $this->assertNotNull($returnLog);
        $this->assertEquals('Defective item packaging', $returnLog->properties['reason']);
    }

    /**
     * AO. Purchase Cancellation Audit
     */
    public function test_purchase_cancellation_audit()
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Acme Supplies',
            'phone' => '1234567890',
        ]);

        $purchase = Purchase::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'supplier_id' => $supplier->id,
            'user_id' => $this->owner->id,
            'po_number' => 'PO-TEST-001',
            'date' => now()->toDateString(),
            'total' => 500,
            'subtotal' => 500,
            'status' => 'pending',
        ]);

        $resCancel = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/purchases/{$purchase->id}/cancel", [
                'reason' => 'Order duplicated by vendor',
            ]);
        $resCancel->assertStatus(200);

        $purchaseLog = ActivityLog::where('log_name', 'purchase')
            ->where('event', 'cancelled')
            ->where('subject_id', $purchase->id)
            ->first();
        $this->assertNotNull($purchaseLog);
        $this->assertEquals('Order duplicated by vendor', $purchaseLog->properties['reason']);
    }

    /**
     * AQ & AR. Customer and Supplier Payment Audits
     */
    public function test_customer_and_supplier_payment_audits()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'name' => 'John Doe',
            'phone' => '03001234567',
            'opening_balance' => 500,
            'current_balance' => 500,
        ]);

        // 1. Customer payment creation
        $resCustPayment = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/customers/{$customer->id}/payments", [
                'branch_id' => $this->branch1->id,
                'amount' => 200,
                'payment_method' => 'cash',
                'financial_account_id' => $this->account->id,
            ]);
        $resCustPayment->assertStatus(201);
        $custPaymentId = $resCustPayment->json('id');

        $custPayLog = ActivityLog::where('log_name', 'customer')
            ->where('event', 'created')
            ->where('properties->action', 'customer_payment')
            ->where('subject_id', $custPaymentId)
            ->first();
        $this->assertNotNull($custPayLog);
        $this->assertEquals('customer_payment', $custPayLog->properties['action']);
        $this->assertEquals(200, $custPayLog->properties['amount']);

        // 2. Customer payment reversal
        $resReverse = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/customer-payments/{$custPaymentId}/reverse", [
                'reason' => 'Cheque bounced',
            ]);
        $resReverse->assertStatus(200);

        $reverseLog = ActivityLog::where('log_name', 'customer')
            ->where('event', 'reversed')
            ->where('properties->action', 'customer_payment')
            ->where('subject_id', $custPaymentId)
            ->first();
        $this->assertNotNull($reverseLog);
        $this->assertEquals('customer_payment', $reverseLog->properties['action']);
        $this->assertEquals('Cheque bounced', $reverseLog->properties['reason']);

        // 3. Supplier payment creation
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Global Supplies',
            'balance' => 1000,
            'opening_balance' => 1000,
            'current_balance' => 1000,
        ]);

        $resSuppPay = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
                'branch_id' => $this->branch1->id,
                'amount' => 450,
                'payment_method' => 'cash',
                'financial_account_id' => $this->account->id,
            ]);
        $resSuppPay->assertStatus(201);
        $suppPayId = $resSuppPay->json('id');

        $suppPayLog = ActivityLog::where('log_name', 'supplier')
            ->where('event', 'created')
            ->where('properties->action', 'supplier_payment')
            ->where('subject_id', $suppPayId)
            ->first();
        $this->assertNotNull($suppPayLog);
        $this->assertEquals('supplier_payment', $suppPayLog->properties['action']);
        $this->assertEquals(450, $suppPayLog->properties['amount']);
    }

    /**
     * AS. Expense Create and Void Audit
     */
    public function test_expense_create_and_void_audit()
    {
        $category = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Office Utilities',
        ]);

        // 1. Create expense
        $resCreate = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson('/api/v1/expenses', [
                'branch_id' => $this->branch1->id,
                'category_id' => $category->id,
                'amount' => 350,
                'date' => now()->toDateString(),
                'payment_method' => 'cash',
                'financial_account_id' => $this->account->id,
                'description' => 'Electricity bill',
            ]);
        $resCreate->assertStatus(201);
        $expenseId = $resCreate->json('id');

        $createLog = ActivityLog::where('log_name', 'expense')
            ->where('event', 'created')
            ->where('subject_id', $expenseId)
            ->first();
        $this->assertNotNull($createLog);
        $this->assertEquals(350, $createLog->properties['amount']);

        // 2. Void expense
        $resVoid = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/expenses/{$expenseId}/void", [
                'reason' => 'Entered with wrong amount',
            ]);
        $resVoid->assertStatus(200);

        $voidLog = ActivityLog::where('log_name', 'expense')
            ->where('event', 'voided')
            ->where('subject_id', $expenseId)
            ->first();
        $this->assertNotNull($voidLog);
        $this->assertEquals('Entered with wrong amount', $voidLog->properties['reason']);
    }

    /**
     * AT. Financial Account Capital and Transfer Audit
     */
    public function test_financial_account_capital_and_transfer_audit()
    {
        $accountB = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'name' => 'Secondary Vault',
            'type' => 'bank',
            'current_balance' => 5000,
            'is_active' => true,
        ]);

        // 1. Capital In
        $resCapIn = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/financial-accounts/{$this->account->id}/capital-in", [
                'amount' => 2500,
                'description' => 'Owner equity injection',
            ]);
        $resCapIn->assertStatus(201);

        $capInLog = ActivityLog::where('log_name', 'financial')
            ->where('event', 'created')
            ->where('properties->action', 'capital_in')
            ->where('subject_id', $resCapIn->json('id'))
            ->first();
        $this->assertNotNull($capInLog);
        $this->assertEquals('capital_in', $capInLog->properties['action']);
        $this->assertEquals(2500, $capInLog->properties['amount']);

        // 2. Capital Out
        $resCapOut = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson("/api/v1/financial-accounts/{$this->account->id}/capital-out", [
                'amount' => 500,
                'description' => 'Owner equity withdrawal',
            ]);
        $resCapOut->assertStatus(201);

        $capOutLog = ActivityLog::where('log_name', 'financial')
            ->where('event', 'created')
            ->where('properties->action', 'capital_out')
            ->where('subject_id', $resCapOut->json('id'))
            ->first();
        $this->assertNotNull($capOutLog);
        $this->assertEquals('capital_out', $capOutLog->properties['action']);
        $this->assertEquals(500, $capOutLog->properties['amount']);

        // 3. Transfer between accounts
        $resTransfer = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $this->account->id,
                'destination_account_id' => $accountB->id,
                'amount' => 1000,
                'date' => now()->toDateString(),
                'notes' => 'Branch float transfer',
            ]);
        $resTransfer->assertStatus(201);

        $transferLog = ActivityLog::where('log_name', 'financial')
            ->where('event', 'created')
            ->where('properties->action', 'account_transfer')
            ->latest('id')
            ->first();
        $this->assertNotNull($transferLog);
        $this->assertEquals('account_transfer', $transferLog->properties['action']);
        $this->assertEquals(1000, $transferLog->properties['amount']);
        $this->assertEquals($this->account->id, $transferLog->properties['source_account_id']);
        $this->assertEquals($accountB->id, $transferLog->properties['destination_account_id']);
    }

    /**
     * AV. Product Governance Mutation Audit
     */
    public function test_product_governance_mutation_audit()
    {
        $resUpdate = $this->actingAs($this->owner)
            ->withHeader('X-Business-ID', $this->business->id)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => $this->product->name,
                'cost_price' => 65,
                'selling_price' => 120,
            ]);
        $resUpdate->assertStatus(200);

        $prodLog = ActivityLog::where('log_name', 'governance')
            ->where('event', 'updated')
            ->where('subject_id', $this->product->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($prodLog);
        $this->assertEquals(50, $prodLog->properties['old']['cost_price']);
        $this->assertEquals(65, $prodLog->properties['new']['cost_price']);
        $this->assertEquals(100, $prodLog->properties['old']['selling_price']);
        $this->assertEquals(120, $prodLog->properties['new']['selling_price']);
    }

    /**
     * AW. Failure Atomicity: Rolled-back Transaction Does NOT Persist Audit Event
     */
    public function test_failure_atomicity_prevents_audit_persistence_on_rollback()
    {
        $initialAuditCount = ActivityLog::count();

        // Simulate an atomic operation that logs inside a transaction and then throws an exception
        try {
            DB::transaction(function () {
                app(AuditService::class)->log(
                    logName: 'inventory',
                    event: 'adjusted',
                    description: 'This transaction will fail',
                    businessId: $this->business->id,
                    branchId: $this->branch1->id,
                    causer: $this->owner
                );

                // Simulate a severe transactional failure
                throw new \RuntimeException('Simulated transactional failure during stock ledger processing');
            });
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        $afterAuditCount = ActivityLog::count();
        $this->assertEquals($initialAuditCount, $afterAuditCount, 'Rolled-back transaction must not persist audit log');
    }
}
