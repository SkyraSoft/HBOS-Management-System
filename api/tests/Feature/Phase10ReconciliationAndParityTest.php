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
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Carbon\Carbon;

class Phase10ReconciliationAndParityTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'Reconciliation Corp']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);
        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'role' => 'Business Owner',
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $token = $this->owner->createToken('test')->plainTextToken;
        $this->headers = [
            'Authorization' => "Bearer $token",
            'X-Business-ID' => (string) $this->business->id,
        ];
    }

    /**
     * Section AK: Reporting Numeric Reconciliation (AC-10.27)
     */
    public function test_reporting_numeric_reconciliation_against_authoritative_ledgers()
    {
        $cash = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Cash Account',
            'type' => 'cash',
            'opening_balance' => 10000.00,
            'balance' => 10000.00,
            'status' => 'active',
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Widget A',
            'sku' => 'SKU-WID-A',
            'cost_price' => 50.00,
            'retail_price' => 100.00,
            'track_quantity' => true,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 20,
        ]);

        // Sale: 2 units @ 100.00 = 200.00, COGS = 100.00
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-RECON-1',
            'date' => now()->toDateString(),
            'subtotal' => 200.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => 200.00,
            'paid_amount' => 200.00,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'cost_price' => 50.00,
            'subtotal' => 200.00,
            'total' => 200.00,
        ]);

        // Expense: 50.00
        $category = ExpenseCategory::create(['business_id' => $this->business->id, 'name' => 'Office Supplies']);
        Expense::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'category_id' => $category->id,
            'category' => 'Office Supplies',
            'financial_account_id' => $cash->id,
            'amount' => 50.00,
            'date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        // Query sales report
        $salesReport = $this->withHeaders($this->headers)->getJson('/api/v1/reports/sales?from=' . now()->toDateString() . '&to=' . now()->toDateString());
        $salesReport->assertStatus(200);

        $summary = $salesReport->json('summary');
        $this->assertEquals(200.00, (float) $summary['gross_sales']);
        $this->assertEquals(200.00, (float) $summary['net_sales']);
        $this->assertEquals(100.00, (float) $summary['net_cogs']);
        $this->assertEquals(100.00, (float) $summary['gross_profit']);

        // Query dashboard stats
        $dash = $this->withHeaders($this->headers)->getJson('/api/v1/dashboard/stats');
        $dash->assertStatus(200);
        $this->assertNotNull($dash->json());
    }

    /**
     * Section AL: Business Switch Report Reconciliation
     */
    public function test_business_switching_reconciliation_prevents_stale_or_cross_tenant_totals()
    {
        $bizB = Business::create(['name' => 'Foreign Biz Beta']);
        $branchB = Branch::create(['business_id' => $bizB->id, 'name' => 'Beta HQ', 'is_primary' => true]);
        $this->owner->businesses()->attach($bizB->id);

        // Sale in A: 100.00
        Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-AAA-1',
            'date' => now()->toDateString(),
            'total' => 100.00,
            'subtotal' => 100.00,
            'paid_amount' => 100.00,
            'status' => 'completed',
        ]);

        // Sale in B: 500.00
        Sale::create([
            'business_id' => $bizB->id,
            'branch_id' => $branchB->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-BBB-1',
            'date' => now()->toDateString(),
            'total' => 500.00,
            'subtotal' => 500.00,
            'paid_amount' => 500.00,
            'status' => 'completed',
        ]);

        // 1. Query A -> Expect 100.00
        $resA1 = $this->withHeaders($this->headers)->getJson('/api/v1/reports/sales?from=' . now()->toDateString() . '&to=' . now()->toDateString());
        $resA1->assertStatus(200);
        $this->assertEquals(100.00, (float) $resA1->json('summary.net_sales'));

        // 2. Query B with X-Business-ID = B -> Expect 500.00
        $headersB = $this->headers;
        $headersB['X-Business-ID'] = (string) $bizB->id;
        $resB = $this->withHeaders($headersB)->getJson('/api/v1/reports/sales?from=' . now()->toDateString() . '&to=' . now()->toDateString());
        $resB->assertStatus(200);
        $this->assertEquals(500.00, (float) $resB->json('summary.net_sales'));

        // 3. Switch back to A -> Expect 100.00 immediately without stale cache
        $resA2 = $this->withHeaders($this->headers)->getJson('/api/v1/reports/sales?from=' . now()->toDateString() . '&to=' . now()->toDateString());
        $resA2->assertStatus(200);
        $this->assertEquals(100.00, (float) $resA2->json('summary.net_sales'));
    }

    /**
     * Section AM: Timezone Boundary Tests (Asia/Karachi) (AC-10.34)
     */
    public function test_timezone_boundary_at_midnight_prevents_utc_day_drift()
    {
        // 23:59:59 PKT on 2026-09-20 is 2026-09-20 18:59:59 UTC
        $dateStr = '2026-09-20';

        $saleToday = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-PKT-TODAY',
            'date' => $dateStr,
            'total' => 300.00,
            'subtotal' => 300.00,
            'paid_amount' => 300.00,
            'status' => 'completed',
        ]);

        $saleTomorrow = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-PKT-TOMORROW',
            'date' => '2026-09-21',
            'total' => 400.00,
            'subtotal' => 400.00,
            'paid_amount' => 400.00,
            'status' => 'completed',
        ]);

        $report = $this->withHeaders($this->headers)->getJson("/api/v1/reports/sales?from={$dateStr}&to={$dateStr}");
        $report->assertStatus(200);

        $this->assertEquals(300.00, (float) $report->json('summary.net_sales'));
        $sales = $report->json('sales');
        $this->assertCount(1, $sales);
        $this->assertEquals('INV-PKT-TODAY', $sales[0]['invoice_number']);
    }

    /**
     * Section AN: Monetary Precision Consistency (AC-10.35)
     */
    public function test_monetary_precision_preserves_two_decimals_across_edge_amounts()
    {
        $amounts = [0.01, 0.10, 0.29, 99999.99];

        foreach ($amounts as $amount) {
            $customer = Customer::create([
                'business_id' => $this->business->id,
                'name' => "Precision Cust {$amount}",
                'phone' => '0300' . rand(1000000, 9999999),
                'opening_balance' => $amount,
            ]);

            $customer->refresh();
            $this->assertEquals($amount, (float) $customer->opening_balance);

            $res = $this->withHeaders($this->headers)->getJson("/api/v1/customers/{$customer->id}");
            $res->assertStatus(200);
            $this->assertEquals($amount, (float) $res->json('opening_balance'));
        }
    }

    /**
     * Section AO: CSV Export Formula Injection Neutralization
     */
    public function test_csv_export_neutralizes_formula_injection_characters()
    {
        // Create product with formula injection in name
        $prod = Product::create([
            'business_id' => $this->business->id,
            'name' => '=CMD(\'calc\')',
            'sku' => '+FORMULA-SKU',
            'cost_price' => 10.00,
            'retail_price' => 20.00,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $prod->id,
            'quantity_on_hand' => 5,
        ]);

        // Request CSV export of inventory
        $res = $this->withHeaders($this->headers)->get('/api/v1/reports/inventory?format=csv');
        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $res->streamedContent();
        
        // Assert formula prefix "'" is prepended to neutralize formula injection
        $this->assertStringContainsString("'=CMD('calc')", $content);
        $this->assertStringContainsString("'+FORMULA-SKU", $content);
    }
}
