<?php

use App\Models\Category;
use App\Models\Product;

$businessId = 1;

// Define categories
$categoriesData = [
    'Stationery',
    'Automotive',
    'Pet Supplies'
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
        'name' => 'Premium Notebook',
        'category_name' => 'Stationery',
        'sku' => 'STA-PN001',
        'cost_price' => 150,
        'selling_price' => 300,
        'stock' => 100
    ],
    [
        'name' => 'Blue Ink Pens (Pack of 10)',
        'category_name' => 'Stationery',
        'sku' => 'STA-BP002',
        'cost_price' => 50,
        'selling_price' => 120,
        'stock' => 200
    ],
    [
        'name' => 'Car Wash Shampoo 1L',
        'category_name' => 'Automotive',
        'sku' => 'AUT-CS001',
        'cost_price' => 400,
        'selling_price' => 850,
        'stock' => 50
    ],
    [
        'name' => 'Microfiber Cleaning Cloths (Set of 3)',
        'category_name' => 'Automotive',
        'sku' => 'AUT-MC002',
        'cost_price' => 200,
        'selling_price' => 450,
        'stock' => 150
    ],
    [
        'name' => 'Premium Dog Food 5kg',
        'category_name' => 'Pet Supplies',
        'sku' => 'PET-DF001',
        'cost_price' => 1500,
        'selling_price' => 2800,
        'stock' => 40,
        'unit' => 'Kg'
    ],
    [
        'name' => 'Cat Litter 10kg',
        'category_name' => 'Pet Supplies',
        'sku' => 'PET-CL002',
        'cost_price' => 800,
        'selling_price' => 1400,
        'stock' => 60,
        'unit' => 'Kg'
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
