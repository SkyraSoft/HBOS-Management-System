<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Supplier;
use Database\Seeders\RolesAndPermissionsSeeder;

class SupplierPaymentTest extends TestCase
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

        $this->business = Business::create(['name' => 'Payment Test Business']);
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

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);
        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => null, 'name' => 'Main Bank', 'type' => 'bank', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => false, 'status' => 'active']);

        $this->supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Payment Supplier',
            'code' => 'SUP-PAY',
            'balance' => 1000.00
        ]);
    }

    public function test_business_owner_can_record_supplier_payment()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
            'amount' => 400.00,
            'date' => '2026-09-14',
            'payment_method' => 'bank_transfer',
            'notes' => 'Part settlement'
        ]);

        $response->assertStatus(201);

        // Balance decremented to 600.00
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id, 'balance' => 600.00]);

        // Payment record created
        $this->assertDatabaseHas('supplier_payments', [
            'business_id' => $this->business->id,
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->owner->id,
            'amount' => 400.00,
            'payment_method' => 'bank_transfer'
        ]);
    }

    public function test_branch_manager_cannot_record_supplier_payment()
    {
        $token = $this->manager->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
            'amount' => 100.00
        ]);

        // Access Denied for Branch Manager (Business-wide supplier debt mutation is Owner only)
        $response->assertStatus(403);
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id, 'balance' => 1000.00]);
    }

    public function test_salesperson_cannot_record_supplier_payment()
    {
        $token = $this->salesperson->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
            'amount' => 100.00
        ]);

        $response->assertStatus(403);
    }

    public function test_payment_exceeding_supplier_balance_is_rejected()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Balance is 1000.00. Attempting to pay 1500.00 -> Fails with 422
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", [
            'amount' => 1500.00
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('suppliers', ['id' => $this->supplier->id, 'balance' => 1000.00]);
    }

    public function test_payment_history_can_be_retrieved_by_owner_and_manager()
    {
        $ownerToken = $this->owner->createToken('test')->plainTextToken;
        $managerToken = $this->manager->createToken('test')->plainTextToken;

        // Record a payment first as Owner
        $this->withHeaders(['Authorization' => "Bearer $ownerToken", 'X-Business-ID' => $this->business->id])
            ->postJson("/api/v1/suppliers/{$this->supplier->id}/payments", ['amount' => 200.00]);

        // Owner can view history
        $this->withHeaders(['Authorization' => "Bearer $ownerToken", 'X-Business-ID' => $this->business->id])
            ->getJson("/api/v1/suppliers/{$this->supplier->id}/payments")
            ->assertStatus(200)
            ->assertJsonCount(1);

        // Manager can view supplier payment history
        $this->withHeaders(['Authorization' => "Bearer $managerToken", 'X-Business-ID' => $this->business->id])
            ->getJson("/api/v1/suppliers/{$this->supplier->id}/payments")
            ->assertStatus(200);
    }
}
