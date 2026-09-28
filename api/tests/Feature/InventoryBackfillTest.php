<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Business;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\InventoryMovement;

class InventoryBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_a_single_branch_business_backfills_stock_and_creates_one_opening_movement()
    {
        $business = Business::create(['name' => 'Single Branch Business']);
        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Only Branch',
            'is_primary' => false
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Single Branch Product',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 10,
            'unit' => 'pcs'
        ]);

        $migration = require database_path('migrations/2026_09_15_000000_create_branch_inventories_and_update_movements_table.php');
        $migration->up();

        $this->assertDatabaseHas('branch_inventories', [
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 10
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'opening',
            'quantity' => 10
        ]);
    }

    public function test_case_b_multibranch_business_with_one_primary_branch_backfills_to_primary_branch_only()
    {
        $business = Business::create(['name' => 'Multi-Branch Business']);
        $primaryBranch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Primary Branch',
            'is_primary' => true
        ]);
        $secondaryBranch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Secondary Branch',
            'is_primary' => false
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Primary Product',
            'cost_price' => 15,
            'selling_price' => 30,
            'stock' => 25,
            'unit' => 'pcs'
        ]);

        $migration = require database_path('migrations/2026_09_15_000000_create_branch_inventories_and_update_movements_table.php');
        $migration->up();

        $this->assertDatabaseHas('branch_inventories', [
            'branch_id' => $primaryBranch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 25
        ]);

        $this->assertDatabaseMissing('branch_inventories', [
            'branch_id' => $secondaryBranch->id,
            'product_id' => $product->id
        ]);
    }

    public function test_case_c_multibranch_business_with_no_primary_branch_does_not_guess_branch()
    {
        $business = Business::create(['name' => 'Ambiguous Business']);
        Branch::create([
            'business_id' => $business->id,
            'name' => 'Branch 1',
            'is_primary' => false
        ]);
        Branch::create([
            'business_id' => $business->id,
            'name' => 'Branch 2',
            'is_primary' => false
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Ambiguous Product',
            'cost_price' => 5,
            'selling_price' => 10,
            'stock' => 100,
            'unit' => 'pcs'
        ]);

        $migration = require database_path('migrations/2026_09_15_000000_create_branch_inventories_and_update_movements_table.php');
        $migration->up();

        $this->assertDatabaseMissing('branch_inventories', [
            'product_id' => $product->id
        ]);

        $this->assertEquals(100, $product->fresh()->stock);
    }

    public function test_case_d_zero_branch_business_does_not_fabricate_branch()
    {
        $business = Business::create(['name' => 'No Branch Business']);
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Orphan Product',
            'cost_price' => 12,
            'selling_price' => 24,
            'stock' => 50,
            'unit' => 'pcs'
        ]);

        $migration = require database_path('migrations/2026_09_15_000000_create_branch_inventories_and_update_movements_table.php');
        $migration->up();

        $this->assertDatabaseMissing('branch_inventories', [
            'product_id' => $product->id
        ]);
    }

    public function test_case_e_zero_stock_product_does_not_create_opening_movement()
    {
        $business = Business::create(['name' => 'Zero Stock Business']);
        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Branch 1',
            'is_primary' => true
        ]);
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Zero Stock Product',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 0,
            'unit' => 'pcs'
        ]);

        $migration = require database_path('migrations/2026_09_15_000000_create_branch_inventories_and_update_movements_table.php');
        $migration->up();

        $this->assertDatabaseHas('branch_inventories', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity_on_hand' => 0
        ]);

        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'opening'
        ]);
    }

    public function test_case_f_g_existing_opening_movement_is_not_duplicated()
    {
        $business = Business::create(['name' => 'Idempotency Business']);
        Branch::create([
            'business_id' => $business->id,
            'name' => 'Branch 1',
            'is_primary' => true
        ]);
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Idempotent Product',
            'cost_price' => 10,
            'selling_price' => 20,
            'stock' => 15,
            'unit' => 'pcs'
        ]);

        $migration = require database_path('migrations/2026_09_15_000000_create_branch_inventories_and_update_movements_table.php');
        
        $migration->up();
        $migration->up();

        $movementCount = InventoryMovement::where('product_id', $product->id)->where('type', 'opening')->count();
        $this->assertEquals(1, $movementCount);
    }

    public function test_migration_rollback_down_safely_drops_branch_inventories_and_reverts_movements_columns()
    {
        $migration = require database_path('migrations/2026_09_15_000000_create_branch_inventories_and_update_movements_table.php');
        
        $migration->up();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('branch_inventories'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('inventory_movements', 'unit_cost'));

        $migration->down();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('branch_inventories'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('inventory_movements', 'unit_cost'));
    }
}

