<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\CustomerPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBranchSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $owner;
    protected User $managerBranch1;
    protected User $salesperson;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Multi-Branch Biz']);
        $this->branch1 = Branch::create(['business_id' => $this->business->id, 'name' => 'Branch North', 'is_primary' => true]);
        $this->branch2 = Branch::create(['business_id' => $this->business->id, 'name' => 'Branch South', 'is_primary' => false]);

        setPermissionsTeamId($this->business->id);

        // Owner
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        // Manager for Branch 1
        $this->managerBranch1 = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->managerBranch1->businesses()->attach($this->business->id);
        $this->managerBranch1->branches()->attach($this->branch1->id, ['business_id' => $this->business->id]);
        $this->managerBranch1->assignRole('Branch Manager');

        // Salesperson
        $this->salesperson = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->branches()->attach($this->branch1->id, ['business_id' => $this->business->id]);
        $this->salesperson->assignRole('Salesperson');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch1->id, 'name' => 'Drawer 1', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);
        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch2->id, 'name' => 'Drawer 2', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Widget A',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 100,
            'unit' => 'pcs',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50,
        ]);
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50,
        ]);

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Corporate Client',
            'opening_balance' => 500.00,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($this->customer);
    }

    public function test_branch_manager_exposure_and_payment_collection_boundaries()
    {
        // Create Sale at Branch 1: total 1000, paid 0, due 1000
        $sale1 = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-B1-001',
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
        SaleItem::create([
            'sale_id' => $sale1->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 100,
            'cost_price' => 50,
            'discount' => 0,
            'total' => 1000,
            'returned_quantity' => 0,
        ]);

        // Create Sale at Branch 2: total 2000, paid 0, due 2000
        $sale2 = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'user_id' => $this->owner->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-B2-001',
            'date' => date('Y-m-d'),
            'subtotal' => 2000,
            'discount' => 0,
            'tax' => 0,
            'total' => 2000,
            'paid_amount' => 0,
            'due_amount' => 2000,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        SaleItem::create([
            'sale_id' => $sale2->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'unit_price' => 100,
            'cost_price' => 50,
            'discount' => 0,
            'total' => 2000,
            'returned_quantity' => 0,
        ]);

        $accountService = app(CustomerAccountService::class);
        $accountService->recalculateBalance($this->customer);

        // Business-wide raw balance = 500 (opening) + 1000 (B1) + 2000 (B2) = 3500.00
        $this->assertEquals(3500.00, $accountService->calculateRawBalance($this->customer));

        // Manager Branch 1 exposure = 1000.00
        $this->assertEquals(1000.00, $accountService->calculateBranchExposure($this->customer, $this->branch1));

        // Calling branch exposure must NOT alter customers.balance
        $this->assertEquals(3500.00, $this->customer->fresh()->balance);

        // 1. Manager Branch 1 can record payment for Branch 1 up to 1000.00
        $response = $this->actingAs($this->managerBranch1, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/customers/{$this->customer->id}/payments", [
                'branch_id' => $this->branch1->id,
                'amount' => 400.00,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(201);
        $this->assertEquals(3100.00, $this->customer->fresh()->balance);
        $this->assertEquals(600.00, $accountService->calculateBranchExposure($this->customer, $this->branch1));

        // 2. Manager Branch 1 attempting to collect 700.00 (exceeding remaining 600.00) is rejected
        $overResponse = $this->actingAs($this->managerBranch1, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/customers/{$this->customer->id}/payments", [
                'branch_id' => $this->branch1->id,
                'amount' => 700.00,
                'payment_method' => 'cash',
            ]);

        $overResponse->assertStatus(422);

        // 3. Manager Branch 1 attempting to collect for Branch 2 is rejected
        $branch2Response = $this->actingAs($this->managerBranch1, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/customers/{$this->customer->id}/payments", [
                'branch_id' => $this->branch2->id,
                'amount' => 500.00,
                'payment_method' => 'cash',
            ]);

        $branch2Response->assertStatus(422);
    }

    public function test_salesperson_cannot_record_or_reverse_customer_payments()
    {
        // Record attempt
        $response = $this->actingAs($this->salesperson, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/customers/{$this->customer->id}/payments", [
                'branch_id' => $this->branch1->id,
                'amount' => 100.00,
            ]);

        $response->assertStatus(422);

        // Reversal attempt
        $payment = CustomerPayment::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->owner->id,
            'payment_number' => 'CP-OWN-001',
            'amount' => 100.00,
            'payment_method' => 'cash',
            'date' => date('Y-m-d'),
            'status' => 'posted',
        ]);

        $revResponse = $this->actingAs($this->salesperson, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/customer-payments/{$payment->id}/reverse", [
                'reason' => 'Salesperson attempt',
            ]);

        // Salesperson is blocked from reversal
        $this->assertEquals('posted', $payment->fresh()->status);
    }
}
