<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Supplier;
use Database\Seeders\RolesAndPermissionsSeeder;

class SupplierPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $manager;
    protected $salesperson;
    protected $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->business = Business::create(['name' => 'RBAC Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        // Owner
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        // Manager
        $this->manager = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->manager->businesses()->attach($this->business->id);
        $this->manager->assignRole('Branch Manager');

        // Salesperson
        $this->salesperson = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->salesperson->businesses()->attach($this->business->id);
        $this->salesperson->assignRole('Salesperson');

        $this->supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Test Supplier',
            'code' => 'SUP-001'
        ]);
    }

    public function test_business_owner_can_manage_suppliers()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // List
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->getJson('/api/v1/suppliers')
            ->assertStatus(200);

        // Create
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/suppliers', ['name' => 'Supplier 2', 'code' => 'SUP-002'])
            ->assertStatus(201);

        // Update
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->putJson("/api/v1/suppliers/{$this->supplier->id}", ['name' => 'Updated Supplier'])
            ->assertStatus(200);
    }

    public function test_branch_manager_can_view_but_cannot_manage_suppliers()
    {
        $token = $this->manager->createToken('test')->plainTextToken;

        // View List - Allowed
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->getJson('/api/v1/suppliers')
            ->assertStatus(200);

        // View Show - Allowed
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->getJson("/api/v1/suppliers/{$this->supplier->id}")
            ->assertStatus(200);

        // Create - Denied (403)
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/suppliers', ['name' => 'Denied Supplier'])
            ->assertStatus(403);

        // Update - Denied (403)
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->putJson("/api/v1/suppliers/{$this->supplier->id}", ['name' => 'Denied Update'])
            ->assertStatus(403);

        // Delete - Denied (403)
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->deleteJson("/api/v1/suppliers/{$this->supplier->id}")
            ->assertStatus(403);
    }

    public function test_salesperson_cannot_view_or_manage_suppliers()
    {
        $token = $this->salesperson->createToken('test')->plainTextToken;

        // View List - Denied (403)
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->getJson('/api/v1/suppliers')
            ->assertStatus(403);

        // Create - Denied (403)
        $this->withHeaders(['Authorization' => "Bearer $token", 'X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/suppliers', ['name' => 'Denied'])
            ->assertStatus(403);
    }
}
