<?php

use App\Models\Category;
use App\Models\Product;

$businessId = 1;

// Define categories
$categoriesData = [
    'Electronics',
    'Clothing',
    'Groceries'
];

$categories = [];

foreach ($categoriesData as $catName) {
    $category = Category::firstOrCreate([
        'business_id' => $businessId,
        'name' => $catName
    ]);
    $categories[$catName] = $category->id;
}

// Define products
$productsData = [
    [
        'name' => 'Wireless Mouse',
        'category_name' => 'Electronics',
        'sku' => 'ELEC-WM001',
        'cost_price' => 500,
        'selling_price' => 1500,
        'stock' => 50
    ],
    [
        'name' => 'Bluetooth Headphones',
        'category_name' => 'Electronics',
        'sku' => 'ELEC-BH002',
        'cost_price' => 2000,
        'selling_price' => 4500,
        'stock' => 30
    ],
    [
        'name' => 'Men\'s T-Shirt',
        'category_name' => 'Clothing',
        'sku' => 'CLO-TS001',
        'cost_price' => 300,
        'selling_price' => 800,
        'stock' => 100
    ],
    [
        'name' => 'Denim Jeans',
        'category_name' => 'Clothing',
        'sku' => 'CLO-DJ002',
        'cost_price' => 800,
        'selling_price' => 2200,
        'stock' => 60
    ],
    [
        'name' => 'Organic Apples',
        'category_name' => 'Groceries',
        'sku' => 'GRO-OA001',
        'cost_price' => 150,
        'selling_price' => 250,
        'stock' => 200,
        'unit' => 'Kg'
    ],
    [
        'name' => 'Whole Wheat Bread',
        'category_name' => 'Groceries',
        'sku' => 'GRO-WB002',
        'cost_price' => 50,
        'selling_price' => 120,
        'stock' => 40
    ]
];

foreach ($productsData as $data) {
    Product::firstOrCreate(
        [
            'business_id' => $businessId,
            'sku' => $data['sku']
        ],
        [
            'category_id' => $categories[$data['category_name']],
            'name' => $data['name'],
            'cost_price' => $data['cost_price'],
            'selling_price' => $data['selling_price'],
            'stock' => $data['stock'],
            'min_stock' => 10,
            'unit' => $data['unit'] ?? 'Piece',
            'is_active' => true,
        ]
    );
}

echo "Seeded categories and products successfully.\n";
