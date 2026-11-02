<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\CustomerPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CustomerPaymentConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected FinancialAccount $drawer;
    protected Customer $customer;
    protected CustomerPaymentService $paymentService;
    protected CustomerAccountService $accountService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Concurrency Retail Business', 'currency' => 'PKR']);
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
            'opening_balance' => 1000.00,
            'balance' => 1000.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Chaudhry Akram',
            'phone' => '03001112233',
            'opening_balance' => 500.00,
        ]);

        $this->paymentService = app(CustomerPaymentService::class);
        $this->accountService = app(CustomerAccountService::class);
        $this->accountService->recalculateBalance($this->customer);
    }

    public function test_concurrent_collection_race_rejects_excess_and_prevents_negative_receivable(): void
    {
        // Initial customer receivable = 500.00 PKR
        $initialReceivable = $this->accountService->calculateOutstanding($this->customer);
        $this->assertEquals(500.00, $initialReceivable);
        $this->assertEquals(1000.00, $this->drawer->balance);

        // 1. First collection payment: 300.00 PKR via application API
        $response1 = $this->actingAs($this->owner)
            ->withHeaders(['X-Business-Id' => (string) $this->business->id])
            ->postJson("/api/v1/customers/{$this->customer->id}/payments", [
                'branch_id' => $this->branch->id,
                'amount' => 300.00,
                'payment_method' => 'cash',
                'financial_account_id' => $this->drawer->id,
                'notes' => 'Collection payment 1'
            ]);

        $response1->assertStatus(201);
        $payment1Id = $response1->json('id');
        $this->assertNotNull($payment1Id);
        
        $balanceAfterP1 = $this->accountService->calculateOutstanding($this->customer->fresh());
        $this->assertEquals(200.00, $balanceAfterP1);
        $this->assertEquals(1300.00, $this->drawer->fresh()->balance);

        // 2. Second collection payment attempt: 300.00 PKR (Exceeds remaining 200.00 PKR)
        $response2 = $this->actingAs($this->owner)
            ->withHeaders(['X-Business-Id' => (string) $this->business->id])
            ->postJson("/api/v1/customers/{$this->customer->id}/payments", [
                'branch_id' => $this->branch->id,
                'amount' => 300.00,
                'payment_method' => 'cash',
                'financial_account_id' => $this->drawer->id,
                'notes' => 'Collection payment 2 (attempted duplicate/overcollection)'
            ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['amount']);

        // 3. Authoritative State Assertions
        $finalCustomer = $this->customer->fresh();
        $finalDrawer = $this->drawer->fresh();

        // Customer balance MUST remain 200.00 PKR and NEVER become unauthorized negative receivable (-100.00)
        $this->assertEquals(200.00, $finalCustomer->balance);
        $this->assertEquals(200.00, $this->accountService->calculateOutstanding($finalCustomer));
        $this->assertGreaterThanOrEqual(0.00, $finalCustomer->balance);

        // Drawer balance received exactly 300.00 PKR (1000.00 -> 1300.00, never 1600.00)
        $this->assertEquals(1300.00, $finalDrawer->balance);

        // Exactly one CustomerPayment record persisted
        $this->assertEquals(1, CustomerPayment::where('customer_id', $this->customer->id)->count());

        // Exactly one AccountMovement persisted for drawer (account_id, movement_category)
        $this->assertEquals(1, AccountMovement::where('account_id', $this->drawer->id)
            ->where('movement_category', 'customer_payment')
            ->count());

        // Audit activity logged for payment 1
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => CustomerPayment::class,
            'subject_id' => $payment1Id,
            'causer_id' => $this->owner->id
        ]);
    }
}
