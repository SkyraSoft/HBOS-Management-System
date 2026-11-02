<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use App\Models\FinancialAccount;
use App\Models\AccountMovement;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Services\CashRegisterService;
use App\Services\AccountMovementService;
use App\Services\CustomerPaymentService;
use App\Services\ExpenseService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CashRegisterSessionTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected Branch $branch2;
    protected User $owner;
    protected User $manager;
    protected User $cashier;
    protected FinancialAccount $drawer;
    protected FinancialAccount $bank;
    protected CashRegisterService $registerService;
    protected AccountMovementService $movementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Cash Register Test Biz']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Store', 'is_primary' => true]);
        $this->branch2 = Branch::create(['business_id' => $this->business->id, 'name' => 'Second Branch', 'is_primary' => false]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->manager = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->assignRole('Branch Manager');

        $this->cashier = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->assignRole('Salesperson');

        $this->drawer = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 5000.00,
            'balance' => 5000.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->bank = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'HBL Bank Account',
            'type' => 'bank',
            'opening_balance' => 50000.00,
            'balance' => 50000.00,
            'is_default' => false,
            'status' => 'active',
        ]);

        $this->registerService = app(CashRegisterService::class);
        $this->movementService = app(AccountMovementService::class);
    }

    public function test_owner_zero_staff_can_open_and_close_session()
    {
        $session = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        $this->assertEquals('open', $session->status);
        $this->assertEquals('5000.00', (string) $session->opening_float);
        $this->assertEquals($this->drawer->id, $session->financial_account_id);

        $closed = $this->registerService->closeRegisterSession(
            $this->owner,
            $session,
            '5000.00'
        );

        $this->assertEquals('closed', $closed->status);
        $this->assertEquals('0.00', (string) $closed->cash_variance);
    }

    public function test_opening_float_must_match_drawer_ledger_balance()
    {
        $this->expectException(ValidationException::class);
        $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '4500.00' // does not match 5000.00
        );
    }

    public function test_cashier_receives_privacy_safe_mismatch_message()
    {
        try {
            $this->registerService->openRegisterSession(
                $this->cashier,
                $this->business->id,
                $this->branch->id,
                '4500.00'
            );
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $msg = $e->errors()['opening_float'][0];
            $this->assertStringContainsString('Opening cash does not match the registered drawer balance', $msg);
            $this->assertStringNotContainsString('5000', $msg); // Must not reveal actual ledger balance to cashier
        }
    }

    public function test_cross_tenant_drawer_resolution_is_denied()
    {
        $otherBiz = Business::create(['name' => 'Foreign Biz']);
        $otherBranch = Branch::create(['business_id' => $otherBiz->id, 'name' => 'Foreign Branch']);
        $otherDrawer = FinancialAccount::create([
            'business_id' => $otherBiz->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Foreign Drawer',
            'type' => 'cash',
            'opening_balance' => 1000,
            'balance' => 1000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);
        $this->registerService->resolveDrawer($this->business->id, $this->branch->id, $otherDrawer->id);
    }

    public function test_concurrent_double_open_is_denied()
    {
        $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        $this->expectException(ValidationException::class);
        $this->registerService->openRegisterSession(
            $this->manager,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );
    }

    public function test_multiple_sequential_sessions_allowed_on_same_day()
    {
        // First session
        $s1 = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );
        $this->registerService->closeRegisterSession($this->owner, $s1, '5000.00');

        // Second sequential session on same drawer and day
        $s2 = $this->registerService->openRegisterSession(
            $this->manager,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        $this->assertEquals('open', $s2->status);
        $this->assertNotEquals($s1->id, $s2->id);
    }

    public function test_expected_cash_derives_strictly_from_account_movements()
    {
        $session = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        // Post Cash Inflow (e.g. Sale of 1500)
        $this->movementService->postInflow($this->drawer, [
            'branch_id' => $this->branch->id,
            'movement_category' => 'sale_pos',
            'amount' => '1500.00',
            'description' => 'Test Sale Inflow',
            'user_id' => $this->owner->id,
        ]);

        // Post Cash Outflow (e.g. Expense of 300)
        $this->movementService->postOutflow($this->drawer, [
            'branch_id' => $this->branch->id,
            'movement_category' => 'expense',
            'amount' => '300.00',
            'description' => 'Test Tea Expense',
            'user_id' => $this->owner->id,
        ]);

        // Expected Cash = 5000 + 1500 - 300 = 6200.00
        $expected = $this->registerService->calculateExpectedCash($session);
        $this->assertEquals('6200.00', $expected);

        // Voided movement must be excluded
        $voidedMov = $this->movementService->postInflow($this->drawer, [
            'branch_id' => $this->branch->id,
            'movement_category' => 'sale_pos',
            'amount' => '500.00',
            'description' => 'Accidental Inflow',
            'user_id' => $this->owner->id,
        ]);
        $this->movementService->voidMovement($voidedMov, $this->owner->id, 'Customer Cancelled');

        $expectedAfterVoid = $this->registerService->calculateExpectedCash($session);
        $this->assertEquals('6200.00', $expectedAfterVoid);
    }

    public function test_cash_discrepancy_requires_reason_and_reconciles_treasury_balance()
    {
        $session = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        // Counted cash is 4900 (100 shortage)
        // Attempt close without reason -> fail
        try {
            $this->registerService->closeRegisterSession($this->owner, $session, '4900.00');
            $this->fail('Expected ValidationException when closing with discrepancy and no reason');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('variance_reason', $e->errors());
        }

        // Close with reason
        $closed = $this->registerService->closeRegisterSession(
            $this->owner,
            $session,
            '4900.00',
            'Lost Rs 100 note behind counter'
        );

        // Historical pre-adjustment variance preserved on session
        $this->assertEquals('5000.00', (string) $closed->closing_cash_expected);
        $this->assertEquals('4900.00', (string) $closed->closing_cash_counted);
        $this->assertEquals('-100.00', (string) $closed->cash_variance);
        $this->assertNotNull($closed->reconciliation_movement_id);

        // Drawer balance matches counted cash post-reconciliation!
        $this->assertEquals('4900.00', $this->movementService->calculateBalance($this->drawer->fresh()));
    }

    public function test_cash_overage_reconciliation_inflow()
    {
        $session = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        // Counted cash is 5050 (50 overage)
        $closed = $this->registerService->closeRegisterSession(
            $this->owner,
            $session,
            '5050.00',
            'Found extra Rs 50 coin'
        );

        $this->assertEquals('50.00', (string) $closed->cash_variance);
        $this->assertEquals('5050.00', $this->movementService->calculateBalance($this->drawer->fresh()));
    }

    public function test_double_close_is_denied()
    {
        $session = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        $this->registerService->closeRegisterSession($this->owner, $session, '5000.00');

        $this->expectException(ValidationException::class);
        $this->registerService->closeRegisterSession($this->owner, $session, '5000.00');
    }

    public function test_api_cashier_blind_close_hides_expected_cash_and_variance()
    {
        $session = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        $response = $this->actingAs($this->cashier)
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/cash-register-sessions/close', [
                'session_id' => $session->id,
                'closing_cash_counted' => 5000.00,
            ]);

        $response->assertStatus(200);
        $response->assertJsonMissing(['closing_cash_expected']);
        $response->assertJsonMissing(['cash_variance']);
        $response->assertJsonPath('session.status', 'closed');
    }

    public function test_migration_down_rollback_guard_blocks_deletion_if_adjustment_exists()
    {
        // Insert an adjustment movement
        $session = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        $closed = $this->registerService->closeRegisterSession(
            $this->owner,
            $session,
            '4950.00',
            'Test Shortage'
        );

        $migration = require database_path('migrations/2026_10_18_000001_create_cash_register_sessions_table.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("contains 'adjustment' category rows which would cause data loss");
        $migration->down();
    }

    public function test_same_second_sequential_session_boundary_and_zero_double_attribution()
    {
        $exactSecond = \Carbon\Carbon::parse('2026-10-18 12:00:00');
        \Carbon\Carbon::setTestNow($exactSecond);

        // 1. Session A opens at 12:00:00
        $sessionA = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        // 2. A cash sale movement occurs at the exact same second 12:00:00
        $this->movementService->postInflow($this->drawer, [
            'branch_id' => $this->branch->id,
            'movement_category' => 'sale_pos',
            'amount' => '500.00',
            'date' => '2026-10-18',
            'description' => 'Same-second sale',
            'idempotency_key' => 'same_sec_sale_1',
        ]);

        // 3. Session A closes at the exact same second 12:00:00
        $closedA = $this->registerService->closeRegisterSession(
            $this->owner,
            $sessionA,
            '5500.00',
            null
        );

        $this->assertEquals('5500.00', (string) $closedA->closing_cash_expected);
        $this->assertEquals('0.00', (string) $closedA->cash_variance);

        // 4. Session B opens at the exact same second 12:00:00
        $sessionB = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5500.00'
        );

        // 5. Calculate expected cash for Session B at 12:00:00
        // The sale at 12:00:00 must NOT be double-counted in Session B (it belongs to Session A only)
        $expectedB = $this->registerService->calculateExpectedCash($sessionB, $exactSecond);
        $this->assertEquals('5500.00', $expectedB);

        // Now advance time to 12:05:00 and add a Session B movement
        $fiveMinutesLater = \Carbon\Carbon::parse('2026-10-18 12:05:00');
        \Carbon\Carbon::setTestNow($fiveMinutesLater);

        $this->movementService->postInflow($this->drawer, [
            'branch_id' => $this->branch->id,
            'movement_category' => 'sale_pos',
            'amount' => '300.00',
            'date' => '2026-10-18',
            'description' => 'Session B sale',
            'idempotency_key' => 'session_b_sale_1',
        ]);

        $expectedBAfter = $this->registerService->calculateExpectedCash($sessionB, $fiveMinutesLater);
        $this->assertEquals('5800.00', $expectedBAfter);

        \Carbon\Carbon::setTestNow(); // Reset Mock
    }

    public function test_reconciliation_adjustment_boundary_and_zero_self_inclusion()
    {
        $closeTime = \Carbon\Carbon::parse('2026-10-18 14:00:00');
        \Carbon\Carbon::setTestNow($closeTime);

        $session = $this->registerService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '5000.00'
        );

        // Close with a 100.00 shortage -> reconciliation outflow adjustment created
        $closed = $this->registerService->closeRegisterSession(
            $this->owner,
            $session,
            '4900.00',
            'Shortage Rs 100'
        );

        $this->assertEquals('5000.00', (string) $closed->closing_cash_expected);
        $this->assertEquals('-100.00', (string) $closed->cash_variance);
        $this->assertNotNull($closed->reconciliation_movement_id);

        // Re-calculate expected cash on closed session -> must not self-include reconciliation adjustment!
        $recalculatedExpected = $this->registerService->calculateExpectedCash($closed);
        $this->assertEquals('5000.00', $recalculatedExpected);

        \Carbon\Carbon::setTestNow();
    }
}
