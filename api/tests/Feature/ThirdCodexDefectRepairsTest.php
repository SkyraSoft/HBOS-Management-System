<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\SupplierPayment;
use App\Models\CustomerPayment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\CashRegisterSession;
use App\Models\BranchInventory;
use App\Services\CustomerAccountService;
use App\Services\BulkSyncService;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ThirdCodexDefectRepairsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * Helper to register a test business and return auth context.
     */
    protected function registerTestBusiness(string $suffix = '1'): array
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => "Third QA Test Store {$suffix}",
            'name' => "Owner {$suffix}",
            'email' => "owner.third{$suffix}@example.com",
            'phone' => "0300111223{$suffix}",
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $regResponse->assertStatus(201);

        $businessId = $regResponse->json('business.id');
        $branchId = $regResponse->json('branch.id');
        $token = $regResponse->json('access_token');
        $user = User::find($regResponse->json('user.id'));
        $drawer = FinancialAccount::where('business_id', $businessId)->where('type', 'cash')->first();

        return compact('businessId', 'branchId', 'token', 'user', 'drawer');
    }

    /**
     * THIRD-P1-001: Purchase with cash payment creates AccountMovement outflow and deducts drawer balance.
     */
    public function test_third_p1_001_purchase_with_cash_payment_creates_account_movement_outflow_and_deducts_drawer(): void
    {
        extract($this->registerTestBusiness('p1_001'));

        // 1. Open session with PKR 5,000 float
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 5000.00,
            'notes' => 'Day 1 morning float',
        ])->assertStatus(201);

        $this->assertEquals('5000.00', (string) $drawer->fresh()->balance);

        // 2. Create supplier & product
        $supplier = Supplier::create([
            'business_id' => $businessId,
            'name' => 'Metro Wholesale',
            'phone' => '03009998888',
        ]);

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Basmati Rice 10kg',
            'sku' => 'RICE-001',
            'cost_price' => 1100,
            'selling_price' => 1400,
            'stock' => 0,
            'is_active' => true,
        ]);

        // 3. Post purchase of PKR 2,200 with cash payment
        $poNum = 'PO-' . uniqid();
        $purchaseResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/purchases', [
            'po_number' => $poNum,
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_cost' => 1100,
                ]
            ],
            'paid_amount' => 2200.00,
            'payment_method' => 'cash',
            'financial_account_id' => $drawer->id,
            'notes' => 'Full cash purchase test',
        ]);

        $purchaseResponse->assertStatus(201);
        $purchaseId = $purchaseResponse->json('id');
        $purchase = Purchase::find($purchaseId);

        // Assert purchase status & financial reconciliation
        $this->assertEquals('received', $purchase->status);
        $this->assertEquals('2200.00', (string) $purchase->total);
        $this->assertEquals('2200.00', (string) $purchase->paid_amount);
        $this->assertEquals('0.00', (string) $purchase->due_amount);

        // Assert SupplierPayment record was created
        $payment = SupplierPayment::where('purchase_id', $purchaseId)->first();
        $this->assertNotNull($payment, 'SupplierPayment record must be created for paid purchase.');
        $this->assertEquals('2200.00', (string) $payment->amount);
        $this->assertEquals('completed', $payment->status);

        // Assert AccountMovement outflow record was created
        $movement = AccountMovement::where('reference_type', SupplierPayment::class)
            ->where('reference_id', $payment->id)
            ->first();

        $this->assertNotNull($movement, 'AccountMovement must be recorded for supplier payment outflow.');
        $this->assertEquals('outflow', $movement->type);
        $this->assertEquals('supplier_payment', $movement->movement_category);
        $this->assertEquals($drawer->id, $movement->account_id);
        $this->assertEquals('2200.00', (string) $movement->amount);

        // Assert drawer balance was deducted: 5,000 - 2,200 = 2,800.00
        $this->assertEquals('2800.00', (string) $drawer->fresh()->balance, 'Drawer balance must deduct 2,200 exactly.');
        $this->assertEquals('0.00', (string) $supplier->fresh()->balance, 'Supplier balance must be 0.00 after full payment.');
    }

    /**
     * THIRD-P1-001: Unpaid purchase creates zero outflow; partial purchase creates exact outflow.
     */
    public function test_third_p1_001_unpaid_and_partial_purchase_accounting(): void
    {
        extract($this->registerTestBusiness('p1_partial'));

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 5000.00,
        ])->assertStatus(201);

        $supplier = Supplier::create([
            'business_id' => $businessId,
            'name' => 'National Traders',
            'phone' => '03001239999',
        ]);

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Cooking Oil 5L',
            'sku' => 'OIL-005',
            'cost_price' => 1000,
            'selling_price' => 1300,
            'stock' => 0,
            'is_active' => true,
        ]);

        // A. Partial purchase: Total 3,000, Paid 1,000 cash
        $partialRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/purchases', [
            'po_number' => 'PO-' . uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 1000]
            ],
            'paid_amount' => 1000.00,
            'payment_method' => 'cash',
            'financial_account_id' => $drawer->id,
        ]);

        $partialRes->assertStatus(201);
        $this->assertEquals('4000.00', (string) $drawer->fresh()->balance, 'Drawer must be 5000 - 1000 = 4000.');
        $this->assertEquals('2000.00', (string) $supplier->fresh()->balance, 'Supplier liability must be 2000.');

        // B. Unpaid purchase: Total 1,500, Paid 0
        $unpaidRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/purchases', [
            'po_number' => 'PO-' . uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 1500]
            ],
            'paid_amount' => 0.00,
        ]);

        $unpaidRes->assertStatus(201);
        $this->assertEquals('4000.00', (string) $drawer->fresh()->balance, 'Drawer must remain 4000 after unpaid purchase.');
        $this->assertEquals('3500.00', (string) $supplier->fresh()->balance, 'Supplier liability must be 2000 + 1500 = 3500.');
    }

    /**
     * THIRD-P1-001: Insufficient drawer balance rejects cash payment on purchase.
     */
    public function test_third_p1_001_insufficient_drawer_balance_rejects_purchase_payment(): void
    {
        extract($this->registerTestBusiness('p1_insufficient'));

        // Drawer has 0 balance, open session with 100 float
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 100.00,
        ])->assertStatus(201);

        $supplier = Supplier::create([
            'business_id' => $businessId,
            'name' => 'Wholesale Depot',
            'phone' => '03001234444',
        ]);

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Tea 1kg',
            'sku' => 'TEA-001',
            'cost_price' => 500,
            'selling_price' => 700,
            'stock' => 0,
            'is_active' => true,
        ]);

        // Attempt purchase of 500 with 500 cash payment when drawer only has 100
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/purchases', [
            'po_number' => 'PO-' . uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 500]
            ],
            'paid_amount' => 500.00,
            'payment_method' => 'cash',
            'financial_account_id' => $drawer->id,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Insufficient account balance', json_encode($res->json()));
        $this->assertEquals('100.00', (string) $drawer->fresh()->balance);
    }

    /**
     * THIRD-P1-001: Reversal of supplier payment restores drawer funds and purchase liability.
     */
    public function test_third_p1_001_reversal_of_supplier_payment_restores_drawer_and_purchase_liability(): void
    {
        extract($this->registerTestBusiness('p1_rev'));

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 5000.00,
        ])->assertStatus(201);

        $supplier = Supplier::create([
            'business_id' => $businessId,
            'name' => 'Al-Karam Mill',
            'phone' => '03007778888',
        ]);

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Flour 20kg',
            'sku' => 'FLOUR-020',
            'cost_price' => 2200,
            'selling_price' => 2600,
            'stock' => 0,
            'is_active' => true,
        ]);

        // Create purchase with 2,200 cash payment
        $purchaseRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/purchases', [
            'po_number' => 'PO-' . uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 2200]
            ],
            'paid_amount' => 2200.00,
            'payment_method' => 'cash',
            'financial_account_id' => $drawer->id,
        ]);

        $purchaseRes->assertStatus(201);
        $purchaseId = $purchaseRes->json('id');
        $payment = SupplierPayment::where('purchase_id', $purchaseId)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('2800.00', (string) $drawer->fresh()->balance);

        // Reverse the supplier payment
        $revRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson("/api/v1/supplier-payments/{$payment->id}/reverse");

        $revRes->assertStatus(200);
        $payment->refresh();
        $this->assertEquals('reversed', $payment->status);

        // Assert purchase amounts restored
        $purchase = Purchase::find($purchaseId);
        $this->assertEquals('0.00', (string) $purchase->paid_amount);
        $this->assertEquals('2200.00', (string) $purchase->due_amount);

        // Assert drawer balance restored to 5,000.00 via compensating inflow
        $compensatingMovement = AccountMovement::where('reference_type', SupplierPayment::class)
            ->where('reference_id', $payment->id)
            ->where('movement_category', 'supplier_payment_reversal')
            ->first();

        $this->assertNotNull($compensatingMovement, 'Compensating inflow movement must be recorded.');
        $this->assertEquals('inflow', $compensatingMovement->type);
        $this->assertEquals('2200.00', (string) $compensatingMovement->amount);
        $this->assertEquals('5000.00', (string) $drawer->fresh()->balance);
        $this->assertEquals('2200.00', (string) $supplier->fresh()->balance);
    }

    /**
     * THIRD-P1-002: Bulk sync offline sale normalized to application timezone and included in day close.
     */
    public function test_third_p1_002_bulk_sync_offline_sale_timezone_normalized_and_included_in_day_close(): void
    {
        extract($this->registerTestBusiness('p1_sync'));

        // 1. Open session at 01:02:41 with 5,000 float
        $sessionRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 5000.00,
        ]);
        $sessionRes->assertStatus(201);
        $sessionId = $sessionRes->json('session.id');

        // Set session opened_at to 01:02:41 as in the real defect scenario
        $session = CashRegisterSession::find($sessionId);
        $session->opened_at = Carbon::parse('2026-10-24 01:02:41');
        $session->save();

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Snack Biscuits',
            'sku' => 'SNACK-001',
            'cost_price' => 400,
            'selling_price' => 605,
            'stock' => 100,
            'is_active' => true,
        ]);

        BranchInventory::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'product_id' => $product->id,
            'quantity_on_hand' => 100,
        ]);

        // 2. Client sends offline sale with UTC ISO string: '2026-10-23T20:10:58.000Z'
        // In Asia/Karachi (UTC+5), this is '2026-10-24 01:10:58', which is AFTER 01:02:41
        $clientUuid = 'off-sale-uuid-001';
        $syncPayload = [
            'sales' => [
                [
                    'client_uuid' => $clientUuid,
                    'idempotency_key' => $clientUuid,
                    'branch_id' => $branchId,
                    'occurred_at' => '2026-10-23T20:10:58.000Z',
                    'customer_id' => null,
                    'payment_method' => 'cash',
                    'paid_amount' => 605.00,
                    'total' => 605.00,
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 1,
                            'unit_price' => 605.00,
                            'subtotal' => 605.00,
                        ]
                    ]
                ]
            ]
        ];

        $syncRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/sync/bulk-transactions', $syncPayload);

        $syncRes->assertStatus(200);

        // Verify sale was created
        $serverId = $syncRes->json('sales.0.server_id');
        $sale = Sale::find($serverId);
        $this->assertNotNull($sale);
        $this->assertEquals('605.00', (string) $sale->total);

        // Verify movement timestamp is normalized to Asia/Karachi (01:10:58)
        $movement = AccountMovement::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals('2026-10-24 01:10:58', $movement->created_at->format('Y-m-d H:i:s'));

        // 3. Close the session with exact counted cash: 5,000 + 605 = 5,605.00
        $closeRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/close', [
            'closing_cash_counted' => 5605.00,
            'closing_notes' => 'Closing register after synced offline cash sale',
        ]);

        $closeRes->assertStatus(200);
        $session = CashRegisterSession::find($sessionId);
        $this->assertEquals('closed', $session->status);
        $this->assertEquals('5605.00', (string) $session->closing_cash_expected, 'Expected cash must include 605 offline sale.');
        $this->assertEquals('5605.00', (string) $session->closing_cash_counted);
        $this->assertEquals('0.00', (string) $session->cash_variance, 'Variance must be exactly 0.00.');
    }

    /**
     * THIRD-P1-003: Khata customer payment reversal restores customer receivable and deducts cash.
     */
    public function test_third_p1_003_customer_payment_reversal_restores_receivable_and_deducts_drawer(): void
    {
        extract($this->registerTestBusiness('p1_khata'));

        // Open session with 2,000 float
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 2000.00,
        ])->assertStatus(201);

        $customer = Customer::create([
            'business_id' => $businessId,
            'name' => 'Tariq Mehmood',
            'phone' => '03215554433',
            'opening_balance' => 1300.00,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($customer);

        $this->assertEquals('1300.00', (string) $customer->fresh()->balance);

        // 1. Customer pays 500
        $payRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson("/api/v1/customers/{$customer->id}/payments", [
            'amount' => 500.00,
            'payment_method' => 'Cash',
            'notes' => 'Partial payment',
        ]);

        $payRes->assertStatus(201);
        $paymentId = $payRes->json('id');
        $this->assertEquals('800.00', (string) $customer->fresh()->balance);
        $this->assertEquals('2500.00', (string) $drawer->fresh()->balance);

        // 2. Reverse customer payment
        $revRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson("/api/v1/customer-payments/{$paymentId}/reverse", [
            'reason' => 'Bounced cheque reversal test',
        ]);

        $revRes->assertStatus(200);
        $this->assertEquals('voided', $revRes->json('status'));

        // Customer receivable restored to 1300
        $this->assertEquals('1300.00', (string) $customer->fresh()->balance);

        // Drawer deducted by 500 back to 2000
        $this->assertEquals('2000.00', (string) $drawer->fresh()->balance);
    }

    /**
     * THIRD-P2-003: Inactive product is rejected at POS sale service.
     */
    public function test_third_p2_003_inactive_product_rejected_at_pos_sale(): void
    {
        extract($this->registerTestBusiness('p2_inactive'));

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 1000.00,
        ])->assertStatus(201);

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Discontinued Item',
            'sku' => 'DISC-001',
            'cost_price' => 100,
            'selling_price' => 150,
            'stock' => 50,
            'is_active' => false,
        ]);

        BranchInventory::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'product_id' => $product->id,
            'quantity_on_hand' => 50,
        ]);

        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/sales', [
            'branch_id' => $branchId,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 150]
            ],
            'payment_method' => 'cash',
            'paid_amount' => 150.00,
        ]);

        $saleRes->assertStatus(422);
        $this->assertStringContainsString('inactive and cannot be sold', json_encode($saleRes->json()));
    }

    /**
     * THIRD-P2-004: Opening float by Owner creates auditable AccountMovement with category capital_in.
     */
    public function test_third_p2_004_owner_opening_float_records_auditable_capital_in_account_movement(): void
    {
        extract($this->registerTestBusiness('p2_float'));

        $this->assertEquals(0, $drawer->movements()->count());

        $sessionRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 5000.00,
            'notes' => 'Auditable opening float',
        ]);

        $sessionRes->assertStatus(201);

        $movement = AccountMovement::where('account_id', $drawer->id)
            ->where('movement_category', 'capital_in')
            ->first();

        $this->assertNotNull($movement, 'Auditable AccountMovement of category capital_in must be created.');
        $this->assertEquals('inflow', $movement->type);
        $this->assertEquals('5000.00', (string) $movement->amount);
        $this->assertEquals(CashRegisterSession::class, $movement->reference_type);
        $this->assertEquals('5000.00', (string) $drawer->fresh()->balance);
    }

    /**
     * Comprehensive synthetic automated financial lifecycle test.
     */
    public function test_comprehensive_synthetic_financial_lifecycle(): void
    {
        extract($this->registerTestBusiness('lifecycle'));

        // 1. Initial Opening float PKR 5,000 (AccountMovement capital_in)
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 5000.00,
        ])->assertStatus(201);
        $this->assertEquals('5000.00', (string) $drawer->fresh()->balance);

        // 2. Cash purchase PKR 2,200 (Supplier payment outflow -> Drawer becomes 2,800)
        $supplier = Supplier::create([
            'business_id' => $businessId,
            'name' => 'General Provisions Co',
            'phone' => '03001119999',
        ]);
        $prodA = Product::create([
            'business_id' => $businessId,
            'name' => 'Wheat 50kg',
            'sku' => 'WHEAT-50',
            'cost_price' => 2200,
            'selling_price' => 2600,
            'stock' => 0,
            'is_active' => true,
        ]);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/purchases', [
            'po_number' => 'PO-' . uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'items' => [['product_id' => $prodA->id, 'quantity' => 1, 'unit_cost' => 2200]],
            'paid_amount' => 2200.00,
            'payment_method' => 'cash',
            'financial_account_id' => $drawer->id,
        ])->assertStatus(201);
        $this->assertEquals('2800.00', (string) $drawer->fresh()->balance);

        // 3. Partial purchase PKR 3,000 with PKR 1,000 cash paid (Drawer becomes 1,800, Supplier liability 2,000)
        $prodB = Product::create([
            'business_id' => $businessId,
            'name' => 'Sugar 50kg',
            'sku' => 'SUGAR-50',
            'cost_price' => 3000,
            'selling_price' => 3500,
            'stock' => 0,
            'is_active' => true,
        ]);

        $partialPurchaseRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/purchases', [
            'po_number' => 'PO-' . uniqid(),
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'items' => [['product_id' => $prodB->id, 'quantity' => 1, 'unit_cost' => 3000]],
            'paid_amount' => 1000.00,
            'payment_method' => 'cash',
            'financial_account_id' => $drawer->id,
        ]);
        $partialPurchaseRes->assertStatus(201);
        $purchaseId = $partialPurchaseRes->json('id');
        $this->assertEquals('1800.00', (string) $drawer->fresh()->balance);
        $this->assertEquals('2000.00', (string) $supplier->fresh()->balance);

        // 4. Supplier settlement PKR 500 allocated to purchase (Drawer becomes 1,300, Supplier liability 1,500)
        $settleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
            'amount' => 500.00,
            'payment_method' => 'cash',
            'account_id' => $drawer->id,
            'purchase_id' => $purchaseId,
            'date' => now()->toDateString(),
        ]);
        $settleRes->assertStatus(201);
        $settlePayId = $settleRes->json('id');
        $this->assertEquals('1300.00', (string) $drawer->fresh()->balance);
        $this->assertEquals('1500.00', (string) $supplier->fresh()->balance);

        // 5. Reverse supplier settlement PKR 500 (Compensating inflow -> Drawer restored to 1,800, Supplier liability 2,000)
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson("/api/v1/supplier-payments/{$settlePayId}/reverse")->assertStatus(200);
        $this->assertEquals('1800.00', (string) $drawer->fresh()->balance);
        $this->assertEquals('2000.00', (string) $supplier->fresh()->balance);

        // 6. POS Cash sale PKR 1,200 (Drawer becomes 3,000)
        BranchInventory::updateOrCreate(
            ['business_id' => $businessId, 'branch_id' => $branchId, 'product_id' => $prodA->id],
            ['quantity_on_hand' => 10]
        );
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/sales', [
            'branch_id' => $branchId,
            'items' => [['product_id' => $prodA->id, 'quantity' => 1, 'unit_price' => 1200]],
            'payment_method' => 'cash',
            'paid_amount' => 1200.00,
        ])->assertStatus(201);
        $this->assertEquals('3000.00', (string) $drawer->fresh()->balance);

        // 7. Khata Credit sale PKR 800 (Customer receivable +800, zero cash impact)
        $customer = Customer::create([
            'business_id' => $businessId,
            'name' => 'Waqas Retailer',
            'phone' => '03331112233',
            'opening_balance' => 0.00,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($customer);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'branch_id' => $branchId,
            'items' => [['product_id' => $prodA->id, 'quantity' => 1, 'unit_price' => 800]],
            'payment_method' => 'credit',
            'paid_amount' => 0.00,
        ])->assertStatus(201);
        $this->assertEquals('800.00', (string) $customer->fresh()->balance);
        $this->assertEquals('3000.00', (string) $drawer->fresh()->balance);

        // 8. Khata customer cash collection PKR 300 (Drawer becomes 3,300, Customer receivable 500)
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson("/api/v1/customers/{$customer->id}/payments", [
            'amount' => 300.00,
            'payment_method' => 'Cash',
        ])->assertStatus(201);
        $this->assertEquals('500.00', (string) $customer->fresh()->balance);
        $this->assertEquals('3300.00', (string) $drawer->fresh()->balance);

        // 9. Offline synced cash sale PKR 605 with UTC timestamp (Drawer becomes 3,905)
        $prodOffline = Product::create([
            'business_id' => $businessId,
            'name' => 'Snack Pack 100g',
            'sku' => 'SNACK-100',
            'cost_price' => 400,
            'selling_price' => 605,
            'stock' => 10,
            'is_active' => true,
        ]);
        BranchInventory::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'product_id' => $prodOffline->id,
            'quantity_on_hand' => 10,
        ]);

        $clientUuid = 'lifecycle-offline-sale-001';
        $syncPayload = [
            'sales' => [
                [
                    'client_uuid' => $clientUuid,
                    'idempotency_key' => $clientUuid,
                    'branch_id' => $branchId,
                    'occurred_at' => now()->utc()->toIso8601String(),
                    'customer_id' => null,
                    'payment_method' => 'cash',
                    'paid_amount' => 605.00,
                    'total' => 605.00,
                    'items' => [
                        ['product_id' => $prodOffline->id, 'quantity' => 1, 'unit_price' => 605.00, 'subtotal' => 605.00]
                    ]
                ]
            ]
        ];
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/sync/bulk-transactions', $syncPayload)->assertStatus(200);
        $this->assertEquals('3905.00', (string) $drawer->fresh()->balance);

        // 10. Day Close with exact physical count PKR 3,905: Expected = 3,905.00, Counted = 3,905.00, Variance = 0.00
        $closeRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/close', [
            'closing_cash_counted' => 3905.00,
            'closing_notes' => 'Synthetic lifecycle balanced close',
        ]);
        $closeRes->assertStatus(200);

        $session = CashRegisterSession::where('financial_account_id', $drawer->id)->first();
        $this->assertEquals('closed', $session->status);
        $this->assertEquals('3905.00', (string) $session->closing_cash_expected);
        $this->assertEquals('3905.00', (string) $session->closing_cash_counted);
        $this->assertEquals('0.00', (string) $session->cash_variance);
    }
}
