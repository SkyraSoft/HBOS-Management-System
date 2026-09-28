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
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\AccountTransfer;
use App\Models\AccountMovement;
use App\Models\CustomerPayment;
use App\Models\SupplierPayment;
use App\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;

class Phase10ReleaseJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * BA. Critical Journey 1 — Owner Onboarding
     */
    public function test_journey_1_owner_onboarding()
    {
        // 1. Register Owner
        $regRes = $this->postJson('/api/v1/auth/register', [
            'name' => 'Owner Alice',
            'email' => 'alice@hbos.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Alice Enterprises',
        ]);
        $regRes->assertStatus(201);
        $token = $regRes->json('access_token');
        $user = User::where('email', 'alice@hbos.local')->first();
        $this->assertNotNull($user);

        // Verify Business & Primary Branch creation
        $business = Business::where('name', 'Alice Enterprises')->first();
        $this->assertNotNull($business);
        $primaryBranch = Branch::where('business_id', $business->id)->where('is_primary', true)->first();
        $this->assertNotNull($primaryBranch);

        // Authenticated context test
        $dashRes = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => (string) $business->id,
        ])->getJson('/api/v1/dashboard');
        $dashRes->assertStatus(200);
        $this->assertEquals('Business Owner', $dashRes->json('role'));
    }

    /**
     * BB. Journey 2 — Staff Governance (Manager and Salesperson onboarding)
     */
    public function test_journey_2_staff_governance()
    {
        $biz = Business::create(['name' => 'Gov Corp']);
        $branch = Branch::create(['business_id' => $biz->id, 'name' => 'Main', 'is_primary' => true]);
        setPermissionsTeamId($biz->id);

        $owner = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $branch->id, 'role' => 'Business Owner']);
        $owner->businesses()->attach($biz->id);
        $owner->assignRole('Business Owner');
        $ownerToken = $owner->createToken('test')->plainTextToken;

        $headers = ['Authorization' => "Bearer $ownerToken", 'X-Business-ID' => (string) $biz->id];

        // 1. Create Branch Manager
        $mgrRes = $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Bob Manager',
            'email' => 'bob@gov.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'Branch Manager',
            'branch_id' => $branch->id,
        ]);
        $mgrRes->assertStatus(201);
        $mgrUser = User::where('email', 'bob@gov.local')->first();
        $this->assertNotNull($mgrUser);

        // 2. Create Salesperson
        $salesRes = $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Charlie Sales',
            'email' => 'charlie@gov.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'Salesperson',
            'branch_id' => $branch->id,
        ]);
        $salesRes->assertStatus(201);
        $salesUser = User::where('email', 'charlie@gov.local')->first();
        $this->assertNotNull($salesUser);

        // Login as Salesperson and verify cannot access user governance
        $this->flushHeaders();
        app('auth')->forgetGuards();
        $salesToken = $salesUser->createToken('test')->plainTextToken;
        $salesHeaders = ['Authorization' => "Bearer $salesToken", 'X-Business-ID' => (string) $biz->id];

        $forbidRes = $this->withHeaders($salesHeaders)->getJson('/api/v1/users');
        $forbidRes->assertStatus(403);
    }

    /**
     * BC & BD: Journey 3 & 4 — Branch Operations and Salesperson POS
     */
    public function test_journey_3_and_4_manager_and_salesperson_operations()
    {
        $biz = Business::create(['name' => 'Retail Corp']);
        $branch = Branch::create(['business_id' => $biz->id, 'name' => 'Retail Branch', 'is_primary' => true]);
        $foreignBranch = Branch::create(['business_id' => $biz->id, 'name' => 'Other Branch', 'is_primary' => false]);
        setPermissionsTeamId($biz->id);

        $salesperson = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $branch->id, 'role' => 'Salesperson']);
        $salesperson->businesses()->attach($biz->id);
        $salesperson->assignRole('Salesperson');

        $cash = FinancialAccount::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'name' => 'POS Drawer',
            'type' => 'cash',
            'opening_balance' => 5000.00,
            'balance' => 5000.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Retail Item',
            'sku' => 'SKU-RET-99',
            'cost_price' => 50.00,
            'retail_price' => 100.00,
            'selling_price' => 100.00,
            'track_quantity' => true,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
        ]);

        $salesToken = $salesperson->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer $salesToken", 'X-Business-ID' => (string) $biz->id];

        // 1. Salesperson creates POS Sale
        $saleRes = $this->withHeaders($headers)->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100.00]],
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'financial_account_id' => $cash->id,
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('data.id') ?? $saleRes->json('id');

        // Inventory deducted to 8
        $this->assertEquals(8, BranchInventory::where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity_on_hand'));
        // Cash increased by 200.00 to 5200.00
        $this->assertEquals(5200.00, (float) $cash->fresh()->balance);

        // 2. Salesperson tries forbidden audit access -> 403
        $this->withHeaders($headers)->getJson('/api/v1/audit-logs')->assertStatus(403);
    }

    /**
     * BE. Journey 5 — Sale Return + Refund
     */
    public function test_journey_5_sale_return_and_refund_lifecycle()
    {
        $biz = Business::create(['name' => 'Return Corp']);
        $branch = Branch::create(['business_id' => $biz->id, 'name' => 'HQ', 'is_primary' => true]);
        setPermissionsTeamId($biz->id);

        $owner = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $branch->id, 'role' => 'Business Owner']);
        $owner->businesses()->attach($biz->id);
        $owner->assignRole('Business Owner');
        $token = $owner->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer $token", 'X-Business-ID' => (string) $biz->id];

        $cash = FinancialAccount::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'name' => 'Cash Account',
            'type' => 'cash',
            'opening_balance' => 10000.00,
            'balance' => 10000.00,
            'status' => 'active',
        ]);

        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Return Item',
            'sku' => 'SKU-RI-1',
            'cost_price' => 50.00,
            'retail_price' => 100.00,
            'track_quantity' => true,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
        ]);

        // Create Sale of 2 items
        $saleRes = $this->withHeaders($headers)->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100.00]],
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'financial_account_id' => $cash->id,
        ]);
        $saleRes->assertStatus(201);
        $saleId = $saleRes->json('data.id') ?? $saleRes->json('id');
        $sale = Sale::with('items')->find($saleId);

        // Return 1 item
        $retRes = $this->withHeaders($headers)->postJson("/api/v1/sales/{$saleId}/returns", [
            'reason' => 'Defective',
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]]
        ]);
        $retRes->assertStatus(201);
        $retId = $retRes->json('data.id') ?? $retRes->json('id');
        $saleReturn = SaleReturn::find($retId);

        // Restock verified
        $this->assertEquals(9, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));

        // Settle Refund
        $settleRes = $this->withHeaders($headers)->postJson("/api/v1/sale-returns/{$retId}/settle-refund", [
            'financial_account_id' => $cash->id,
            'amount' => 100.00,
            'date' => now()->toDateString(),
        ]);
        $this->assertTrue(in_array($settleRes->status(), [200, 201]));

        // Cash refunded
        $this->assertEquals(10100.00, (float) $cash->fresh()->balance);

        // Reverse Refund
        $revRes = $this->withHeaders($headers)->postJson("/api/v1/sale-returns/{$retId}/reverse-refund", [
            'reason' => 'Customer store credit exchange'
        ]);
        $revRes->assertStatus(200);
        $this->assertEquals(10200.00, (float) $cash->fresh()->balance);
    }

    /**
     * BF & BH: Journey 6 & 8 — Purchase, Stock Increment & Supplier Settlement
     */
    public function test_journey_6_and_8_purchase_lifecycle_and_supplier_settlement()
    {
        $biz = Business::create(['name' => 'Wholesale Corp']);
        $branch = Branch::create(['business_id' => $biz->id, 'name' => 'HQ', 'is_primary' => true]);
        setPermissionsTeamId($biz->id);

        $owner = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $branch->id, 'role' => 'Business Owner']);
        $owner->businesses()->attach($biz->id);
        $owner->assignRole('Business Owner');
        $headers = ['Authorization' => "Bearer " . $owner->createToken('test')->plainTextToken, 'X-Business-ID' => (string) $biz->id];

        $supplier = Supplier::create([
            'business_id' => $biz->id,
            'name' => 'Mega Supplier',
            'code' => 'SUP-MEGA',
            'balance' => 0.00,
        ]);

        $bank = FinancialAccount::create([
            'business_id' => $biz->id,
            'name' => 'Bank Account',
            'type' => 'bank',
            'opening_balance' => 50000.00,
            'balance' => 50000.00,
            'status' => 'active',
        ]);

        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Purchased Item',
            'sku' => 'SKU-PUR-1',
            'cost_price' => 20.00,
            'retail_price' => 40.00,
            'track_quantity' => true,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 0,
        ]);

        // 1. Create Purchase of 50 units @ 20 = 1000.00 (Unpaid)
        $purRes = $this->withHeaders($headers)->postJson('/api/v1/purchases', [
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-001',
            'status' => 'received',
            'items' => [['product_id' => $product->id, 'quantity' => 50, 'unit_cost' => 20.00]],
            'paid_amount' => 0.00,
        ]);
        $purRes->assertStatus(201);

        // Stock incremented to 50
        $this->assertEquals(50, BranchInventory::where('product_id', $product->id)->value('quantity_on_hand'));
        // Supplier payable is 1000.00
        $this->assertEquals(1000.00, (float) $supplier->fresh()->balance);

        // 2. Pay Supplier 600.00
        $payRes = $this->withHeaders($headers)->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
            'amount' => 600.00,
            'payment_method' => 'bank_transfer',
            'financial_account_id' => $bank->id,
        ]);
        $payRes->assertStatus(201);

        // Supplier balance down to 400.00
        $this->assertEquals(400.00, (float) $supplier->fresh()->balance);
        $this->assertEquals(49400.00, (float) $bank->fresh()->balance);

        // 3. Overpayment attempt (paying 500 when debt is 400) -> strictly rejected 422
        $overRes = $this->withHeaders($headers)->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
            'amount' => 500.00,
            'financial_account_id' => $bank->id,
        ]);
        $overRes->assertStatus(422);
    }

    /**
     * BG. Journey 7 — Customer Credit / Khata & Reversal
     */
    public function test_journey_7_customer_credit_and_khata_lifecycle()
    {
        $biz = Business::create(['name' => 'Khata Corp']);
        $branch = Branch::create(['business_id' => $biz->id, 'name' => 'HQ', 'is_primary' => true]);
        setPermissionsTeamId($biz->id);

        $owner = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $branch->id, 'role' => 'Business Owner']);
        $owner->businesses()->attach($biz->id);
        $owner->assignRole('Business Owner');
        $headers = ['Authorization' => "Bearer " . $owner->createToken('test')->plainTextToken, 'X-Business-ID' => (string) $biz->id];

        $cash = FinancialAccount::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'name' => 'Cash',
            'type' => 'cash',
            'opening_balance' => 1000.00,
            'balance' => 1000.00,
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'business_id' => $biz->id,
            'name' => 'Credit Customer',
            'phone' => '03004445566',
            'opening_balance' => 0.00,
        ]);

        $product = Product::create([
            'business_id' => $biz->id,
            'name' => 'Credit Item',
            'sku' => 'SKU-CR-1',
            'cost_price' => 100.00,
            'retail_price' => 300.00,
            'track_quantity' => true,
            'status' => 'active',
        ]);

        BranchInventory::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10,
        ]);

        // 1. Credit Sale of 300.00 (unpaid)
        $saleRes = $this->withHeaders($headers)->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 300.00]],
            'payment_status' => 'unpaid',
            'paid_amount' => 0.00,
        ]);
        $saleRes->assertStatus(201);

        // Khata balance rises to 300.00
        $this->assertEquals(300.00, (float) $customer->fresh()->balance);

        // 2. Customer pays 200.00
        $payRes = $this->withHeaders($headers)->postJson("/api/v1/customers/{$customer->id}/payments", [
            'branch_id' => $branch->id,
            'amount' => 200.00,
            'payment_method' => 'cash',
            'financial_account_id' => $cash->id,
        ]);
        $payRes->assertStatus(201);
        $paymentId = $payRes->json('data.id') ?? $payRes->json('id');

        $this->assertEquals(100.00, (float) $customer->fresh()->balance);
        $this->assertEquals(1200.00, (float) $cash->fresh()->balance);

        // 3. Payment Reversal
        $revRes = $this->withHeaders($headers)->postJson("/api/v1/customer-payments/{$paymentId}/reverse", [
            'reason' => 'Bounced payment check'
        ]);
        $revRes->assertStatus(200);

        // Debt returns to 300.00, cash restored to 1000.00
        $this->assertEquals(300.00, (float) $customer->fresh()->balance);
        $this->assertEquals(1000.00, (float) $cash->fresh()->balance);
    }

    /**
     * BI & BJ: Journey 9 & 10 — Expense Lifecycle, Cash/Bank Account Movements
     */
    public function test_journey_9_and_10_expense_and_financial_accounts()
    {
        $biz = Business::create(['name' => 'Financial Corp']);
        $branch = Branch::create(['business_id' => $biz->id, 'name' => 'HQ', 'is_primary' => true]);
        setPermissionsTeamId($biz->id);

        $owner = User::factory()->create(['business_id' => $biz->id, 'branch_id' => $branch->id, 'role' => 'Business Owner']);
        $owner->businesses()->attach($biz->id);
        $owner->assignRole('Business Owner');
        $headers = ['Authorization' => "Bearer " . $owner->createToken('test')->plainTextToken, 'X-Business-ID' => (string) $biz->id];

        $cash = FinancialAccount::create([
            'business_id' => $biz->id,
            'branch_id' => $branch->id,
            'name' => 'Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 2000.00,
            'balance' => 2000.00,
            'status' => 'active',
        ]);

        $bank = FinancialAccount::create([
            'business_id' => $biz->id,
            'name' => 'Bank Account',
            'type' => 'bank',
            'opening_balance' => 10000.00,
            'balance' => 10000.00,
            'status' => 'active',
        ]);

        // 1. Post Expense of 500.00 from Cash Drawer
        $cat = ExpenseCategory::create(['business_id' => $biz->id, 'name' => 'Office Rent']);
        $expRes = $this->withHeaders($headers)->postJson('/api/v1/expenses', [
            'branch_id' => $branch->id,
            'category_id' => $cat->id,
            'financial_account_id' => $cash->id,
            'amount' => 500.00,
            'date' => now()->toDateString(),
            'notes' => 'Rent installment',
        ]);
        $expRes->assertStatus(201);
        $expId = $expRes->json('data.id') ?? $expRes->json('id');

        $this->assertEquals(1500.00, (float) $cash->fresh()->balance);

        // Void the expense
        $voidRes = $this->withHeaders($headers)->postJson("/api/v1/expenses/{$expId}/void", [
            'reason' => 'Duplicate voucher'
        ]);
        $voidRes->assertStatus(200);
        $this->assertEquals(2000.00, (float) $cash->fresh()->balance);

        // 2. Transfer 500.00 from Cash -> Bank
        $transRes = $this->withHeaders($headers)->postJson('/api/v1/account-transfers', [
            'source_account_id' => $cash->id,
            'destination_account_id' => $bank->id,
            'amount' => 500.00,
            'date' => now()->toDateString(),
            'notes' => 'Deposit to bank',
        ]);
        $transRes->assertStatus(201);

        $this->assertEquals(1500.00, (float) $cash->fresh()->balance);
        $this->assertEquals(10500.00, (float) $bank->fresh()->balance);
    }

    /**
     * BK, BL, BM, BN: Journeys 11, 12, 13, 14 — Reports, Audit, Settings & Multi-Business A/B/A
     */
    public function test_journey_11_to_14_reporting_audit_settings_and_aba_switch()
    {
        $bizA = Business::create(['name' => 'Alpha Business']);
        $branchA = Branch::create(['business_id' => $bizA->id, 'name' => 'Alpha Main', 'is_primary' => true]);

        $bizB = Business::create(['name' => 'Beta Business']);
        $branchB = Branch::create(['business_id' => $bizB->id, 'name' => 'Beta Main', 'is_primary' => true]);

        setPermissionsTeamId($bizA->id);
        $owner = User::factory()->create(['business_id' => $bizA->id, 'branch_id' => $branchA->id, 'role' => 'Business Owner']);
        $owner->businesses()->attach([$bizA->id, $bizB->id]);
        $owner->assignRole('Business Owner');

        setPermissionsTeamId($bizB->id);
        $owner->assignRole('Business Owner');
        
        $token = $owner->createToken('test')->plainTextToken;

        // 1. Settings persistence in A
        $resSet = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => (string) $bizA->id])
            ->postJson('/api/v1/settings', [
                'settings' => [
                    ['key' => 'business_tagline', 'value' => 'Alpha Tagline Updated'],
                    ['key' => 'receipt_footer', 'value' => 'Thank you for shopping at Alpha!']
                ]
            ]);
        $resSet->assertStatus(200);

        // 2. Audit check in A
        $resAudit = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => (string) $bizA->id])
            ->getJson('/api/v1/audit-logs');
        $resAudit->assertStatus(200);

        // 3. Switch A -> B -> A
        $resB = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => (string) $bizB->id])
            ->getJson('/api/v1/settings');
        $resB->assertStatus(200);
        $this->assertStringNotContainsString('Alpha Tagline Updated', json_encode($resB->json()));

        $resA = $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => (string) $bizA->id])
            ->getJson('/api/v1/settings');
        $resA->assertStatus(200);
        $this->assertStringContainsString('Alpha Tagline Updated', json_encode($resA->json()));
    }
}
