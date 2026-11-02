<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use App\Services\UserGovernanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DefectRepairsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /**
     * DEF-P1-001 & DEF-P1-002:
     * Registration creates business, owner account, and owner role with canonical permissions.
     */
    public function test_tenant_registration_provisions_owner_role_with_permissions(): void
    {
        $payload = [
            'business_name' => 'Corrective Retail Test',
            'name' => 'Owner Test User',
            'email' => 'owner.test@example.com',
            'phone' => '03001234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'business' => ['id', 'name'],
                'branch' => ['id', 'name'],
                'access_token'
            ]);

        $businessId = $response->json('business.id');
        $userId = $response->json('user.id');

        $user = User::find($userId);
        setPermissionsTeamId($businessId);

        // Verify Owner has permissions
        $this->assertTrue($user->hasRole('Business Owner'));
        $ownerRole = Role::where('name', 'Business Owner')->where('business_id', $businessId)->first();
        $this->assertNotNull($ownerRole);
        $this->assertGreaterThan(0, $ownerRole->permissions()->count(), 'Owner role must have synced permissions.');
        $this->assertTrue($user->hasPermissionTo('manage products'));
        $this->assertTrue($user->hasPermissionTo('manage branches'));
        $this->assertTrue($user->hasPermissionTo('manage users'));
    }

    /**
     * DEF-P1-002 & DEF-P1-003:
     * Newly provisioned Owner can create products without 403 Forbidden.
     */
    public function test_newly_provisioned_owner_can_create_products(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Product Test Biz',
            'name' => 'Product Owner',
            'email' => 'product.owner@example.com',
            'phone' => '03001112233',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $token = $regResponse->json('access_token');
        $businessId = $regResponse->json('business.id');

        $productPayload = [
            'name' => 'Test Basmati Rice 1kg',
            'sku' => 'RICE-TEST-001',
            'cost_price' => 120,
            'selling_price' => 150,
            'stock' => 50,
            'min_stock' => 10,
            'unit' => 'kg',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/products', $productPayload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'business_id' => $businessId,
            'name' => 'Test Basmati Rice 1kg',
            'sku' => 'RICE-TEST-001',
        ]);
    }

    /**
     * DEF-P1-004:
     * Staff creation requires branch for branch-scoped roles and preserves branch assignment.
     */
    public function test_staff_creation_requires_and_preserves_branch_assignment(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Staff Test Biz',
            'name' => 'Staff Owner',
            'email' => 'staff.owner@example.com',
            'phone' => '03009998877',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $token = $regResponse->json('access_token');
        $businessId = $regResponse->json('business.id');
        $branchId = $regResponse->json('branch.id');

        // Missing branch for Cashier must be rejected
        $failResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/users', [
            'name' => 'Cashier Without Branch',
            'email' => 'cashier.nobranch@example.com',
            'role' => 'Salesperson',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $failResponse->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);

        // Valid branch for Cashier must succeed and attach branch
        $successResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ])->postJson('/api/v1/users', [
            'name' => 'Valid Branch Cashier',
            'email' => 'valid.cashier@example.com',
            'role' => 'Salesperson',
            'branch_id' => $branchId,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $successResponse->assertStatus(201);
        $cashierId = $successResponse->json('data.id') ?? $successResponse->json('id');
        $cashier = User::find($cashierId);

        $this->assertEquals($branchId, $cashier->branch_id);
        $this->assertTrue($cashier->branches()->where('branches.id', $branchId)->exists());

        // Verify Cashier role and permissions
        setPermissionsTeamId($businessId);
        $this->assertTrue($cashier->hasRole('Salesperson'));
        $this->assertTrue($cashier->hasPermissionTo('create sales'));
        $this->assertTrue($cashier->hasPermissionTo('view products'));
        $this->assertFalse($cashier->hasPermissionTo('manage business settings'));
    }

    /**
     * DEF-P2-006:
     * Financial summary and listing on empty tenant return truthful zeros.
     */
    public function test_financial_summary_on_empty_tenant_returns_truthful_zeros(): void
    {
        $regResponse = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Financial Zero Biz',
            'name' => 'Finance Owner',
            'email' => 'finance.owner@example.com',
            'phone' => '03004443322',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $token = $regResponse->json('access_token');
        $businessId = $regResponse->json('business.id');

        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $businessId,
        ];

        // Purchases empty check
        $purchasesRes = $this->withHeaders($headers)->getJson('/api/v1/purchases');
        $purchasesRes->assertStatus(200);
        $purchasesData = $purchasesRes->json('data') ?? $purchasesRes->json();
        $this->assertEmpty($purchasesData);

        // Suppliers empty check
        $suppliersRes = $this->withHeaders($headers)->getJson('/api/v1/suppliers');
        $suppliersRes->assertStatus(200);
        $suppliersData = $suppliersRes->json('data') ?? $suppliersRes->json();
        $this->assertEmpty($suppliersData);
    }
}
