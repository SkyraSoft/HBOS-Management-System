<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Deep Assurance: Suppliers, Purchases, Landed Cost, Payables, and Outflows
 */
class SupplierPurchasesAndPayablesDeepTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $businessB;
    protected $branch;
    protected $owner;
    protected $cashAccount;
    protected $product;
    protected $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->business = Business::create([
            'name' => 'Wholesale Depot',
            'legal_name' => 'Wholesale Depot Pvt Ltd',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->businessB = Business::create([
            'name' => 'Competitor Depot',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Depot Central',
            'code' => 'DPT-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Depot Master',
            'email' => 'master@depot.test',
            'password' => bcrypt('Secret123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);

        setPermissionsTeamId($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->cashAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Treasury Cash Outflow',
            'type' => 'cash',
            'account_number' => 'CSH-DPT-1',
            'opening_balance' => 50000.00,
            'balance' => 50000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Bulk Basmati Rice 25kg',
            'sku' => 'RICE-25KG',
            'retail_price' => 7500.00,
            'cost_price' => 6000.00,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 10.000,
            'minimum_stock' => 5.000,
        ]);

        $this->supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Punjab Grain Mills',
            'code' => 'SUP-0001',
            'contact_person' => 'Chaudhry Akram',
            'phone' => '03001234567',
            'email' => 'grainmills@test.com',
            'address' => 'G.T. Road Gujranwala',
            'opening_balance' => 0.00,
            'balance' => 0.00,
        ]);
    }

    public function test_supplier_creation_with_valid_details(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/suppliers', [
                'name' => 'Sindh Spice Traders',
                'code' => 'SUP-SPICE',
                'contact_person' => 'Farhan Bhai',
                'phone' => '03219876543',
                'email' => 'spice@traders.test',
                'address' => 'Jodia Bazaar Karachi',
                'opening_balance' => 15000.00,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('suppliers', [
            'business_id' => $this->business->id,
            'name' => 'Sindh Spice Traders',
            'balance' => 15000.00,
        ]);
    }

    public function test_duplicate_supplier_code_handling(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/suppliers', [
                'name' => 'Duplicate Code Vendor',
                'code' => 'SUP-0001', // Duplicate code in same business
                'phone' => '03009999999',
            ]);

        $response->assertStatus(422);
    }

    public function test_purchase_order_creation_increases_inventory_and_payable(): void
    {
        $poNumber = 'PO-' . strtoupper(Str::random(6));

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'branch_id' => $this->branch->id,
                'po_number' => $poNumber,
                'date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 20,
                        'unit_cost' => 5800.00,
                    ]
                ],
                'notes' => 'Bulk paddy intake',
            ]);

        $response->assertStatus(201);

        // Verify stock increased by 20 (10 + 20 = 30)
        $inv = BranchInventory::where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertEquals(30.000, (float)$inv->quantity_on_hand);

        // Verify supplier payable increased by 20 * 5800 = 116,000
        $this->supplier->refresh();
        $this->assertEquals(116000.00, (float)$this->supplier->balance);
    }

    public function test_purchase_order_with_freight_allocates_landed_cost(): void
    {
        $poNumber = 'PO-FRT-' . strtoupper(Str::random(4));

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'branch_id' => $this->branch->id,
                'po_number' => $poNumber,
                'date' => now()->toDateString(),
                'freight_amount' => 2000.00, // 2,000 freight over 10 units = 200/unit landed overhead
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_cost' => 6000.00,
                    ]
                ],
            ]);

        $response->assertStatus(201);
        $purchase = Purchase::where('po_number', $poNumber)->first();
        $this->assertNotNull($purchase);
        $this->assertEquals(2000.00, (float)$purchase->freight_amount);
        $this->assertEquals(62000.00, (float)$purchase->total); // 60,000 goods + 2,000 freight
    }

    public function test_purchase_order_last_price_and_trend_history(): void
    {
        $po1 = 'PO-TR1-' . strtoupper(Str::random(4));
        $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'branch_id' => $this->branch->id,
                'po_number' => $po1,
                'date' => now()->subDays(5)->toDateString(),
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 5000.00]
                ],
            ]);

        $po2 = 'PO-TR2-' . strtoupper(Str::random(4));
        $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'branch_id' => $this->branch->id,
                'po_number' => $po2,
                'date' => now()->toDateString(),
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 5500.00]
                ],
            ]);

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson("/api/v1/purchases?supplier_id={$this->supplier->id}&product_id={$this->product->id}&action=last-price");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['found']);
        $this->assertEquals(5500.00, (float)$data['last_price']);
    }

    public function test_supplier_payment_reduces_payable_and_posts_outflow(): void
    {
        // First create a debt of 30,000
        $this->supplier->update(['balance' => 30000.00]);

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
                'amount' => 12000.00,
                'payment_method' => 'cash',
                'financial_account_id' => $this->cashAccount->id,
                'date' => now()->toDateString(),
                'notes' => 'Partial payment via cash desk',
            ]);

        $response->assertStatus(201);

        $this->supplier->refresh();
        // 30,000 - 12,000 = 18,000
        $this->assertEquals(18000.00, (float)$this->supplier->balance);

        $this->cashAccount->refresh();
        // 50,000 - 12,000 = 38,000
        $this->assertEquals(38000.00, (float)$this->cashAccount->balance);
    }

    public function test_supplier_overpayment_rejected_by_balance_service(): void
    {
        $this->supplier->update(['balance' => 5000.00]);

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
                'amount' => 10000.00, // Overpayment beyond 5,000
                'payment_method' => 'cash',
                'financial_account_id' => $this->cashAccount->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_supplier_payment_idempotency_replay_returns_existing(): void
    {
        $this->supplier->update(['balance' => 20000.00]);
        $idempotencyKey = 'IDEM-SUPP-' . Str::uuid()->toString();

        $first = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
                'amount' => 5000.00,
                'payment_method' => 'cash',
                'financial_account_id' => $this->cashAccount->id,
                'idempotency_key' => $idempotencyKey,
            ]);

        $first->assertStatus(201);
        $firstId = $first->json('id');

        // Replay with exact same key and parameters
        $second = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
                'amount' => 5000.00,
                'payment_method' => 'cash',
                'financial_account_id' => $this->cashAccount->id,
                'idempotency_key' => $idempotencyKey,
            ]);

        $second->assertStatus(200);
        $this->assertEquals($firstId, $second->json('id'));

        // Balance must only have been deducted once: 20,000 - 5,000 = 15,000
        $this->supplier->refresh();
        $this->assertEquals(15000.00, (float)$this->supplier->balance);
    }

    public function test_supplier_payment_idempotency_key_mismatch_rejected(): void
    {
        $this->supplier->update(['balance' => 20000.00]);
        $idempotencyKey = 'IDEM-SUPP-MIS-' . Str::uuid()->toString();

        $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
                'amount' => 4000.00,
                'payment_method' => 'cash',
                'financial_account_id' => $this->cashAccount->id,
                'idempotency_key' => $idempotencyKey,
            ]);

        // Attempt different amount with same idempotency key
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
                'amount' => 8000.00,
                'payment_method' => 'cash',
                'financial_account_id' => $this->cashAccount->id,
                'idempotency_key' => $idempotencyKey,
            ]);

        $response->assertStatus(422);
    }

    public function test_cross_tenant_supplier_access_denied(): void
    {
        $foreignSupplier = Supplier::create([
            'business_id' => $this->businessB->id,
            'name' => 'Foreign Grain Exporter',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson("/api/v1/suppliers/{$foreignSupplier->id}/payments");

        $response->assertStatus(404);
    }
}
