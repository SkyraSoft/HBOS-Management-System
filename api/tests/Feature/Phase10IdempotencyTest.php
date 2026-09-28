<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\FinancialAccount;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use App\Models\AccountTransfer;
use App\Models\AccountMovement;
use App\Models\InventoryMovement;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

class Phase10IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $headers;
    protected $cashAccount;
    protected $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'Idempotency Test Co']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'HQ Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'role' => 'Business Owner',
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->cashAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Cash Register',
            'type' => 'cash',
            'opening_balance' => 50000.00,
            'balance' => 50000.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->bankAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => null,
            'name' => 'Main Bank',
            'type' => 'bank',
            'opening_balance' => 100000.00,
            'balance' => 100000.00,
            'is_default' => false,
            'status' => 'active',
        ]);

        $token = $this->owner->createToken('test')->plainTextToken;
        $this->headers = [
            'Authorization' => "Bearer $token",
            'X-Business-ID' => (string) $this->business->id,
        ];
    }

    /**
     * Section Y: Sales Idempotency
     */
    public function test_sale_idempotency_replay_and_conflict_rejection()
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Test Item',
            'sku' => 'SKU-IDEM-1',
            'cost_price' => 50.00,
            'retail_price' => 100.00,
            'track_quantity' => true,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'idempotency_key' => 'SALE-KEY-12345',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 100.00,
                ]
            ],
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'financial_account_id' => $this->cashAccount->id,
        ];

        // 1. Initial Submission
        $res1 = $this->withHeaders($this->headers)->postJson('/api/v1/sales', $payload);
        $res1->assertStatus(201);
        $saleId1 = $res1->json('data.id') ?? $res1->json('id');

        // Verify initial state
        $this->assertEquals(1, Sale::where('idempotency_key', 'SALE-KEY-12345')->count());
        $this->assertEquals(8, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));
        $this->assertEquals(50200.00, (float) $this->cashAccount->fresh()->balance);

        // 2. Replay with identical payload -> Returns same sale idempotently
        $res2 = $this->withHeaders($this->headers)->postJson('/api/v1/sales', $payload);
        $this->assertTrue(in_array($res2->status(), [200, 201]));
        $saleId2 = $res2->json('data.id') ?? $res2->json('id');
        $this->assertEquals($saleId1, $saleId2);

        // Zero duplicate entities
        $this->assertEquals(1, Sale::where('idempotency_key', 'SALE-KEY-12345')->count());
        $this->assertEquals(1, SaleItem::where('sale_id', $saleId1)->count());
        $this->assertEquals(1, InventoryMovement::where('reference_id', $saleId1)->where('type', 'sale')->count());
        $this->assertEquals(8, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));
        $this->assertEquals(50200.00, (float) $this->cashAccount->fresh()->balance);

        // 3. Conflicting submission with same key but different items/amount -> 422
        $conflictPayload = $payload;
        $conflictPayload['items'][0]['quantity'] = 3;
        $res3 = $this->withHeaders($this->headers)->postJson('/api/v1/sales', $conflictPayload);
        $res3->assertStatus(422);

        // Invariants remain undisturbed
        $this->assertEquals(1, Sale::where('idempotency_key', 'SALE-KEY-12345')->count());
        $this->assertEquals(8, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));
    }

    /**
     * Section Z: Customer Payment Idempotency
     */
    public function test_customer_payment_idempotency_replay_and_conflict_rejection()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Khata Customer',
            'phone' => '03001234567',
            'opening_balance' => 1000.00,
            'balance' => 1000.00,
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'amount' => 300.00,
            'payment_method' => 'cash',
            'financial_account_id' => $this->cashAccount->id,
            'idempotency_key' => 'CP-KEY-999',
            'date' => now()->toDateString(),
        ];

        // 1. First submission
        $res1 = $this->withHeaders($this->headers)->postJson("/api/v1/customers/{$customer->id}/payments", $payload);
        $res1->assertStatus(201);
        $cpId = $res1->json('data.id') ?? $res1->json('id');

        $this->assertEquals(700.00, (float) $customer->fresh()->balance);
        $this->assertEquals(50300.00, (float) $this->cashAccount->fresh()->balance);
        $this->assertEquals(1, CustomerPayment::where('idempotency_key', 'CP-KEY-999')->count());

        // 2. Duplicate submission with exact payload -> Reused
        $res2 = $this->withHeaders($this->headers)->postJson("/api/v1/customers/{$customer->id}/payments", $payload);
        $this->assertTrue(in_array($res2->status(), [200, 201]));
        $this->assertEquals($cpId, $res2->json('data.id') ?? $res2->json('id'));

        // No double-credit, no double inflow
        $this->assertEquals(700.00, (float) $customer->fresh()->balance);
        $this->assertEquals(50300.00, (float) $this->cashAccount->fresh()->balance);
        $this->assertEquals(1, CustomerPayment::where('idempotency_key', 'CP-KEY-999')->count());
        $this->assertEquals(1, AccountMovement::where('reference_type', CustomerPayment::class)->where('reference_id', $cpId)->count());

        // 3. Conflicting payload -> 422
        $conflictPayload = $payload;
        $conflictPayload['amount'] = 400.00;
        $res3 = $this->withHeaders($this->headers)->postJson("/api/v1/customers/{$customer->id}/payments", $conflictPayload);
        $res3->assertStatus(422);

        $this->assertEquals(700.00, (float) $customer->fresh()->balance);
    }

    /**
     * Section AA: Supplier Payment Idempotency
     */
    public function test_supplier_payment_idempotency_replay_and_conflict_rejection()
    {
        $supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Wholesale Supplier',
            'code' => 'SUP-IDEM-01',
            'balance' => 2000.00,
        ]);

        $payload = [
            'amount' => 500.00,
            'payment_method' => 'cash',
            'financial_account_id' => $this->cashAccount->id,
            'idempotency_key' => 'SP-KEY-888',
            'date' => now()->toDateString(),
        ];

        // 1. Initial submission
        $res1 = $this->withHeaders($this->headers)->postJson("/api/v1/suppliers/{$supplier->id}/payments", $payload);
        $res1->assertStatus(201);
        $spId = $res1->json('id');

        $this->assertEquals(1500.00, (float) $supplier->fresh()->balance);
        $this->assertEquals(49500.00, (float) $this->cashAccount->fresh()->balance);
        $this->assertEquals(1, SupplierPayment::where('idempotency_key', 'SP-KEY-888')->count());

        // 2. Replay with identical payload -> 200, reused existing
        $res2 = $this->withHeaders($this->headers)->postJson("/api/v1/suppliers/{$supplier->id}/payments", $payload);
        $this->assertTrue(in_array($res2->status(), [200, 201]));
        $this->assertEquals($spId, $res2->json('id'));

        // Zero duplicate deduction or outflow
        $this->assertEquals(1500.00, (float) $supplier->fresh()->balance);
        $this->assertEquals(49500.00, (float) $this->cashAccount->fresh()->balance);
        $this->assertEquals(1, SupplierPayment::where('idempotency_key', 'SP-KEY-888')->count());
        $this->assertEquals(1, AccountMovement::where('reference_type', SupplierPayment::class)->where('reference_id', $spId)->count());

        // 3. Conflict payload -> 422
        $conflictPayload = $payload;
        $conflictPayload['amount'] = 600.00;
        $res3 = $this->withHeaders($this->headers)->postJson("/api/v1/suppliers/{$supplier->id}/payments", $conflictPayload);
        $res3->assertStatus(422);

        $this->assertEquals(1500.00, (float) $supplier->fresh()->balance);
    }

    /**
     * Section AB: Sale Return Idempotency
     */
    public function test_sale_return_idempotency_replay_and_conflict_rejection()
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Returnable Item',
            'sku' => 'SKU-RET-1',
            'cost_price' => 40.00,
            'retail_price' => 100.00,
            'track_quantity' => true,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
        ]);

        // Create initial sale
        $saleRes = $this->withHeaders($this->headers)->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 100.00]
            ],
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'financial_account_id' => $this->cashAccount->id,
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('data.id') ?? $saleRes->json('id');
        $sale = Sale::with('items')->find($saleId);
        $saleItem = $sale->items->first();

        $this->assertEquals(7, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));

        $returnPayload = [
            'idempotency_key' => 'RET-KEY-777',
            'reason' => 'Customer defect',
            'items' => [
                ['sale_item_id' => $saleItem->id, 'quantity' => 1]
            ]
        ];

        // 1. Initial return
        $retRes1 = $this->withHeaders($this->headers)->postJson("/api/v1/sales/{$saleId}/returns", $returnPayload);
        $retRes1->assertStatus(201);
        $returnId = $retRes1->json('data.id') ?? $retRes1->json('id');

        // Stock restored from 7 to 8
        $this->assertEquals(8, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));
        $this->assertEquals(1, SaleReturn::where('idempotency_key', 'RET-KEY-777')->count());

        // 2. Replay return -> Reused
        $retRes2 = $this->withHeaders($this->headers)->postJson("/api/v1/sales/{$saleId}/returns", $returnPayload);
        $this->assertTrue(in_array($retRes2->status(), [200, 201]));
        $this->assertEquals($returnId, $retRes2->json('data.id') ?? $retRes2->json('id'));

        // Stock still exactly 8 (no double restock!)
        $this->assertEquals(8, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));
        $this->assertEquals(1, SaleReturn::where('idempotency_key', 'RET-KEY-777')->count());

        // 3. Conflicting payload -> 422
        $conflictPayload = $returnPayload;
        $conflictPayload['items'][0]['quantity'] = 2;
        $retRes3 = $this->withHeaders($this->headers)->postJson("/api/v1/sales/{$saleId}/returns", $conflictPayload);
        $retRes3->assertStatus(422);

        $this->assertEquals(8, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));
    }

    /**
     * Section AC: Account Transfer Idempotency
     */
    public function test_account_transfer_idempotency_replay_and_conflict_rejection()
    {
        $payload = [
            'source_account_id' => $this->bankAccount->id,
            'destination_account_id' => $this->cashAccount->id,
            'amount' => 1000.00,
            'date' => now()->toDateString(),
            'notes' => 'Cash replenishment',
            'idempotency_key' => 'TRANS-KEY-555',
        ];

        // 1. Initial Transfer
        $res1 = $this->withHeaders($this->headers)->postJson('/api/v1/account-transfers', $payload);
        $res1->assertStatus(201);
        $transferId = $res1->json('data.id') ?? $res1->json('id');

        $this->assertEquals(99000.00, (float) $this->bankAccount->fresh()->balance);
        $this->assertEquals(51000.00, (float) $this->cashAccount->fresh()->balance);
        $this->assertEquals(1, AccountTransfer::where('idempotency_key', 'TRANS-KEY-555')->count());

        // 2. Replay with identical payload -> reused
        $res2 = $this->withHeaders($this->headers)->postJson('/api/v1/account-transfers', $payload);
        $this->assertTrue(in_array($res2->status(), [200, 201]));
        $this->assertEquals($transferId, $res2->json('data.id') ?? $res2->json('id'));

        // No double-debit or double-credit
        $this->assertEquals(99000.00, (float) $this->bankAccount->fresh()->balance);
        $this->assertEquals(51000.00, (float) $this->cashAccount->fresh()->balance);
        $this->assertEquals(1, AccountTransfer::where('idempotency_key', 'TRANS-KEY-555')->count());
        $this->assertEquals(2, AccountMovement::where('reference_type', AccountTransfer::class)->where('reference_id', $transferId)->count());

        // 3. Conflicting payload -> 422
        $conflictPayload = $payload;
        $conflictPayload['amount'] = 2000.00;
        $res3 = $this->withHeaders($this->headers)->postJson('/api/v1/account-transfers', $conflictPayload);
        $res3->assertStatus(422);

        $this->assertEquals(99000.00, (float) $this->bankAccount->fresh()->balance);
        $this->assertEquals(51000.00, (float) $this->cashAccount->fresh()->balance);
    }

    /**
     * Section AD: Refund Settlement Duplication Protection
     */
    public function test_refund_settlement_duplication_rejection_and_reversal()
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Settlement Item',
            'sku' => 'SKU-SETTLE-1',
            'cost_price' => 50.00,
            'retail_price' => 200.00,
            'track_quantity' => true,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
        ]);

        // Sale
        $saleRes = $this->withHeaders($this->headers)->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 200.00]],
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'financial_account_id' => $this->cashAccount->id,
        ]);
        $saleId = $saleRes->json('data.id') ?? $saleRes->json('id');
        $sale = Sale::with('items')->find($saleId);

        // Sale Return
        $returnRes = $this->withHeaders($this->headers)->postJson("/api/v1/sales/{$saleId}/returns", [
            'reason' => 'Defect',
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]]
        ]);
        $returnId = $returnRes->json('data.id') ?? $returnRes->json('id');
        $saleReturn = SaleReturn::find($returnId);

        $movementService = app(\App\Services\AccountMovementService::class);

        // 1. Initial Refund Settlement
        $initialBalance = (float) $this->cashAccount->fresh()->balance;
        $movement = $movementService->settleSaleReturnRefund(
            $saleReturn,
            $this->cashAccount,
            200.00,
            now()->toDateString(),
            $this->owner->id
        );

        $this->assertNotNull($movement);
        $this->assertEquals($initialBalance - 200.00, (float) $this->cashAccount->fresh()->balance);

        // 2. Duplicate settlement attempt -> strictly rejected with ValidationException
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $movementService->settleSaleReturnRefund(
            $saleReturn,
            $this->cashAccount,
            200.00,
            now()->toDateString(),
            $this->owner->id
        );
    }

    /**
     * Section AE: Failure Atomicity across Workflows
     */
    public function test_sale_creation_failure_is_atomic_with_zero_side_effects()
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Atomic Item',
            'sku' => 'SKU-ATOMIC-1',
            'cost_price' => 50.00,
            'retail_price' => 100.00,
            'track_quantity' => true,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 1, // Only 1 in stock
        ]);

        $initialSalesCount = Sale::count();
        $initialItemCount = SaleItem::count();
        $initialMovementCount = InventoryMovement::count();
        $initialCash = (float) $this->cashAccount->fresh()->balance;

        // Attempt sale of 2 items (exceeds stock of 1)
        $res = $this->withHeaders($this->headers)->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100.00]
            ],
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'financial_account_id' => $this->cashAccount->id,
        ]);

        $this->assertTrue(in_array($res->status(), [400, 422]));

        // Verify total rollback
        $this->assertEquals($initialSalesCount, Sale::count());
        $this->assertEquals($initialItemCount, SaleItem::count());
        $this->assertEquals($initialMovementCount, InventoryMovement::count());
        $this->assertEquals(1, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));
        $this->assertEquals($initialCash, (float) $this->cashAccount->fresh()->balance);
    }
}
