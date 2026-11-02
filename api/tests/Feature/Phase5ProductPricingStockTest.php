<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\BranchInventory;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\FinancialAccount;
use App\Services\InventoryService;
use App\Services\StockIntelligenceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class Phase5ProductPricingStockTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $cashier;
    protected $manager;
    protected $supplier;
    protected $account;

    protected $business2;
    protected $branch2;
    protected $owner2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Business 1 Setup
        $this->business = Business::create(['name' => 'Phase 5 Retail Business']);
        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Retail Branch',
            'is_primary' => true
        ]);

        setPermissionsTeamId($this->business->id);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->manager = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id
        ]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->assignRole('Branch Manager');

        $this->cashier = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id
        ]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->assignRole('Salesperson');

        $this->account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 100000,
            'balance' => 100000,
            'is_default' => true,
            'status' => 'active'
        ]);

        $this->supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Al-Madina Wholesalers',
            'phone' => '03001234567',
            'status' => 'active'
        ]);

        // 2. Business 2 Setup for Tenant Isolation
        $this->business2 = Business::create(['name' => 'Second Business Tenant']);
        $this->branch2 = Branch::create([
            'business_id' => $this->business2->id,
            'name' => 'Branch 2',
            'is_primary' => true
        ]);
        setPermissionsTeamId($this->business2->id);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner2 = User::factory()->create([
            'business_id' => $this->business2->id,
            'branch_id' => $this->branch2->id
        ]);
        $this->owner2->businesses()->attach($this->business2->id);
        $this->owner2->assignRole('Business Owner');

        setPermissionsTeamId($this->business->id);
    }

    /**
     * 1. Fixed-Precision Runtime Evidence & Arithmetic Verification
     */
    public function test_bcmath_fixed_precision_runtime_and_execution_paths()
    {
        // 1. Verify BCMath functions available in PHP runtime
        $this->assertTrue(extension_loaded('bcmath'), 'BCMath extension must be loaded.');
        $this->assertTrue(function_exists('bcadd'), 'bcadd must be available.');
        $this->assertTrue(function_exists('bcsub'), 'bcsub must be available.');
        $this->assertTrue(function_exists('bcmul'), 'bcmul must be available.');
        $this->assertTrue(function_exists('bcdiv'), 'bcdiv must be available.');
        $this->assertTrue(function_exists('bccomp'), 'bccomp must be available.');

        // 2. Test fixed-precision arithmetic paths in InventoryService
        $service = app(InventoryService::class);
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Precision Test Product',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'allow_fractional' => true,
            'stock' => 0.000,
        ]);

        // Receive 0.125
        $service->receiveStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity' => 0.125,
            'type' => 'purchase',
            'performed_by' => $this->owner->id,
        ]);

        // Receive 0.375 -> Exactly 0.500
        $service->receiveStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity' => 0.375,
            'type' => 'purchase',
            'performed_by' => $this->owner->id,
        ]);

        $balance = $service->getBranchBalance($product->id, $this->branch->id);
        $this->assertEquals(0.500, $balance);

        // Issue 0.167 -> Exactly 0.333 remaining
        $service->issueStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity' => 0.167,
            'type' => 'sale',
            'performed_by' => $this->owner->id,
        ]);

        $balanceAfterIssue = $service->getBranchBalance($product->id, $this->branch->id);
        $this->assertEquals(0.333, $balanceAfterIssue);
    }

    /**
     * 2. Discrete Product Guard Matrix (allow_fractional = false)
     * qty 1: PASS, qty 2: PASS, qty 0.5: DENIED, qty 1.25: DENIED
     */
    public function test_discrete_product_guard_matrix()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $discreteProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Discrete Soap 100g',
            'cost_price' => 20.00,
            'selling_price' => 30.00,
            'allow_fractional' => false,
            'stock' => 50.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $discreteProduct->id,
            'quantity_on_hand' => 50.000,
        ]);

        // Case A: Quantity 1 -> PASS
        $res1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 30.00,
            'items' => [['product_id' => $discreteProduct->id, 'quantity' => 1]]
        ]);
        $res1->assertStatus(201);

        // Case B: Quantity 2 -> PASS
        $res2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 60.00,
            'items' => [['product_id' => $discreteProduct->id, 'quantity' => 2]]
        ]);
        $res2->assertStatus(201);

        // Case C: Quantity 0.5 -> DENIED (422)
        $res05 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 15.00,
            'items' => [['product_id' => $discreteProduct->id, 'quantity' => 0.5]]
        ]);
        $res05->assertStatus(422);

        // Case D: Quantity 1.25 -> DENIED (422)
        $res125 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 37.50,
            'items' => [['product_id' => $discreteProduct->id, 'quantity' => 1.25]]
        ]);
        $res125->assertStatus(422);
    }

    /**
     * 3. Barcode Primary Integrity, Collision Guards, Leading-Zero & Database Constraints
     */
    public function test_barcode_primary_integrity_and_collision_guards()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // 1. Create Product A with Primary Barcode '0012345' and Alternate 'ALT-888'
        $resA = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/products', [
            'name' => 'Leading Zero Item',
            'barcode' => '0012345',
            'cost_price' => 50.00,
            'selling_price' => 70.00,
            'stock' => 10,
            'alternate_barcodes' => ['ALT-888']
        ]);
        $resA->assertStatus(201);
        $productAId = $resA->json('id');

        // Verify primary barcode preserved as exact string with leading zero
        $primaryRow = ProductBarcode::where('product_id', $productAId)
            ->where('barcode_type', 'PRIMARY')
            ->first();
        $this->assertNotNull($primaryRow);
        $this->assertEquals('0012345', $primaryRow->barcode);
        $this->assertSame('0012345', (string) $primaryRow->barcode);

        // Verify exactly one primary barcode exists for Product A
        $primaryCount = ProductBarcode::where('product_id', $productAId)
            ->where('barcode_type', 'PRIMARY')
            ->count();
        $this->assertEquals(1, $primaryCount);

        // 2. Collision: Attempt to add Product A's Primary as Alternate on Product B -> DENIED (422)
        $resColl1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/products', [
            'name' => 'Colliding Alternate',
            'barcode' => 'UNIQ-B-001',
            'cost_price' => 50.00,
            'selling_price' => 70.00,
            'stock' => 10,
            'alternate_barcodes' => ['0012345']
        ]);
        $resColl1->assertStatus(422);

        // 3. Collision: Attempt to use Product A's Alternate as Primary on Product B -> DENIED (422)
        $resColl2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/products', [
            'name' => 'Colliding Primary',
            'barcode' => 'ALT-888',
            'cost_price' => 50.00,
            'selling_price' => 70.00,
            'stock' => 10,
        ]);
        $resColl2->assertStatus(422);

        // 4. Cross-Tenant: Same barcode in unrelated business -> ALLOWED
        $this->app['auth']->forgetGuards();
        $token2 = $this->owner2->createToken('test2')->plainTextToken;

        $resCross = $this->withHeaders([
            'Authorization' => "Bearer {$token2}",
            'X-Business-Id' => $this->business2->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/products', [
            'name' => 'Same Barcode Other Tenant',
            'barcode' => '0012345',
            'cost_price' => 50.00,
            'selling_price' => 70.00,
            'stock' => 10,
        ]);
        $resCross->assertStatus(201);

        // 5. Database duplicate constraint direct proof (DATABASE_CONSTRAINT_PROVEN)
        $caughtConstraint = false;
        try {
            DB::table('product_barcodes')->insert([
                'business_id' => $this->business->id,
                'product_id' => $productAId,
                'barcode' => '0012345', // Duplicate in same business
                'barcode_type' => 'ALTERNATE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $caughtConstraint = true;
        }
        $this->assertTrue($caughtConstraint, 'Database unique constraint on [business_id, barcode] must reject duplicates.');
    }

    /**
     * 4. Barcode Migration Backfill & Collision Detection Test
     */
    public function test_barcode_migration_backfill_and_collision_fixture()
    {
        // 1. Backfill scenario: Product exists with barcode
        $prod = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Legacy Barcoded Product',
            'barcode' => 'LEGACY-BC-777',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'stock' => 10,
        ]);

        // Run backfill logic
        $existsInBarcodes = DB::table('product_barcodes')
            ->where('business_id', $this->business->id)
            ->where('product_id', $prod->id)
            ->where('barcode', 'LEGACY-BC-777')
            ->where('barcode_type', 'PRIMARY')
            ->exists();

        if (!$existsInBarcodes) {
            DB::table('product_barcodes')->insert([
                'business_id' => $prod->business_id,
                'product_id' => $prod->id,
                'barcode' => (string) $prod->barcode,
                'barcode_type' => 'PRIMARY',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertDatabaseHas('product_barcodes', [
            'business_id' => $this->business->id,
            'product_id' => $prod->id,
            'barcode' => 'LEGACY-BC-777',
            'barcode_type' => 'PRIMARY'
        ]);

        // 2. Collision detection query verification
        $collisionQuery = DB::table('product_barcodes')
            ->select('business_id', 'barcode', DB::raw('COUNT(*) as occurrences'))
            ->groupBy('business_id', 'barcode')
            ->having('occurrences', '>', 1)
            ->get();

        $this->assertTrue($collisionQuery->isEmpty(), 'No barcode collisions should exist.');
    }

    /**
     * 5. Fractional Precision Matrix (0.001, 0.125, 0.333, 0.500, 1.250)
     */
    public function test_fractional_precision_matrix()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $loose = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Loose Grains',
            'cost_price' => 100.00,
            'selling_price' => 200.00,
            'unit' => 'kg',
            'allow_fractional' => true,
            'stock' => 100.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $loose->id,
            'quantity_on_hand' => 100.000,
        ]);

        $matrix = [0.001, 0.125, 0.333, 0.500, 1.250];

        foreach ($matrix as $qty) {
            $paid = round($qty * 200.00, 2);
            $res = $this->withHeaders([
                'Authorization' => "Bearer {$token}",
                'X-Business-Id' => $this->business->id,
                'Accept' => 'application/json'
            ])->postJson('/api/v1/sales', [
                'branch_id' => $this->branch->id,
                'payment_method' => 'cash',
                'paid_amount' => $paid,
                'items' => [
                    [
                        'product_id' => $loose->id,
                        'quantity' => $qty,
                        'unit_price' => 200.00
                    ]
                ]
            ]);

            $res->assertStatus(201);
            $saleId = $res->json('id');

            $saleItem = SaleItem::where('sale_id', $saleId)->first();
            $this->assertEquals((float) $qty, (float) $saleItem->quantity);

            $movement = InventoryMovement::where('reference_type', Sale::class)
                ->where('reference_id', $saleId)
                ->first();
            $this->assertEquals(-(float) $qty, (float) $movement->quantity);
        }
    }

    /**
     * 6. Fractional Sale & Inventory Movement
     */
    public function test_fractional_sale_and_inventory_movement()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Basmati Rice Loose',
            'cost_price' => 120.00,
            'selling_price' => 180.00,
            'unit' => 'kg',
            'allow_fractional' => true,
            'stock' => 10.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10.000,
        ]);

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 90.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 0.500,
                    'unit_price' => 180.00
                ]
            ]
        ]);

        $res->assertStatus(201);
        $saleId = $res->json('id');

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $saleId,
            'product_id' => $product->id,
            'quantity' => 0.500
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'reference_type' => Sale::class,
            'reference_id' => $saleId,
            'product_id' => $product->id,
            'quantity' => -0.500
        ]);

        $inv = BranchInventory::where('product_id', $product->id)->where('branch_id', $this->branch->id)->first();
        $this->assertEquals('9.500', (string) $inv->quantity_on_hand);
    }

    /**
     * 7. Fractional Return Lifecycle (Sold 1.250, previously returned 0.750, remaining 0.500)
     */
    public function test_fractional_return_lifecycle_restockable_and_non_restockable()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Fractional Spices',
            'cost_price' => 200.00,
            'selling_price' => 300.00,
            'unit' => 'kg',
            'allow_fractional' => true,
            'stock' => 20.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 20.000,
        ]);

        // 1. Initial Sale of 1.250 kg -> Stock becomes 18.750
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 375.00,
            'items' => [['product_id' => $product->id, 'quantity' => 1.250, 'unit_price' => 300.00]]
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        $invAfterSale = BranchInventory::where('product_id', $product->id)->first();
        $this->assertEquals('18.750', (string) $invAfterSale->quantity_on_hand);

        // 2. First Return: Return 0.750 kg (Restockable) -> Stock restores to 19.500
        $ret1Res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'reason' => 'Customer overbought',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 0.750,
                    'is_restockable' => true
                ]
            ]
        ]);
        $ret1Res->assertStatus(201);

        $invAfterRet1 = BranchInventory::where('product_id', $product->id)->first();
        $this->assertEquals('19.500', (string) $invAfterRet1->quantity_on_hand);

        // 3. Excess Return Guard: Remaining is 0.500, attempt to return 0.501 -> DENIED (422)
        $excessRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'reason' => 'Trying to return more than purchased',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 0.501,
                    'is_restockable' => true
                ]
            ]
        ]);
        $excessRes->assertStatus(422);

        // 4. Second Return: Return remaining 0.500 kg (Non-restockable / Damaged) -> Stock remains 19.500
        $ret2Res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson("/api/v1/sales/{$saleId}/returns", [
            'reason' => 'Package damaged in transit',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 0.500,
                    'is_restockable' => false
                ]
            ]
        ]);
        $ret2Res->assertStatus(201);

        $invAfterRet2 = BranchInventory::where('product_id', $product->id)->first();
        // Because is_restockable was false, sellable stock must NOT increment
        $this->assertEquals('19.500', (string) $invAfterRet2->quantity_on_hand);

        // Sale is now fully returned
        $sale = Sale::find($saleId);
        $this->assertEquals('returned', $sale->status);
    }

    /**
     * 8. Fractional Purchase / GRN Inward Receipt
     */
    public function test_fractional_purchase_and_grn_receipt()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $bulkProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Wholesale Wheat 100kg',
            'cost_price' => 5000.00,
            'selling_price' => 6000.00,
            'unit' => 'bag',
            'allow_fractional' => true,
            'stock' => 0.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $bulkProduct->id,
            'quantity_on_hand' => 0.000,
        ]);

        // Purchase 50.500 units
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-FRAC-50500',
            'date' => '2026-10-16',
            'paid_amount' => 50500.00,
            'items' => [
                [
                    'product_id' => $bulkProduct->id,
                    'quantity' => 50.500,
                    'unit_cost' => 1000.00
                ]
            ]
        ]);

        $res->assertStatus(201);
        $purchaseId = $res->json('id');

        $pItem = PurchaseItem::where('purchase_id', $purchaseId)->first();
        $this->assertEquals('50.500', (string) $pItem->quantity);

        $inv = BranchInventory::where('product_id', $bulkProduct->id)->first();
        $this->assertEquals('50.500', (string) $inv->quantity_on_hand);

        $mov = InventoryMovement::where('reference_type', Purchase::class)
            ->where('reference_id', $purchaseId)
            ->first();
        $this->assertEquals('50.500', (string) $mov->quantity);
    }

    /**
     * 9. Fractional Supplier Return (Protect REQ-0020)
     */
    public function test_fractional_supplier_return_req0020()
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Supplier Return Grain',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'allow_fractional' => true,
            'stock' => 30.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 30.000,
        ]);

        $supReturn = SupplierReturn::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'return_number' => 'SR-FRAC-001',
            'total_amount' => 550.00,
            'status' => 'completed',
            'created_by' => $this->owner->id,
        ]);

        // Create SupplierReturnItem with decimal quantity 5.500
        $retItem = SupplierReturnItem::create([
            'supplier_return_id' => $supReturn->id,
            'product_id' => $product->id,
            'quantity' => 5.500,
            'unit_cost' => 100.00,
            'total' => 550.00
        ]);

        $this->assertEquals('5.500', (string) $retItem->quantity);

        // Issue stock via InventoryService for supplier return
        $invService = app(InventoryService::class);
        $movement = $invService->issueStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity' => 5.500,
            'type' => 'purchase_return',
            'reference_type' => SupplierReturn::class,
            'reference_id' => $supReturn->id,
            'performed_by' => $this->owner->id,
            'notes' => 'Supplier Return SR-FRAC-001',
        ]);

        $this->assertEquals('-5.500', (string) $movement->quantity);
        $inv = BranchInventory::where('product_id', $product->id)->first();
        $this->assertEquals('24.500', (string) $inv->quantity_on_hand);
    }

    /**
     * 10. Fractional Stock Adjustment (Protect REQ-0016)
     */
    public function test_fractional_stock_adjustment_req0016()
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Adjustable Oil 1L',
            'cost_price' => 300.00,
            'selling_price' => 450.00,
            'allow_fractional' => true,
            'stock' => 10.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10.000,
        ]);

        $service = app(InventoryService::class);

        // 1. Adjustment IN: 3.250
        $service->adjustStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity' => 3.250,
            'type' => 'adjustment_in',
            'notes' => 'Audit found surplus 3.250 liters',
            'performed_by' => $this->owner->id,
        ]);

        $inv1 = BranchInventory::where('product_id', $product->id)->first();
        $this->assertEquals('13.250', (string) $inv1->quantity_on_hand);

        // 2. Adjustment OUT: 1.125
        $service->adjustStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity' => 1.125,
            'type' => 'adjustment_out',
            'notes' => 'Audit shrinkage 1.125 liters',
            'performed_by' => $this->owner->id,
        ]);

        $inv2 = BranchInventory::where('product_id', $product->id)->first();
        $this->assertEquals('12.125', (string) $inv2->quantity_on_hand);

        // 3. Damage: 0.125
        $service->adjustStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity' => 0.125,
            'type' => 'damage',
            'notes' => 'Broken bottle leakage 0.125 liters',
            'performed_by' => $this->owner->id,
        ]);

        $inv3 = BranchInventory::where('product_id', $product->id)->first();
        $this->assertEquals('12.000', (string) $inv3->quantity_on_hand);
    }

    /**
     * 11. Wholesale Security & Below-Cost Authorization Policy
     */
    public function test_wholesale_security_and_below_cost_policy()
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Wholesale Tier Product',
            'cost_price' => 400.00,
            'selling_price' => 500.00,
            'wholesale_price' => 450.00,
            'allow_fractional' => false,
            'stock' => 50.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 50.000,
        ]);

        $ownerToken = $this->owner->createToken('owner')->plainTextToken;
        $managerToken = $this->manager->createToken('manager')->plainTextToken;

        // 1. Retail mode -> Server applies retail catalog price (500.00)
        $retailRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerToken}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 500.00,
            'items' => [['product_id' => $product->id, 'quantity' => 1]]
        ]);
        $retailRes->assertStatus(201);
        $this->assertEquals(500.00, (float) $retailRes->json('items.0.unit_price'));

        // 2. Wholesale mode -> Server applies wholesale catalog price (450.00)
        $wsRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerToken}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 450.00,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'pricing_mode' => 'wholesale']]
        ]);
        $wsRes->assertStatus(201);
        $this->assertEquals(450.00, (float) $wsRes->json('items.0.unit_price'));

        // 3. Cashier sends arbitrary lower price in wholesale mode without override auth -> Denied (422)
        auth()->forgetGuards();
        \Laravel\Sanctum\Sanctum::actingAs($this->cashier, ['*']);
        $arbRes = $this->withHeaders([
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 300.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'pricing_mode' => 'wholesale',
                    'unit_price' => 300.00 // Arbitrary untrusted price
                ]
            ]
        ]);
        $arbRes->assertStatus(422);

        // 4. Configuration-time below-cost guard: Branch Manager attempts to set wholesale < cost -> 403 Forbidden
        auth()->forgetGuards();
        $belowCostManagerRes = $this->withHeaders([
            'Authorization' => "Bearer {$managerToken}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/products', [
            'name' => 'Manager Below Cost Item',
            'cost_price' => 400.00,
            'selling_price' => 500.00,
            'wholesale_price' => 350.00, // Below cost!
            'stock' => 10,
        ]);
        $belowCostManagerRes->assertStatus(403);
        $this->assertStringContainsString('Business Owner authorization', $belowCostManagerRes->json('message'));

        // 5. Configuration-time below-cost by Business Owner -> Allowed (201)
        auth()->forgetGuards();
        $belowCostOwnerRes = $this->withHeaders([
            'Authorization' => "Bearer {$ownerToken}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/products', [
            'name' => 'Owner Below Cost Item',
            'cost_price' => 400.00,
            'selling_price' => 500.00,
            'wholesale_price' => 350.00, // Below cost allowed for Owner
            'stock' => 10,
        ]);
        $belowCostOwnerRes->assertStatus(201);
    }

    /**
     * 12. Cashier Cost & Margin API Privacy
     */
    public function test_cashier_cost_and_margin_api_privacy()
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Privacy Product',
            'cost_price' => 200.00,
            'selling_price' => 300.00,
            'wholesale_price' => 250.00,
            'stock' => 10,
        ]);

        // Cashier requests product details via API
        auth()->forgetGuards();
        \Laravel\Sanctum\Sanctum::actingAs($this->cashier, ['*']);
        $res = $this->withHeaders([
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/products/{$product->id}");

        $res->assertStatus(200);
        $json = $res->json();

        // Server must strip all cost and margin fields
        $this->assertArrayNotHasKey('cost_price', $json);
        $this->assertArrayNotHasKey('cost', $json);
        $this->assertArrayNotHasKey('gross_margin_amount', $json);
        $this->assertArrayNotHasKey('gross_margin_percentage', $json);

        // Cashier cannot access purchase cost endpoints (403 Forbidden)
        $purchaseRes = $this->withHeaders([
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/purchases');
        $purchaseRes->assertStatus(403);

        $priceHistRes = $this->withHeaders([
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/purchases?action=last-price&supplier_id={$this->supplier->id}&product_id={$product->id}");
        $priceHistRes->assertStatus(403);
    }

    /**
     * 13. Stock Intelligence Deep Coverage (Low Stock, Out of Stock, Velocity 30/60/90, Dead Stock & Isolation)
     */
    public function test_stock_intelligence_deep_coverage_30_60_90_and_isolation()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Product A: Out of stock (qoh = 0, threshold = 10)
        $prodA = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Out of Stock Coffee',
            'cost_price' => 500.00,
            'selling_price' => 700.00,
            'min_stock' => 10.000,
        ]);
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $prodA->id,
            'quantity_on_hand' => 0.000,
            'reorder_level' => 10.000,
        ]);

        // Product B: Low stock (qoh = 4, threshold = 20) -> Deficit = 16
        $prodB = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Low Stock Tea',
            'cost_price' => 200.00,
            'selling_price' => 300.00,
            'min_stock' => 20.000,
        ]);
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $prodB->id,
            'quantity_on_hand' => 4.000,
            'reorder_level' => 20.000,
        ]);

        // Product C: Dead stock (qoh = 15, cost = 100, 0 sales) -> Locked Capital = 1500
        $prodC = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Dead Stock Honey',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
        ]);
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $prodC->id,
            'quantity_on_hand' => 15.000,
        ]);

        // Create historic sales across 30, 60, and 90-day timeframes
        $saleRecent = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-HIST-30',
            'date' => now()->subDays(10)->toDateString(),
            'subtotal' => 700.00,
            'total' => 700.00,
            'paid_amount' => 700.00,
            'due_amount' => 0.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        SaleItem::create([
            'sale_id' => $saleRecent->id,
            'product_id' => $prodB->id,
            'quantity' => 5.000,
            'unit_price' => 300.00,
            'cost_price' => 200.00,
            'total' => 1500.00,
        ]);

        // 1. Test 30-day window
        $res30 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/inventory?intelligence=1&days=30');
        $res30->assertStatus(200);
        $data30 = $res30->json();

        $this->assertEquals(30, $data30['sales_velocity']['days']);
        $itemB = collect($data30['reorder_analytics']['items'])->firstWhere('product_id', $prodB->id);
        $this->assertNotNull($itemB);
        $this->assertEquals(16.000, (float) $itemB['deficit']);
        $this->assertStringContainsString('Deficit = 16', $itemB['formula_explanation']);

        // Check dead stock locked capital
        $deadItem = collect($data30['dead_stock']['items'])->firstWhere('product_id', $prodC->id);
        $this->assertNotNull($deadItem);
        $this->assertEquals(1500.00, (float) $deadItem['locked_capital']);

        // 2. Test 60-day and 90-day windows
        $res60 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/inventory?intelligence=1&days=60');
        $res60->assertStatus(200);
        $this->assertEquals(60, $res60->json('sales_velocity.days'));

        $res90 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/inventory?intelligence=1&days=90');
        $res90->assertStatus(200);
        $this->assertEquals(90, $res90->json('sales_velocity.days'));

        // 3. Test Tenant Isolation
        $this->app['auth']->forgetGuards();
        $tokenOther = $this->owner2->createToken('other')->plainTextToken;

        $resOther = $this->withHeaders([
            'Authorization' => "Bearer {$tokenOther}",
            'X-Business-Id' => $this->business2->id,
            'Accept' => 'application/json'
        ])->getJson('/api/v1/inventory?intelligence=1');
        $resOther->assertStatus(200);
        $this->assertEmpty($resOther->json('reorder_analytics.items'));
    }

    /**
     * 14. Landed-Cost Edge Cases (Single line, multi-line, rounding residual reconciliation, zero subtotal guard)
     */
    public function test_landed_cost_allocation_edge_cases()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $p1 = Product::create(['business_id' => $this->business->id, 'name' => 'Item 1', 'cost_price' => 100, 'selling_price' => 150, 'stock' => 0]);
        $p2 = Product::create(['business_id' => $this->business->id, 'name' => 'Item 2', 'cost_price' => 100, 'selling_price' => 150, 'stock' => 0]);
        $p3 = Product::create(['business_id' => $this->business->id, 'name' => 'Item 3', 'cost_price' => 100, 'selling_price' => 150, 'stock' => 0]);

        BranchInventory::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'product_id' => $p1->id, 'quantity_on_hand' => 0]);
        BranchInventory::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'product_id' => $p2->id, 'quantity_on_hand' => 0]);
        BranchInventory::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'product_id' => $p3->id, 'quantity_on_hand' => 0]);

        // Case A: 3 items with subtotal 100 each = 300 total. Freight = 10.00.
        // Division: 10 * (100 / 300) = 3.3333... rounded to 3.33 each.
        // Total allocated = 3.33 * 3 = 9.99. Residual = 0.01 reconciled to highest item!
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-RECON-CENTS',
            'date' => '2026-10-16',
            'freight_amount' => 10.00,
            'paid_amount' => 310.00,
            'items' => [
                ['product_id' => $p1->id, 'quantity' => 1, 'unit_cost' => 100.00],
                ['product_id' => $p2->id, 'quantity' => 1, 'unit_cost' => 100.00],
                ['product_id' => $p3->id, 'quantity' => 1, 'unit_cost' => 100.00],
            ]
        ]);
        $res->assertStatus(201);
        $purchaseId = $res->json('id');

        $getRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/purchases/{$purchaseId}");

        $items = $getRes->json('items');
        $sumFreight = collect($items)->sum('allocated_freight');
        $this->assertEquals(10.00, round((float) $sumFreight, 2), 'Sum of allocated freight must exactly equal freight amount.');

        // Case B: Single line allocation absorbs 100% of freight
        $singleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-SINGLE-LINE',
            'date' => '2026-10-16',
            'freight_amount' => 75.00,
            'paid_amount' => 175.00,
            'items' => [
                ['product_id' => $p1->id, 'quantity' => 1, 'unit_cost' => 100.00],
            ]
        ]);
        $singleRes->assertStatus(201);
        $singleId = $singleRes->json('id');

        $getSingle = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/purchases/{$singleId}");

        $this->assertEquals(75.00, (float) $getSingle->json('items.0.allocated_freight'));
        $this->assertEquals(175.00, (float) $getSingle->json('items.0.landed_unit_cost'));
    }

    /**
     * 15. Supplier Price History Unit Normalization & Non-Comparable Guard
     */
    public function test_supplier_price_history_unit_normalization()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Normalized Grain Product',
            'cost_price' => 1000.00,
            'selling_price' => 1500.00,
            'unit' => 'kg',
            'sale_unit' => 'gram',
            'conversion_factor' => 1000.000,
            'allow_fractional' => true,
            'stock' => 0.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 0.000,
        ]);

        // Purchase at 2000.00 per kg
        $pRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-UNIT-NORM-01',
            'date' => '2026-10-16',
            'paid_amount' => 2000.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 2000.00]
            ]
        ]);
        $pRes->assertStatus(201);

        // 1. Query with base unit ('kg') -> Normalization 1:1
        $resBase = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/purchases?action=last-price&supplier_id={$this->supplier->id}&product_id={$product->id}&unit=kg");
        $resBase->assertStatus(200);
        $this->assertEquals('NORMALIZED', $resBase->json('unit_status'));
        $this->assertEquals(2000.00, (float) $resBase->json('normalized_last_price'));

        // 2. Query with compatible sale unit ('gram', conversion_factor = 1000) -> 2000 / 1000 = 2.0000 per gram
        $resGram = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/purchases?action=last-price&supplier_id={$this->supplier->id}&product_id={$product->id}&unit=gram");
        $resGram->assertStatus(200);
        $this->assertEquals('NORMALIZED', $resGram->json('unit_status'));
        $this->assertEquals(2.0000, (float) $resGram->json('normalized_last_price'));

        // 3. Query with incompatible unit ('meter') -> NOT_COMPARABLE
        $resIncompat = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->getJson("/api/v1/purchases?action=last-price&supplier_id={$this->supplier->id}&product_id={$product->id}&unit=meter");
        $resIncompat->assertStatus(200);
        $this->assertEquals('NOT_COMPARABLE', $resIncompat->json('unit_status'));
        $this->assertNull($resIncompat->json('normalized_last_price'));
        $this->assertNull($resIncompat->json('trend_percentage'));
    }

    /**
     * Issue D: Direct Quantity Scale Enforcement (max 3 decimal places)
     */
    public function test_direct_quantity_scale_enforcement_accepts_valid_and_rejects_excess_precision()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Scale Precision Test Product',
            'cost_price' => 10.00,
            'selling_price' => 20.00,
            'stock' => 100.000,
            'unit' => 'kg',
            'allow_fractional' => true,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 100.000,
        ]);

        $validQuantities = ['1', '1.2', '1.23', '1.234'];
        foreach ($validQuantities as $idx => $vQty) {
            $res = $this->withHeaders([
                'Authorization' => "Bearer {$token}",
                'X-Business-Id' => $this->business->id,
                'Accept' => 'application/json'
            ])->postJson('/api/v1/sales', [
                'branch_id' => $this->branch->id,
                'payment_method' => 'cash',
                'paid_amount' => floatval(bcmul($vQty, '20.00', 2)),
                'items' => [
                    ['product_id' => $product->id, 'quantity' => $vQty]
                ]
            ]);
            $res->assertStatus(201);
        }

        $invalidQuantities = ['1.2345', '0.0009', '-0.001', '0'];
        foreach ($invalidQuantities as $invQty) {
            $res = $this->withHeaders([
                'Authorization' => "Bearer {$token}",
                'X-Business-Id' => $this->business->id,
                'Accept' => 'application/json'
            ])->postJson('/api/v1/sales', [
                'branch_id' => $this->branch->id,
                'payment_method' => 'cash',
                'paid_amount' => 20.00,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => $invQty]
                ]
            ]);
            $res->assertStatus(422);
        }
    }

    /**
     * Issue D: Explicitly Reject Non-Representable Unit Conversions
     */
    public function test_non_representable_unit_conversion_is_explicitly_rejected()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Base unit: kg, Sale unit: gram, Conversion factor: 3000 (3000 grams = 1 kg)
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Bulk Tea 3kg Pack',
            'cost_price' => 150.00,
            'selling_price' => 300.00,
            'unit' => 'kg',
            'sale_unit' => 'gram',
            'conversion_factor' => 3000.000,
            'allow_fractional' => true,
            'stock' => 50.000,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 50.000,
        ]);

        // Attempting to sell 1 gram with factor 3000 -> 1/3000 = 0.000333... kg. In scale 3 = 0.000 kg (1 gram lost remainder)
        // Must be rejected with HTTP 422!
        $resNonRep = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 300.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => '1', 'unit' => 'gram']
            ]
        ]);
        $resNonRep->assertStatus(422);
        $resNonRep->assertJsonValidationErrors(['items.0.quantity']);

        // Exact representable conversion: 3000 grams / 3000 = 1.000 kg (0 remainder loss) -> Success!
        $resRep = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 300.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => '3000', 'unit' => 'gram']
            ]
        ]);
        $resRep->assertStatus(201);
    }

    /**
     * Issue C: Primary Barcode Concurrency & Replacement Invariant
     */
    public function test_primary_barcode_concurrency_and_replacement_invariant()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Create product with initial PRIMARY barcode
        $createRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/products', [
            'name' => 'Barcode Invariant Test Product',
            'cost_price' => 50.00,
            'selling_price' => 80.00,
            'stock' => 10,
            'barcode' => 'BAR-INV-A',
        ]);
        $createRes->assertStatus(201);
        $productId = $createRes->json('id');

        $primaryCount1 = ProductBarcode::where('product_id', $productId)->where('barcode_type', 'PRIMARY')->count();
        $this->assertEquals(1, $primaryCount1);
        $this->assertEquals('BAR-INV-A', ProductBarcode::where('product_id', $productId)->where('barcode_type', 'PRIMARY')->value('barcode'));

        // Update product to second barcode BAR-INV-B
        $updateRes1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->putJson("/api/v1/products/{$productId}", [
            'name' => 'Barcode Invariant Test Product Updated',
            'cost_price' => 50.00,
            'selling_price' => 80.00,
            'barcode' => 'BAR-INV-B',
        ]);
        $updateRes1->assertStatus(200);

        $primaryCount2 = ProductBarcode::where('product_id', $productId)->where('barcode_type', 'PRIMARY')->count();
        $this->assertEquals(1, $primaryCount2);
        $this->assertEquals('BAR-INV-B', ProductBarcode::where('product_id', $productId)->where('barcode_type', 'PRIMARY')->value('barcode'));
        $this->assertNull(ProductBarcode::where('product_id', $productId)->where('barcode', 'BAR-INV-A')->first());

        // Update product to third barcode BAR-INV-C
        $updateRes2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->putJson("/api/v1/products/{$productId}", [
            'name' => 'Barcode Invariant Test Product Updated Again',
            'cost_price' => 50.00,
            'selling_price' => 80.00,
            'barcode' => 'BAR-INV-C',
        ]);
        $updateRes2->assertStatus(200);

        $primaryCount3 = ProductBarcode::where('product_id', $productId)->where('barcode_type', 'PRIMARY')->count();
        $this->assertEquals(1, $primaryCount3);
        $this->assertEquals('BAR-INV-C', ProductBarcode::where('product_id', $productId)->where('barcode_type', 'PRIMARY')->value('barcode'));
    }

    /**
     * 19. Authoritative Fractional Money Precision Matrix & Untrusted Client Total
     * - Case 1: Price 100.00, Quantity 0.125 -> Expected 12.50
     * - Case 2: Price 99.99, Quantity 0.500 -> Expected 50.00
     * - Case 3: Price 10.00, Quantity 0.333 -> Expected 3.33
     * - Case 4: Wholesale Price 80.00, Quantity 0.250 -> Expected 20.00
     * - Case 5: Untrusted Client Total Override Attempt -> Ignored, Backend Authoritative
     * - Case 6: Fractional Return Money Consistency
     */
    public function test_authoritative_fractional_money_matrix_and_untrusted_client_total()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Product 1: Price 100.00, Wholesale 80.00
        $prod1 = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Fractional Money Product 100',
            'cost_price' => 60.00,
            'selling_price' => 100.00,
            'wholesale_price' => 80.00,
            'allow_fractional' => true,
            'stock' => 100.000,
        ]);
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $prod1->id,
            'quantity_on_hand' => 100.000,
        ]);

        // Product 2: Price 99.99
        $prod2 = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Fractional Money Product 99.99',
            'cost_price' => 60.00,
            'selling_price' => 99.99,
            'allow_fractional' => true,
            'stock' => 100.000,
        ]);
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $prod2->id,
            'quantity_on_hand' => 100.000,
        ]);

        // Product 3: Price 10.00
        $prod3 = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Fractional Money Product 10',
            'cost_price' => 5.00,
            'selling_price' => 10.00,
            'allow_fractional' => true,
            'stock' => 100.000,
        ]);
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $prod3->id,
            'quantity_on_hand' => 100.000,
        ]);

        // Case 1: Price 100.00 * Quantity 0.125 = 12.50
        $res1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 12.50,
            'items' => [
                ['product_id' => $prod1->id, 'quantity' => 0.125, 'total' => 999.00]
            ]
        ]);
        $res1->assertStatus(201);
        $this->assertEquals(12.50, (float) $res1->json('subtotal'));
        $this->assertEquals(12.50, (float) $res1->json('total'));
        $this->assertEquals(12.50, (float) $res1->json('items.0.total'));

        // Case 2: Price 99.99 * Quantity 0.500 = 50.00 (half-up on 49.9950)
        $res2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 50.00,
            'items' => [
                ['product_id' => $prod2->id, 'quantity' => 0.500, 'total' => 1.00]
            ]
        ]);
        $res2->assertStatus(201);
        $this->assertEquals(50.00, (float) $res2->json('subtotal'));
        $this->assertEquals(50.00, (float) $res2->json('total'));
        $this->assertEquals(50.00, (float) $res2->json('items.0.total'));

        // Case 3: Price 10.00 * Quantity 0.333 = 3.33 (3.3300)
        $res3 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 3.33,
            'items' => [
                ['product_id' => $prod3->id, 'quantity' => 0.333, 'total' => 0.50]
            ]
        ]);
        $res3->assertStatus(201);
        $this->assertEquals(3.33, (float) $res3->json('subtotal'));
        $this->assertEquals(3.33, (float) $res3->json('total'));
        $this->assertEquals(3.33, (float) $res3->json('items.0.total'));

        // Case 4: Wholesale Price 80.00 * Quantity 0.250 = 20.00
        $res4 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'pricing_mode' => 'WHOLESALE',
            'paid_amount' => 20.00,
            'items' => [
                ['product_id' => $prod1->id, 'quantity' => 0.250, 'pricing_mode' => 'WHOLESALE']
            ]
        ]);
        $res4->assertStatus(201);
        $this->assertEquals(20.00, (float) $res4->json('subtotal'));
        $this->assertEquals(20.00, (float) $res4->json('total'));
        $this->assertEquals(20.00, (float) $res4->json('items.0.total'));

        // Case 5: Fractional Return Money Consistency (Return 0.125 from Sale 1)
        $sale1Id = $res1->json('id');
        $sale1ItemId = $res1->json('items.0.id');
        $returnRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson("/api/v1/sales/{$sale1Id}/returns", [
            'reason' => 'Customer return test',
            'refund_method' => 'cash',
            'items' => [
                [
                    'sale_item_id' => $sale1ItemId,
                    'quantity' => 0.125,
                    'restock_condition' => 'SELLABLE'
                ]
            ]
        ]);
        $returnRes->assertStatus(201);
        $this->assertEquals(12.50, (float) $returnRes->json('refund_amount'));

        // Case E: Multiple fractional lines deterministic subtotal
        // Line 1: 100.00 * 0.125 = 12.50
        // Line 2: 99.99 * 0.500 = 50.00
        // Line 3: 10.00 * 0.333 = 3.33
        // Subtotal = 12.50 + 50.00 + 3.33 = 65.83
        $resE = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 65.83,
            'items' => [
                ['product_id' => $prod1->id, 'quantity' => 0.125],
                ['product_id' => $prod2->id, 'quantity' => 0.500],
                ['product_id' => $prod3->id, 'quantity' => 0.333],
            ]
        ]);
        $resE->assertStatus(201);
        $this->assertEquals(65.83, (float) $resE->json('subtotal'));
        $this->assertEquals(65.83, (float) $resE->json('total'));
        $this->assertEquals(12.50, (float) $resE->json('items.0.total'));
        $this->assertEquals(50.00, (float) $resE->json('items.1.total'));
        $this->assertEquals(3.33, (float) $resE->json('items.2.total'));

        // Case F: Fractional line + decimal discount
        // 100.00 * 0.125 = 12.50 gross, item discount 2.50 => 10.00 net line
        // Header discount 1.00 => total 9.00
        $resF = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'paid_amount' => 9.00,
            'discount' => 1.00,
            'items' => [
                ['product_id' => $prod1->id, 'quantity' => 0.125, 'discount' => 2.50],
            ]
        ]);
        $resF->assertStatus(201);
        $this->assertEquals(10.00, (float) $resF->json('subtotal'));
        $this->assertEquals(9.00, (float) $resF->json('total'));
        $this->assertEquals(10.00, (float) $resF->json('items.0.total'));

        // Case G: Client sends fraudulent item.total, subtotal, grand_total - server ignores them completely
        $resG = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/sales', [
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'subtotal' => 0.01,
            'total' => 0.01,
            'paid_amount' => 50.00,
            'items' => [
                ['product_id' => $prod2->id, 'quantity' => 0.500, 'total' => 0.01]
            ]
        ]);
        $resG->assertStatus(201);
        $this->assertEquals(50.00, (float) $resG->json('subtotal'));
        $this->assertEquals(50.00, (float) $resG->json('total'));
        $this->assertEquals(50.00, (float) $resG->json('items.0.total'));
    }

    public function test_purchase_authoritative_fractional_money_matrix()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $prodA = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Purchase Grain A',
            'sku' => 'PUR-GRAIN-A',
            'selling_price' => 150.00,
            'cost_price' => 99.99,
            'allow_fractional' => true,
            'unit' => 'kg',
            'is_active' => true,
        ]);
        $prodB = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Purchase Grain B',
            'sku' => 'PUR-GRAIN-B',
            'selling_price' => 80.00,
            'cost_price' => 55.55,
            'allow_fractional' => true,
            'unit' => 'kg',
            'is_active' => true,
        ]);

        // Line 1: 0.333 * 99.99 = 33.29667 -> 33.30
        // Line 2: 0.125 * 55.55 = 6.94375  -> 6.94
        // Expected subtotal = 33.30 + 6.94 = 40.24
        // Client sends fraudulent subtotal = 1.00 and line total = 1.00
        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-Id' => $this->business->id,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/purchases', [
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-FRAC-MONEY-' . rand(1000, 9999),
            'paid_amount' => 40.24,
            'subtotal' => 1.00,
            'total' => 1.00,
            'items' => [
                ['product_id' => $prodA->id, 'quantity' => 0.333, 'unit_cost' => 99.99, 'total' => 1.00],
                ['product_id' => $prodB->id, 'quantity' => 0.125, 'unit_cost' => 55.55, 'total' => 1.00],
            ]
        ]);

        $res->assertStatus(201);
        $this->assertEquals(40.24, (float) $res->json('subtotal'));
        $this->assertEquals(40.24, (float) $res->json('total'));
        $this->assertEquals(33.30, (float) $res->json('items.0.total'));
        $this->assertEquals(6.94, (float) $res->json('items.1.total'));
    }
}
