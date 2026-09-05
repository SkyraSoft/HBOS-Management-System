<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \App\Models\Product::where('image', 'like', '%source.unsplash.com%')->get();
foreach($products as $p) {
    $name = strtolower($p->name);
    $url = 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=800&q=80'; // generic product
    if (strpos($name, 'iphone') !== false || strpos($name, 'mobile') !== false) {
        $url = 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?auto=format&fit=crop&w=800&q=80';
    }
    if (strpos($name, 'watch') !== false) {
        $url = 'https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?auto=format&fit=crop&w=800&q=80';
    }
    $p->update(['image' => $url]);
}
echo "Updated broken Unsplash URLs\n";
