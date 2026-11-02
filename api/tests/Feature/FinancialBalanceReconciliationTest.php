<?php

namespace Tests\Feature;

use App\Models\AccountMovement;
use App\Models\Branch;
use App\Models\Business;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountMovementService;
use App\Services\CashRegisterService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FinancialBalanceReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected FinancialAccount $cashDrawer;
    protected FinancialAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Financial Reconcile Store']);
        setPermissionsTeamId($this->business->id);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Branch',
            'code' => 'MAIN-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->cashDrawer = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Cash Drawer Account',
            'type' => 'cash',
            'opening_balance' => 0.00,
            'balance' => 0.00,
            'is_default' => true,
            'status' => 'active'
        ]);

        $this->bankAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Business Bank Account',
            'type' => 'bank',
            'opening_balance' => 10000.00,
            'balance' => 10000.00,
            'status' => 'active'
        ]);
    }

    public function test_financial_account_balance_reconciles_against_posted_movements()
    {
        $movementService = app(AccountMovementService::class);

        // 1. Inflow from cash sale: 2500 PKR
        $movementService->postInflow($this->cashDrawer, [
            'amount' => 2500.00,
            'movement_category' => 'sale_cash',
            'user_id' => $this->owner->id,
            'description' => 'Sale revenue',
            'reference_type' => Sale::class,
            'reference_id' => 101,
            'branch_id' => $this->branch->id,
        ]);

        // 2. Outflow for daily expense: 450.50 PKR
        $movementService->postOutflow($this->cashDrawer, [
            'amount' => 450.50,
            'movement_category' => 'expense',
            'user_id' => $this->owner->id,
            'description' => 'Store cleaning supplies',
            'branch_id' => $this->branch->id,
        ]);

        $this->cashDrawer->refresh();

        $storedBalance = floatval($this->cashDrawer->balance);

        // Independent calculation from posted movements
        $inflows = AccountMovement::where('account_id', $this->cashDrawer->id)
            ->where('type', 'inflow')
            ->where('status', 'posted')
            ->sum('amount');

        $outflows = AccountMovement::where('account_id', $this->cashDrawer->id)
            ->where('type', 'outflow')
            ->where('status', 'posted')
            ->sum('amount');

        $expectedBalance = floatval(bcsub((string)$inflows, (string)$outflows, 2));

        $this->assertEquals($expectedBalance, $storedBalance);
        $this->assertEquals(2049.50, $storedBalance);
    }

    public function test_customer_khata_balance_reconciles_exactly()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Tariq Mehmood',
            'phone' => '03219876543',
            'opening_balance' => 0.00,
            'balance' => 0.00
        ]);

        // Credit sale: 1500 PKR due
        Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-KHATA-001',
            'date' => now()->toDateString(),
            'subtotal' => 1500,
            'tax' => 0,
            'discount' => 0,
            'total' => 1500,
            'paid_amount' => 0,
            'due_amount' => 1500,
            'status' => 'completed',
            'payment_status' => 'unpaid',
            'payment_type' => 'khata'
        ]);

        // Payment received: 500 PKR
        CustomerPayment::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'user_id' => $this->owner->id,
            'payment_number' => 'CP-001',
            'financial_account_id' => $this->cashDrawer->id,
            'amount' => 500,
            'date' => now()->toDateString(),
            'status' => 'posted'
        ]);

        // Recalculate customer balance via canonical service
        $calcService = app(\App\Services\CustomerAccountService::class);
        $rawBalance = $calcService->recalculateBalance($customer);

        $this->assertEquals(1000.00, $rawBalance);
        $this->assertEquals(1000.00, floatval($customer->balance));

        // Run read-only diagnostic command
        $exitCode = Artisan::call('hbos:audit-khata-ledger-discrepancies');
        $this->assertEquals(0, $exitCode);
    }

    public function test_cash_session_closed_snapshot_is_immutable_under_late_replay()
    {
        $sessionService = app(CashRegisterService::class);

        // 1. Open Session A with float 0.00 (matching drawer balance 0.00)
        $sessionA = $sessionService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '0.00',
            $this->cashDrawer->id,
            'Morning Shift'
        );

        // 2. Close Session A with blind count 0.00
        $sessionService->closeRegisterSession(
            $this->owner,
            $sessionA,
            '0.00',
            'Closed on time'
        );

        $sessionA->refresh();
        $this->assertEquals('closed', $sessionA->status);
        $expectedClosingCash = $sessionA->closing_balance;
        $closedAt = $sessionA->closed_at;

        // 3. Open Session B
        $sessionB = $sessionService->openRegisterSession(
            $this->owner,
            $this->business->id,
            $this->branch->id,
            '0.00',
            $this->cashDrawer->id,
            'Afternoon Shift'
        );

        // 4. Simulate a late replay cash sale that occurred back during Session A's time window
        $lateSaleTime = Carbon::parse($sessionA->opened_at)->addMinutes(10);
        $sale = Sale::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-LATE-REPLAY-1',
            'date' => $lateSaleTime->toDateString(),
            'subtotal' => 600,
            'tax' => 0,
            'discount' => 0,
            'total' => 600,
            'paid_amount' => 600,
            'due_amount' => 0,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_type' => 'cash',
            'created_at' => $lateSaleTime
        ]);

        SalePayment::create([
            'business_id' => $this->business->id,
            'sale_id' => $sale->id,
            'financial_account_id' => $this->cashDrawer->id,
            'payment_method' => 'cash',
            'amount' => 600,
            'paid_at' => $lateSaleTime
        ]);

        // Post financial movement
        app(AccountMovementService::class)->postInflow($this->cashDrawer, [
            'amount' => 600.00,
            'movement_category' => 'sale_cash',
            'user_id' => $this->owner->id,
            'description' => 'Late replayed cash sale',
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'created_at' => $lateSaleTime,
        ]);

        // Assert Session A snapshot remains completely unchanged
        $sessionA->refresh();
        $this->assertEquals('closed', $sessionA->status);
        $this->assertEquals($expectedClosingCash, $sessionA->closing_balance);
        $this->assertEquals($closedAt, $sessionA->closed_at);

        // Assert Financial Account correctly holds the updated total
        $this->cashDrawer->refresh();
        $this->assertEquals(600.00, floatval($this->cashDrawer->balance));
    }
}