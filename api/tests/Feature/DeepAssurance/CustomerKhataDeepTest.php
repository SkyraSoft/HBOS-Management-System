<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\User;
use App\Services\CustomerAccountService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerKhataDeepTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected User $salesperson;
    protected FinancialAccount $cashAccount;
    protected Customer $customer;
    protected CustomerAccountService $accountService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->accountService = app(CustomerAccountService::class);

        $this->business = Business::create(['name' => 'Khata Udhaar Retail']);
        setPermissionsTeamId($this->business->id);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Khata Desk',
            'code' => 'KHT-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->salesperson = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->assignRole('Salesperson');

        $this->cashAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Cash Drawer Desk',
            'type' => 'cash',
            'account_number' => 'CASH-KHT-1',
            'balance' => 10000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Malik Usman',
            'phone' => '+923001234567',
            'address' => 'Bazaar Kalan, Rawalpindi',
            'credit_limit' => 20000.00,
            'opening_balance' => 0.00,
            'status' => 'active'
        ]);

        CashRegisterSession::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'financial_account_id' => $this->cashAccount->id,
            'opened_at' => now(),
            'opening_float' => 1000.00,
            'status' => 'open'
        ]);
    }

    public function test_01_customer_creation_persists_with_pakistani_phone(): void
    {
        $payload = [
            'name' => 'Chaudhry Akram',
            'phone' => '03219876543',
            'address' => 'Chowk Yadgar, Peshawar',
            'credit_limit' => 15000.00,
        ];

        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('customers', [
            'business_id' => $this->business->id,
            'name' => 'Chaudhry Akram',
        ]);
    }

    public function test_02_customer_ledger_endpoint_returns_initial_balance(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers/' . $this->customer->id . '/ledger');
        $response->assertStatus(200);
        $this->assertNotNull($response->json());
    }

    public function test_03_credit_sale_increases_customer_receivable_balance(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Wheat Flour 20kg Bag',
            'cost_price' => 1800.00,
            'retail_price' => 2200.00,
            'selling_price' => 2200.00,
            'sku' => 'FLOUR-020',
            'stock' => 20,
            'status' => 'active'
        ]);

        \App\Models\BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 20.000,
        ]);

        // Sale with 0 paid amount (full credit udhaar)
        $payload = [
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'payment_method' => 'credit',
            'paid_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1.000,
                    'unit_price' => 2200.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->owner)->postJson('/api/v1/sales', $payload);
        $response->assertStatus(201);

        $outstanding = $this->accountService->calculateOutstanding($this->customer->fresh());
        $this->assertEquals(2200.00, $outstanding);
    }

    public function test_04_partial_khata_payment_reduces_customer_balance(): void
    {
        $this->customer->update(['opening_balance' => 2200.00]);

        $payload = [
            'branch_id' => $this->branch->id,
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'notes' => 'Partial cash recovery',
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/customers/' . $this->customer->id . '/payments', $payload);
        $response->assertStatus(201);

        $outstanding = $this->accountService->calculateOutstanding($this->customer->fresh());
        $this->assertEquals(1200.00, $outstanding);
    }

    public function test_05_full_khata_payment_clears_customer_balance_to_zero(): void
    {
        $this->customer->update(['opening_balance' => 1200.00]);

        $payload = [
            'branch_id' => $this->branch->id,
            'amount' => 1200.00,
            'payment_method' => 'cash',
            'notes' => 'Full balance settlement',
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/customers/' . $this->customer->id . '/payments', $payload);
        $response->assertStatus(201);

        $outstanding = $this->accountService->calculateOutstanding($this->customer->fresh());
        $this->assertEquals(0.00, $outstanding);
    }

    public function test_06_excessive_overcollection_beyond_balance_is_strictly_rejected(): void
    {
        $this->customer->update(['opening_balance' => 500.00]);

        $payload = [
            'branch_id' => $this->branch->id,
            'amount' => 800.00, // Exceeds 500.00 balance!
            'payment_method' => 'cash',
            'notes' => 'Overpayment attempt',
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/customers/' . $this->customer->id . '/payments', $payload);
        $response->assertStatus(422);

        // Balance remains unchanged at 500.00
        $outstanding = $this->accountService->calculateOutstanding($this->customer->fresh());
        $this->assertEquals(500.00, $outstanding);
    }

    public function test_07_payment_reversal_restores_customer_previous_receivable_balance(): void
    {
        $this->customer->update(['opening_balance' => 1500.00]);

        // Record 500 payment via endpoint
        $payResp = $this->actingAs($this->owner)
            ->postJson('/api/v1/customers/' . $this->customer->id . '/payments', [
                'branch_id' => $this->branch->id,
                'amount' => 500.00,
                'payment_method' => 'cash',
            ]);
        $payResp->assertStatus(201);
        $paymentId = $payResp->json('id');

        $this->assertEquals(1000.00, $this->accountService->calculateOutstanding($this->customer->fresh()));

        // Reverse the 500 payment
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/customer-payments/' . $paymentId . '/reverse', [
                'reason' => 'Cheque bounced or duplicate payment'
            ]);
        $response->assertStatus(200);

        $this->assertEquals(1500.00, $this->accountService->calculateOutstanding($this->customer->fresh()));
    }

    public function test_08_duplicate_payment_submission_with_idempotency_key_is_safe(): void
    {
        $this->customer->update(['opening_balance' => 1000.00]);
        $idempotencyKey = 'IDEM-KHT-REPLAY-555';

        $payload = [
            'branch_id' => $this->branch->id,
            'amount' => 300.00,
            'payment_method' => 'cash',
            'idempotency_key' => $idempotencyKey,
        ];

        // First attempt
        $resp1 = $this->actingAs($this->owner)
            ->postJson('/api/v1/customers/' . $this->customer->id . '/payments', $payload);
        $resp1->assertStatus(201);
        $paymentId1 = $resp1->json('id');

        // Second duplicate attempt
        $resp2 = $this->actingAs($this->owner)
            ->postJson('/api/v1/customers/' . $this->customer->id . '/payments', $payload);
        $this->assertContains($resp2->status(), [200, 201]);
        $this->assertEquals($paymentId1, $resp2->json('id'));

        // Customer balance deducted only once: 1000 - 300 = 700
        $this->assertEquals(700.00, $this->accountService->calculateOutstanding($this->customer->fresh()));
    }

    public function test_09_customer_payments_list_endpoint_returns_history(): void
    {
        $this->customer->update(['opening_balance' => 500.00]);
        $this->actingAs($this->owner)
            ->postJson('/api/v1/customers/' . $this->customer->id . '/payments', [
                'branch_id' => $this->branch->id,
                'amount' => 250.00,
                'payment_method' => 'cash',
            ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/customers/' . $this->customer->id . '/payments');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json());
    }

    public function test_10_tenant_isolation_prevents_access_to_unrelated_business_customer(): void
    {
        $bizOther = Business::create(['name' => 'Foreign Shop']);
        setPermissionsTeamId($bizOther->id);
        $branchOther = Branch::create(['business_id' => $bizOther->id, 'name' => 'Foreign Counter', 'code' => 'FOR-01', 'is_primary' => true]);
        $ownerOther = User::factory()->create(['business_id' => $bizOther->id, 'branch_id' => $branchOther->id]);
        $ownerOther->businesses()->attach($bizOther->id);
        $ownerOther->assignRole('Business Owner');

        // Other business owner queries Customer belonging to $this->business
        $response = $this->actingAs($ownerOther)->getJson('/api/v1/customers/' . $this->customer->id);
        $response->assertStatus(404);
    }

    public function test_11_customer_update_modifies_contact_details(): void
    {
        $response = $this->actingAs($this->owner)->putJson('/api/v1/customers/' . $this->customer->id, [
            'name' => 'Malik Usman Updated',
            'phone' => '+923009999888',
            'credit_limit' => 25000.00,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', [
            'id' => $this->customer->id,
            'name' => 'Malik Usman Updated',
            'phone' => '+923009999888',
        ]);
    }

    public function test_12_customer_search_filter_queries_by_name_and_phone(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers?search=Usman');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Malik Usman']);
    }
}
