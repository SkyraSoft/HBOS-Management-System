<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\CustomerPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerPaymentIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB;
    protected User $userA;
    protected User $userB;
    protected Customer $customerA1;
    protected Customer $customerA2;
    protected Customer $customerB;
    protected CustomerPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Business A
        $this->businessA = Business::create(['name' => 'Business A']);
        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A1', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A2', 'is_primary' => false]);

        setPermissionsTeamId($this->businessA->id);
        $this->userA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA1->id]);
        $this->userA->businesses()->attach($this->businessA->id);
        $this->userA->assignRole('Business Owner');

        \App\Models\FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Drawer A1',
            'type' => 'cash',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => true,
            'status' => 'active',
        ]);
        \App\Models\FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'name' => 'Drawer A2',
            'type' => 'cash',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => true,
            'status' => 'active',
        ]);
        \App\Models\FinancialAccount::create([
            'business_id' => $this->businessA->id,
            'branch_id' => null,
            'name' => 'Bank A',
            'type' => 'bank',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => false,
            'status' => 'active',
        ]);

        $this->customerA1 = Customer::create(['business_id' => $this->businessA->id, 'name' => 'Customer A1', 'opening_balance' => 1000]);
        $this->customerA2 = Customer::create(['business_id' => $this->businessA->id, 'name' => 'Customer A2', 'opening_balance' => 1000]);
        app(CustomerAccountService::class)->recalculateBalance($this->customerA1);
        app(CustomerAccountService::class)->recalculateBalance($this->customerA2);

        // Business B
        $this->businessB = Business::create(['name' => 'Business B']);
        $this->branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);

        setPermissionsTeamId($this->businessB->id);
        $this->userB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB->id]);
        $this->userB->businesses()->attach($this->businessB->id);
        $this->userB->assignRole('Business Owner');

        \App\Models\FinancialAccount::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Drawer B',
            'type' => 'cash',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => true,
            'status' => 'active',
        ]);
        \App\Models\FinancialAccount::create([
            'business_id' => $this->businessB->id,
            'branch_id' => null,
            'name' => 'Bank B',
            'type' => 'bank',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => false,
            'status' => 'active',
        ]);

        $this->customerB = Customer::create(['business_id' => $this->businessB->id, 'name' => 'Customer B', 'opening_balance' => 1000]);
        app(CustomerAccountService::class)->recalculateBalance($this->customerB);

        $this->paymentService = app(CustomerPaymentService::class);
    }

    public function test_identical_retry_returns_original_payment_without_duplicate_balance_deduction()
    {
        $payload = [
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $this->customerA1->id,
            'amount' => 300.00,
            'payment_method' => 'cash',
            'date' => '2026-09-19',
            'idempotency_key' => 'idemp-cp-test-001',
        ];

        // 1. Initial request
        $payment1 = $this->paymentService->recordPayment($payload, $this->userA);
        $this->assertEquals(700.00, $this->customerA1->fresh()->balance);
        $this->assertEquals(1, CustomerPayment::where('business_id', $this->businessA->id)->count());

        // 2. Identical retry
        $payment2 = $this->paymentService->recordPayment($payload, $this->userA);

        $this->assertEquals($payment1->id, $payment2->id);
        $this->assertEquals($payment1->payment_number, $payment2->payment_number);
        // Balance remains 700.00 (not deducted again to 400.00)
        $this->assertEquals(700.00, $this->customerA1->fresh()->balance);
        $this->assertEquals(1, CustomerPayment::where('business_id', $this->businessA->id)->count());
    }

    public function test_conflicting_payload_reuse_is_rejected()
    {
        $payload = [
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $this->customerA1->id,
            'amount' => 200.00,
            'payment_method' => 'cash',
            'date' => '2026-09-19',
            'idempotency_key' => 'idemp-cp-test-conflict',
        ];

        $this->paymentService->recordPayment($payload, $this->userA);

        // 1. Conflicting amount
        $conflictAmount = $payload;
        $conflictAmount['amount'] = 300.00;
        try {
            $this->paymentService->recordPayment($conflictAmount, $this->userA);
            $this->fail('Expected ValidationException for conflicting amount');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('idempotency_key', $e->errors());
        }

        // 2. Conflicting customer
        $conflictCust = $payload;
        $conflictCust['customer_id'] = $this->customerA2->id;
        try {
            $this->paymentService->recordPayment($conflictCust, $this->userA);
            $this->fail('Expected ValidationException for conflicting customer');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('idempotency_key', $e->errors());
        }

        // 3. Conflicting branch
        $conflictBranch = $payload;
        $conflictBranch['branch_id'] = $this->branchA2->id;
        try {
            $this->paymentService->recordPayment($conflictBranch, $this->userA);
            $this->fail('Expected ValidationException for conflicting branch');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('idempotency_key', $e->errors());
        }

        // 4. Conflicting method
        $conflictMethod = $payload;
        $conflictMethod['payment_method'] = 'bank';
        try {
            $this->paymentService->recordPayment($conflictMethod, $this->userA);
            $this->fail('Expected ValidationException for conflicting method');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('idempotency_key', $e->errors());
        }

        // 5. Conflicting date
        $conflictDate = $payload;
        $conflictDate['date'] = '2026-09-20';
        try {
            $this->paymentService->recordPayment($conflictDate, $this->userA);
            $this->fail('Expected ValidationException for conflicting date');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('idempotency_key', $e->errors());
        }

        // 6. Conflicting sale_id
        $product = \App\Models\Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Product For Sale Test',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 100,
            'unit' => 'pcs',
        ]);
        \App\Models\BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 100,
        ]);
        $sale = \App\Models\Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->userA->id,
            'customer_id' => $this->customerA1->id,
            'invoice_number' => 'INV-IDEMP-SALE-1',
            'date' => date('Y-m-d'),
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        $conflictSale = $payload;
        $conflictSale['sale_id'] = $sale->id;
        try {
            $this->paymentService->recordPayment($conflictSale, $this->userA);
            $this->fail('Expected ValidationException for conflicting sale_id');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('idempotency_key', $e->errors());
        }
    }

    public function test_same_idempotency_key_in_different_business_is_permitted()
    {
        $key = 'cross-biz-shared-idemp-key';

        $pA = $this->paymentService->recordPayment([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $this->customerA1->id,
            'amount' => 150.00,
            'idempotency_key' => $key,
        ], $this->userA);

        $pB = $this->paymentService->recordPayment([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'customer_id' => $this->customerB->id,
            'amount' => 250.00,
            'idempotency_key' => $key,
        ], $this->userB);

        $this->assertNotEquals($pA->id, $pB->id);
        $this->assertEquals($this->businessA->id, $pA->business_id);
        $this->assertEquals($this->businessB->id, $pB->business_id);
    }
}
