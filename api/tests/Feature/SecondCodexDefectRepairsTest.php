<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\FinancialAccount;
use App\Models\BranchInventory;
use App\Models\InventoryMovement;
use App\Models\CashRegisterSession;
use App\Services\FinancialAccountService;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class SecondCodexDefectRepairsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * PF-P1-001: Main Cash Account is marked is_default=true, status='active', opening_balance=0.00.
     */
    public function test_registration_provisions_default_cash_drawer_with_active_status(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Default Drawer Test Store',
            'name' => 'Owner Drawer',
            'email' => 'drawer.owner@example.com',
            'phone' => '03009988771',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $regResponse->assertStatus(201);
        $businessId = $regResponse->json('business.id');
        $branchId = $regResponse->json('branch.id');

        $account = FinancialAccount::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('type', 'cash')
            ->first();

        $this->assertNotNull($account, 'Cash account must be created upon registration.');
        $this->assertTrue((bool) $account->is_default, 'Cash account must have is_default = true.');
        $this->assertEquals('active', $account->status, 'Cash account must have status = active.');
        $this->assertEquals('0.00', (string) $account->opening_balance, 'Opening balance must be 0.00.');

        // Test FinancialAccountService fallback resolution
        $service = app(FinancialAccountService::class);
        $resolved = $service->getDefaultCashAccount($businessId, $branchId);
        $this->assertNotNull($resolved);
        $this->assertEquals($account->id, $resolved->id);
    }

    /**
     * PF-P1-001: Owner can initialize opening float (e.g. 5,000 PKR) on a fresh zero-movement cash drawer.
     */
    public function test_owner_can_initialize_opening_float_on_zero_movement_fresh_drawer(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Float Init Test Store',
            'name' => 'Float Owner',
            'email' => 'float.owner@example.com',
            'phone' => '03009988772',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $token = $regResponse->json('access_token');
        $businessId = $regResponse->json('business.id');
        $branchId = $regResponse->json('branch.id');
        $user = User::find($regResponse->json('user.id'));

        $drawer = FinancialAccount::where('business_id', $businessId)->where('type', 'cash')->first();
        $this->assertEquals(0, $drawer->movements()->count());

        $sessionResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 5000.00,
            'notes' => 'Day 1 morning float',
        ]);

        $sessionResponse->assertStatus(201);
        $drawer->refresh();
        $this->assertEquals('0.00', (string) $drawer->opening_balance, 'Opening balance remains 0.00 per THIRD-P2-004.');
        $this->assertEquals('5000.00', (string) $drawer->balance, 'Balance reflects auditable capital_in movement.');

        $session = CashRegisterSession::where('financial_account_id', $drawer->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('5000.00', (string) $session->opening_float);
        $this->assertEquals('open', $session->status);
    }

    /**
     * PF-P1-003: Customer Khata Payment accepts payment with nullable branch_id and resolves fallback.
     */
    public function test_customer_payment_accepts_payment_with_null_branch_and_resolves_fallback(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Customer Payment Test Store',
            'name' => 'Payment Owner',
            'email' => 'pay.owner@example.com',
            'phone' => '03009988773',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $token = $regResponse->json('access_token');
        $businessId = $regResponse->json('business.id');
        $branchId = $regResponse->json('branch.id');

        $customer = Customer::create([
            'business_id' => $businessId,
            'name' => 'Ali Ahmed',
            'phone' => '03211234567',
            'opening_balance' => 2000.00,
        ]);

        // Post payment without explicit branch_id
        $paymentResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson("/api/v1/customers/{$customer->id}/payments", [
            'amount' => 500.00,
            'payment_method' => 'Cash',
            'notes' => 'Partial repayment without branch specified',
        ]);

        $paymentResponse->assertStatus(201)
            ->assertJsonPath('amount', '500.00');

        $payment = \App\Models\CustomerPayment::where('customer_id', $customer->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals($branchId, $payment->branch_id);
    }

    /**
     * PF-P1-005 & PF-P2-005: Product creation provisions BranchInventory and defaults is_active=true.
     */
    public function test_product_creation_provisions_branch_inventory_and_defaults_active(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Product Stock Test Store',
            'name' => 'Stock Owner',
            'email' => 'stock.owner@example.com',
            'phone' => '03009988774',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $token = $regResponse->json('access_token');
        $businessId = $regResponse->json('business.id');
        $branchId = $regResponse->json('branch.id');

        $productResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/products', [
            'name' => 'Fresh Dairy Milk 1L',
            'sku' => 'MILK-001',
            'cost_price' => 150,
            'selling_price' => 180,
            'stock' => 25,
            'min_stock' => 5,
        ]);

        $productResponse->assertStatus(201);
        $productId = $productResponse->json('id');
        $product = Product::find($productId);

        $this->assertTrue((bool) $product->is_active, 'Product must default to is_active = true.');
        $this->assertEquals(25, (float) $product->stock);

        // Verify BranchInventory was provisioned
        $inventory = BranchInventory::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        $this->assertNotNull($inventory, 'BranchInventory must be created on product creation.');
        $this->assertEquals(25.000, (float) $inventory->quantity_on_hand);

        // Verify InventoryMovement was created
        $movement = InventoryMovement::where('product_id', $productId)->first();
        $this->assertNotNull($movement, 'InventoryMovement must be recorded for starting stock.');
        $this->assertEquals('adjustment', $movement->type);
        $this->assertEquals(25.000, (float) $movement->quantity);
    }

    /**
     * PF-P1-002: Purchase creates correct paid_amount and due_amount.
     */
    public function test_purchase_payable_records_paid_and_due_amounts(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Purchase Payable Test Store',
            'name' => 'Purchase Owner',
            'email' => 'purchase.owner@example.com',
            'phone' => '03009988775',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $token = $regResponse->json('access_token');
        $businessId = $regResponse->json('business.id');
        $branchId = $regResponse->json('branch.id');

        $supplier = Supplier::create([
            'business_id' => $businessId,
            'name' => 'Metro Cash & Carry',
            'phone' => '03001234567',
        ]);

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Cooking Oil 5L',
            'sku' => 'OIL-5L',
            'cost_price' => 2000,
            'selling_price' => 2400,
            'stock' => 0,
            'is_active' => true,
        ]);

        // Fund drawer before purchase with cash payment (THIRD-P1-001)
        $drawer = FinancialAccount::where('business_id', $businessId)->where('type', 'cash')->first();
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/cash-register-sessions/open', [
            'branch_id' => $branchId,
            'financial_account_id' => $drawer->id,
            'opening_float' => 5000.00,
        ]);

        $purchaseResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id,
            'branch_id' => $branchId,
            'po_number' => 'PO-TEST-001',
            'subtotal' => 10000.00,
            'freight_amount' => 500.00,
            'total' => 10500.00,
            'paid_amount' => 4500.00,
            'payment_method' => 'Cash',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_cost' => 2000.00,
                    'total' => 10000.00,
                ]
            ]
        ]);

        $purchaseResponse->assertStatus(201)
            ->assertJsonPath('total', '10500.00')
            ->assertJsonPath('paid_amount', '4500.00')
            ->assertJsonPath('due_amount', '6000.00');

        $supplier->refresh();
        $this->assertEquals('6000.00', (string) $supplier->balance, 'Supplier balance must reflect unpaid due amount.');
    }

    /**
     * PF-P2-010: Five-Stage Onboarding wizard supports Stage 3 Primary Branch configuration.
     */
    public function test_onboarding_five_stage_flow_supports_stage_3_primary_branch(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Wizard Test Store',
            'name' => 'Wizard Owner',
            'email' => 'wizard.owner@example.com',
            'phone' => '03009988776',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $token = $regResponse->json('access_token');
        $businessId = $regResponse->json('business.id');

        // Stage 2: Business Profile
        $step2Response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/onboarding/step', [
            'step' => 2,
            'name' => 'Wizard Retail Ltd',
            'type' => 'Supermarket',
            'city' => 'Lahore',
        ]);
        $step2Response->assertStatus(200);

        // Stage 3: Primary Main Store
        $step3Response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/onboarding/step', [
            'step' => 3,
            'branch_name' => 'Flagship Mall Branch',
            'branch_code' => 'FLAG-01',
            'branch_phone' => '04235889900',
            'branch_city' => 'Lahore',
            'branch_address' => 'Mall Road Commercial Complex',
        ]);
        $step3Response->assertStatus(200);

        $primaryBranch = Branch::where('business_id', $businessId)->where('is_primary', true)->first();
        $this->assertEquals('Flagship Mall Branch', $primaryBranch->name);
        $this->assertEquals('FLAG-01', $primaryBranch->code);

        // Stage 4: Preferences
        $step4Response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/onboarding/step', [
            'step' => 4,
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'negative_stock_policy' => 'block',
        ]);
        $step4Response->assertStatus(200);

        // Stage 5: Complete & Review
        $step5Response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/onboarding/step', [
            'step' => 5,
            'confirm' => true,
        ]);
        $step5Response->assertStatus(200)
            ->assertJsonPath('onboarding_status', 'completed');
    }

    /**
     * Section 15: Ordinary user login does not flush permission cache or mutate roles.
     */
    public function test_section_15_login_does_not_mutate_roles_or_flush_cache(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Cache Test Store',
            'name' => 'Cache Owner',
            'email' => 'cache.owner@example.com',
            'phone' => '03009988777',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $businessId = $regResponse->json('business.id');
        $user = User::find($regResponse->json('user.id'));
        setPermissionsTeamId($businessId);

        $initialRoleCount = $user->roles()->count();

        // Perform login
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'cache.owner@example.com',
            'password' => 'Password123!',
        ]);

        $loginResponse->assertStatus(200);
        $user->refresh();
        $this->assertEquals($initialRoleCount, $user->roles()->count());
    }
}
