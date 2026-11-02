<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\AccountMovement;
use App\Models\AccountTransfer;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Deep Assurance: Treasury, Cash Outflows, Expenses, Internal Transfers, and Wealth Conservation
 */
class TreasuryAndExpenseDeepTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $businessB;
    protected $branch;
    protected $owner;
    protected $cashAccount;
    protected $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->business = Business::create([
            'name' => 'Apex Retailers',
            'legal_name' => 'Apex Retailers Pvt Ltd',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->businessB = Business::create([
            'name' => 'Rival Merchant',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Apex Flagship Store',
            'code' => 'APX-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Treasury Controller',
            'email' => 'treasury@apex.test',
            'password' => bcrypt('Secret123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);

        setPermissionsTeamId($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->cashAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Main Cash Safe',
            'type' => 'cash',
            'account_number' => 'CSH-APX-01',
            'opening_balance' => 40000.00,
            'balance' => 40000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->bankAccount = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'name' => 'Meezan Corporate Bank',
            'type' => 'bank',
            'account_number' => 'BNK-APX-01',
            'opening_balance' => 100000.00,
            'balance' => 100000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    public function test_expense_recording_reduces_treasury_account_balance(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/expenses', [
                'financial_account_id' => $this->cashAccount->id,
                'category' => 'Utilities',
                'amount' => 8500.00,
                'date' => now()->toDateString(),
                'description' => 'Electricity bill payment',
                'branch_id' => $this->branch->id,
            ]);

        $response->assertStatus(201);
        $this->cashAccount->refresh();
        // 40,000 - 8,500 = 31,500
        $this->assertEquals(31500.00, (float)$this->cashAccount->balance);

        $this->assertDatabaseHas('expenses', [
            'business_id' => $this->business->id,
            'financial_account_id' => $this->cashAccount->id,
            'amount' => 8500.00,
            'category' => 'Utilities',
        ]);
    }

    public function test_expense_recording_requires_category(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/expenses', [
                'financial_account_id' => $this->cashAccount->id,
                'amount' => 1500.00,
                'date' => now()->toDateString(),
                'description' => 'Office cleaning without category',
            ]);

        $response->assertStatus(422);
    }

    public function test_expense_recording_with_insufficient_account_balance_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/expenses', [
                'financial_account_id' => $this->cashAccount->id,
                'category' => 'Capital Equipment',
                'amount' => 999999.00, // Exceeds 40,000
                'date' => now()->toDateString(),
                'description' => 'Commercial generator purchase',
            ]);

        $response->assertStatus(422);
    }

    public function test_expense_voiding_reverses_outflow_back_to_account(): void
    {
        $createResp = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/expenses', [
                'financial_account_id' => $this->cashAccount->id,
                'category' => 'Office Supplies',
                'amount' => 5000.00,
                'date' => now()->toDateString(),
                'description' => 'Stationery invoice',
            ]);

        $expenseId = $createResp->json('id');
        $this->cashAccount->refresh();
        $this->assertEquals(35000.00, (float)$this->cashAccount->balance);

        // Void the expense
        $voidResp = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson("/api/v1/expenses/{$expenseId}/void", [
                'reason' => 'Duplicate invoice cancelled by vendor',
            ]);

        $voidResp->assertStatus(200);

        $this->cashAccount->refresh();
        // 35,000 + 5,000 reversed = 40,000
        $this->assertEquals(40000.00, (float)$this->cashAccount->balance);

        $expense = Expense::find($expenseId);
        $this->assertEquals('voided', $expense->status);
    }

    public function test_expense_idempotency_key_replay_returns_existing(): void
    {
        $idempotencyKey = 'IDEM-EXP-' . Str::uuid()->toString();

        $first = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/expenses', [
                'financial_account_id' => $this->cashAccount->id,
                'category' => 'Internet',
                'amount' => 3000.00,
                'date' => now()->toDateString(),
                'idempotency_key' => $idempotencyKey,
            ]);

        $first->assertStatus(201);
        $expenseId = $first->json('id');

        // Replay with exact same key
        $second = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/expenses', [
                'financial_account_id' => $this->cashAccount->id,
                'category' => 'Internet',
                'amount' => 3000.00,
                'date' => now()->toDateString(),
                'idempotency_key' => $idempotencyKey,
            ]);

        $this->assertTrue(in_array($second->status(), [200, 201]));
        $this->assertEquals($expenseId, $second->json('id'));
        $this->assertEquals(1, Expense::where('idempotency_key', $idempotencyKey)->count());

        // Account must be deducted only once: 40,000 - 3,000 = 37,000
        $this->cashAccount->refresh();
        $this->assertEquals(37000.00, (float)$this->cashAccount->balance);
    }

    public function test_account_transfer_between_cash_and_bank_conserves_total_wealth(): void
    {
        $initialWealth = 40000.00 + 100000.00; // 140,000

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $this->cashAccount->id,
                'destination_account_id' => $this->bankAccount->id,
                'amount' => 15000.00,
                'date' => now()->toDateString(),
                'notes' => 'Cash deposit to bank account',
            ]);

        $response->assertStatus(201);

        $this->cashAccount->refresh();
        $this->bankAccount->refresh();

        // Cash: 40,000 - 15,000 = 25,000
        $this->assertEquals(25000.00, (float)$this->cashAccount->balance);
        // Bank: 100,000 + 15,000 = 115,000
        $this->assertEquals(115000.00, (float)$this->bankAccount->balance);
        // Total wealth strictly conserved
        $this->assertEquals($initialWealth, (float)$this->cashAccount->balance + (float)$this->bankAccount->balance);
    }

    public function test_account_transfer_to_same_account_rejected(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $this->cashAccount->id,
                'destination_account_id' => $this->cashAccount->id,
                'amount' => 5000.00,
                'date' => now()->toDateString(),
            ]);

        $response->assertStatus(422);
    }

    public function test_account_transfer_with_insufficient_funds_rejected(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $this->cashAccount->id,
                'destination_account_id' => $this->bankAccount->id,
                'amount' => 50000.00, // Exceeds 40,000 cash balance
                'date' => now()->toDateString(),
            ]);

        $response->assertStatus(422);
    }

    public function test_account_transfer_idempotency_replay_prevents_duplicate_deduction(): void
    {
        $idempotencyKey = 'IDEM-TRF-' . Str::uuid()->toString();

        $first = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $this->cashAccount->id,
                'destination_account_id' => $this->bankAccount->id,
                'amount' => 10000.00,
                'date' => now()->toDateString(),
                'idempotency_key' => $idempotencyKey,
            ]);

        $first->assertStatus(201);
        $transferId = $first->json('id');

        // Replay with exact same key
        $second = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $this->cashAccount->id,
                'destination_account_id' => $this->bankAccount->id,
                'amount' => 10000.00,
                'date' => now()->toDateString(),
                'idempotency_key' => $idempotencyKey,
            ]);

        $second->assertStatus(201); // Or 200 depending on idempotency return
        $this->assertEquals($transferId, $second->json('id'));

        // Balances must reflect only single 10,000 transfer
        $this->cashAccount->refresh();
        $this->bankAccount->refresh();
        $this->assertEquals(30000.00, (float)$this->cashAccount->balance);
        $this->assertEquals(110000.00, (float)$this->bankAccount->balance);
    }

    public function test_cross_tenant_account_transfer_denied(): void
    {
        $foreignAccount = FinancialAccount::create([
            'business_id' => $this->businessB->id,
            'name' => 'Foreign Merchant Safe',
            'type' => 'cash',
            'account_number' => 'CSH-FOR-01',
            'opening_balance' => 20000.00,
            'balance' => 20000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/account-transfers', [
                'source_account_id' => $this->cashAccount->id,
                'destination_account_id' => $foreignAccount->id,
                'amount' => 5000.00,
                'date' => now()->toDateString(),
            ]);

        $response->assertStatus(404);
    }
}
