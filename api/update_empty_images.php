<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \App\Models\Product::whereNull('image')->orWhere('image', '')->get();
foreach($products as $p) {
    $p->update(['image' => 'https://source.unsplash.com/800x800/?' . urlencode($p->name)]);
}
echo 'done';
