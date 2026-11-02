<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class Phase9MigrationUpDownTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_phase_9_migrations_up_and_down_execution(): void
    {
        $migrationFiles = [
            '2026_10_02_000001_add_snapshot_columns_to_sale_items_table.php',
            '2026_10_02_000002_add_wholesale_price_to_products_table.php',
            '2026_10_02_000003_create_sale_payments_table.php',
            '2026_10_02_000004_enforce_non_null_financial_account_on_expenses.php',
            '2026_10_02_000005_add_landed_cost_to_purchases_table.php',
            '2026_10_02_000006_add_transfer_status_to_inventory_movements.php',
            '2026_10_02_000007_add_restock_condition_to_sale_return_items.php',
            '2026_10_02_000008_create_supplier_returns_table.php',
            '2026_10_02_000009_add_payroll_columns_to_users_table.php',
            '2026_10_02_000010_add_secondary_phone_to_customers_table.php',
            '2026_10_02_000011_add_monthly_budget_to_expense_categories_table.php',
        ];

        foreach ($migrationFiles as $file) {
            $path = database_path('migrations/' . $file);
            $this->assertFileExists($path);

            $migration = require $path;

            // When RefreshDatabase runs, all migrations are already up.
            // 1. Test DOWN execution
            $migration->down();

            // Verify specific columns / tables were dropped
            if (str_contains($file, 'sale_items')) {
                $this->assertFalse(Schema::hasColumn('sale_items', 'product_name'));
            } elseif (str_contains($file, 'products')) {
                $this->assertFalse(Schema::hasColumn('products', 'wholesale_price'));
            } elseif (str_contains($file, 'sale_payments')) {
                $this->assertFalse(Schema::hasTable('sale_payments'));
            } elseif (str_contains($file, 'supplier_returns')) {
                $this->assertFalse(Schema::hasTable('supplier_returns'));
            }

            // 2. Test UP execution
            $migration->up();

            // Verify specific columns / tables were restored
            if (str_contains($file, 'sale_items')) {
                $this->assertTrue(Schema::hasColumn('sale_items', 'product_name'));
                $this->assertTrue(Schema::hasColumn('sale_items', 'sku'));
            } elseif (str_contains($file, 'products')) {
                $this->assertTrue(Schema::hasColumn('products', 'wholesale_price'));
            } elseif (str_contains($file, 'sale_payments')) {
                $this->assertTrue(Schema::hasTable('sale_payments'));
            } elseif (str_contains($file, 'supplier_returns')) {
                $this->assertTrue(Schema::hasTable('supplier_returns'));
                $this->assertTrue(Schema::hasTable('supplier_return_items'));
            }
        }

        $this->assertTrue(true);
    }
}
