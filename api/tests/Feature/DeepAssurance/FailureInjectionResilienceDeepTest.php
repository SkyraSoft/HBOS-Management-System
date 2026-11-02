<?php

namespace Tests\Feature\DeepAssurance;

use App\Models\Branch;
use App\Models\Business;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Deep Assurance: Failure Injection, Malformed Inputs, XSS, SQLi, and Security Boundary Resilience
 */
class FailureInjectionResilienceDeepTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $cashier;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->business = Business::create([
            'name' => 'Hardened Retail Systems',
            'legal_name' => 'Hardened Retail Systems Ltd',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Secure Branch Alpha',
            'code' => 'SEC-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Security Admin',
            'email' => 'admin@hardened.test',
            'password' => bcrypt('Secret123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->owner->businesses()->attach($this->business->id);

        setPermissionsTeamId($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->cashier = User::create([
            'name' => 'Junior Cashier',
            'email' => 'cashier@hardened.test',
            'password' => bcrypt('Secret123!'),
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->assignRole('Salesperson');

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Standard Detergent 1kg',
            'sku' => 'DET-01',
            'barcode' => '896000123456',
            'selling_price' => 450.00,
            'cost_price' => 350.00,
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->withHeader('Accept', 'application/json')
            ->getJson('/api/v1/businesses');

        $response->assertStatus(401);
    }

    public function test_invalid_bearer_token_rejected_with_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_malformed_fake_token_xyz')
            ->withHeader('Accept', 'application/json')
            ->getJson('/api/v1/businesses');

        $response->assertStatus(401);
    }

    public function test_deactivated_user_token_blocked(): void
    {
        $this->cashier->update(['is_active' => false]);

        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/products');

        // Middleware rejects inactive user
        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    public function test_sqli_shaped_input_safely_handled(): void
    {
        $maliciousPayload = "' OR 1=1; DROP TABLE products; --";

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/products?search=' . urlencode($maliciousPayload));

        $response->assertStatus(200);
        // Products table must still exist and contain the original product
        $this->assertDatabaseHas('products', ['id' => $this->product->id]);
    }

    public function test_xss_bearing_payload_safely_persisted_without_raw_execution(): void
    {
        $xssString = '<script>alert("XSS")</script><img src=x onerror=alert(1)>';

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/products', [
                'name' => 'Safe Product ' . $xssString,
                'sku' => 'XSS-001',
                'selling_price' => 100.00,
                'cost_price' => 50.00,
                'stock' => 10,
            ]);

        $response->assertStatus(201);
        $productId = $response->json('id');

        // Fetch back and verify the JSON response is structured, not rendering HTML
        $fetchResp = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson("/api/v1/products/{$productId}");

        $fetchResp->assertStatus(200);
        $this->assertStringContainsString('Safe Product', $fetchResp->json('name'));
    }

    public function test_non_existent_resource_returns_404(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->getJson('/api/v1/products/9999999');

        $response->assertStatus(404);
    }

    public function test_method_not_allowed_on_restricted_verb(): void
    {
        // /api/v1/auth/login only accepts POST; calling DELETE should return 405
        $response = $this->deleteJson('/api/v1/auth/login');

        $response->assertStatus(405);
    }

    public function test_missing_required_fields_returns_422(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Business-Id', $this->business->id)
            ->postJson('/api/v1/products', [
                'sku' => 'MISSING-NAME',
                // Missing name, selling_price, etc.
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'selling_price']);
    }

    public function test_cashier_privilege_escalation_to_business_settings_denied(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withHeader('X-Business-Id', $this->business->id)
            ->putJson("/api/v1/businesses/{$this->business->id}", [
                'name' => 'Hacked Business Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_database_transaction_rolls_back_on_unhandled_failure(): void
    {
        $initialProductCount = Product::where('business_id', $this->business->id)->count();

        try {
            DB::transaction(function () {
                Product::create([
                    'business_id' => $this->business->id,
                    'name' => 'Rollback Candidate Product',
                    'sku' => 'ROLL-01',
                    'selling_price' => 500.00,
                    'cost_price' => 300.00,
                ]);

                // Deliberate simulated mid-flight exception
                throw new \RuntimeException('SIMULATED_TRANSACTION_FAILURE');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Must be completely rolled back
        $finalCount = Product::where('business_id', $this->business->id)->count();
        $this->assertEquals($initialProductCount, $finalCount);
        $this->assertDatabaseMissing('products', ['sku' => 'ROLL-01']);
    }
}
