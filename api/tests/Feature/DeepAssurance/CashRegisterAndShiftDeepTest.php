<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashRegisterSession;
use App\Models\FinancialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deep Assurance: Cash Register Sessions, Shift Controls, Float, Blind Close, and Privacy
 */
class CashRegisterAndShiftDeepTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch1;
    protected $branch2;
    protected $owner;
    protected $cashier;
    protected $drawer1;
    protected $drawer2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->business = Business::create([
            'name' => 'Metro Retail Mart',
            'legal_name' => 'Metro Retail Mart Ltd',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->branch1 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Mart Branch Alpha',
            'code' => 'MTR-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->branch2 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Mart Branch Beta',
            'code' => 'MTR-02',
            'is_primary' => false,
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Register Owner',
            'email' => 'reg_owner@metro.test',
            'password' => bcrypt('Secret123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);

        setPermissionsTeamId($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->cashier = User::create([
            'name' => 'Shift Cashier',
            'email' => 'shift_cashier@metro.test',
            'password' => bcrypt('Secret123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->assignRole('Salesperson');

        $this->drawer1 = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Till Register 1',
            'type' => 'cash',
            'account_number' => 'REG-01',
            'opening_balance' => 2000.00,
            'balance' => 2000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->drawer2 = FinancialAccount::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'name' => 'Till Register 2',
            'type' => 'cash',
            'account_number' => 'REG-02',
            'opening_balance' => 5000.00,
            'balance' => 5000.00,
            'currency' => 'PKR',
            'status' => 'active',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public function test_cash_register_open_creates_active_session(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/open', [
                'opening_float' => 2000.00,
                'financial_account_id' => $this->drawer1->id,
                'notes' => 'Morning shift open',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cash_register_sessions', [
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->cashier->id,
            'status' => 'open',
            'opening_float' => 2000.00,
        ]);
    }

    public function test_cash_register_open_duplicate_rejected(): void
    {
        // First session open
        $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/open', [
                'opening_float' => 2000.00,
                'financial_account_id' => $this->drawer1->id,
            ]);

        // Attempting to open another session on the same drawer/branch while one is open
        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/open', [
                'opening_float' => 2000.00,
                'financial_account_id' => $this->drawer1->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_cash_register_current_returns_active_session(): void
    {
        $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/open', [
                'opening_float' => 2000.00,
                'financial_account_id' => $this->drawer1->id,
            ]);

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/cash-register-sessions/current');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['has_active_session']);
        $this->assertEquals(2000.00, (float)$data['session']['opening_float']);
    }

    public function test_cash_register_cashier_privacy_masks_expected_cash(): void
    {
        $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/open', [
                'opening_float' => 2000.00,
                'financial_account_id' => $this->drawer1->id,
            ]);

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/cash-register-sessions/current');

        $response->assertStatus(200);
        $data = $response->json();
        // Blind count privacy: cashiers must NOT see expected_cash or variance
        $this->assertArrayNotHasKey('expected_cash', $data['session']);
        $this->assertArrayNotHasKey('variance', $data['session']);
    }

    public function test_cash_register_owner_sees_full_session_metrics(): void
    {
        $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/open', [
                'opening_float' => 2000.00,
                'financial_account_id' => $this->drawer1->id,
            ]);

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/cash-register-sessions/current?branch_id=' . $this->branch1->id);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['has_active_session']);
        // Owner has full visibility
        $this->assertArrayHasKey('opening_float', $data['session']);
    }

    public function test_cash_register_exact_blind_close_zero_variance(): void
    {
        $openResp = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/open', [
                'opening_float' => 2000.00,
                'financial_account_id' => $this->drawer1->id,
            ]);

        $sessionId = $openResp->json('session.id');

        $closeResp = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/close', [
                'session_id' => $sessionId,
                'closing_cash_counted' => 2000.00, // Exact match
                'notes' => 'Evening shift close',
            ]);

        $closeResp->assertStatus(200);

        $session = CashRegisterSession::find($sessionId);
        $this->assertEquals('closed', $session->status);
        $this->assertEquals(0.00, (float)$session->cash_variance);
    }

    public function test_cash_register_blind_close_with_cash_shortage(): void
    {
        $openResp = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/open', [
                'opening_float' => 2000.00,
                'financial_account_id' => $this->drawer1->id,
            ]);

        $sessionId = $openResp->json('session.id');

        $closeResp = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/close', [
                'session_id' => $sessionId,
                'closing_cash_counted' => 1800.00, // 200 PKR shortage
                'variance_reason' => 'Customer short change discrepancy',
            ]);

        $closeResp->assertStatus(200);

        $session = CashRegisterSession::find($sessionId);
        $this->assertEquals('closed', $session->status);
        // Shortage is negative variance: counted (1800) - expected (2000) = -200
        $this->assertEquals(-200.00, (float)$session->cash_variance);
    }

    public function test_cash_register_blind_close_with_cash_surplus(): void
    {
        $openResp = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/open', [
                'opening_float' => 2000.00,
                'financial_account_id' => $this->drawer1->id,
            ]);

        $sessionId = $openResp->json('session.id');

        $closeResp = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/close', [
                'session_id' => $sessionId,
                'closing_cash_counted' => 2150.00, // 150 PKR surplus
                'variance_reason' => 'Customer left extra change tip',
            ]);

        $closeResp->assertStatus(200);

        $session = CashRegisterSession::find($sessionId);
        $this->assertEquals('closed', $session->status);
        // Surplus is positive variance: counted (2150) - expected (2000) = +150
        $this->assertEquals(150.00, (float)$session->cash_variance);
    }

    public function test_cash_register_close_without_open_session_rejected(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/close', [
                'closing_cash_counted' => 1000.00,
            ]);

        $response->assertStatus(422);
    }

    public function test_cash_register_unauthorized_branch_session_close_denied(): void
    {
        // Open session on branch 2
        $session = CashRegisterSession::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch2->id,
            'user_id' => $this->owner->id,
            'financial_account_id' => $this->drawer2->id,
            'opened_at' => now(),
            'opening_float' => 1000.00,
            'status' => 'open',
        ]);

        // Cashier assigned to Branch 1 attempts to close Branch 2 session
        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/cash-register-sessions/close', [
                'session_id' => $session->id,
                'closing_cash_counted' => 1000.00,
            ]);

        $response->assertStatus(403);
    }
}
