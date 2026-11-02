<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Business;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\StockTransferService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $owner;
    protected Product $product;
    protected Product $fractionalProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->business = Business::create(['name' => 'Stock Movement Reconcile Biz']);
        setPermissionsTeamId($this->business->id);

        $this->branch1 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Warehouse Branch 1',
            'code' => 'WH-01',
            'is_primary' => true,
            'status' => 'active'
        ]);

        $this->branch2 = Branch::create([
            'business_id' => $this->business->id,
            'name' => 'Retail Branch 2',
            'code' => 'RET-02',
            'is_primary' => false,
            'status' => 'active'
        ]);

        $this->owner = User::factory()->create([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
        ]);
        $this->owner->businesses()->attach($this->business->id);
        $this->owner->assignRole('Business Owner');

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Standard Pack Item',
            'sku' => 'SKU-STD-001',
            'cost_price' => 100,
            'retail_price' => 150,
            'is_active' => true
        ]);

        $this->fractionalProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Loose Sugar Kg',
            'sku' => 'SKU-SUGAR-KG',
            'cost_price' => 80,
            'retail_price' => 110,
            'is_active' => true
        ]);
    }

    public function test_stock_on_hand_reconciles_with_immutable_movements_ledger()
    {
        $invService = app(InventoryService::class);

        // 1. Receive 100 units from purchase
        $invService->receiveStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'unit_cost' => 100.00,
            'type' => 'purchase',
            'performed_by' => $this->owner->id,
        ]);

        // 2. Issue 15 units from sale
        $invService->issueStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
            'type' => 'sale',
            'performed_by' => $this->owner->id,
        ]);

        // 3. Return 2 units restocked
        $invService->receiveStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_cost' => 100.00,
            'type' => 'return',
            'performed_by' => $this->owner->id,
        ]);

        // 4. Adjust subtract 3 broken units
        $invService->adjustStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'type' => 'damage',
            'notes' => 'Broken in display',
            'performed_by' => $this->owner->id,
        ]);

        $inventory = BranchInventory::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->product->id)
            ->first();

        // 100 - 15 + 2 - 3 = 84
        $this->assertEquals(84.000, floatval($inventory->quantity_on_hand));

        // Independent calculation from movements
        $netMovements = InventoryMovement::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->product->id)
            ->sum('quantity');

        $this->assertEquals(floatval($netMovements), floatval($inventory->quantity_on_hand));
    }

    public function test_fractional_stock_quantity_precision_scale_3()
    {
        $invService = app(InventoryService::class);

        // Receive 50.000 kg
        $invService->receiveStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->fractionalProduct->id,
            'quantity' => 50.000,
            'unit_cost' => 80.00,
            'type' => 'purchase',
            'performed_by' => $this->owner->id,
        ]);

        // Sell 1.250 kg
        $invService->issueStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->fractionalProduct->id,
            'quantity' => 1.250,
            'type' => 'sale',
            'performed_by' => $this->owner->id,
        ]);

        // Sell 0.125 kg
        $invService->issueStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->fractionalProduct->id,
            'quantity' => 0.125,
            'type' => 'sale',
            'performed_by' => $this->owner->id,
        ]);

        $inventory = BranchInventory::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->fractionalProduct->id)
            ->first();

        // 50.000 - 1.250 - 0.125 = 48.625
        $this->assertEquals(48.625, floatval($inventory->quantity_on_hand));
    }

    public function test_inter_branch_stock_transfer_in_transit_reconciliation()
    {
        $invService = app(InventoryService::class);
        $transferService = app(StockTransferService::class);
        app()->instance('active_business_id', $this->business->id);

        // Branch 1 has 50 units
        $invService->receiveStock([
            'business_id' => $this->business->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
            'unit_cost' => 100.00,
            'type' => 'purchase',
            'performed_by' => $this->owner->id,
        ]);

        // Create transfer of 20 units to Branch 2
        $transfer = $transferService->createTransfer([
            'business_id' => $this->business->id,
            'from_branch_id' => $this->branch1->id,
            'to_branch_id' => $this->branch2->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 20]
            ]
        ], $this->owner);

        // Dispatch transfer
        $transferService->dispatchTransfer($transfer->id, $this->owner);

        $inv1 = BranchInventory::where('branch_id', $this->branch1->id)->where('product_id', $this->product->id)->first();
        $inv2 = BranchInventory::where('branch_id', $this->branch2->id)->where('product_id', $this->product->id)->first();

        // Branch 1 has 30; Branch 2 has 0 (not received yet)
        $this->assertEquals(30.000, floatval($inv1->quantity_on_hand));
        $this->assertEquals(0.000, floatval($inv2->quantity_on_hand ?? 0));

        // Company-wide stock = 30 + 0 + 20 (in transit) = 50
        $inTransit = StockTransferItem::whereHas('stockTransfer', function ($q) {
            $q->where('status', 'dispatched');
        })->where('product_id', $this->product->id)->sum('quantity_sent');

        $totalCompanyStock = floatval($inv1->quantity_on_hand) + floatval($inv2->quantity_on_hand ?? 0) + floatval($inTransit);
        $this->assertEquals(50.000, $totalCompanyStock);

        // Now receive transfer at Branch 2
        $transferService->receiveTransfer($transfer->id, $this->owner);
        $inv2After = BranchInventory::where('branch_id', $this->branch2->id)->where('product_id', $this->product->id)->first();

        $this->assertNotNull($inv2After);
        $this->assertEquals(20.000, floatval($inv2After->quantity_on_hand));
    }
}