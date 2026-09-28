<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Purchase;
use Database\Seeders\RolesAndPermissionsSeeder;

class SupplierBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'Balance Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Balance Product',
            'cost_price' => 20,
            'selling_price' => 40,
            'stock' => 0
        ]);
    }

    public function test_supplier_opening_balance_initialized_on_create()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/suppliers', [
            'name' => 'Opening Supplier',
            'balance' => 1500.50
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Opening Supplier',
            'balance' => 1500.50
        ]);
    }

    public function test_supplier_update_cannot_arbitrarily_overwrite_balance()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Debt Supplier',
            'balance' => 5000.00
        ]);

        // Attempting to wipe debt via PUT /suppliers/{id}
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->putJson("/api/v1/suppliers/{$supplier->id}", [
            'name' => 'Renamed Supplier',
            'balance' => 0.00 // Malicious attempt to clear debt
        ]);

        $response->assertStatus(200);

        // Balance MUST remain intact at 5000.00
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Renamed Supplier',
            'balance' => 5000.00
        ]);
    }

    public function test_unpaid_purchase_increments_supplier_balance()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Purchase Supplier',
            'balance' => 0
        ]);

        $purchaseData = [
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-BAL-01',
            'date' => '2026-09-14',
            'paid_amount' => 0, // Fully unpaid
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_cost' => 20
                ]
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/purchases', $purchaseData);

        $response->assertStatus(201);

        // Supplier balance must increase by 200.00 (10 * 20)
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'balance' => 200.00
        ]);
    }

    public function test_supplier_payment_decrements_balance()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Payment Supplier',
            'balance' => 1000.00
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
            'amount' => 400.00,
            'date' => '2026-09-14',
            'payment_method' => 'cash'
        ]);

        $response->assertStatus(201);

        // Balance decremented from 1000 to 600
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'balance' => 600.00
        ]);

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id,
            'amount' => 400.00,
            'user_id' => $this->owner->id
        ]);
    }

    public function test_supplier_overpayment_is_rejected()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Overpay Supplier',
            'balance' => 300.00
        ]);

        // Attempting to pay 500 when debt is only 300
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
            'amount' => 500.00,
            'date' => '2026-09-14'
        ]);

        $response->assertStatus(422);

        // Balance remains 300
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'balance' => 300.00
        ]);
    }

    public function test_supplier_deletion_fails_if_history_or_balance_exists()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $supplierWithDebt = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Debt Supplier',
            'balance' => 100.00
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->deleteJson("/api/v1/suppliers/{$supplierWithDebt->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('suppliers', ['id' => $supplierWithDebt->id]);
    }

    public function test_supplier_balance_reconciliation_formulas()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // 1. Create Supplier with Opening Balance = 100
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Reconciliation Supplier',
            'opening_balance' => 100.00,
            'balance' => 100.00
        ]);

        // 2. Create Purchase A: Total 1000, Paid 400, Due 600
        $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-RECON-01',
            'date' => '2026-09-14',
            'paid_amount' => 400,
            'items' => [['product_id' => $this->product->id, 'quantity' => 20, 'unit_cost' => 50]]
        ])->assertStatus(201);

        // 3. Create Purchase B: Total 500, Paid 0, Due 500
        $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-RECON-02',
            'date' => '2026-09-14',
            'paid_amount' => 0,
            'items' => [['product_id' => $this->product->id, 'quantity' => 10, 'unit_cost' => 50]]
        ])->assertStatus(201);

        // 4. Record standalone payment = 200
        $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
            'amount' => 200.00,
            'date' => '2026-09-14',
            'payment_method' => 'cash'
        ])->assertStatus(201);

        $supplier->refresh();

        // Cached Balance expected: 100 (opening) + 600 (due A) + 500 (due B) - 200 (standalone payment) = 1000.00
        $this->assertEquals(1000.00, (float) $supplier->balance);

        $service = app(\App\Services\SupplierBalanceService::class);

        // Formula 1 (Gross Total): opening_balance (100) + SUM(total=1500) - SUM(all payments=600) = 1000.00
        $reconciledGross = $service->reconcileBalance($supplier);
        $this->assertEquals(1000.00, $reconciledGross);

        // Formula 2 (Net Due): opening_balance (100) + SUM(due=1100) - SUM(standalone payments=200) = 1000.00
        $reconciledNet = $service->reconcileBalanceFromDue($supplier);
        $this->assertEquals(1000.00, $reconciledNet);

        // Initial Purchase A payment (400) MUST carry purchase_id
        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id,
            'amount' => 400.00,
            'purchase_id' => Purchase::where('po_number', 'PO-RECON-01')->first()->id
        ]);

        // Standalone payment (200) MUST have purchase_id IS NULL
        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id,
            'amount' => 200.00,
            'purchase_id' => null
        ]);
    }
}
