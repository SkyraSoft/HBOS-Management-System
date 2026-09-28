<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\CustomerPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected Product $product;
    protected Customer $customer;
    protected CustomerAccountService $accountService;
    protected CustomerPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Ledger Test Biz']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Product 1',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 100,
            'unit' => 'pcs',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 100,
        ]);

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Ledger Customer',
            'opening_balance' => 100.00,
        ]);

        $this->accountService = app(CustomerAccountService::class);
        $this->paymentService = app(CustomerPaymentService::class);
        $this->accountService->recalculateBalance($this->customer);
    }

    public function test_customer_ledger_exact_sequence_and_running_balance_parity()
    {
        // 1. Initial State: Opening Balance 100.00
        $this->assertEquals(100.00, $this->accountService->calculateRawBalance($this->customer));

        // 2. Credit Sale: total 1,000, paid 400, due 600 -> raw = 100 + 600 = 700.00
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-LEDGER-001',
            'date' => date('Y-m-d'),
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000,
            'paid_amount' => 400,
            'due_amount' => 600,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100,
            'cost_price' => 50,
            'discount' => 0,
            'total' => 1000,
            'returned_quantity' => 0,
        ]);
        $this->accountService->recalculateBalance($this->customer);
        $this->assertEquals(700.00, $this->accountService->calculateRawBalance($this->customer));

        // 3. Customer Payment 200.00 -> raw = 700 - 200 = 500.00
        $payment = $this->paymentService->recordPayment([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 200.00,
            'payment_method' => 'cash',
        ], $this->owner);
        $this->assertEquals(500.00, $this->accountService->calculateRawBalance($this->customer));

        // 4. Sale Return reducing receivable by 100.00 (refund 100) -> effective due becomes 500.00 -> raw = 100 + 500 - 200 = 400.00
        SaleReturn::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'return_number' => 'RET-LEDGER-001',
            'refund_amount' => 100.00,
        ]);
        $this->accountService->recalculateBalance($this->customer);
        $this->assertEquals(400.00, $this->accountService->calculateRawBalance($this->customer));

        // 5. Void payment 200.00 -> raw = 100 + 500 - 0 = 600.00
        $this->paymentService->reversePayment($payment, $this->owner, 'Voiding payment in ledger test');
        $this->assertEquals(600.00, $this->accountService->calculateRawBalance($this->customer));

        // Verify ledger endpoint
        $ledger = $this->accountService->getAccountLedger($this->customer);
        $this->assertNotEmpty($ledger);

        // Final entry running balance MUST equal calculateRawBalance()
        $lastEntry = end($ledger);
        $this->assertEquals(600.00, $lastEntry['running_balance']);
        $this->assertEquals($this->accountService->calculateRawBalance($this->customer), $lastEntry['running_balance']);
    }
}
