<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerActiveBusinessSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $multiUser;
    protected Customer $customerA;
    protected Customer $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Business A
        $this->businessA = Business::create(['name' => 'Business Alpha']);
        $this->branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch Alpha', 'is_primary' => true]);

        // Business B
        $this->businessB = Business::create(['name' => 'Business Beta']);
        $this->branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch Beta', 'is_primary' => true]);

        // Multi-tenant user belongs to both businesses
        $this->multiUser = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
        ]);
        $this->multiUser->businesses()->attach([$this->businessA->id, $this->businessB->id]);

        // Assign Owner in Business A
        setPermissionsTeamId($this->businessA->id);
        $this->multiUser->assignRole('Business Owner');

        // Assign Owner in Business B
        setPermissionsTeamId($this->businessB->id);
        $this->multiUser->assignRole('Business Owner');

        // Customers
        $this->customerA = Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Alpha Customer',
            'opening_balance' => 300.00,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($this->customerA);

        $this->customerB = Customer::create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta Customer',
            'opening_balance' => 700.00,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($this->customerB);
    }

    public function test_switching_active_business_header_dynamically_isolates_customers()
    {
        // 1. Request with Business A header
        $resA = $this->actingAs($this->multiUser, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->getJson('/api/v1/customers');

        $resA->assertStatus(200);
        $resA->assertJsonFragment(['name' => 'Alpha Customer']);
        $resA->assertJsonMissing(['name' => 'Beta Customer']);

        // 2. Request with Business B header
        $resB = $this->actingAs($this->multiUser, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessB->id])
            ->getJson('/api/v1/customers');

        $resB->assertStatus(200);
        $resB->assertJsonFragment(['name' => 'Beta Customer']);
        $resB->assertJsonMissing(['name' => 'Alpha Customer']);

        // 3. Switch back to Business A
        $resA2 = $this->actingAs($this->multiUser, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->getJson('/api/v1/customers');

        $resA2->assertStatus(200);
        $resA2->assertJsonFragment(['name' => 'Alpha Customer']);
        $resA2->assertJsonMissing(['name' => 'Beta Customer']);
    }

    public function test_switching_active_business_isolates_customer_details_and_ledger()
    {
        // Under Business A, customerA is accessible, customerB returns 404
        $this->actingAs($this->multiUser, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->getJson("/api/v1/customers/{$this->customerA->id}")
            ->assertStatus(200);

        $this->actingAs($this->multiUser, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessA->id])
            ->getJson("/api/v1/customers/{$this->customerB->id}")
            ->assertStatus(404);

        // Under Business B, customerB is accessible, customerA returns 404
        $this->actingAs($this->multiUser, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessB->id])
            ->getJson("/api/v1/customers/{$this->customerB->id}")
            ->assertStatus(200);

        $this->actingAs($this->multiUser, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->businessB->id])
            ->getJson("/api/v1/customers/{$this->customerA->id}")
            ->assertStatus(404);
    }
}
