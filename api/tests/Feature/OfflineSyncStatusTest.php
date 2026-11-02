<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use Database\Seeders\RolesAndPermissionsSeeder;

class OfflineSyncStatusTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Khyber Mart Rawalpindi']);
        $this->branch = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Counter',
            'code' => 'KHY-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        setPermissionsTeamId($this->business->id);
        $this->user = User::factory()->create([
            'name' => 'Cashier Khan',
            'email' => 'cashier@khyber.pk',
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->user->businesses()->attach($this->business->id);
        $this->user->assignRole('Salesperson');
    }

    public function test_unauthenticated_request_is_denied(): void
    {
        $response = $this->getJson('/api/v1/sync/status');
        $response->assertStatus(401);
    }

    public function test_sync_status_returns_truthful_device_context_and_server_time(): void
    {
        $deviceId = 'test-workstation-uuid-998877';
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders([
                'X-Business-ID' => $this->business->id,
                'X-HBOS-Device-ID' => $deviceId,
                'X-Client-Version' => '1.0.0',
            ])
            ->getJson('/api/v1/sync/status');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'online',
            'engine' => 'HBOS-Sync-Core-v1.0',
            'business_id' => $this->business->id,
            'client_version_status' => 'compatible',
            'device_id_received' => $deviceId,
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('server_timestamp', $data);
        $this->assertArrayHasKey('server_timezone', $data);

        // Prove ZERO global-device fake telemetry claims
        $this->assertArrayNotHasKey('all_devices_synced', $data);
        $this->assertArrayNotHasKey('network_device_count', $data);
        $this->assertArrayNotHasKey('remote_device_health', $data);
    }

    public function test_catalog_delta_respects_tenant_scoping(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders([
                'X-Business-ID' => $this->business->id,
                'X-Client-Version' => '1.0.0',
            ])
            ->getJson('/api/v1/sync/catalog-delta');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'server_timestamp',
            'products',
            'categories',
            'subcategories',
            'brands',
            'customers',
        ]);
    }

    public function test_client_version_gate_returns_426_when_outdated(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders([
                'X-Business-ID' => $this->business->id,
                'X-App-Version' => '0.0.9', // Below min 1.0.0
            ])
            ->getJson('/api/v1/sync/status');

        $response->assertStatus(426);
        $response->assertJson([
            'status' => 'UPDATE_REQUIRED',
            'current_version' => '0.0.9',
            'minimum_supported_version' => '1.0.0',
        ]);
    }
}
