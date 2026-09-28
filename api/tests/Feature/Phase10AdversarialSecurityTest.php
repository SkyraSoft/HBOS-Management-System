<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Setting;
use Spatie\Activitylog\Models\Activity;
use Database\Seeders\RolesAndPermissionsSeeder;

class Phase10AdversarialSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA;
    protected $branchB;
    protected $userA;
    protected $userB;
    protected $headersA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Business A
        $this->businessA = Business::create(['name' => 'Security Corp Alpha']);
        $this->branchA = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A', 'is_primary' => true]);
        setPermissionsTeamId($this->businessA->id);
        $this->userA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'role' => 'Business Owner',
        ]);
        $this->userA->businesses()->attach($this->businessA->id);
        $this->userA->assignRole('Business Owner');

        // Business B
        $this->businessB = Business::create(['name' => 'Target Corp Beta']);
        $this->branchB = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B', 'is_primary' => true]);
        setPermissionsTeamId($this->businessB->id);
        $this->userB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'role' => 'Business Owner',
        ]);
        $this->userB->businesses()->attach($this->businessB->id);
        $this->userB->assignRole('Business Owner');

        $tokenA = $this->userA->createToken('test')->plainTextToken;
        $this->headersA = [
            'Authorization' => "Bearer $tokenA",
            'X-Business-ID' => (string) $this->businessA->id,
        ];
    }

    /**
     * Section AP: XSS Adversarial Injection
     */
    public function test_xss_payloads_stored_without_execution_or_unescaped_leak()
    {
        $xssPayload = "<script>alert('XSS-TEST')</script><img src=x onerror=alert(1)>";

        $res = $this->withHeaders($this->headersA)->postJson('/api/v1/customers', [
            'name' => "John {$xssPayload}",
            'phone' => '03009998877',
            'notes' => $xssPayload,
        ]);

        $res->assertStatus(201);
        $customerId = $res->json('data.id') ?? $res->json('id');
        $customer = Customer::find($customerId);

        // Raw text is safely stored as string in DB, JSON serialization escapes/quotes it
        $this->assertStringContainsString("alert('XSS-TEST')", $customer->name);
        
        $getRes = $this->withHeaders($this->headersA)->getJson("/api/v1/customers/{$customerId}");
        $getRes->assertStatus(200);
        $getRes->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Section AQ: SQL Injection Adversarial Search Testing
     */
    public function test_sql_injection_fragments_in_search_filters_fail_safely()
    {
        Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Alice Valid',
            'phone' => '03001112233',
        ]);

        Customer::create([
            'business_id' => $this->businessB->id,
            'name' => 'Secret Customer B',
            'phone' => '03009999999',
        ]);

        $sqlInjectionStrings = [
            "' OR '1'='1",
            "'; DROP TABLE customers; --",
            "admin'--",
            "\" OR \"\"=\"",
            "1 UNION SELECT null, null, null, null--",
        ];

        foreach ($sqlInjectionStrings as $sqli) {
            $res = $this->withHeaders($this->headersA)->getJson('/api/v1/customers?search=' . urlencode($sqli));
            $res->assertStatus(200);
            
            // Cross-tenant customer B must never leak
            $content = $res->getContent();
            $this->assertStringNotContainsString('Secret Customer B', $content);
            $this->assertStringNotContainsString('SQLSTATE', $content);
        }
    }

    /**
     * Section AR: Mass Assignment Attack Resistance
     */
    public function test_mass_assignment_tampering_is_strictly_prevented()
    {
        $customer = Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Honest Customer',
            'phone' => '03005556677',
            'opening_balance' => 100.00,
        ]);
        $customer->balance = 100.00;
        $customer->save();

        // Attempt to forge business_id, balance, and opening_balance via customer update
        $res = $this->withHeaders($this->headersA)->putJson("/api/v1/customers/{$customer->id}", [
            'name' => 'Renamed Customer',
            'business_id' => $this->businessB->id, // Attempt tenant change
            'balance' => 0.00, // Attempt khata debt wipe
            'opening_balance' => 0.00,
        ]);

        $res->assertStatus(200);
        $customer->refresh();

        $this->assertEquals('Renamed Customer', $customer->name);
        $this->assertEquals($this->businessA->id, $customer->business_id);
        $this->assertEquals(100.00, (float) $customer->balance);
        $this->assertEquals(100.00, (float) $customer->opening_balance);
    }

    /**
     * Section AS: IDOR Cross-Tenant Access Protection Campaign
     */
    public function test_idor_campaign_across_all_endpoints_returns_404_or_403()
    {
        // Setup B records
        $prodB = Product::create([
            'business_id' => $this->businessB->id,
            'name' => 'Foreign Product B',
            'sku' => 'SKU-FOR-B',
            'cost_price' => 10.00,
            'retail_price' => 20.00,
        ]);

        $custB = Customer::create([
            'business_id' => $this->businessB->id,
            'name' => 'Foreign Customer B',
            'phone' => '03007778899',
        ]);

        $suppB = Supplier::create([
            'business_id' => $this->businessB->id,
            'name' => 'Foreign Supplier B',
            'code' => 'SUP-B',
        ]);

        $accB = FinancialAccount::create([
            'business_id' => $this->businessB->id,
            'name' => 'Foreign Bank B',
            'type' => 'bank',
            'opening_balance' => 5000.00,
            'balance' => 5000.00,
            'status' => 'active',
        ]);

        $catB = ExpenseCategory::create([
            'business_id' => $this->businessB->id,
            'name' => 'Foreign Utilities',
        ]);

        $expB = Expense::create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB->id,
            'user_id' => $this->userB->id,
            'category_id' => $catB->id,
            'category' => 'Foreign Utilities',
            'financial_account_id' => $accB->id,
            'amount' => 150.00,
            'date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        // Attacking user A tries to access Business B resources
        // 1. Branch
        $this->withHeaders($this->headersA)->getJson("/api/v1/branches/{$this->branchB->id}")
            ->assertStatus(404);

        // 2. Product
        $this->withHeaders($this->headersA)->getJson("/api/v1/products/{$prodB->id}")
            ->assertStatus(404);
        $this->withHeaders($this->headersA)->deleteJson("/api/v1/products/{$prodB->id}")
            ->assertStatus(404);

        // 3. Customer
        $this->withHeaders($this->headersA)->getJson("/api/v1/customers/{$custB->id}")
            ->assertStatus(404);

        // 4. Supplier
        $this->withHeaders($this->headersA)->getJson("/api/v1/suppliers/{$suppB->id}")
            ->assertStatus(404);

        // 5. Financial Account
        $this->withHeaders($this->headersA)->getJson("/api/v1/financial-accounts/{$accB->id}")
            ->assertStatus(404);

        // 6. Expense
        $this->withHeaders($this->headersA)->getJson("/api/v1/expenses/{$expB->id}")
            ->assertStatus(404);
        $this->withHeaders($this->headersA)->postJson("/api/v1/expenses/{$expB->id}/void", ['reason' => 'Adversarial void attempt'])
            ->assertStatus(404);
    }

    /**
     * Section AU: Error Masking and Security Response Verification
     */
    public function test_security_error_masking_does_not_leak_credentials_or_traces()
    {
        // Request invalid route -> 404
        $res404 = $this->withHeaders($this->headersA)->getJson('/api/v1/non-existent-endpoint');
        $this->assertEquals(404, $res404->status());
        $this->assertStringNotContainsString('SQLSTATE', $res404->getContent());
        $this->assertStringNotContainsString('DB_PASSWORD', $res404->getContent());

        // Flush auth headers to test unauthenticated access -> 401
        $this->flushHeaders();
        $res401 = $this->getJson('/api/v1/products');
        $this->assertEquals(401, $res401->status());
        $this->assertStringNotContainsString('stack trace', $res401->getContent());
    }
}
