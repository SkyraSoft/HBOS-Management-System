<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SRT-GAP-012: Quantity Precision Conversion to DECIMAL(12, 3) across 7 active tables
     * and Fractional Product Unit Configuration.
     */
    public function up(): void
    {
        // 1. products table
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'allow_fractional')) {
                    $table->boolean('allow_fractional')->default(false)->after('unit');
                }
                if (!Schema::hasColumn('products', 'sale_unit')) {
                    $table->string('sale_unit', 20)->nullable()->after('allow_fractional');
                }
                if (!Schema::hasColumn('products', 'conversion_factor')) {
                    $table->decimal('conversion_factor', 12, 3)->default(1.000)->after('sale_unit');
                }

                if (Schema::hasColumn('products', 'stock')) {
                    $table->decimal('stock', 12, 3)->default(0.000)->change();
                }
                if (Schema::hasColumn('products', 'min_stock')) {
                    $table->decimal('min_stock', 12, 3)->default(0.000)->change();
                }
            });
        }

        // 2. branch_inventories table
        if (Schema::hasTable('branch_inventories')) {
            Schema::table('branch_inventories', function (Blueprint $table) {
                if (Schema::hasColumn('branch_inventories', 'quantity_on_hand')) {
                    $table->decimal('quantity_on_hand', 12, 3)->default(0.000)->change();
                }
                if (Schema::hasColumn('branch_inventories', 'minimum_stock')) {
                    $table->decimal('minimum_stock', 12, 3)->default(0.000)->change();
                }
                if (Schema::hasColumn('branch_inventories', 'reorder_level')) {
                    $table->decimal('reorder_level', 12, 3)->default(0.000)->change();
                }
            });
        }

        // 3. inventory_movements table
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_movements', 'quantity')) {
                    $table->decimal('quantity', 12, 3)->change();
                }
                if (Schema::hasColumn('inventory_movements', 'balance')) {
                    $table->decimal('balance', 12, 3)->default(0.000)->change();
                }
            });
        }

        // 4. sale_items table
        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                if (Schema::hasColumn('sale_items', 'quantity')) {
                    $table->decimal('quantity', 12, 3)->default(1.000)->change();
                }
                if (Schema::hasColumn('sale_items', 'returned_quantity')) {
                    $table->decimal('returned_quantity', 12, 3)->default(0.000)->change();
                }
            });
        }

        // 5. sale_return_items table
        if (Schema::hasTable('sale_return_items')) {
            Schema::table('sale_return_items', function (Blueprint $table) {
                if (Schema::hasColumn('sale_return_items', 'quantity')) {
                    $table->decimal('quantity', 12, 3)->change();
                }
            });
        }

        // 6. purchase_items table
        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_items', 'quantity')) {
                    $table->decimal('quantity', 12, 3)->change();
                }
            });
        }

        // 7. supplier_return_items table
        if (Schema::hasTable('supplier_return_items')) {
            Schema::table('supplier_return_items', function (Blueprint $table) {
                if (Schema::hasColumn('supplier_return_items', 'quantity')) {
                    $table->decimal('quantity', 12, 3)->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('products', 'allow_fractional')) {
                    $columnsToDrop[] = 'allow_fractional';
                }
                if (Schema::hasColumn('products', 'sale_unit')) {
                    $columnsToDrop[] = 'sale_unit';
                }
                if (Schema::hasColumn('products', 'conversion_factor')) {
                    $columnsToDrop[] = 'conversion_factor';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
                if (Schema::hasColumn('products', 'stock')) {
                    $table->integer('stock')->default(0)->change();
                }
                if (Schema::hasColumn('products', 'min_stock')) {
                    $table->integer('min_stock')->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('branch_inventories')) {
            Schema::table('branch_inventories', function (Blueprint $table) {
                if (Schema::hasColumn('branch_inventories', 'quantity_on_hand')) {
                    $table->integer('quantity_on_hand')->default(0)->change();
                }
                if (Schema::hasColumn('branch_inventories', 'minimum_stock')) {
                    $table->integer('minimum_stock')->default(0)->change();
                }
                if (Schema::hasColumn('branch_inventories', 'reorder_level')) {
                    $table->integer('reorder_level')->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_movements', 'quantity')) {
                    $table->integer('quantity')->change();
                }
                if (Schema::hasColumn('inventory_movements', 'balance')) {
                    $table->integer('balance')->change();
                }
            });
        }

        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                if (Schema::hasColumn('sale_items', 'quantity')) {
                    $table->integer('quantity')->default(1)->change();
                }
                if (Schema::hasColumn('sale_items', 'returned_quantity')) {
                    $table->integer('returned_quantity')->default(0)->change();
                }
            });
        }

        if (Schema::hasTable('sale_return_items')) {
            Schema::table('sale_return_items', function (Blueprint $table) {
                if (Schema::hasColumn('sale_return_items', 'quantity')) {
                    $table->integer('quantity')->change();
                }
            });
        }

        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_items', 'quantity')) {
                    $table->integer('quantity')->change();
                }
            });
        }

        if (Schema::hasTable('supplier_return_items')) {
            Schema::table('supplier_return_items', function (Blueprint $table) {
                if (Schema::hasColumn('supplier_return_items', 'quantity')) {
                    $table->integer('quantity')->change();
                }
            });
        }
    }
};
