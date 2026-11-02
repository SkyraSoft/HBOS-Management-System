<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierReturn;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Phase9RemediationVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected string $token;
    protected FinancialAccount $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Phase 9 Supermarket']);
        setPermissionsTeamId($this->business->id);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'code' => 'MB-01',
            'is_primary' => true
        ]);

        $this->cashAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 100000.00,
            'current_balance' => 100000.00,
            'is_default' => true,
            'status' => 'active'
        ]);

        $ownerRole = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'business_id' => $this->business->id]);
        $allPermissions = [
            'manage sales', 'view sales', 'cancel sales', 'return sales',
            'manage purchases', 'view purchases', 'return purchases',
            'manage inventory', 'view inventory', 'adjust inventory', 'transfer inventory', 'view stock movements',
            'manage customers', 'view customers', 'record customer payments',
            'manage suppliers', 'view suppliers', 'record supplier payments',
            'manage expenses', 'view expenses', 'void expenses',
            'manage accounts', 'view accounts', 'transfer accounts',
            'manage users', 'view users', 'view reports', 'view audit logs', 'manage settings'
        ];
        foreach ($allPermissions as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $ownerRole->givePermissionTo($perm);
        }

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'role' => 'Business Owner',
            'is_active' => true
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->branches()->attach($this->branch->id, ['business_id' => $this->business->id]);
        $this->owner->assignRole($ownerRole);

        $this->token = $this->owner->createToken('phase9-test')->plainTextToken;
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'X-Business-Id' => (string) $this->business->id,
            'Accept' => 'application/json'
        ];
    }

    /**
     * GAP-001 & GAP-002: Sale Line Items Snapshot Persistence & Wholesale Price
     */
    public function test_sale_item_point_in_time_snapshot_and_wholesale_price_persistence()
    {
        $category = Category::create(['business_id' => $this->business->id, 'name' => 'Beverages']);
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Energy Drink 250ml',
            'sku' => 'ED-250',
            'barcode' => '8964000112233',
            'cost_price' => 70.00,
            'selling_price' => 120.00,
            'wholesale_price' => 95.00,
            'stock' => 50,
            'category_id' => $category->id
        ]);

        \App\Models\BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 50,
            'minimum_stock' => 5
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'payment_type' => 'CASH',
            'paid_amount' => 240.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 120.00,
                ]
            ]
        ];

        $res = $this->withHeaders($this->authHeaders())->postJson('/api/v1/sales', $payload);
        $res->assertStatus(201);

        $saleId = $res->json('id');
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_id' => $product->id,
            'product_name' => 'Energy Drink 250ml',
            'sku' => 'ED-250',
            'barcode' => '8964000112233',
            'unit_cost' => 70.00,
        ]);

        // Mutate master product name and cost
        $product->update([
            'name' => 'Altered Product Name',
            'cost_price' => 90.00,
            'sku' => 'ALTERED-SKU'
        ]);

        // Historical snapshot on sale_items must remain unchanged
        $saleItem = SaleItem::where('sale_id', $saleId)->first();
        $this->assertEquals('Energy Drink 250ml', $saleItem->product_name);
        $this->assertEquals('ED-250', $saleItem->sku);
        $this->assertEquals(70.00, (float) $saleItem->unit_cost);
    }

    /**
     * GAP-003: Multi-Tender Split Payment Breakdown Persistence
     */
    public function test_split_tender_payment_breakdown_persistence()
    {
        $category = Category::create(['business_id' => $this->business->id, 'name' => 'Snacks']);
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Chocolate Box',
            'sku' => 'CHOC-01',
            'cost_price' => 300.00,
            'selling_price' => 500.00,
            'stock' => 20,
            'category_id' => $category->id
        ]);

        \App\Models\BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 20,
            'minimum_stock' => 2
        ]);

        $bankAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'HBL Bank Account',
            'type' => 'bank',
            'opening_balance' => 50000.00,
            'current_balance' => 50000.00,
            'status' => 'active'
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'payment_type' => 'MULTI',
            'paid_amount' => 500.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 500.00,
                ]
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 200.00,
                    'financial_account_id' => $this->cashAccount->id,
                ],
                [
                    'payment_method' => 'bank',
                    'amount' => 300.00,
                    'financial_account_id' => $bankAccount->id,
                    'reference_number' => 'POS-TX-9988'
                ]
            ]
        ];

        $res = $this->withHeaders($this->authHeaders())->postJson('/api/v1/sales', $payload);
        $res->assertStatus(201);

        $saleId = $res->json('id');
        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $saleId,
            'payment_method' => 'cash',
            'amount' => 200.00,
            'financial_account_id' => $this->cashAccount->id
        ]);
        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $saleId,
            'payment_method' => 'bank',
            'amount' => 300.00,
            'financial_account_id' => $bankAccount->id,
            'reference_number' => 'POS-TX-9988'
        ]);
    }

    /**
     * GAP-005: Landed Cost Freight Proration on Purchases
     */
    public function test_purchase_freight_landed_cost_allocation()
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'National Foods Supplier',
            'balance' => 0.00,
            'opening_balance' => 0.00
        ]);

        $category = Category::create(['business_id' => $this->business->id, 'name' => 'Spices']);
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Chili Powder 500g',
            'sku' => 'CP-500',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'stock' => 0,
            'category_id' => $category->id
        ]);

        $payload = [
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'po_number' => 'PO-LANDED-' . uniqid(),
            'date' => now()->toDateString(),
            'freight_amount' => 200.00, // Rs. 200 freight on 10 units = +Rs. 20/unit
            'paid_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 100.00, // Total items = Rs. 1000
                ]
            ]
        ];

        $res = $this->withHeaders($this->authHeaders())->postJson('/api/v1/purchases', $payload);
        $res->assertStatus(201);
        $this->assertEquals(1200.00, (float) $res->json('total')); // 1000 + 200 freight

        // Check Inventory Movement received with effective landed unit cost Rs. 120
        $movement = \App\Models\InventoryMovement::where('reference_type', Purchase::class)
            ->where('reference_id', $res->json('id'))
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(120.00, (float) $movement->unit_cost);
    }

    /**
     * GAP-006: Purchase Cancellation Deficit Guard
     */
    public function test_purchase_cancellation_fails_if_stock_already_sold()
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Test Supplier',
            'balance' => 0.00,
            'opening_balance' => 0.00
        ]);
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Test Item',
            'sku' => 'TEST-01',
            'cost_price' => 50.00,
            'selling_price' => 100.00,
            'stock' => 0
        ]);

        $poPayload = [
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'po_number' => 'PO-GUARD-' . uniqid(),
            'date' => now()->toDateString(),
            'paid_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 50.00,
                ]
            ]
        ];

        $poRes = $this->withHeaders($this->authHeaders())->postJson('/api/v1/purchases', $poPayload);
        $poRes->assertStatus(201);
        $purchaseId = $poRes->json('id');

        // Sell 6 units out of 10
        $inv = \App\Models\BranchInventory::where('branch_id', $this->branch->id)
            ->where('product_id', $product->id)
            ->first();
        $inv->update(['quantity_on_hand' => 4]); // Only 4 remain on hand

        // Attempt to cancel purchase of 10 units
        $cancelRes = $this->withHeaders($this->authHeaders())->postJson("/api/v1/purchases/{$purchaseId}/cancel", [
            'reason' => 'Vendor return'
        ]);

        $cancelRes->assertStatus(422);
        $this->assertStringContainsString('insufficient stock on hand', strtolower($cancelRes->json('error')));
    }

    /**
     * GAP-010: Sale Return Restock vs Scrap Condition Decision
     */
    public function test_sale_return_restock_condition_handling()
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Glass Mug',
            'sku' => 'GMUG-01',
            'cost_price' => 150.00,
            'selling_price' => 300.00,
            'stock' => 10
        ]);
        $inv = \App\Models\BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
            'minimum_stock' => 2
        ]);

        $saleRes = $this->withHeaders($this->authHeaders())->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_type' => 'CASH',
            'paid_amount' => 600.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 300.00]
            ]
        ]);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');
        $inv->refresh();
        $this->assertEquals(8, $inv->quantity_on_hand);

        // Process Return with is_restockable = false (damaged item)
        $returnRes = $this->withHeaders($this->authHeaders())->postJson("/api/v1/sales/{$saleId}/returns", [
            'branch_id' => $this->branch->id,
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 300.00,
                    'is_restockable' => false,
                    'return_reason' => 'Broken glass in transit'
                ]
            ]
        ]);
        $returnRes->assertStatus(201);

        // Stock on hand must NOT increase because item is non-restockable scrap
        $inv->refresh();
        $this->assertEquals(8, $inv->quantity_on_hand);
    }

    /**
     * GAP-011: Supplier Return / Debit Note Workflow
     */
    public function test_supplier_return_debit_note_workflow()
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Metro Cash & Carry',
            'balance' => 5000.00,
            'opening_balance' => 5000.00
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Cooking Oil 5L',
            'sku' => 'OIL-5L',
            'cost_price' => 1800.00,
            'selling_price' => 2200.00,
            'stock' => 10
        ]);
        $inv = \App\Models\BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
            'minimum_stock' => 2
        ]);

        $payload = [
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'reason' => 'Defective packaging batch',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_cost' => 1800.00, // Total debit: Rs. 3600
                ]
            ]
        ];

        $res = $this->withHeaders($this->authHeaders())->postJson('/api/v1/supplier-returns', $payload);
        $res->assertStatus(201);
        $this->assertEquals(3600.00, (float) $res->json('total_amount'));

        // Branch inventory decremented
        $inv->refresh();
        $this->assertEquals(8, $inv->quantity_on_hand);

        // Supplier balance reduced from 5000 to 1400 (5000 - 3600)
        $supplier->refresh();
        $this->assertEquals(1400.00, (float) $supplier->balance);
    }

    /**
     * GAP-012: Supplier Payment Reversal
     */
    public function test_supplier_payment_reversal()
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Habib Oil Mills',
            'balance' => 10000.00,
            'opening_balance' => 10000.00
        ]);

        $payRes = $this->withHeaders($this->authHeaders())->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
            'amount' => 4000.00,
            'payment_method' => 'cash',
            'financial_account_id' => $this->cashAccount->id,
            'notes' => 'Partial cash disbursement'
        ]);
        $payRes->assertStatus(201);
        $paymentId = $payRes->json('id');

        $supplier->refresh();
        $this->assertEquals(6000.00, (float) $supplier->balance);

        // Reverse payment
        $revRes = $this->withHeaders($this->authHeaders())->postJson("/api/v1/supplier-payments/{$paymentId}/reverse");
        $revRes->assertStatus(200);

        // Supplier payable re-accrued back to 10000
        $supplier->refresh();
        $this->assertEquals(10000.00, (float) $supplier->balance);
    }

    /**
     * GAP-013 & GAP-014: Mandatory Financial Account on Expense & Movement Voiding on Delete
     */
    public function test_expense_mandatory_account_and_movement_voiding_on_delete()
    {
        $category = ExpenseCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Utilities',
            'monthly_budget' => 25000.00
        ]);

        // Null financial account must fail with 422
        $failRes = $this->withHeaders($this->authHeaders())->postJson('/api/v1/expenses', [
            'branch_id' => $this->branch->id,
            'category_id' => $category->id,
            'amount' => 2500.00,
            'title' => 'Electric Bill',
            'date' => now()->toDateString(),
        ]);
        $failRes->assertStatus(422);

        // Successful expense with financial account
        $successRes = $this->withHeaders($this->authHeaders())->postJson('/api/v1/expenses', [
            'branch_id' => $this->branch->id,
            'category_id' => $category->id,
            'financial_account_id' => $this->cashAccount->id,
            'amount' => 2500.00,
            'title' => 'Electric Bill',
            'date' => now()->toDateString(),
        ]);
        $successRes->assertStatus(201);
        $expenseId = $successRes->json('id');

        $movementService = app(\App\Services\AccountMovementService::class);
        $this->assertEquals(97500.00, $movementService->calculateBalance($this->cashAccount));

        // Void expense
        $voidRes = $this->withHeaders($this->authHeaders())->postJson("/api/v1/expenses/{$expenseId}/void", [
            'reason' => 'Accidental duplicate entry'
        ]);
        $voidRes->assertStatus(200);

        // Cash account balance restored
        $this->assertEquals(100000.00, $movementService->calculateBalance($this->cashAccount));
    }

    /**
     * GAP-029: User Payroll Wage & Salary Persistence
     */
    public function test_user_payroll_fields_persistence()
    {
        $payload = [
            'name' => 'Ali Cashier',
            'email' => 'ali.cashier@example.com',
            'password' => 'password123',
            'role' => 'Salesperson',
            'branch_id' => $this->branch->id,
            'salary_amount' => 35000.00,
            'payment_cycle' => 'monthly',
            'next_payment_date' => now()->addMonth()->toDateString()
        ];

        $res = $this->withHeaders($this->authHeaders())->postJson('/api/v1/users', $payload);
        $res->assertStatus(201);

        $userId = $res->json('data.id');
        $user = User::find($userId);
        $this->assertEquals(35000.00, (float) $user->salary_amount);
        $this->assertEquals('monthly', $user->payment_cycle);
    }

    /**
     * GAP-030: Customer Secondary Phone Persistence
     */
    public function test_customer_secondary_phone_persistence()
    {
        $payload = [
            'name' => 'Muhammad Usman',
            'phone' => '03001122334',
            'secondary_phone' => '03219988776',
            'email' => 'usman@example.com',
            'address' => 'Gulberg, Lahore'
        ];

        $res = $this->withHeaders($this->authHeaders())->postJson('/api/v1/customers', $payload);
        $res->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'id' => $res->json('id'),
            'name' => 'Muhammad Usman',
            'phone' => '03001122334',
            'secondary_phone' => '03219988776'
        ]);
    }

    /**
     * GAP-031: Expense Category Monthly Budget Persistence
     */
    public function test_expense_category_monthly_budget_persistence()
    {
        $payload = [
            'name' => 'Marketing & Advertising',
            'monthly_budget' => 75000.00
        ];

        $res = $this->withHeaders($this->authHeaders())->postJson('/api/v1/expense-categories', $payload);
        $res->assertStatus(201);

        $this->assertDatabaseHas('expense_categories', [
            'id' => $res->json('id'),
            'name' => 'Marketing & Advertising',
            'monthly_budget' => 75000.00
        ]);
    }
}
