<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\Customer;
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

class CustomerAccountReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected Product $product;
    protected CustomerAccountService $accountService;
    protected CustomerPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Recon Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Test Item',
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

        $this->accountService = app(CustomerAccountService::class);
        $this->paymentService = app(CustomerPaymentService::class);
    }

    public function test_opening_balance_scenarios_and_invariant_preservation()
    {
        // 1. Legacy balance with zero authoritative sales
        $c1 = Customer::create(['business_id' => $this->business->id, 'name' => 'C1', 'opening_balance' => 0]);
        $this->assertEquals(0.00, $this->accountService->calculateRawBalance($c1));
        $this->assertEquals(0.00, $this->accountService->calculateOutstanding($c1));
        $this->assertEquals(0.00, $this->accountService->calculateCredit($c1));

        // 2. Legacy positive balance
        $c2 = Customer::create(['business_id' => $this->business->id, 'name' => 'C2', 'opening_balance' => 1500.00]);
        $this->assertEquals(1500.00, $this->accountService->calculateRawBalance($c2));
        $this->assertEquals(1500.00, $this->accountService->calculateOutstanding($c2));
        $this->assertEquals(0.00, $this->accountService->calculateCredit($c2));

        // 3. Legacy negative balance (customer credit)
        $c3 = Customer::create(['business_id' => $this->business->id, 'name' => 'C3', 'opening_balance' => -500.00]);
        $this->assertEquals(-500.00, $this->accountService->calculateRawBalance($c3));
        $this->assertEquals(0.00, $this->accountService->calculateOutstanding($c3));
        $this->assertEquals(500.00, $this->accountService->calculateCredit($c3));

        // 4. Existing credit Sale due
        $c4 = Customer::create(['business_id' => $this->business->id, 'name' => 'C4', 'opening_balance' => 200.00]);
        $sale4 = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'customer_id' => $c4->id,
            'invoice_number' => 'INV-C4-1',
            'date' => date('Y-m-d'),
            'subtotal' => 800,
            'discount' => 0,
            'tax' => 0,
            'total' => 800,
            'paid_amount' => 300,
            'due_amount' => 500,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        $this->assertEquals(700.00, $this->accountService->calculateRawBalance($c4)); // 200 + 500 = 700

        // 5. Cancelled Sale does not contribute
        $sale4Cancel = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'customer_id' => $c4->id,
            'invoice_number' => 'INV-C4-2',
            'date' => date('Y-m-d'),
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
            'payment_method' => 'Cash',
            'status' => 'cancelled',
        ]);
        $this->assertEquals(700.00, $this->accountService->calculateRawBalance($c4));

        // 6. Sale with partial return reduces effective due
        $saleRet = SaleReturn::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'sale_id' => $sale4->id,
            'user_id' => $this->owner->id,
            'return_number' => 'RET-C4-1',
            'refund_amount' => 200.00,
        ]);
        // effectiveNet = 800 - 200 = 600; effectiveDue = 600 - 300 = 300; raw = 200 (opening) + 300 = 500
        $this->assertEquals(500.00, $this->accountService->calculateRawBalance($c4));
    }

    public function test_negative_legacy_credit_offsets_future_credit_sales_and_blocks_overpayment()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Credit Customer',
            'opening_balance' => -500.00,
        ]);

        $this->assertEquals(-500.00, $this->accountService->calculateRawBalance($customer));
        $this->assertEquals(0.00, $this->accountService->calculateOutstanding($customer));
        $this->assertEquals(500.00, $this->accountService->calculateCredit($customer));

        // 1. Payment attempt when outstanding is 0 is rejected
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->paymentService->recordPayment([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'amount' => 100.00,
        ], $this->owner);
    }

    public function test_new_customer_creation_rejects_negative_opening_balance()
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/customers', [
                'name' => 'Invalid Customer',
                'opening_balance' => -200.00,
            ]);

        $response->assertStatus(422);
    }
}
