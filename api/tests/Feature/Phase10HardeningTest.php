<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class Phase10HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Branch $branchA;
    protected Business $businessB;
    protected Branch $branchB;
    protected User $multiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Provision Business A
        $this->businessA = Business::create(['name' => 'Business Alpha', 'currency' => 'PKR']);
        $this->branchA = Branch::create([
            'business_id' => $this->businessA->id,
            'name' => 'Branch Alpha 1',
            'is_primary' => true,
        ]);

        // Provision Business B
        $this->businessB = Business::create(['name' => 'Business Beta', 'currency' => 'USD']);
        $this->branchB = Branch::create([
            'business_id' => $this->businessB->id,
            'name' => 'Branch Beta 1',
            'is_primary' => true,
        ]);

        // Multi-tenant user with legacy business_id pointing to A
        $this->multiUser = User::factory()->create([
            'email' => 'multiowner@example.com',
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'is_active' => true,
        ]);

        $this->multiUser->businesses()->attach([$this->businessA->id, $this->businessB->id]);

        setPermissionsTeamId($this->businessA->id);
        $this->multiUser->assignRole('Business Owner');

        setPermissionsTeamId($this->businessB->id);
        $this->multiUser->assignRole('Business Owner');
    }

    /**
     * AC-10.12: Release Health Endpoint
     */
    public function test_health_endpoint_returns_ok_without_exposing_internals(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'database' => 'ok',
        ]);

        // Assert zero disclosure of credentials or database internals
        $data = $response->json();
        $this->assertArrayNotHasKey('host', $data);
        $this->assertArrayNotHasKey('database_name', $data);
        $this->assertArrayNotHasKey('username', $data);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('version', $data);

        // AC-10.39: Security Headers attached
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * AC-10.13: Login Rate Limiting (throttle:6,1)
     */
    public function test_login_throttling_protects_against_brute_force(): void
    {
        // 6 attempts allowed
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'attacker@example.com',
                'password' => 'wrong-password',
            ]);
            $this->assertNotEquals(429, $response->status(), "Attempt {$i} should not be throttled.");
        }

        // 7th attempt must be throttled with HTTP 429
        $response7 = $this->postJson('/api/v1/auth/login', [
            'email' => 'attacker@example.com',
            'password' => 'wrong-password',
        ]);

        $response7->assertStatus(429);
    }

    /**
     * AC-10.13: Register Rate Limiting (throttle:6,1)
     */
    public function test_register_throttling_protects_against_abuse(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'Candidate',
                'email' => "candidate{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'business_name' => "Shop {$i}",
            ]);
            $this->assertNotEquals(429, $response->status(), "Attempt {$i} should not be throttled.");
        }

        $response7 = $this->postJson('/api/v1/auth/register', [
            'name' => 'Candidate 7',
            'email' => 'candidate7@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'business_name' => 'Shop 7',
        ]);

        $response7->assertStatus(429);
    }

    /**
     * AC-10.14: Multi-business user without active Business header MUST NOT silently fall back to users.business_id
     */
    public function test_missing_active_business_header_fails_explicitly_without_fallback(): void
    {
        // Create customers in Business A and B
        Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Alpha Customer',
            'phone' => '1111111111',
        ]);
        Customer::create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta Customer',
            'phone' => '2222222222',
        ]);

        // Request WITHOUT X-Business-ID header: must fail explicitly with HTTP 400
        $response = $this->actingAs($this->multiUser)->getJson('/api/v1/customers');
        $response->assertStatus(400);
        $response->assertJsonFragment(['message' => 'Active business context is required.']);

        // Test representative endpoints also reject missing tenant context explicitly
        $this->actingAs($this->multiUser)->getJson('/api/v1/categories')->assertStatus(400);
        $this->actingAs($this->multiUser)->getJson('/api/v1/brands')->assertStatus(400);
        $this->actingAs($this->multiUser)->getJson('/api/v1/suppliers')->assertStatus(400);
        $this->actingAs($this->multiUser)->getJson('/api/v1/expenses')->assertStatus(400);
        $this->actingAs($this->multiUser)->getJson('/api/v1/inventory')->assertStatus(400);
        $this->actingAs($this->multiUser)->getJson('/api/v1/purchases')->assertStatus(400);
        $this->actingAs($this->multiUser)->getJson('/api/v1/financial-accounts')->assertStatus(400);
        $this->actingAs($this->multiUser)->getJson('/api/v1/notifications')->assertStatus(400);
    }

    /**
     * AC-10.14: Multi-business switching A -> B -> A with isolated context and no stale data
     */
    public function test_multi_business_switching_a_to_b_to_a_prevents_stale_context(): void
    {
        $custA = Customer::create([
            'business_id' => $this->businessA->id,
            'name' => 'Alpha Cust 100',
            'phone' => '1000000001',
        ]);

        $custB = Customer::create([
            'business_id' => $this->businessB->id,
            'name' => 'Beta Cust 200',
            'phone' => '2000000002',
        ]);

        // 1. Request with X-Business-ID: A -> sees only A
        $resA = $this->actingAs($this->multiUser)
            ->withHeader('X-Business-ID', (string) $this->businessA->id)
            ->getJson('/api/v1/customers');

        $resA->assertStatus(200);
        $custNamesA = collect($resA->json())->pluck('name')->all();
        $this->assertContains('Alpha Cust 100', $custNamesA);
        $this->assertNotContains('Beta Cust 200', $custNamesA);

        // 2. Request with X-Business-ID: B -> sees only B
        $resB = $this->actingAs($this->multiUser)
            ->withHeader('X-Business-ID', (string) $this->businessB->id)
            ->getJson('/api/v1/customers');

        $resB->assertStatus(200);
        $custNamesB = collect($resB->json())->pluck('name')->all();
        $this->assertContains('Beta Cust 200', $custNamesB);
        $this->assertNotContains('Alpha Cust 100', $custNamesB);

        // 3. Switch back to X-Business-ID: A -> sees only A, no leftover B context
        $resA2 = $this->actingAs($this->multiUser)
            ->withHeader('X-Business-ID', (string) $this->businessA->id)
            ->getJson('/api/v1/customers');

        $resA2->assertStatus(200);
        $custNamesA2 = collect($resA2->json())->pluck('name')->all();
        $this->assertContains('Alpha Cust 100', $custNamesA2);
        $this->assertNotContains('Beta Cust 200', $custNamesA2);
    }

    /**
     * AC-10.14 / Notifications Scope: Notifications isolated strictly to active Business context
     */
    public function test_notifications_active_business_scope(): void
    {
        // Add notification directly to Business A
        $this->businessA->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => 'App\Notifications\LowStockNotification',
            'data' => ['message' => 'Low stock in Alpha'],
        ]);

        // Request as A -> sees notification
        $resA = $this->actingAs($this->multiUser)
            ->withHeader('X-Business-ID', (string) $this->businessA->id)
            ->getJson('/api/v1/notifications');

        $resA->assertStatus(200);
        $dataA = $resA->json('data');
        $this->assertCount(1, $dataA);
        $this->assertEquals('Low stock in Alpha', $dataA[0]['data']['message']);

        // Request as B -> receives empty notifications
        $resB = $this->actingAs($this->multiUser)
            ->withHeader('X-Business-ID', (string) $this->businessB->id)
            ->getJson('/api/v1/notifications');

        $resB->assertStatus(200);
        $dataB = $resB->json('data');
        $this->assertCount(0, $dataB);
    }

    /**
     * AC-10.09: Business Deletion Guard: Reject deletion with HTTP 422 if ActivityLog audit history exists
     */
    public function test_business_deletion_blocked_if_audit_history_exists(): void
    {
        // Create an empty business with NO sales, purchases, or accounts
        $emptyBiz = Business::create(['name' => 'Audit Guarded Business', 'currency' => 'PKR']);
        $this->multiUser->businesses()->attach($emptyBiz->id);
        setPermissionsTeamId($emptyBiz->id);
        $this->multiUser->assignRole('Business Owner');

        // Create an audit log record tied to this business
        ActivityLog::create([
            'log_name' => 'governance',
            'description' => 'Security review completed',
            'subject_type' => Business::class,
            'subject_id' => $emptyBiz->id,
            'causer_type' => User::class,
            'causer_id' => $this->multiUser->id,
            'business_id' => $emptyBiz->id,
        ]);

        // Attempt deletion while active context is Business A (not deleting active branch)
        $response = $this->actingAs($this->multiUser)
            ->withHeader('X-Business-ID', (string) $this->businessA->id)
            ->deleteJson("/api/v1/businesses/{$emptyBiz->id}");

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Cannot delete business with existing operational, financial, or audit history.'
        ]);

        // Verify business remains and audit row is NOT orphaned
        $this->assertDatabaseHas('businesses', ['id' => $emptyBiz->id]);
        $this->assertDatabaseHas('activity_log', [
            'business_id' => $emptyBiz->id,
            'description' => 'Security review completed',
        ]);
    }

    /**
     * AC-10.09: Truly empty business without audit history can be deleted
     */
    public function test_truly_empty_business_without_audit_history_can_be_deleted(): void
    {
        $emptyBiz = Business::create(['name' => 'Truly Empty Business', 'currency' => 'PKR']);
        $this->multiUser->businesses()->attach($emptyBiz->id);
        setPermissionsTeamId($emptyBiz->id);
        $this->multiUser->assignRole('Business Owner');

        $response = $this->actingAs($this->multiUser)
            ->withHeader('X-Business-ID', (string) $this->businessA->id)
            ->deleteJson("/api/v1/businesses/{$emptyBiz->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('businesses', ['id' => $emptyBiz->id]);
    }

    /**
     * Product.stock Authority Check: BranchInventory is authoritative
     */
    public function test_branch_inventory_authority(): void
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Test Item',
            'sku' => 'SKU-AUTH-001',
            'stock' => 999, // Legacy column
        ]);

        $bi = BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 42,
            'minimum_stock' => 10,
        ]);

        $response = $this->actingAs($this->multiUser)
            ->withHeader('X-Business-ID', (string) $this->businessA->id)
            ->getJson('/api/v1/inventory');

        $response->assertStatus(200);
        $item = collect($response->json())->firstWhere('product_id', $product->id);
        $this->assertNotNull($item);
        $this->assertEquals(42, $item['quantity_on_hand']);
    }
}
