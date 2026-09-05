<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;

class TransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $business;
    protected $token;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->business = Business::create(['name' => 'Test Business']);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'password' => bcrypt('password123')
        ]);
        
        $this->token = $this->user->createToken('test_token')->plainTextToken;

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Test Product',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 100,
            'unit' => 'pcs'
        ]);
    }

    public function test_purchase_increases_product_stock()
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Test Supplier'
        ]);

        $purchaseData = [
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-001',
            'date' => '2026-08-24',
            'status' => 'received',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 50,
                    'unit_cost' => 10,
                    'total' => 500
                ]
            ],
            'subtotal' => 500,
            'total' => 500,
            'paid_amount' => 500
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->postJson('/api/v1/purchases', $purchaseData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('purchases', ['total' => 500, 'po_number' => 'PO-001']);
        $this->assertDatabaseHas('purchase_items', ['quantity' => 50]);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 150]);
    }

    public function test_sale_decreases_product_stock()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Test Customer'
        ]);

        $saleData = [
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-001',
            'date' => '2026-08-24',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 20,
                    'unit_price' => 20,
                    'total' => 400
                ]
            ],
            'subtotal' => 400,
            'discount' => 0,
            'tax' => 0,
            'total' => 400,
            'paid_amount' => 400,
            'payment_method' => 'cash'
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $this->token"])
                         ->postJson('/api/v1/sales', $saleData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('sales', ['total' => 400]);
        $this->assertDatabaseHas('sale_items', ['quantity' => 20]);
        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 80]);
    }
}
