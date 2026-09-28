<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use Illuminate\Database\QueryException;

class InventoryUniqueConstraintTest extends TestCase
{
    use RefreshDatabase;

    protected $businessA;
    protected $businessB;
    protected $branchA1;
    protected $branchA2;
    protected $branchB1;
    protected $productA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessA = Business::create(['name' => 'Unique Test Business A']);
        $this->branchA1 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A1', 'is_primary' => true]);
        $this->branchA2 = Branch::create(['business_id' => $this->businessA->id, 'name' => 'Branch A2', 'is_primary' => false]);

        $this->businessB = Business::create(['name' => 'Unique Test Business B']);
        $this->branchB1 = Branch::create(['business_id' => $this->businessB->id, 'name' => 'Branch B1', 'is_primary' => true]);

        $this->productA = Product::create([
            'business_id' => $this->businessA->id,
            'name' => 'Unique Test Product',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 100,
            'unit' => 'pcs'
        ]);
    }

    public function test_same_branch_same_product_duplicate_inventory_fails_database_unique_constraint()
    {
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 50,
            'minimum_stock' => 5
        ]);

        $this->expectException(QueryException::class);

        // Duplicate record for same business, branch, and product must fail unique index
        BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 25,
            'minimum_stock' => 2
        ]);
    }

    public function test_different_branches_same_product_succeeds()
    {
        $inv1 = BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 50,
            'minimum_stock' => 5
        ]);

        $inv2 = BranchInventory::create([
            'business_id' => $this->businessA->id,
            'branch_id' => $this->branchA2->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 30,
            'minimum_stock' => 5
        ]);

        $this->assertNotNull($inv1->id);
        $this->assertNotNull($inv2->id);
        $this->assertNotEquals($inv1->id, $inv2->id);
    }
}
