<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $userA;
    protected User $userB;
    protected Customer $customerA;
    protected Customer $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Business A
        $this->businessA = Business::create(['name' => 'Business A']);
        $this->branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A', 'is_primary' => true]);
        
        setPermissionsTeamId($this->businessA->id);
        $this->userA = User::factory()->create(['business_id' => $this->businessA->id, 'branch_id' => $this->branchA->id]);
        $this->userA->businesses()->attach($this->businessA->id);
        $this->userA->assignRole('Business Owner');

        $this->customerA = Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Customer A',
            'opening_balance' => 500.00,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($this->customerA);

        // Business B
        $this->businessB = Business::create(['name' => 'Business B']);
        $this->branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);

        setPermissionsTeamId($this->businessB->id);
        $this->userB = User::factory()->create(['business_id' => $this->businessB->id, 'branch_id' => $this->branchB->id]);
        $this->userB->businesses()->attach($this->businessB->id);
        $this->userB->assignRole('Business Owner');

        $this->customerB = Customer::create([
            'business_id' => $this->businessB->id,
            'name' => 'Customer B',
            'opening_balance' => 1000.00,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($this->customerB);
    }

    public function test_user_in_business_a_cannot_view_business_b_customer()
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->getJson("/api/v1/customers/{$this->customerB->id}");

        $response->assertStatus(404);
    }

    public function test_user_in_business_a_cannot_list_business_b_customers()
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->getJson('/api/v1/customers');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Customer A']);
        $response->assertJsonMissing(['name' => 'Customer B']);
    }

    public function test_user_in_business_a_cannot_update_business_b_customer()
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->putJson("/api/v1/customers/{$this->customerB->id}", [
                'name' => 'Tampered Customer B',
            ]);

        $response->assertStatus(404);
        $this->assertEquals('Customer B', $this->customerB->fresh()->name);
    }

    public function test_user_in_business_a_cannot_delete_business_b_customer()
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->deleteJson("/api/v1/customers/{$this->customerB->id}");

        $response->assertStatus(404);
        $this->assertNull($this->customerB->fresh()->deleted_at);
    }

    public function test_user_in_business_a_cannot_view_business_b_customer_ledger()
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->getJson("/api/v1/customers/{$this->customerB->id}/ledger");

        $response->assertStatus(404);
    }

    public function test_user_in_business_a_cannot_view_business_b_customer_payments()
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->getJson("/api/v1/customers/{$this->customerB->id}/payments");

        $response->assertStatus(404);
    }

    public function test_user_in_business_a_cannot_record_payment_for_business_b_customer()
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->postJson("/api/v1/customers/{$this->customerB->id}/payments", [
                'branch_id' => $this->branchA->id,
                'amount' => 100.00,
            ]);

        $response->assertStatus(404);
    }

    public function test_user_in_business_a_cannot_reverse_business_b_customer_payment()
    {
        // Record payment in Business B
        $paymentB = CustomerPayment::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'customer_id' => $this->customerB->id,
            'user_id' => $this->userB->id,
            'payment_number' => 'CP-B-0001',
            'amount' => 200.00,
            'payment_method' => 'cash',
            'date' => date('Y-m-d'),
            'status' => 'posted',
        ]);

        $response = $this->actingAs($this->userA, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->postJson("/api/v1/customer-payments/{$paymentB->id}/reverse", [
                'reason' => 'Unauthorized attempt by User A',
            ]);

        $response->assertStatus(404);
        $this->assertEquals('posted', $paymentB->fresh()->status);
    }
}
