<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\BulkSyncService;
use App\Services\CustomerAccountService;
use App\Services\InventoryService;
use App\Services\ReportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CriticalEndpointPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected Product $product;
    protected Customer $customer;
    protected FinancialAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Performance Test Superstore']);
        setPermissionsTeamId($this->business->id);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Performance Main Branch',
            'code' => 'PERF-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Fast Lookup Item',
            'sku' => 'PERF-SKU-001',
            'barcode' => '8961234567890',
            'cost_price' => 100,
            'retail_price' => 150,
            'selling_price' => 150,
            'is_active' => true
        ]);

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Frequent Buyer',
            'phone' => '03009988776',
            'opening_balance' => 0.00,
            'balance' => 0.00
        ]);

        $this->account = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 5000.00,
            'balance' => 5000.00,
            'is_default' => true,
            'status' => 'active'
        ]);
    }

    public function test_pos_product_search_performance_meets_sla()
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $t0 = microtime(true);
        $products = Product::where('business_id', $this->business->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', '%Lookup%')
                  ->orWhere('sku', 'like', '%PERF%')
                  ->orWhere('barcode', '8961234567890');
            })
            ->limit(20)
            ->get();
        $elapsedMs = (microtime(true) - $t0) * 1000;
        $queries = count(DB::getQueryLog());

        $this->assertNotEmpty($products);
        $this->assertLessThan(50.0, $elapsedMs, "POS Product Search SLA violated (> 50ms)");
        $this->assertLessThanOrEqual(3, $queries, "POS Product Search query count exceeded");
    }

    public function test_customer_ledger_recalc_performance_meets_sla()
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $t0 = microtime(true);
        $calcService = app(CustomerAccountService::class);
        $balance = $calcService->recalculateBalance($this->customer);
        $elapsedMs = (microtime(true) - $t0) * 1000;
        $queries = count(DB::getQueryLog());

        $this->assertLessThan(50.0, $elapsedMs, "Customer Ledger Recalc SLA violated (> 50ms)");
        $this->assertLessThanOrEqual(5, $queries, "Customer Ledger Recalc query count exceeded");
    }

    public function test_profitability_report_performance_meets_sla()
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $t0 = microtime(true);
        $reportService = app(ReportService::class);
        $report = $reportService->getProfitabilityReport(
            $this->business->id,
            $this->branch->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );
        $elapsedMs = (microtime(true) - $t0) * 1000;
        $queries = count(DB::getQueryLog());

        $this->assertNotNull($report);
        $this->assertLessThan(300.0, $elapsedMs, "Profitability Report SLA violated (> 300ms)");
        $this->assertLessThanOrEqual(10, $queries, "Profitability Report query count exceeded");
    }

    public function test_bulk_ingestion_replay_performance_meets_sla()
    {
        $syncService = app(BulkSyncService::class);

        // Receive stock first so sale can be fulfilled
        app(InventoryService::class)->receiveStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_cost' => 100.00,
            'type' => 'purchase',
            'performed_by' => $this->owner->id,
        ]);

        $payload = [
            'sales' => [
                [
                    'idempotency_key' => 'PERF-REPLAY-KEY-001',
                    'branch_id' => $this->branch->id,
                    'invoice_number' => 'INV-PERF-001',
                    'date' => now()->toDateString(),
                    'subtotal' => 150,
                    'tax' => 0,
                    'discount' => 0,
                    'total' => 150,
                    'paid_amount' => 150,
                    'payment_method' => 'cash',
                    'payment_type' => 'cash',
                    'payment_status' => 'paid',
                    'financial_account_id' => $this->account->id,
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => 1,
                            'unit_price' => 150,
                            'total' => 150
                        ]
                    ]
                ]
            ]
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();

        $t0 = microtime(true);
        $result = $syncService->ingestBulkTransactions($payload, $this->owner, $this->business->id);
        $elapsedMs = (microtime(true) - $t0) * 1000;
        $queries = count(DB::getQueryLog());

        $this->assertEquals(1, $result['total_processed']);
        $this->assertLessThan(250.0, $elapsedMs, "Bulk Ingestion SLA violated (> 250ms)");
        $this->assertLessThanOrEqual(35, $queries, "Bulk Ingestion query count exceeded");
    }
}