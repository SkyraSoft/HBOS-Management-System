<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Customer;

class DashboardTest extends TestCase
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
        $this->user->businesses()->attach($this->business->id);
        
        $this->token = $this->user->createToken('test_token')->plainTextToken;
    }

    public function test_dashboard_stats_calculate_correctly()
    {
        // 1. Create a Sale for today (100)
        Sale::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'invoice_number' => 'INV-01',
            'date' => now()->format('Y-m-d'),
            'total' => 100,
            'status' => 'completed',
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0
        ]);

        // 2. Create a Purchase (50)
        Purchase::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'po_number' => 'PO-01',
            'date' => now()->format('Y-m-d'),
            'total' => 50,
            'status' => 'received',
            'subtotal' => 50
        ]);

        // 3. Create an Expense for today (20)
        Expense::create([
            'business_id' => $this->business->id,
            'category' => 'Misc',
            'amount' => 20,
            'date' => now()->format('Y-m-d'),
            'status' => 'posted',
        ]);

        // 4. Create a Customer
        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Test Customer'
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200)
                 ->assertJsonPath('role', 'Business Owner')
                 ->assertJsonPath('sales.gross_sales', 100)
                 ->assertJsonPath('expenses.total', 20)
                 ->assertJsonPath('operating_position.amount', 80);
    }
}
