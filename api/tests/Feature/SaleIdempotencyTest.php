<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;

class SaleIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected $business;
    protected $branch;
    protected $owner;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Idempotency Test Business']);
        $this->branch = Branch::create(['business_id' => $this->business->id, 'name' => 'Main Branch', 'is_primary' => true]);

        setPermissionsTeamId($this->business->id);

        $this->owner = User::factory()->create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        \App\Models\FinancialAccount::create(['business_id' => $this->business->id, 'branch_id' => $this->branch->id, 'name' => 'Main Drawer', 'type' => 'cash', 'opening_balance' => 100000, 'balance' => 100000, 'is_default' => true, 'status' => 'active']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Idempotent Product',
            'cost_price' => 10,
            'selling_price' => 25,
            'stock' => 50,
            'unit' => 'pcs'
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 50
        ]);
    }

    public function test_duplicate_checkout_with_same_idempotency_key_does_not_deduct_stock_twice()
    {
        $token = $this->owner->createToken('test')->plainTextToken;
        $idempotencyKey = 'IDEM-KEY-999-UUID';

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'paid_amount' => 50,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]]
        ];

        // First checkout request
        $res1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', $payload);

        $res1->assertStatus(201);
        $saleId1 = $res1->json('id');
        $this->assertDatabaseHas('branch_inventories', ['quantity_on_hand' => 48]);

        // Second checkout request (retry with identical idempotency_key)
        $res2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', $payload);

        $res2->assertStatus(201);
        $this->assertEquals($saleId1, $res2->json('id'));

        // Stock quantity on hand remains 48 (NOT 46!)
        $this->assertDatabaseHas('branch_inventories', ['quantity_on_hand' => 48]);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
    }

    public function test_duplicate_checkout_with_conflicting_payload_is_rejected()
    {
        $token = $this->owner->createToken('test')->plainTextToken;
        $idempotencyKey = 'IDEM-KEY-CONFLICT-1';

        $payloadOriginal = [
            'idempotency_key' => $idempotencyKey,
            'paid_amount' => 50,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]]
        ];

        $res1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', $payloadOriginal);

        $res1->assertStatus(201);

        // Conflicting payload with same idempotency key (different quantity 5 instead of 2)
        $payloadConflict = [
            'idempotency_key' => $idempotencyKey,
            'paid_amount' => 125,
            'items' => [['product_id' => $this->product->id, 'quantity' => 5]]
        ];

        $res2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', $payloadConflict);

        $res2->assertStatus(422);
        $res2->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_duplicate_checkout_with_conflicting_payment_and_pricing_is_rejected()
    {
        $token = $this->owner->createToken('test')->plainTextToken;
        $idempotencyKey = 'IDEM-KEY-PAYMENT-CONFLICT';

        $payloadOriginal = [
            'idempotency_key' => $idempotencyKey,
            'paid_amount' => 45,
            'payment_method' => 'Cash',
            'discount' => 5,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]]
        ];

        $res1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', $payloadOriginal);

        $res1->assertStatus(201);

        // Retry with same key but different paid_amount
        $resConflictPaid = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', array_merge($payloadOriginal, ['paid_amount' => 10]));
        $resConflictPaid->assertStatus(422)->assertJsonValidationErrors(['idempotency_key']);

        // Retry with same key but different payment_method
        $resConflictMethod = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', array_merge($payloadOriginal, ['payment_method' => 'Card']));
        $resConflictMethod->assertStatus(422)->assertJsonValidationErrors(['idempotency_key']);

        // Retry with same key but different sale discount
        $resConflictDisc = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', array_merge($payloadOriginal, ['discount' => 10]));
        $resConflictDisc->assertStatus(422)->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_return_idempotency_with_identical_and_conflicting_payload()
    {
        $token = $this->owner->createToken('test')->plainTextToken;

        // Create sale
        $saleRes = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson('/api/v1/sales', [
            'paid_amount' => 100,
            'items' => [['product_id' => $this->product->id, 'quantity' => 4]]
        ]);
        $saleId = $saleRes->json('id');
        $saleItemId = $saleRes->json('items.0.id');

        $returnKey = 'RET-KEY-UUID-55';
        $returnPayload = [
            'idempotency_key' => $returnKey,
            'reason' => 'Defective item',
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 2]]
        ];

        // First return request
        $retRes1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", $returnPayload);

        $retRes1->assertStatus(201);
        $returnId1 = $retRes1->json('id');

        // Identical return retry
        $retRes2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", $returnPayload);

        $retRes2->assertStatus(201);
        $this->assertEquals($returnId1, $retRes2->json('id'));
        $this->assertDatabaseCount('sale_returns', 1);

        // Conflicting return retry with same return key (quantity 3 instead of 2)
        $conflictingReturn = [
            'idempotency_key' => $returnKey,
            'reason' => 'Defective item',
            'items' => [['sale_item_id' => $saleItemId, 'quantity' => 3]]
        ];

        $retRes3 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => $this->business->id
        ])->postJson("/api/v1/sales/{$saleId}/returns", $conflictingReturn);

        $retRes3->assertStatus(422);
        $retRes3->assertJsonValidationErrors(['idempotency_key']);
    }
}
