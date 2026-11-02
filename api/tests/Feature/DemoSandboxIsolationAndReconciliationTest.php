<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Expense;
use App\Models\AccountMovement;

class DemoSandboxIsolationAndReconciliationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Authoritative Demo Fixture Manifest Integrity & Mathematical Consistency
     */
    public function test_demo_fixture_manifest_loads_and_reconciles()
    {
        $manifestPath = base_path('../docs/release/hbos_demo_fixture_manifest.json');
        $this->assertFileExists($manifestPath, 'Demo manifest must exist at docs/release/hbos_demo_fixture_manifest.json');

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);
        $this->assertEquals('2.0.0', $manifest['manifest_version']);

        $single = $manifest['templates']['single_store'];
        $this->assertEquals('Al-Madina General Store', $single['business_name']);
        $this->assertEquals('Karachi', $single['city']);
        $this->assertEquals(8500.00, (float)$single['opening_cash_float']);
        
        $this->assertCount(4, $single['brands'], 'Must have exactly 4 brands');
        $this->assertCount(8, $single['categories'], 'Must have exactly 8 categories');
        $this->assertCount(16, $single['subcategories'], 'Must have exactly 16 subcategories');
        $this->assertCount(32, $single['product_families'], 'Must have exactly 32 product families');
        $this->assertCount(44, $single['products'], 'Must have 44 stockable SKU records');
        $this->assertCount(8, $single['barcode_aliases'], 'Must have 8 barcode aliases');
        $this->assertCount(4, $single['customers'], 'Must have exactly 4 customers in single store template');
        $this->assertCount(3, $single['suppliers'], 'Must have exactly 3 suppliers in single store template');

        // Verify initial customer receivables match summary
        $sumReceivables = array_sum(array_column($single['customers'], 'current_balance'));
        $this->assertEquals(12400.00, (float)$sumReceivables);
        $this->assertEquals(12400.00, (float)$single['initial_total_receivables']);

        // Verify initial supplier payables match summary
        $sumPayables = array_sum(array_column($single['suppliers'], 'current_balance'));
        $this->assertEquals(105000.00, (float)$sumPayables);
        $this->assertEquals(105000.00, (float)$single['initial_total_payables']);

        // Verify Multi-Store template structure
        $multi = $manifest['templates']['multi_store'];
        $this->assertEquals('Al-Madina Supermarket Chain', $multi['business_name']);
        $this->assertCount(3, $multi['branches']);
        $this->assertEquals('Clifton Main Store', $multi['branches'][0]['name']);
        $this->assertTrue($multi['branches'][0]['is_primary']);
        $this->assertCount(132, $multi['branch_inventories'], 'Must have 132 branch inventory records (44 prods x 3 branches)');
        $this->assertCount(1, $multi['in_transit_transfers']);
        $this->assertEquals('in_transit', $multi['in_transit_transfers'][0]['status']);
    }

    /**
     * Test 2: Clean Zero-Data Business Registration Guarantee
     * Registering a real business creates ZERO sample products, sales, customers, or expenses.
     */
    public function test_real_registration_starts_with_zero_sample_records()
    {
        $resp = $this->postJson('/api/v1/auth/register', [
            'name' => 'Real Shop Owner',
            'email' => 'realowner@example.pk',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'business_name' => 'Real Karachi Corner Shop',
            'phone' => '03009876543',
        ]);

        $resp->assertStatus(201);
        $bizId = $resp->json('business.id');
        $this->assertNotNull($bizId);

        // Verify zero sample records exist for this real business
        $this->assertEquals(0, Product::where('business_id', $bizId)->count());
        $this->assertEquals(0, Sale::where('business_id', $bizId)->count());
        $this->assertEquals(0, Customer::where('business_id', $bizId)->count());
        $this->assertEquals(0, Supplier::where('business_id', $bizId)->count());
        $this->assertEquals(0, Expense::where('business_id', $bizId)->count());
        $this->assertEquals(0, AccountMovement::where('business_id', $bizId)->count());
    }

    /**
     * Test 3: Sandbox Security Barrier — Unauthenticated Demo Requests Cannot Mutate Server Database
     * Requests without a valid bearer token are rejected with 401 Unauthenticated.
     */
    public function test_demo_requests_without_bearer_token_fail_closed_on_server()
    {
        // Attempting to post a sale without auth token fails closed
        $saleResp = $this->postJson('/api/v1/sales', [
            'branch_id' => 1,
            'items' => [['product_id' => 1, 'quantity' => 2, 'unit_price' => 350]],
            'payment_method' => 'cash',
        ]);
        $saleResp->assertStatus(401);

        // Attempting to post an expense without auth token fails closed
        $expResp = $this->postJson('/api/v1/expenses', [
            'amount' => 500,
            'description' => 'Fake Demo Expense',
        ]);
        $expResp->assertStatus(401);

        // Attempting to sync offline transactions without auth token fails closed
        $syncResp = $this->postJson('/api/v1/sync/bulk-transactions', [
            'transactions' => [
                ['idempotency_key' => 'fake-uuid-001', 'type' => 'sale', 'payload' => []]
            ]
        ]);
        $syncResp->assertStatus(401);
    }

    /**
     * Test 4: All 44 Products have valid SKUs, Barcodes, Categories, and Positive Margins
     * And exactly 2 product families per subcategory across all 16 subcategories.
     */
    public function test_all_44_products_have_valid_skus_and_margins()
    {
        $manifestPath = base_path('../docs/release/hbos_demo_fixture_manifest.json');
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $products = $manifest['templates']['single_store']['products'];
        $subcategories = $manifest['templates']['single_store']['subcategories'];
        $families = $manifest['templates']['single_store']['product_families'];

        $this->assertCount(44, $products);
        $this->assertCount(16, $subcategories);
        $this->assertCount(32, $families);

        // Check exactly 2 families per subcategory
        foreach ($subcategories as $sub) {
            $matchingFamilies = array_filter($families, fn($f) => $f['subcategory_id'] === $sub['id']);
            $this->assertCount(2, $matchingFamilies, "Subcategory {$sub['name']} must have exactly 2 product families");
        }

        $skus = [];
        $barcodes = [];

        foreach ($products as $p) {
            $this->assertNotEmpty($p['sku'], "Product {$p['id']} must have SKU");
            $this->assertNotEmpty($p['barcode'], "Product {$p['id']} must have Barcode");
            $this->assertNotEmpty($p['name'], "Product {$p['id']} must have Name");
            $this->assertGreaterThan(0, $p['selling_price'], "Product {$p['id']} selling price must be > 0");
            $this->assertGreaterThan(0, $p['cost_price'], "Product {$p['id']} cost price must be > 0");
            $this->assertGreaterThan($p['cost_price'], $p['selling_price'], "Product {$p['id']} selling price must exceed cost price");
            $this->assertGreaterThan(0, $p['stock'], "Product {$p['id']} initial stock must be > 0");

            $this->assertNotContains($p['sku'], $skus, "SKU {$p['sku']} must be unique");
            $this->assertNotContains($p['barcode'], $barcodes, "Barcode {$p['barcode']} must be unique");

            $skus[] = $p['sku'];
            $barcodes[] = $p['barcode'];
        }
    }

    /**
     * Test 5: All Customer & Supplier Opening Balances Reconcile Exactly
     */
    public function test_all_customers_and_suppliers_reconcile_with_opening_balances()
    {
        $manifestPath = base_path('../docs/release/hbos_demo_fixture_manifest.json');
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $customers = $manifest['templates']['single_store']['customers'];
        $suppliers = $manifest['templates']['single_store']['suppliers'];

        // Customer 1: Tariq Mehmood opening balance must be 3500.00
        $tariq = $customers[0];
        $this->assertEquals('Tariq Mehmood', $tariq['name']);
        $this->assertEquals(3500.00, (float)$tariq['current_balance']);

        $totalCust = 0.0;
        foreach ($customers as $c) {
            $totalCust += (float)$c['current_balance'];
        }
        $this->assertEquals(12400.00, $totalCust);

        // Supplier 1: DailyHarvest Agriculture Trading Co. opening payable must be 45000.00
        $supp1 = $suppliers[0];
        $this->assertEquals('DailyHarvest Agriculture Trading Co.', $supp1['name']);
        $this->assertEquals(45000.00, (float)$supp1['current_balance']);

        $totalSupp = 0.0;
        foreach ($suppliers as $s) {
            $totalSupp += (float)$s['current_balance'];
        }
        $this->assertEquals(105000.00, $totalSupp);
    }

    /**
     * Test 6: Walkthrough Transaction Mathematical Reconciliation
     * Proves:
     * - Opening float PKR 8,500.00
     * - Sale: 2x Khyber Gold Danedar Tea 450g @ PKR 780.00 = PKR 1,560.00 cash
     *   -> Drawer cash: 8,500 + 1,560 = 10,060.00
     *   -> Khyber Tea stock: 85.000 - 2.000 = 83.000
     * - Customer recovery: Tariq Mehmood pays PKR 1,000.00 cash
     *   -> Tariq balance: 3,500.00 - 1,000.00 = 2,500.00
     *   -> Drawer cash: 10,060.00 + 1,000.00 = 11,060.00
     * - Register close: 11,060.00 counted -> discrepancy 0.00
     */
    public function test_walkthrough_transaction_arithmetic_matches_manifest_fixtures()
    {
        $manifestPath = base_path('../docs/release/hbos_demo_fixture_manifest.json');
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $products = $manifest['templates']['single_store']['products'];
        $tea = null;
        foreach ($products as $p) {
            if ($p['id'] === 14) {
                $tea = $p;
                break;
            }
        }
        $this->assertNotNull($tea);
        $this->assertEquals('Khyber Gold Danedar Tea 450g', $tea['name']);
        $this->assertEquals(780.00, (float)$tea['selling_price']);
        $this->assertEquals(85.000, (float)$tea['stock']);

        $openingFloat = 8500.00;
        $saleQty = 2.000;
        $saleAmount = $saleQty * $tea['selling_price']; // 1560.00
        $drawerAfterSale = $openingFloat + $saleAmount; // 10060.00
        $stockAfterSale = $tea['stock'] - $saleQty; // 83.000

        $this->assertEquals(1560.00, $saleAmount);
        $this->assertEquals(10060.00, $drawerAfterSale);
        $this->assertEquals(83.000, $stockAfterSale);

        $tariqOpening = 3500.00;
        $recoveryAmount = 1000.00;
        $tariqAfterPayment = $tariqOpening - $recoveryAmount; // 2500.00
        $drawerAfterRecovery = $drawerAfterSale + $recoveryAmount; // 11060.00

        $this->assertEquals(2500.00, $tariqAfterPayment);
        $this->assertEquals(11060.00, $drawerAfterRecovery);

        $closingCounted = 11060.00;
        $discrepancy = $closingCounted - $drawerAfterRecovery;
        $this->assertEquals(0.00, $discrepancy);
    }

    /**
     * Test 7: Multi-Store Branches & In-Transit Transfer Reconciliation
     */
    public function test_multi_store_branches_and_in_transit_transfers_reconcile()
    {
        $manifestPath = base_path('../docs/release/hbos_demo_fixture_manifest.json');
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $multi = $manifest['templates']['multi_store'];

        $this->assertEquals('Karachi', $multi['city']);
        $this->assertCount(3, $multi['branches']);
        $this->assertEquals('Clifton Main Store', $multi['branches'][0]['name']);
        $this->assertEquals('Saddar Express', $multi['branches'][1]['name']);
        $this->assertEquals('Gulshan Outlet', $multi['branches'][2]['name']);

        $transfer = $multi['in_transit_transfers'][0];
        $this->assertEquals('TRF-2026-001', $transfer['transfer_id']);
        $this->assertEquals(1, $transfer['source_branch_id']);
        $this->assertEquals(2, $transfer['destination_branch_id']);
        $this->assertEquals('Indus Gold Canola Cooking Oil 1 Litre Pouch', $transfer['items'][0]['product_name']);
        $this->assertEquals(20.000, (float)$transfer['items'][0]['quantity']);
    }
}
