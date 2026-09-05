<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try { DB::statement("ALTER TABLE products DROP FOREIGN KEY products_brand_id_foreign;"); } catch (\Exception $e) {}
try { DB::statement("ALTER TABLE products DROP FOREIGN KEY products_subcategory_id_foreign;"); } catch (\Exception $e) {}

if (Schema::hasColumn('products', 'brand_id')) {
    DB::statement("ALTER TABLE products DROP COLUMN brand_id;");
}
if (Schema::hasColumn('products', 'subcategory_id')) {
    DB::statement("ALTER TABLE products DROP COLUMN subcategory_id;");
}

echo "Done\n";
