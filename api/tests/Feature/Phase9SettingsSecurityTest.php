<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Sale;
use App\Models\Product;
use App\Models\BranchInventory;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Phase9SettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Business $businessA;
    protected Business $businessB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;
    protected User $ownerA;
    protected User $managerA1;
    protected User $salespersonA1;
    protected User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessA = Business::create(['name' => 'Settings Tenant A', 'currency' => 'PKR']);
        $this->businessB = Business::create(['name' => 'Settings Tenant B', 'currency' => 'PKR']);

        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Main Branch A1', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Secondary Branch A2', 'is_primary' => false]);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Main Branch B1', 'is_primary' => true]);

        setPermissionsTeamId($this->businessA->id);
        $roleOwnerA = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $roleManagerA = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);
        $roleSalespersonA = Role::firstOrCreate(['name' => 'Salesperson', 'guard_name' => 'web', 'team_id' => $this->businessA->id]);

        $permManageSettings = Permission::firstOrCreate(['name' => 'manage business settings', 'guard_name' => 'web']);
        $permManageBranches = Permission::firstOrCreate(['name' => 'manage branches', 'guard_name' => 'web']);
        $permManageBranchSettings = Permission::firstOrCreate(['name' => 'manage branch settings', 'guard_name' => 'web']);

        $roleOwnerA->givePermissionTo([$permManageSettings, $permManageBranches, $permManageBranchSettings]);
        $roleManagerA->givePermissionTo([$permManageBranchSettings]);

        $this->ownerA = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'is_active' => true
        ]);
        $this->ownerA->businesses()->attach($this->businessA->id);
        $this->ownerA->assignRole($roleOwnerA);

        $this->managerA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'is_active' => true
        ]);
        $this->managerA1->businesses()->attach($this->businessA->id);
        $this->managerA1->assignRole($roleManagerA);

        $this->salespersonA1 = User::factory()->create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'is_active' => true
        ]);
        $this->salespersonA1->businesses()->attach($this->businessA->id);
        $this->salespersonA1->assignRole($roleSalespersonA);

        // Tenant B
        setPermissionsTeamId($this->businessB->id);
        $roleOwnerB = Role::firstOrCreate(['name' => 'Business Owner', 'guard_name' => 'web', 'team_id' => $this->businessB->id]);
        $roleOwnerB->givePermissionTo([$permManageSettings, $permManageBranches]);

        $this->ownerB = User::factory()->create([
            'business_id' => $this->businessB->id,
            'branch_id' => $this->branchB1->id,
            'is_active' => true
        ]);
        $this->ownerB->businesses()->attach($this->businessB->id);
        $this->ownerB->assignRole($roleOwnerB);
    }

    /**
     * R. Settings Tenant Isolation
     */
    public function test_settings_tenant_isolation()
    {
        Setting::create([
            'business_id' => $this->businessA->id,
            'key' => 'receipt_header',
            'value' => 'Alpha Header',
            'type' => 'string'
        ]);

        Setting::create([
            'business_id' => $this->businessB->id,
            'key' => 'receipt_header',
            'value' => 'Beta Secret Header',
            'type' => 'string'
        ]);

        // Owner A cannot see Tenant B setting
        $resA = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/settings');

        $resA->assertStatus(200);
        $data = $resA->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Alpha Header', $data[0]['value']);
        $this->assertStringNotContainsString('Beta Secret', json_encode($data));

        // Direct show endpoint for key isolates by tenant
        $showA = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/settings/receipt_header');
        $showA->assertStatus(200);
        $this->assertEquals('Alpha Header', $showA->json('data.value'));
    }

    /**
     * S & T. Settings Read and Write RBAC
     */
    public function test_settings_read_and_write_rbac()
    {
        // 1. Salesperson read denied
        $this->actingAs($this->salespersonA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/settings')
            ->assertStatus(403);

        // 2. Salesperson write denied
        $this->actingAs($this->salespersonA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/settings', [
                'settings' => ['receipt_header' => 'Hacked Header']
            ])->assertStatus(403);

        // 3. Branch Manager read denied (lacks manage business settings)
        $this->actingAs($this->managerA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->getJson('/api/v1/settings')
            ->assertStatus(403);

        // 4. Owner write succeeds
        $resOwner = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/settings', [
                'settings' => ['receipt_header' => 'Authorized Owner Header']
            ]);
        $resOwner->assertStatus(200);
    }

    /**
     * U. Settings Allowlist
     */
    public function test_settings_rejects_arbitrary_or_dangerous_keys()
    {
        $dangerousPayloads = [
            'danger_mode' => 'true',
            'disable_tenant_security' => 'yes',
            'allow_negative_stock' => 'true',
            'rewrite_cogs' => 'yes',
            'foo' => 'bar',
        ];

        foreach ($dangerousPayloads as $key => $val) {
            $response = $this->actingAs($this->ownerA)
                ->withHeader('X-Business-ID', $this->businessA->id)
                ->postJson('/api/v1/settings', [
                    'settings' => [$key => $val]
                ]);

            $response->assertStatus(422);
            $this->assertDatabaseMissing('settings', ['key' => $key]);
        }
    }

    /**
     * V. Settings Type Validation
     */
    public function test_settings_type_validation()
    {
        // 1. Valid JSON type setting
        $resJson = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/settings', [
                'settings' => [
                    [
                        'key' => 'receipt_settings',
                        'value' => ['paper_size' => '80mm', 'show_barcode' => true],
                        'type' => 'json'
                    ]
                ]
            ]);
        $resJson->assertStatus(200);

        // 2. Invalid JSON string returns 422
        $resInvalidJson = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/settings', [
                'settings' => [
                    [
                        'key' => 'receipt_settings',
                        'value' => '{invalid_json: true',
                        'type' => 'json'
                    ]
                ]
            ]);
        $resInvalidJson->assertStatus(422);

        // 3. Valid boolean type setting
        $resBool = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/settings', [
                'settings' => [
                    [
                        'key' => 'show_tax_number',
                        'value' => true,
                        'type' => 'boolean'
                    ]
                ]
            ]);
        $resBool->assertStatus(200);

        $saved = Setting::where('business_id', $this->businessA->id)->where('key', 'show_tax_number')->first();
        $this->assertEquals('1', $saved->value);
    }

    /**
     * W & X. Currency Single Authority & Historical Immutability
     */
    public function test_currency_immutability_and_business_authority()
    {
        // 1. Unused business can update currency
        $resEmpty = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/businesses/{$this->businessA->id}", [
                'currency' => 'USD'
            ]);
        $resEmpty->assertStatus(200);
        $this->assertEquals('USD', $this->businessA->fresh()->currency);

        // 2. Create operational transaction (Sale)
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-TEST-001',
            'date' => now()->toDateString(),
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 1000,
            'due_amount' => 0,
            'status' => 'completed',
        ]);

        // 3. Attempting to change currency after operational records exist fails with 422
        $resWithHistory = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/businesses/{$this->businessA->id}", [
                'currency' => 'EUR'
            ]);

        $resWithHistory->assertStatus(422);
        $this->assertEquals('USD', $this->businessA->fresh()->currency);
    }

    /**
     * Y. Stock Threshold Single Authority
     */
    public function test_stock_threshold_preserves_branch_inventory_authority()
    {
        $product = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Test Stock Product',
            'cost_price' => 100,
            'selling_price' => 150,
        ]);

        $branchInv = BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 15,
            'minimum_stock' => 5,
        ]);

        // Setting low_stock_threshold_default does NOT mutate existing BranchInventory.minimum_stock
        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->postJson('/api/v1/settings', [
                'settings' => [
                    'low_stock_threshold_default' => 20
                ]
            ])->assertStatus(200);

        $this->assertEquals(5, $branchInv->fresh()->minimum_stock);
    }

    /**
     * Z. Business Deletion Governance
     */
    public function test_business_deletion_governance()
    {
        // 1. Salesperson cannot delete business
        $this->actingAs($this->salespersonA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->deleteJson("/api/v1/businesses/{$this->businessA->id}")
            ->assertStatus(403);

        // 2. Branch Manager cannot delete business
        $this->actingAs($this->managerA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->deleteJson("/api/v1/businesses/{$this->businessA->id}")
            ->assertStatus(403);

        // 3. Business with operational history cannot be deleted
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-DEL-001',
            'date' => now()->toDateString(),
            'subtotal' => 500,
            'total' => 500,
            'paid_amount' => 500,
            'due_amount' => 0,
            'status' => 'completed',
        ]);

        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->deleteJson("/api/v1/businesses/{$this->businessA->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('businesses', ['id' => $this->businessA->id]);

        // 4. Cross-tenant deletion denied (cannot delete business belonging to another tenant)
        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->deleteJson("/api/v1/businesses/{$this->businessB->id}")
            ->assertStatus(403);
    }

    /**
     * AC & AD. Branch Primary and Deletion Safety
     */
    public function test_branch_primary_and_deletion_safety()
    {
        // 1. Primary branch cannot be deleted
        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->deleteJson("/api/v1/branches/{$this->branchA1->id}")
            ->assertStatus(422);

        // 2. Primary branch cannot be deactivated
        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/branches/{$this->branchA1->id}", [
                'status' => 'inactive'
            ])->assertStatus(422);

        // 3. Non-primary branch with sales cannot be deleted
        Sale::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'user_id' => $this->ownerA->id,
            'invoice_number' => 'INV-BRA2-001',
            'date' => now()->toDateString(),
            'subtotal' => 100,
            'total' => 100,
            'paid_amount' => 100,
            'due_amount' => 0,
            'status' => 'completed',
        ]);

        $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->deleteJson("/api/v1/branches/{$this->branchA2->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('branches', ['id' => $this->branchA2->id]);
    }

    /**
     * AC-9.24. Branch Settings Authorization & Unassigned Branch Protection
     */
    public function test_branch_settings_authorization_and_unassigned_branch_protection()
    {
        // 1. Business Owner can mutate any branch settings in own business
        $resOwner = $this->actingAs($this->ownerA)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/branches/{$this->branchA2->id}", [
                'phone' => '0300-1234567',
            ]);
        $resOwner->assertStatus(200);

        // 2. Manager with 'manage branch settings' can update assigned branch
        $resManagerAssigned = $this->actingAs($this->managerA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/branches/{$this->branchA1->id}", [
                'phone' => '0300-7654321',
            ]);
        $resManagerAssigned->assertStatus(200);

        // 3. Manager cannot alter an unassigned branch in same business
        $resManagerUnassigned = $this->actingAs($this->managerA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/branches/{$this->branchA2->id}", [
                'phone' => '0300-9999999',
            ]);
        $resManagerUnassigned->assertStatus(403);
        $this->assertEquals('Cannot alter an unassigned branch.', $resManagerUnassigned->json('message'));

        // 4. Manager cannot access foreign branch
        $resManagerForeign = $this->actingAs($this->managerA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/branches/{$this->branchB1->id}", [
                'phone' => '0300-0000000',
            ]);
        $resManagerForeign->assertStatus(404);

        // 5. Salesperson has zero branch settings mutation rights
        $resSalesperson = $this->actingAs($this->salespersonA1)
            ->withHeader('X-Business-ID', $this->businessA->id)
            ->putJson("/api/v1/branches/{$this->branchA1->id}", [
                'phone' => '0300-1111111',
            ]);
        $resSalesperson->assertStatus(403);
    }
}
