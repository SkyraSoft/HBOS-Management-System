<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\Customer;
use App\Models\KhataTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSoftDeleteAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch;
    protected User $owner;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Sec Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);
        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Item X',
            'cost_price' => 50,
            'selling_price' => 100,
            'stock' => 100,
            'unit' => 'pcs',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 100,
        ]);
    }

    public function test_customer_creation_guards_balance_and_business_id()
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/customers', [
                'business_id' => 9999, // Attempted spoof
                'name' => 'Protected Customer',
                'balance' => 9999.00,  // Attempted balance forgery
                'opening_balance' => 250.00,
            ]);

        $response->assertStatus(201);
        $customer = Customer::where('name', 'Protected Customer')->first();
        $this->assertEquals($this->business->id, $customer->business_id);
        $this->assertEquals(250.00, $customer->opening_balance);
        $this->assertEquals(250.00, $customer->balance);
    }

    public function test_customer_update_cannot_modify_opening_balance_or_balance()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Original Name',
            'opening_balance' => 100.00,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($customer);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->putJson("/api/v1/customers/{$customer->id}", [
                'name' => 'Updated Name',
                'balance' => 9999.00,
                'opening_balance' => 9999.00,
                'business_id' => 9999,
            ]);

        $response->assertStatus(200);
        $customer->refresh();
        $this->assertEquals('Updated Name', $customer->name);
        $this->assertEquals(100.00, $customer->balance);
        $this->assertEquals(100.00, $customer->opening_balance);
        $this->assertEquals($this->business->id, $customer->business_id);
    }

    public function test_customer_soft_delete_preserves_records_and_blocks_new_operations()
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Customer To Delete',
            'opening_balance' => 100.00,
        ]);
        app(CustomerAccountService::class)->recalculateBalance($customer);

        // Soft delete via API
        $delResponse = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->deleteJson("/api/v1/customers/{$customer->id}");

        $delResponse->assertStatus(200);
        $this->assertNotNull($customer->fresh()->deleted_at);

        // Cannot create new sale with soft-deleted customer
        $saleResponse = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/sales', [
                'branch_id' => $this->branch->id,
                'customer_id' => $customer->id,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100],
                ],
                'paid_amount' => 0,
            ]);

        $saleResponse->assertStatus(422);

        // Cannot record payment for soft-deleted customer
        $pmtResponse = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/customers/{$customer->id}/payments", [
                'branch_id' => $this->branch->id,
                'amount' => 50.00,
            ]);

        $pmtResponse->assertStatus(404);
    }

    public function test_legacy_khata_endpoints_return_410_for_writes_and_preserve_read()
    {
        // Write: POST
        $postRes = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->postJson('/api/v1/khata', [
                'customer_id' => 1,
                'amount' => 100,
                'type' => 'give',
            ]);
        $postRes->assertStatus(410);

        // Write: DELETE
        $delRes = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->deleteJson('/api/v1/khata/1');
        $delRes->assertStatus(410);

        // Read: GET
        $getRes = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Business-ID' => $this->business->id])
            ->getJson('/api/v1/khata');
        $getRes->assertStatus(200);
    }
}
