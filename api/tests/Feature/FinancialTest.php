<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Customer;
use Database\Seeders\RolesAndPermissionsSeeder;

class FinancialTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $business;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'Test Business']);
        setPermissionsTeamId($this->business->id);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'password' => bcrypt('password123')
        ]);
        $this->user->businesses()->attach($this->business->id);
        $this->user->assignRole('Business Owner');
        
        $this->token = $this->user->createToken('test_token')->plainTextToken;
    }

    public function test_legacy_khata_write_endpoints_are_deprecated()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Test Customer',
            'opening_balance' => 0,
            'balance' => 0
        ]);

        $khataData = [
            'customer_id' => $customer->id,
            'type' => 'give',
            'amount' => 500,
            'date' => '2026-08-24',
            'details' => 'Advance'
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->postJson('/api/v1/khata', $khataData);

        $response->assertStatus(410);
    }

    public function test_customer_payment_collection_records_payment_and_reduces_customer_balance()
    {
        $branch = \App\Models\Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'is_primary' => true
        ]);

        \App\Models\FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $branch->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 0,
            'balance' => 0,
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->user->assignRole('Business Owner');

        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Test Customer',
            'opening_balance' => 1000,
            'balance' => 1000
        ]);

        $paymentData = [
            'branch_id' => $branch->id,
            'amount' => 300,
            'payment_method' => 'cash',
            'date' => '2026-08-24',
            'notes' => 'Payment received'
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->token",
            'X-Business-ID' => $this->business->id,
        ])->postJson("/api/v1/customers/{$customer->id}/payments", $paymentData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('customer_payments', [
            'customer_id' => $customer->id,
            'amount' => 300,
            'status' => 'posted'
        ]);
        $this->assertEquals(700, $customer->fresh()->balance);
    }

    public function test_expenses_can_be_recorded()
    {
        $branch = \App\Models\Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'is_primary' => true
        ]);

        $account = \App\Models\FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $branch->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $expenseData = [
            'branch_id' => $branch->id,
            'financial_account_id' => $account->id,
            'amount' => 250,
            'category' => 'Utilities',
            'date' => '2026-08-24',
            'notes' => 'Electric bill'
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('expenses', ['amount' => 250, 'category' => 'Utilities']);
        $this->assertEquals(750, $account->fresh()->balance);
    }
}
