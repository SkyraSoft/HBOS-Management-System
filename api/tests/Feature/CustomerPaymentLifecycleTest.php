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

class CustomerPaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected Customer $customer;
    protected CustomerPaymentService $paymentService;
    protected CustomerAccountService $accountService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Lifecycle Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'HQ Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        \App\Models\FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'HQ Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => true,
            'status' => 'active',
        ]);

        \App\Models\FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => null,
            'name' => 'Main Bank Account',
            'type' => 'bank',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => false,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Acme Corp',
            'opening_balance' => 1000.00,
        ]);

        $this->paymentService = app(CustomerPaymentService::class);
        $this->accountService = app(CustomerAccountService::class);
        $this->accountService->recalculateBalance($this->customer);
    }

    public function test_payment_creation_and_reversal_lifecycle()
    {
        // 1. Record payment 400.00
        $payment = $this->paymentService->recordPayment([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'amount' => 400.00,
            'payment_method' => 'bank_transfer',
            'notes' => 'Invoice settlement',
        ], $this->owner);

        $this->assertEquals('posted', $payment->status);
        $this->assertStringStartsWith('CP-', $payment->payment_number);
        $this->assertEquals(600.00, $this->customer->fresh()->balance);

        // 2. Reverse payment
        $reversed = $this->paymentService->reversePayment($payment, $this->owner, 'Client bounced check');

        $this->assertEquals('voided', $reversed->status);
        $this->assertNotNull($reversed->reversed_at);
        $this->assertEquals($this->owner->id, $reversed->reversed_by);
        $this->assertEquals('Client bounced check', $reversed->reversal_reason);

        // Balance restored back to 1000.00
        $this->assertEquals(1000.00, $this->customer->fresh()->balance);
        $this->assertEquals(1000.00, $this->accountService->calculateRawBalance($this->customer));

        // 3. Double-reversal attempt is rejected
        $this->expectException(ValidationException::class);
        $this->paymentService->reversePayment($reversed, $this->owner, 'Second reversal attempt');
    }

    public function test_concurrency_sequential_overpayment_protection()
    {
        // Customer outstanding = 100.00 (set opening balance to 100.00)
        $c = Customer::create(['business_id' => $this->business->id, 'name' => 'Tight Balance', 'opening_balance' => 100.00]);
        $this->accountService->recalculateBalance($c);

        // First payment of 80.00 succeeds
        $this->paymentService->recordPayment([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $c->id,
            'amount' => 80.00,
        ], $this->owner);

        $this->assertEquals(20.00, $c->fresh()->balance);

        // Second payment of 30.00 must see recalculated balance (20.00) and reject
        try {
            $this->paymentService->recordPayment([
                'business_id' => $this->business->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $c->id,
                'amount' => 30.00,
            ], $this->owner);
            $this->fail('Expected ValidationException for overpayment');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Final balance remains 20.00
        $this->assertEquals(20.00, $c->fresh()->balance);
    }
}
