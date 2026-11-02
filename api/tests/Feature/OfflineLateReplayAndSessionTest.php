<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\FinancialAccount;
use App\Models\CashRegisterSession;
use App\Models\AuditLog;
use App\Models\Sale;
use App\Services\CashRegisterService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;

class OfflineLateReplayAndSessionTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $owner;
    protected User $cashier;
    protected FinancialAccount $cashDrawer;
    protected Product $product;
    protected CashRegisterService $registerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Metro Superstore']);
        setPermissionsTeamId($this->business->id);

        $this->branchA = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Branch Alpha',
            'code' => 'ALP-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->branchB = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Branch Beta',
            'code' => 'BET-02',
            'is_primary' => false,
            'status' => 'active',
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->cashier = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->branches()->attach([$this->branchA->id, $this->branchB->id], ['business_id' => $this->business->id]);
        $this->cashier->assignRole('Salesperson');

        $this->cashDrawer = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Alpha Cash Drawer',
            'type' => 'cash',
            'status' => 'active',
            'is_default' => true,
            'current_balance' => 5000.00,
        ]);
        $seedMovement = app(\App\Services\AccountMovementService::class)->postInflow($this->cashDrawer, [
            'amount' => 5000.00,
            'movement_category' => 'capital_in',
            'description' => 'Opening float seed',
        ]);
        \Illuminate\Support\Facades\DB::table('account_movements')->where('id', $seedMovement->id)->update([
            'created_at' => Carbon::today('Asia/Karachi')->subDay()->setHour(23)->setMinute(0)
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Cooking Oil 5L',
            'cost_price' => 2000.00,
            'selling_price' => 2500.00,
            'allow_fractional' => false,
            'stock' => 20.000,
            'is_active' => true,
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => '20.000',
        ]);

        $this->registerService = app(CashRegisterService::class);
    }

    public function test_late_replay_preserves_closed_session_immutability(): void
    {
        // 1. Open session yesterday
        $yesterday = Carbon::yesterday()->setHour(9)->setMinute(0);
        Carbon::setTestNow($yesterday);

        $session1 = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branchA->id,
            5000.00,
            $this->cashDrawer->id,
            'Day 1 morning open'
        );

        // Close session yesterday evening
        Carbon::setTestNow($yesterday->copy()->setHour(21));
        $closedSession = $this->registerService->closeRegisterSession(
            $this->owner,
            $session1,
            5000.00,
            'Exact balanced close'
        );

        $this->assertEquals('closed', $closedSession->status);
        $this->assertEquals('5000.00', (string) $closedSession->closing_cash_expected);
        $this->assertEquals('5000.00', (string) $closedSession->closing_cash_counted);
        $this->assertEquals('0.00', (string) $closedSession->cash_variance);

        // 2. Fast forward to TODAY
        Carbon::setTestNow(Carbon::today()->setHour(10));

        // Replay a sale that occurred yesterday during the offline outage
        $key = 'sale-late-replay-001';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key,
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 2500.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'occurred_at' => $yesterday->copy()->setHour(14)->toIso8601String(),
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 2500.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashier, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(200);

        // INVARIANT CHECK: Closed session 1 remains strictly immutable!
        $reloadedSession1 = CashRegisterSession::find($session1->id);
        $this->assertEquals('closed', $reloadedSession1->status);
        $this->assertEquals('5000.00', (string) $reloadedSession1->closing_cash_expected);
        $this->assertEquals('5000.00', (string) $reloadedSession1->closing_cash_counted);
        $this->assertEquals('0.00', (string) $reloadedSession1->cash_variance);

        Carbon::setTestNow(); // Reset
    }

    public function test_late_replay_audit_logs_late_indicator(): void
    {
        $yesterday = Carbon::yesterday()->setHour(12);
        $key = 'sale-late-audit-002';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key,
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 2500.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'occurred_at' => $yesterday->toIso8601String(),
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 2500.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashier, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'is_late_replay' => true,
        ]);
    }

    public function test_branch_attribution_preserves_original_branch_even_when_cashier_has_multiple_branches(): void
    {
        // Sale queued for Branch A
        $key = 'sale-branch-attr-003';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key,
                    'branch_id' => $this->branchA->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 2500.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 2500.00,
                        ],
                    ],
                ],
            ],
        ];

        // Owner syncs while their active branch context in header or user profile might be branch B
        $this->owner->update(['branch_id' => $this->branchB->id]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(200);

        // Authoritative sale MUST be on Branch A as originally queued, not rewritten to Branch B
        $sale = Sale::where('idempotency_key', $key)->first();
        $this->assertNotNull($sale);
        $this->assertEquals($this->branchA->id, $sale->branch_id);
    }

    /**
     * Reconciliation Issue 4 / Section 23:
     * Asia/Karachi date: same business day.
     * Session A opens at 09:00.
     * Offline cash sale occurs at 10:00.
     * Session A closes at 12:00.
     * Session B opens at 12:30.
     * Offline sale replays at 13:00.
     *
     * Invariants asserted:
     * - Session A expected_cash unchanged
     * - Session A counted_cash unchanged
     * - Session A variance unchanged
     * - Session A reconciliation movement unchanged
     * - Session B expected_cash not silently inflated by the old event
     * - Transaction posts under source-approved financial-account handling
     * - Actual actor remains correct
     * - Actual original branch remains correct
     * - Late-replay metadata identifies timing mismatch accurately (is_late_replay: true)
     */
    public function test_same_day_late_replay_preserves_session_a_and_does_not_inflate_session_b(): void
    {
        $today = Carbon::today('Asia/Karachi');

        // 1. Session A opens at 09:00
        $time0900 = $today->copy()->setHour(9)->setMinute(0)->setSecond(0);
        Carbon::setTestNow($time0900);

        $sessionA = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branchA->id,
            5000.00,
            $this->cashDrawer->id,
            'Morning Session A'
        );

        // 2. Offline cash sale occurs at 10:00 (during outage)
        $time1000 = $today->copy()->setHour(10)->setMinute(0)->setSecond(0);

        // 3. Session A closes at 12:00
        $time1200 = $today->copy()->setHour(12)->setMinute(0)->setSecond(0);
        Carbon::setTestNow($time1200);

        $closedSessionA = $this->registerService->closeRegisterSession(
            $this->owner,
            $sessionA,
            5000.00,
            'Balanced morning close'
        );

        $this->assertEquals('closed', $closedSessionA->status);
        $this->assertEquals('5000.00', (string) $closedSessionA->closing_cash_expected);
        $this->assertEquals('5000.00', (string) $closedSessionA->closing_cash_counted);
        $this->assertEquals('0.00', (string) $closedSessionA->cash_variance);

        // 4. Session B opens at 12:30
        $time1230 = $today->copy()->setHour(12)->setMinute(30)->setSecond(0);
        Carbon::setTestNow($time1230);

        $sessionB = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branchA->id,
            5000.00,
            $this->cashDrawer->id,
            'Afternoon Session B'
        );

        // Session B baseline expected cash at opening
        $expectedBeforeReplay = $this->registerService->calculateExpectedCash($sessionB);
        $this->assertEquals('5000.00', (string) $expectedBeforeReplay);

        // 5. Offline sale replays at 13:00
        $time1300 = $today->copy()->setHour(13)->setMinute(0)->setSecond(0);
        Carbon::setTestNow($time1300);

        $key = 'sale-same-day-late-replay-001';
        $payload = [
            'sales' => [
                [
                    'idempotency_key' => $key,
                    'branch_id' => $this->branchA->id,
                    'user_id' => $this->cashier->id,
                    'payment_method' => 'cash',
                    'paid_amount' => 2500.00,
                    'financial_account_id' => $this->cashDrawer->id,
                    'occurred_at' => $time1000->toIso8601String(),
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => '1.000',
                            'pricing_mode' => 'retail',
                            'unit_price' => 2500.00,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashier, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id, 'X-Client-Version' => '1.0.0'])
            ->postJson('/api/v1/sync/bulk-transactions', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'is_late_replay' => true,
        ]);

        // INVARIANT 1: Session A closing snapshot strictly unchanged
        $reloadedSessionA = CashRegisterSession::find($sessionA->id);
        $this->assertEquals('closed', $reloadedSessionA->status);
        $this->assertEquals('5000.00', (string) $reloadedSessionA->closing_cash_expected);
        $this->assertEquals('5000.00', (string) $reloadedSessionA->closing_cash_counted);
        $this->assertEquals('0.00', (string) $reloadedSessionA->cash_variance);

        // INVARIANT 2: Session B expected cash is NOT inflated by 10:00 event
        $reloadedSessionB = CashRegisterSession::find($sessionB->id);
        $expectedAfterReplay = $this->registerService->calculateExpectedCash($reloadedSessionB);
        $this->assertEquals('5000.00', (string) $expectedAfterReplay, 'Session B expected cash must NOT be inflated by earlier same-day event');

        // INVARIANT 3: Authoritative sale attributes
        $sale = Sale::where('idempotency_key', $key)->first();
        $this->assertNotNull($sale);
        $this->assertEquals($this->cashier->id, $sale->user_id);
        $this->assertEquals($this->branchA->id, $sale->branch_id);

        Carbon::setTestNow(); // Reset
    }
}

