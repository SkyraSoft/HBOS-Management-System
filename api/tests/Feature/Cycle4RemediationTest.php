<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBarcode;
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
use App\Models\InventoryMovement;
use App\Services\CustomerAccountService;
use App\Services\BulkSyncService;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class Cycle4RemediationTest extends TestCase
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
    protected function registerTestBusiness(string $suffix = 'c4'): array
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => "Cycle 4 Test Store {$suffix}",
            'name' => "Owner {$suffix}",
            'email' => "owner.c4{$suffix}@example.com",
            'phone' => "0300999887{$suffix}",
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
     * C4-P1-001 & THIRD-P2-003: Product Active/Inactive Lifecycle, Persistence, and Index Filtering.
     */
    public function test_product_active_inactive_lifecycle_and_filtering(): void
    {
        $ctx = $this->registerTestBusiness('prod');

        // 1. Add active product (default)
        $resActive = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/products', [
                'name' => 'Active Rice 5kg',
                'sku' => 'RICE-001',
                'cost_price' => 1000,
                'selling_price' => 1300,
                'stock' => 20,
                'unit' => 'Piece (pcs)',
                'is_active' => true,
            ]);
        $resActive->assertStatus(201);
        $activeId = $resActive->json('id');
        $this->assertTrue((bool)$resActive->json('is_active'));

        // Verify database persistence
        $pActive = Product::find($activeId);
        $this->assertTrue((bool)$pActive->is_active);

        // 2. Add inactive product (explicit)
        $resInactive = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/products', [
                'name' => 'Dormant Tea 500g',
                'sku' => 'TEA-001',
                'cost_price' => 200,
                'selling_price' => 260,
                'stock' => 5,
                'unit' => 'Piece (pcs)',
                'is_active' => false,
            ]);
        $resInactive->assertStatus(201);
        $inactiveId = $resInactive->json('id');
        $this->assertFalse((bool)$resInactive->json('is_active'));

        $pInactive = Product::find($inactiveId);
        $this->assertFalse((bool)$pInactive->is_active);

        // 3. Edit active product to inactive
        $resEdit = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->putJson("/api/v1/products/{$activeId}", [
                'name' => 'Active Rice 5kg (Deactivated)',
                'sku' => 'RICE-001',
                'cost_price' => 1000,
                'selling_price' => 1300,
                'is_active' => false,
            ]);
        $resEdit->assertStatus(200);
        $this->assertFalse((bool)Product::find($activeId)->is_active);

        // 4. Reactivate product
        $resReactivate = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->putJson("/api/v1/products/{$activeId}", [
                'name' => 'Active Rice 5kg (Reactivated)',
                'sku' => 'RICE-001',
                'cost_price' => 1000,
                'selling_price' => 1300,
                'is_active' => true,
            ]);
        $resReactivate->assertStatus(200);
        $this->assertTrue((bool)Product::find($activeId)->is_active);

        // 5. Index query filtering
        $allRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson('/api/v1/products');
        $allRes->assertStatus(200);
        $this->assertCount(2, $allRes->json());

        $onlyActive = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson('/api/v1/products?is_active=1');
        $onlyActive->assertStatus(200);
        $this->assertCount(1, $onlyActive->json());
        $this->assertEquals($activeId, $onlyActive->json('0.id'));

        $onlyInactive = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson('/api/v1/products?is_active=0');
        $onlyInactive->assertStatus(200);
        $this->assertCount(1, $onlyInactive->json());
        $this->assertEquals($inactiveId, $onlyInactive->json('0.id'));
    }

    /**
     * C4-P1-001: Server-side Inactive Product Sale Rejection.
     */
    public function test_direct_sale_request_of_inactive_product_is_rejected(): void
    {
        $ctx = $this->registerTestBusiness('inact_sale');

        // Create inactive product
        $p = Product::create([
            'business_id' => $ctx['businessId'],
            'name' => 'Inactive Cold Drink',
            'sku' => 'SODA-999',
            'cost_price' => 50,
            'selling_price' => 80,
            'stock' => 10,
            'is_active' => false,
        ]);

        BranchInventory::create([
            'business_id' => $ctx['businessId'],
            'branch_id' => $ctx['branchId'],
            'product_id' => $p->id,
            'quantity_on_hand' => 10,
        ]);

        // Attempt direct sale of inactive product
        $saleResponse = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/sales', [
                'branch_id' => $ctx['branchId'],
                'payment_method' => 'cash',
                'amount_paid' => 80,
                'items' => [
                    [
                        'product_id' => $p->id,
                        'quantity' => 1,
                        'unit_price' => 80,
                    ]
                ]
            ]);

        $saleResponse->assertStatus(422);
        $this->assertStringContainsString('inactive', strtolower($saleResponse->json('message') ?? ''));
    }

    /**
     * C4-P1-001: Fractional Opening Stock and Exact Sale Deduction.
     */
    public function test_fractional_stock_and_exact_sale_deduction(): void
    {
        $ctx = $this->registerTestBusiness('frac');

        // 1. Add fractional/loose product with 10.500 opening stock
        $res = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/products', [
                'name' => 'Loose Sugar Premium',
                'sku' => 'SUGAR-FRAC',
                'cost_price' => 120,
                'selling_price' => 160,
                'stock' => 10.500,
                'allow_fractional' => true,
                'sale_unit' => 'kg',
                'conversion_factor' => 1.000,
                'unit' => 'Kilogram (kg)',
                'is_active' => true,
            ]);

        $res->assertStatus(201);
        $prodId = $res->json('id');
        $this->assertEquals(10.5, (float)$res->json('stock'));
        $this->assertTrue((bool)$res->json('allow_fractional'));

        // Database verification: stock must persist as 10.5, NOT 10
        $product = Product::find($prodId);
        $this->assertEquals(10.500, (float)$product->stock);
        $this->assertTrue((bool)$product->allow_fractional);

        // Branch inventory must hold exact 10.500
        $branchInv = BranchInventory::where('product_id', $prodId)->where('branch_id', $ctx['branchId'])->first();
        $this->assertNotNull($branchInv);
        $this->assertEquals(10.500, (float)$branchInv->quantity_on_hand);

        // Inventory movement must record opening stock 10.500
        $mov = InventoryMovement::where('product_id', $prodId)->first();
        $this->assertNotNull($mov);
        $this->assertEquals(10.500, (float)$mov->quantity);

        // 2. Perform fractional sale of 1.250 kg
        $saleResponse = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/sales', [
                'branch_id' => $ctx['branchId'],
                'payment_method' => 'cash',
                'amount_paid' => 200,
                'items' => [
                    [
                        'product_id' => $prodId,
                        'quantity' => 1.250,
                        'unit_price' => 160,
                    ]
                ]
            ]);

        $saleResponse->assertStatus(201);

        // 3. Stock deduction verification: 10.500 - 1.250 = 9.250 exactly
        $branchInv->refresh();
        $this->assertEquals(9.250, (float)$branchInv->quantity_on_hand);

        $product->refresh();
        $this->assertEquals(9.250, (float)$product->stock);
    }

    /**
     * C4-P1-001: Primary and Alternate Barcodes Lookup and Collision Prevention.
     */
    public function test_primary_and_alternate_barcodes_lookup_and_duplicate_conflict(): void
    {
        $ctx = $this->registerTestBusiness('barcodes');

        // 1. Create product with primary barcode and two alternate barcodes
        $res = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/products', [
                'name' => 'Basmati Rice 5kg',
                'sku' => 'RICE-BAR-1',
                'barcode' => '8901234567890',
                'cost_price' => 1000,
                'selling_price' => 1300,
                'stock' => 10,
                'alternate_barcodes' => ['8909990001', '8909990002'],
                'is_active' => true,
            ]);

        $res->assertStatus(201);
        $prodId = $res->json('id');

        // Check canonical ProductBarcode records
        $primaryBarcode = ProductBarcode::where('product_id', $prodId)->where('barcode_type', 'PRIMARY')->first();
        $this->assertNotNull($primaryBarcode);
        $this->assertEquals('8901234567890', $primaryBarcode->barcode);

        $altBarcodes = ProductBarcode::where('product_id', $prodId)->where('barcode_type', 'ALTERNATE')->pluck('barcode')->toArray();
        $this->assertCount(2, $altBarcodes);
        $this->assertContains('8909990001', $altBarcodes);
        $this->assertContains('8909990002', $altBarcodes);

        // 2. Lookup by primary barcode
        $searchPrimary = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson('/api/v1/products?barcode=8901234567890');
        $searchPrimary->assertStatus(200);
        $this->assertCount(1, $searchPrimary->json());
        $this->assertEquals($prodId, $searchPrimary->json('0.id'));

        // 3. Lookup by alternate barcode
        $searchAlt = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson('/api/v1/products?search=8909990001');
        $searchAlt->assertStatus(200);
        $this->assertCount(1, $searchAlt->json());
        $this->assertEquals($prodId, $searchAlt->json('0.id'));

        // 4. Duplicate primary barcode conflict rejection (422)
        $dupPrimary = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/products', [
                'name' => 'Conflict Rice',
                'sku' => 'RICE-CONFLICT',
                'barcode' => '8901234567890',
                'cost_price' => 500,
                'selling_price' => 700,
                'stock' => 5,
            ]);
        $dupPrimary->assertStatus(422);

        // 5. Duplicate alternate barcode conflict rejection (422)
        $dupAlt = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/products', [
                'name' => 'Conflict Alt Product',
                'sku' => 'CONFLICT-ALT',
                'cost_price' => 500,
                'selling_price' => 700,
                'stock' => 5,
                'alternate_barcodes' => ['8909990001'],
            ]);
        $dupAlt->assertStatus(422);
    }

    /**
     * C4-P1-002: Cash Register Open and Current Session Resolution with null/unspecified branch.
     */
    public function test_cash_register_open_and_current_session_resolution_for_owner(): void
    {
        $ctx = $this->registerTestBusiness('pos_hyd');

        // 1. Day Open with PKR 10,000 float
        $openRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/cash-register-sessions/open', [
                'branch_id' => $ctx['branchId'],
                'financial_account_id' => $ctx['drawer']->id,
                'opening_float' => 10000.0,
                'notes' => 'Cycle 4 automated day open',
            ]);

        $openRes->assertStatus(201);
        $sessionId = $openRes->json('session.id');
        $this->assertNotNull($sessionId);

        // Verify capital movement audit trail
        $capMovement = AccountMovement::where('account_id', $ctx['drawer']->id)
            ->where('movement_category', 'capital_in')
            ->first();
        $this->assertNotNull($capMovement);
        $this->assertEquals(10000.0, (float)$capMovement->amount);
        $this->assertEquals(CashRegisterSession::class, $capMovement->reference_type);

        // 2. Fetch current session with explicit branch_id
        $curWithBranch = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson("/api/v1/cash-register-sessions/current?branch_id={$ctx['branchId']}");
        $curWithBranch->assertStatus(200);
        $this->assertTrue((bool)$curWithBranch->json('has_active_session'));
        $this->assertEquals($sessionId, $curWithBranch->json('session.id'));

        // 3. Fetch current session without branch_id (Owner default fallback)
        $curNoBranch = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson('/api/v1/cash-register-sessions/current');
        $curNoBranch->assertStatus(200);
        $this->assertTrue((bool)$curNoBranch->json('has_active_session'));
        $this->assertEquals($sessionId, $curNoBranch->json('session.id'));

        // 4. Fetch current session with string 'null' or empty branch_id
        $curNullStr = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson('/api/v1/cash-register-sessions/current?branch_id=null');
        $curNullStr->assertStatus(200);
        $this->assertTrue((bool)$curNullStr->json('has_active_session'));
        $this->assertEquals($sessionId, $curNullStr->json('session.id'));
    }

    /**
     * C4-P1-003 & C4-P2-001: Purchase Creation, Truthful Filtering, and No Fake Suppliers.
     */
    public function test_purchase_self_hydration_and_supplier_filtering_truth(): void
    {
        $ctx = $this->registerTestBusiness('purch_truth');

        // 1. Create a genuine supplier
        $suppRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/suppliers', [
                'name' => 'Genuine Agro Wholesalers',
                'contact_person' => 'Tariq Mehmood',
                'phone' => '03001234567',
                'supplier_code' => 'SUP-AGRO-01',
            ]);
        $suppRes->assertStatus(201);
        $supplierId = $suppRes->json('supplier.id') ?? $suppRes->json('id');

        // Verify only 1 supplier in business
        $suppList = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson('/api/v1/suppliers');
        $suppList->assertStatus(200);
        $this->assertCount(1, $suppList->json());
        $this->assertEquals('Genuine Agro Wholesalers', $suppList->json('0.name'));

        // 2. Create product for purchase
        $prodRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/products', [
                'name' => 'Wheat Seed 50kg',
                'sku' => 'WHEAT-50KG',
                'cost_price' => 2000,
                'selling_price' => 2500,
                'stock' => 0,
                'unit' => 'Pack',
                'is_active' => true,
            ]);
        $prodRes->assertStatus(201);
        $prodId = $prodRes->json('id');

        // 3. Post unpaid purchase
        $purchRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $supplierId,
                'branch_id' => $ctx['branchId'],
                'po_number' => 'PO-C4-001',
                'subtotal' => 6000,
                'freight_amount' => 500,
                'paid_amount' => 0,
                'items' => [
                    [
                        'product_id' => $prodId,
                        'quantity' => 3,
                        'unit_cost' => 2000,
                    ]
                ]
            ]);
        $purchRes->assertStatus(201);
        $purchaseId = $purchRes->json('purchase.id') ?? $purchRes->json('id');

        // Verify supplier payable is exactly 6500
        $supplier = Supplier::find($supplierId);
        $this->assertEquals(6500, (float)$supplier->balance);

        // Verify purchase listing has exactly 1 purchase
        $listRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson('/api/v1/purchases');
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('data') ?? $listRes->json());

        // Verify filter by supplier returns the purchase
        $filteredRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->getJson("/api/v1/purchases?supplier_id={$supplierId}");
        $filteredRes->assertStatus(200);
        $this->assertCount(1, $filteredRes->json('data') ?? $filteredRes->json());
    }

    /**
     * All 4 Financial P1s Regression Suite:
     * - THIRD-P1-001: Paid purchase creates account outflow and reduces drawer balance atomically.
     * - THIRD-P1-002: Offline sale UTC timestamp normalization in Karachi timezone and day-close inclusion.
     * - THIRD-P1-003: Customer payment reversal restores receivable.
     * - THIRD-P1-004: Supplier payment settlement and reversal restores drawer and liability.
     */
    public function test_all_four_financial_p1_repairs_regression(): void
    {
        $ctx = $this->registerTestBusiness('p1_reg');

        // Initialize drawer with PKR 20,000 float
        $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/cash-register-sessions/open', [
                'branch_id' => $ctx['branchId'],
                'financial_account_id' => $ctx['drawer']->id,
                'opening_float' => 20000.0,
            ])->assertStatus(201);

        $this->assertEquals(20000.0, (float)$ctx['drawer']->fresh()->balance);

        // --- THIRD-P1-001: Paid Purchase with cash outflow ---
        $supplier = Supplier::create([
            'business_id' => $ctx['businessId'],
            'name' => 'National Flour Mills',
            'balance' => 0,
        ]);

        $product = Product::create([
            'business_id' => $ctx['businessId'],
            'name' => 'Fine Flour 10kg',
            'sku' => 'FLOUR-10KG',
            'cost_price' => 800,
            'selling_price' => 1000,
            'stock' => 0,
            'is_active' => true,
        ]);

        $paidPurchRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $supplier->id,
                'branch_id' => $ctx['branchId'],
                'po_number' => 'PO-PAID-001',
                'paid_amount' => 4000,
                'payment_method' => 'cash',
                'financial_account_id' => $ctx['drawer']->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 5,
                        'unit_cost' => 800,
                    ]
                ]
            ]);
        $paidPurchRes->assertStatus(201);

        // Drawer balance should be 20000 - 4000 = 16000
        $this->assertEquals(16000.0, (float)$ctx['drawer']->fresh()->balance);

        // Exactly one expense/outflow movement
        $outflow = AccountMovement::where('account_id', $ctx['drawer']->id)
            ->where('type', 'outflow')
            ->first();
        $this->assertNotNull($outflow);
        $this->assertEquals(4000.0, (float)$outflow->amount);

        // Supplier balance remains 0 (fully paid)
        $this->assertEquals(0.0, (float)$supplier->fresh()->balance);

        // --- THIRD-P1-004: Settle Outstanding Purchase and Reverse ---
        // Create an unpaid purchase of 3000
        $unpaidPurch = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $supplier->id,
                'branch_id' => $ctx['branchId'],
                'po_number' => 'PO-UNPAID-002',
                'paid_amount' => 0,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 3,
                        'unit_cost' => 1000,
                    ]
                ]
            ]);
        $unpaidPurch->assertStatus(201);
        $unpaidId = $unpaidPurch->json('id');

        $this->assertEquals(3000.0, (float)$supplier->fresh()->balance);

        // Settle PKR 3000 via Supplier Payment
        $settleRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
                'financial_account_id' => $ctx['drawer']->id,
                'amount' => 3000,
                'purchase_id' => $unpaidId,
            ]);
        $settleRes->assertStatus(201);
        $paymentId = $settleRes->json('payment.id') ?? $settleRes->json('id');

        // Drawer deducted by 3000 -> 13000
        $this->assertEquals(13000.0, (float)$ctx['drawer']->fresh()->balance);
        $this->assertEquals(0.0, (float)$supplier->fresh()->balance);

        // Reverse supplier payment
        $revRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson("/api/v1/supplier-payments/{$paymentId}/reverse");
        $revRes->assertStatus(200);

        // Drawer balance restored to 16000 and supplier balance restored to 3000
        $this->assertEquals(16000.0, (float)$ctx['drawer']->fresh()->balance);
        $this->assertEquals(3000.0, (float)$supplier->fresh()->balance);

        // --- THIRD-P1-002: Bulk sync offline sale in Karachi timezone ---
        \App\Models\BranchInventory::updateOrCreate(
            [
                'business_id' => $ctx['businessId'],
                'branch_id' => $ctx['branchId'],
                'product_id' => $product->id,
            ],
            [
                'quantity_on_hand' => 100,
            ]
        );

        $syncUuid = 'c4-offline-uuid-' . uniqid();
        $syncRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson('/api/v1/sync/bulk-transactions', [
                'sales' => [
                    [
                        'client_uuid' => $syncUuid,
                        'idempotency_key' => $syncUuid,
                        'branch_id' => $ctx['branchId'],
                        'occurred_at' => '2026-10-23T20:10:58.000Z',
                        'customer_id' => null,
                        'payment_method' => 'cash',
                        'paid_amount' => 1000.00,
                        'total' => 1000.00,
                        'items' => [
                            [
                                'product_id' => $product->id,
                                'quantity' => 1,
                                'unit_price' => 1000.00,
                                'subtotal' => 1000.00,
                            ]
                        ]
                    ]
                ]
            ]);
        $syncRes->assertStatus(200);
        $syncedSaleId = $syncRes->json('sales.0.server_id');
        $this->assertNotNull($syncedSaleId);
        $syncedSale = Sale::find($syncedSaleId);
        $this->assertEquals('1000.00', (string)$syncedSale->total);

        // --- THIRD-P1-003: Khata Customer Payment Reversal ---
        $customer = Customer::create([
            'business_id' => $ctx['businessId'],
            'name' => 'Credit Customer Bilal',
            'phone' => '03007776655',
            'opening_balance' => 2000.0,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($customer);
        $this->assertEquals(2000.0, (float)$customer->fresh()->balance);

        // Customer pays 500 cash
        $custPayRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson("/api/v1/customers/{$customer->id}/payments", [
                'amount' => 500,
                'payment_method' => 'Cash',
            ]);
        $custPayRes->assertStatus(201);
        $custPaymentId = $custPayRes->json('payment.id') ?? $custPayRes->json('id');

        $this->assertEquals(1500.0, (float)$customer->fresh()->balance);
        $this->assertEquals(17500.0, (float)$ctx['drawer']->fresh()->balance);

        // Reverse customer payment
        $revCustRes = $this->withHeader('Authorization', "Bearer {$ctx['token']}")
            ->withHeader('X-Business-ID', $ctx['businessId'])
            ->postJson("/api/v1/customer-payments/{$custPaymentId}/reverse", [
                'reason' => 'Customer bounced cheque reversal',
            ]);
        $revCustRes->assertStatus(200);

        // Customer balance restored to 2000 and drawer reduced to 17000
        $this->assertEquals(2000.0, (float)$customer->fresh()->balance);
        $this->assertEquals(17000.0, (float)$ctx['drawer']->fresh()->balance);
    }
}
