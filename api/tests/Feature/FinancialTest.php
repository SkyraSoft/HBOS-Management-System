<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Customer;

class FinancialTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $business;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->business = Business::create(['name' => 'Test Business']);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'password' => bcrypt('password123')
        ]);
        
        $this->token = $this->user->createToken('test_token')->plainTextToken;
    }

    public function test_khata_give_increases_customer_balance()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Test Customer',
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

        $response->assertStatus(201);

        $this->assertDatabaseHas('khata_transactions', ['amount' => 500, 'type' => 'give']);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'balance' => 500]);
    }

    public function test_khata_got_decreases_customer_balance()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Test Customer',
            'balance' => 1000 // They owe us 1000
        ]);

        $khataData = [
            'customer_id' => $customer->id,
            'type' => 'got',
            'amount' => 300,
            'date' => '2026-08-24',
            'details' => 'Payment received'
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->postJson('/api/v1/khata', $khataData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('khata_transactions', ['amount' => 300, 'type' => 'got']);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'balance' => 700]);
    }

    public function test_expenses_can_be_recorded()
    {
        $expenseData = [
            'amount' => 250,
            'category' => 'Utilities',
            'date' => '2026-08-24',
            'notes' => 'Electric bill'
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->postJson('/api/v1/expenses', $expenseData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('expenses', ['amount' => 250, 'category' => 'Utilities']);
    }
}
