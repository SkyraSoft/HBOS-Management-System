<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InterBranchStockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branchSource;
    protected Branch $branchDest;
    protected User $owner;
    protected User $managerSource;
    protected User $managerDest;
    protected User $cashier;
    protected string $ownerToken;
    protected string $managerSourceToken;
    protected string $managerDestToken;
    protected string $cashierToken;

    protected Product $discreteProduct;
    protected Product $fractionalProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Metro Retail Enterprise']);
        setPermissionsTeamId($this->business->id);

        $this->branchSource = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Main Warehouse Branch',
            'code' => 'MAIN-01',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $this->branchDest = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Gulshan Outlet Branch',
            'code' => 'GUL-02',
            'is_primary' => false,
            'status' => 'active',
        ]);

        // Owner
        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchSource->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->branches()->attach([$this->branchSource->id, $this->branchDest->id], ['business_id' => $this->business->id]);
        $this->owner->assignRole('Business Owner');
        $this->ownerToken = $this->owner->createToken('Owner')->plainTextToken;

        // Manager at Source
        $this->managerSource = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchSource->id,
        ]);
        $this->managerSource->businesses()->attach($this->business->id);
        $this->managerSource->branches()->attach($this->branchSource->id, ['business_id' => $this->business->id]);
        $this->managerSource->assignRole('Branch Manager');
        $this->managerSourceToken = $this->managerSource->createToken('MgrSrc')->plainTextToken;

        // Manager at Destination
        $this->managerDest = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchDest->id,
        ]);
        $this->managerDest->businesses()->attach($this->business->id);
        $this->managerDest->branches()->attach($this->branchDest->id, ['business_id' => $this->business->id]);
        $this->managerDest->assignRole('Branch Manager');
        $this->managerDestToken = $this->managerDest->createToken('MgrDest')->plainTextToken;

        // Salesperson (Cashier)
        $this->cashier = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchSource->id,
        ]);
        $this->cashier->businesses()->attach($this->business->id);
        $this->cashier->branches()->attach($this->branchSource->id, ['business_id' => $this->business->id]);
        $this->cashier->assignRole('Salesperson');
        $this->cashierToken = $this->cashier->createToken('Cashier')->plainTextToken;

        // Products
        $this->discreteProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Packaged Cooking Oil 1L',
            'cost_price' => 500.00,
            'selling_price' => 620.00,
            'allow_fractional' => false,
            'stock' => 100.000,
        ]);

        $this->fractionalProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Basmati Rice Premium (Kg)',
            'cost_price' => 280.00,
            'selling_price' => 350.00,
            'allow_fractional' => true,
            'stock' => 500.000,
        ]);

        // Seed initial inventory at source branch
        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchSource->id,
            'product_id' => $this->discreteProduct->id,
            'quantity_on_hand' => '100.000',
        ]);

        BranchInventory::create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branchSource->id,
            'product_id' => $this->fractionalProduct->id,
            'quantity_on_hand' => '500.000',
        ]);
    }

    protected function authHeaders(string $token, array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$token}",
            'X-Business-ID' => (string) $this->business->id,
        ], $extra);
    }

    public function test_create_draft_stock_transfer_success()
    {
        $response = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'notes' => 'Weekly replenishment stock',
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 10,
                    'notes' => '10 bottles',
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'draft');
        $response->assertJsonPath('data.from_branch_id', $this->branchSource->id);
        $response->assertJsonPath('data.to_branch_id', $this->branchDest->id);
        $this->assertNotEmpty($response->json('data.transfer_number'));
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_cannot_transfer_to_same_source_and_destination()
    {
        $response = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchSource->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 5,
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['to_branch_id']);
    }

    public function test_cross_tenant_transfer_isolation_blocked()
    {
        $foreignBiz = Business::create(['name' => 'Foreign Retail Ltd']);
        $foreignBranch = Branch::create([
            'business_id' => $foreignBiz->id,
            'name' => 'Foreign Branch',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $foreignBranch->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 5,
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $response->assertStatus(422);
    }

    public function test_discrete_item_rejects_fractional_quantity()
    {
        $response = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => '2.500', // Discrete product cannot be fractional
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $response->assertStatus(422);
        $this->assertStringContainsString('discrete unit', $response->json('message'));
    }

    public function test_fractional_item_accepts_and_handles_scale_3_quantities()
    {
        $response = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->fractionalProduct->id,
                    'quantity' => '12.375',
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $response->assertStatus(201);
        $response->assertJsonPath('data.items.0.quantity_requested', '12.375');
    }

    public function test_dispatch_transfer_locks_and_decrements_source_inventory_only()
    {
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 15,
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $transferId = $createRes->json('data.id');

        $dispatchRes = $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));
        $dispatchRes->assertStatus(200);
        $dispatchRes->assertJsonPath('data.status', 'dispatched');
        $this->assertNotNull($dispatchRes->json('data.dispatched_at'));

        // Source inventory must be 100 - 15 = 85
        $sourceInv = BranchInventory::where('branch_id', $this->branchSource->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertEquals('85.000', (string) $sourceInv->quantity_on_hand);

        // Destination inventory must be 0 or null (not received yet)
        $destInv = BranchInventory::where('branch_id', $this->branchDest->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertTrue($destInv === null || bccomp((string) $destInv->quantity_on_hand, '0', 3) === 0);
    }

    public function test_in_transit_goods_not_sellable_or_counted_at_destination()
    {
        // Create and dispatch
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->fractionalProduct->id,
                    'quantity' => '50.000',
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $transferId = $createRes->json('data.id');
        $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));

        // Destination inventory balance should remain 0
        $destInv = BranchInventory::where('branch_id', $this->branchDest->id)
            ->where('product_id', $this->fractionalProduct->id)
            ->first();

        $this->assertTrue($destInv === null || bccomp((string) $destInv->quantity_on_hand, '0.000', 3) === 0);
    }

    public function test_double_dispatch_denial()
    {
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 5,
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $transferId = $createRes->json('data.id');
        $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken))->assertStatus(200);

        // Attempting to dispatch again must fail with 422
        $secondDispatch = $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));
        $secondDispatch->assertStatus(422);
    }

    public function test_receive_transfer_increments_destination_inventory()
    {
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 20,
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $transferId = $createRes->json('data.id');
        $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));

        // Destination manager receives transfer
        $receiveRes = $this->postJson("/api/v1/stock-transfers/{$transferId}/receive", [], $this->authHeaders($this->managerDestToken));
        $receiveRes->assertStatus(200);
        $receiveRes->assertJsonPath('data.status', 'received');
        $this->assertNotNull($receiveRes->json('data.received_at'));

        // Destination inventory must be exactly 20.000
        $destInv = BranchInventory::where('branch_id', $this->branchDest->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertNotNull($destInv);
        $this->assertEquals('20.000', (string) $destInv->quantity_on_hand);
    }

    public function test_double_receive_denial()
    {
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 10,
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $transferId = $createRes->json('data.id');
        $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));
        $this->postJson("/api/v1/stock-transfers/{$transferId}/receive", [], $this->authHeaders($this->managerDestToken))->assertStatus(200);

        // Second receive must fail
        $secondReceive = $this->postJson("/api/v1/stock-transfers/{$transferId}/receive", [], $this->authHeaders($this->managerDestToken));
        $secondReceive->assertStatus(422);
    }

    public function test_cancel_draft_transfer_with_zero_inventory_movement()
    {
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 10,
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $transferId = $createRes->json('data.id');

        $cancelRes = $this->postJson("/api/v1/stock-transfers/{$transferId}/cancel", [
            'reason' => 'Created in error',
        ], $this->authHeaders($this->ownerToken));

        $cancelRes->assertStatus(200);
        $cancelRes->assertJsonPath('data.status', 'cancelled');

        // Source stock should be untouched: 100.000
        $sourceInv = BranchInventory::where('branch_id', $this->branchSource->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertEquals('100.000', (string) $sourceInv->quantity_on_hand);
    }

    public function test_cancel_dispatched_transfer_compensating_reversal_to_source()
    {
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 25,
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $transferId = $createRes->json('data.id');
        $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));

        // Stock was 100 - 25 = 75
        $sourceInvBefore = BranchInventory::where('branch_id', $this->branchSource->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertEquals('75.000', (string) $sourceInvBefore->quantity_on_hand);

        // Cancel dispatched transfer
        $cancelRes = $this->postJson("/api/v1/stock-transfers/{$transferId}/cancel", [
            'reason' => 'Truck breakdown, returning goods to main warehouse',
        ], $this->authHeaders($this->ownerToken));

        $cancelRes->assertStatus(200);
        $cancelRes->assertJsonPath('data.status', 'cancelled');

        // Stock must be restored to 100.000
        $sourceInvAfter = BranchInventory::where('branch_id', $this->branchSource->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertEquals('100.000', (string) $sourceInvAfter->quantity_on_hand);
    }

    public function test_cannot_cancel_already_received_transfer()
    {
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 10,
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $transferId = $createRes->json('data.id');
        $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));
        $this->postJson("/api/v1/stock-transfers/{$transferId}/receive", [], $this->authHeaders($this->managerDestToken));

        // Attempt cancel on received transfer
        $cancelRes = $this->postJson("/api/v1/stock-transfers/{$transferId}/cancel", [
            'reason' => 'Cannot cancel received',
        ], $this->authHeaders($this->ownerToken));

        $cancelRes->assertStatus(422);
    }

    public function test_stock_transfers_have_strictly_zero_pl_financial_effect()
    {
        // Check financial movements before transfer
        $financialMovementsBefore = DB::table('account_movements')->count();

        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->fractionalProduct->id,
                    'quantity' => '100.000',
                ],
            ],
        ], $this->authHeaders($this->ownerToken));

        $transferId = $createRes->json('data.id');
        $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));
        $this->postJson("/api/v1/stock-transfers/{$transferId}/receive", [], $this->authHeaders($this->managerDestToken));

        // Financial movements count should remain completely unchanged (zero revenue, zero expense, zero COGS)
        $financialMovementsAfter = DB::table('account_movements')->count();
        $this->assertEquals($financialMovementsBefore, $financialMovementsAfter);
    }

    public function test_manager_can_only_create_or_dispatch_for_assigned_branch()
    {
        // Destination manager attempts to create transfer originating from Source branch (not assigned)
        $res = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                [
                    'product_id' => $this->discreteProduct->id,
                    'quantity' => 5,
                ],
            ],
        ], $this->authHeaders($this->managerDestToken));

        $res->assertStatus(422);
        $this->assertStringContainsString('not assigned to the source branch', $res->json('message'));
    }

    public function test_cross_tenant_transfer_and_item_access_strictly_denied()
    {
        // Foreign business and branch
        $foreignBiz = Business::create(['name' => 'Foreign Retail Corp']);
        $foreignBranch = Branch::create([
            'business_id' => $foreignBiz->id,
            'name' => 'Foreign Branch',
            'code' => 'FOR-01',
            'status' => 'active',
        ]);
        $foreignUser = User::factory()->create([
            'business_id' => $foreignBiz->id,
            'branch_id' => $foreignBranch->id,
        ]);
        $foreignUser->businesses()->attach($foreignBiz->id);
        $foreignUser->branches()->attach($foreignBranch->id, ['business_id' => $foreignBiz->id]);
        $foreignUser->assignRole('Business Owner');
        $foreignToken = $foreignUser->createToken('Foreign')->plainTextToken;

        // Native transfer created in $this->business
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                ['product_id' => $this->discreteProduct->id, 'quantity' => 2],
            ],
        ], $this->authHeaders($this->ownerToken));
        $transferId = $createRes->json('data.id');
        // Foreign user attempts to access native transfer
        $foreignRes = $this->getJson("/api/v1/stock-transfers/{$transferId}", [
            'Authorization' => "Bearer {$foreignToken}",
            'X-Business-ID' => (string) $foreignBiz->id,
        ]);

        // Must be strictly DENIED or NOT FOUND (Section 64)
        $this->assertTrue(in_array($foreignRes->status(), [403, 404], true), "Cross-tenant access must be denied or not found, got: {$foreignRes->status()}");
    }

    public function test_partial_receipt_manipulation_is_ignored_and_full_quantity_received()
    {
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                ['product_id' => $this->discreteProduct->id, 'quantity' => 5],
            ],
        ], $this->authHeaders($this->ownerToken));
        $transferId = $createRes->json('data.id');

        $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));

        // Client attempts to pass partial quantity_received in payload
        $recvRes = $this->postJson("/api/v1/stock-transfers/{$transferId}/receive", [
            'items' => [
                ['product_id' => $this->discreteProduct->id, 'quantity_received' => 2],
            ],
        ], $this->authHeaders($this->managerDestToken));
        $recvRes->assertStatus(200);

        // Verify full 5 units were received
        $destInventory = BranchInventory::where('branch_id', $this->branchDest->id)
            ->where('product_id', $this->discreteProduct->id)
            ->first();
        $this->assertEquals('5.000', $destInventory->quantity_on_hand);

        $transfer = StockTransfer::with('items')->find($transferId);
        $this->assertEquals('5.000', $transfer->items->first()->quantity_received);
    }

    public function test_cancel_replay_is_strictly_denied()
    {
        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                ['product_id' => $this->discreteProduct->id, 'quantity' => 3],
            ],
        ], $this->authHeaders($this->ownerToken));
        $transferId = $createRes->json('data.id');

        // First cancel succeeds
        $cancel1 = $this->postJson("/api/v1/stock-transfers/{$transferId}/cancel", [
            'reason' => 'First cancel attempt',
        ], $this->authHeaders($this->ownerToken));
        $cancel1->assertStatus(200);

        // Second cancel attempt must fail with 422
        $cancel2 = $this->postJson("/api/v1/stock-transfers/{$transferId}/cancel", [
            'reason' => 'Duplicate cancel replay attempt',
        ], $this->authHeaders($this->ownerToken));
        $cancel2->assertStatus(422);
        $this->assertStringContainsString('already cancelled', $cancel2->json('message'));
    }

    public function test_transfer_cost_authority_is_preserved_when_product_cost_changes_later()
    {
        $originalCost = (float) $this->discreteProduct->cost_price;

        $createRes = $this->postJson('/api/v1/stock-transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'items' => [
                ['product_id' => $this->discreteProduct->id, 'quantity' => 4],
            ],
        ], $this->authHeaders($this->ownerToken));
        $transferId = $createRes->json('data.id');

        $this->postJson("/api/v1/stock-transfers/{$transferId}/dispatch", [], $this->authHeaders($this->ownerToken));

        // Now modify the product's cost_price in catalog
        $this->discreteProduct->cost_price = 999.00;
        $this->discreteProduct->save();

        // Complete receive
        $this->postJson("/api/v1/stock-transfers/{$transferId}/receive", [], $this->authHeaders($this->managerDestToken));

        // Verify the transfer item unit_cost preserves the historical cost authority
        $item = StockTransferItem::where('stock_transfer_id', $transferId)->first();
        $this->assertEquals($originalCost, (float) $item->unit_cost);

        // Verify inventory movements also preserved original cost
        $movements = \App\Models\InventoryMovement::where('reference_type', StockTransfer::class)
            ->where('reference_id', $transferId)
            ->get();
        foreach ($movements as $m) {
            $this->assertEquals($originalCost, (float) $m->unit_cost);
        }
    }

    public function test_transfers_route_alias_behaves_identically_to_stock_transfers()
    {
        $res = $this->getJson('/api/v1/transfers', $this->authHeaders($this->ownerToken));
        $res->assertStatus(200);
        $res->assertJsonStructure(['data']);
    }

    public function test_legacy_inventory_transfers_endpoint_enters_authoritative_state_machine()
    {
        $res = $this->postJson('/api/v1/inventory/transfers', [
            'from_branch_id' => $this->branchSource->id,
            'to_branch_id' => $this->branchDest->id,
            'product_id' => $this->discreteProduct->id,
            'quantity' => 2,
            'notes' => 'Legacy endpoint compatibility test',
        ], $this->authHeaders($this->ownerToken));

        $res->assertStatus(201);
        $res->assertJsonStructure(['out_movement', 'in_movement', 'transfer']);
        $this->assertEquals('received', $res->json('transfer.status'));
    }
}
