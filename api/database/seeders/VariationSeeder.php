<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;

class VariationSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::firstOrCreate(
            ['email' => 'admin@hbos.com'],
            ['name' => 'HBOS Store', 'phone' => '1234567890']
        );

        $user = User::firstOrCreate(
            ['email' => 'admin@hbos.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'business_id' => $business->id,
                'role' => 'admin'
            ]
        );

        // Electronics Category
        $electronics = Category::firstOrCreate([
            'business_id' => $business->id,
            'name' => 'Electronics'
        ]);

        // Smart TV Product
        Product::updateOrCreate(
            ['sku' => 'TV-SAMS-001', 'business_id' => $business->id],
            [
                'category_id' => $electronics->id,
                'name' => 'Samsung Smart TV (Dynamic Variations)',
                'barcode' => 'TV-SAMS-001',
                'cost_price' => 45000,
                'selling_price' => 55000,
                'stock' => 50,
                'min_stock' => 5,
                'unit' => 'pcs',
                'description' => 'A dynamic smart TV with size and display variations.',
                'is_active' => true,
                'has_variations' => true,
                'attributes' => json_encode([
                    ['name' => 'Size', 'values' => ['43"', '55"']],
                    ['name' => 'Display', 'values' => ['LED', 'QLED']]
                ]),
                'variations' => json_encode([
                    ['sku' => 'TV-SAMS-001-43-LED', 'attributes' => ['Size' => '43"', 'Display' => 'LED'], 'price' => 55000, 'stock' => 15],
                    ['sku' => 'TV-SAMS-001-43-QLED', 'attributes' => ['Size' => '43"', 'Display' => 'QLED'], 'price' => 65000, 'stock' => 10],
                    ['sku' => 'TV-SAMS-001-55-LED', 'attributes' => ['Size' => '55"', 'Display' => 'LED'], 'price' => 75000, 'stock' => 12],
                    ['sku' => 'TV-SAMS-001-55-QLED', 'attributes' => ['Size' => '55"', 'Display' => 'QLED'], 'price' => 85000, 'stock' => 5]
                ])
            ]
        );

        // Grocery Category
        $grocery = Category::firstOrCreate([
            'business_id' => $business->id,
            'name' => 'Grocery'
        ]);

        // Juice Product
        Product::updateOrCreate(
            ['sku' => 'JUI-APP-002', 'business_id' => $business->id],
            [
                'category_id' => $grocery->id,
                'name' => 'Apple Juice (Dynamic Packs)',
                'barcode' => 'JUI-APP-002',
                'cost_price' => 100,
                'selling_price' => 150,
                'stock' => 200,
                'min_stock' => 20,
                'unit' => 'pcs',
                'description' => 'Fresh apple juice with pack size variations.',
                'is_active' => true,
                'has_variations' => true,
                'attributes' => json_encode([
                    ['name' => 'Pack Type', 'values' => ['Single Pack', 'Box', 'Carton']]
                ]),
                'variations' => json_encode([
                    ['sku' => 'JUI-APP-002-SGL', 'attributes' => ['Pack Type' => 'Single Pack'], 'price' => 150, 'stock' => 100],
                    ['sku' => 'JUI-APP-002-BOX', 'attributes' => ['Pack Type' => 'Box'], 'price' => 1700, 'stock' => 20],
                    ['sku' => 'JUI-APP-002-CTN', 'attributes' => ['Pack Type' => 'Carton'], 'price' => 3300, 'stock' => 10]
                ])
            ]
        );
    }
}
