<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
use App\Models\BranchInventory;
use App\Models\FinancialAccount;
use App\Models\Customer;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'HBOS Flagship Store',
                'type' => 'Retail',
                'currency' => 'PKR'
            ]
        );

        $branch = Branch::firstOrCreate(
            ['id' => 1],
            [
                'business_id' => $business->id,
                'name' => 'Main Counter Branch',
                'code' => 'MAIN-01',
                'is_primary' => true,
                'status' => 'active'
            ]
        );

        $user = User::where('email', 'admin@skyrasoft.com')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'SkyraSoft Admin',
                'email' => 'admin@skyrasoft.com',
                'password' => Hash::make('Skyrasoft@2026'),
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'role' => 'Business Owner',
                'is_active' => true
            ]);
        } else {
            $user->update([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'role' => 'Business Owner',
                'is_active' => true
            ]);
        }

        $user->businesses()->syncWithoutDetaching([$business->id]);

        // Financial Cash Account for branch 1
        FinancialAccount::firstOrCreate(
            ['business_id' => $business->id, 'branch_id' => $branch->id, 'type' => 'cash'],
            [
                'name' => 'Main Cash Drawer',
                'opening_balance' => 0,
                'is_default' => true,
                'status' => 'active'
            ]
        );

        // Seed Customers
        Customer::firstOrCreate(
            ['id' => 1],
            [
                'business_id' => $business->id,
                'name' => 'Tariq Wholesale General Store',
                'phone' => '03001234567',
                'opening_balance' => 5000
            ]
        );

        Customer::firstOrCreate(
            ['id' => 2],
            [
                'business_id' => $business->id,
                'name' => 'Chaudhry Bakers & Sweets',
                'phone' => '03009876543',
                'opening_balance' => 12000
            ]
        );

        // Seed products for business_id 1
        $prods = [
            ['id' => 1, 'name' => 'Olpers Milk 1L', 'selling_price' => 290, 'cost_price' => 260],
            ['id' => 2, 'name' => 'Nestle Everyday Tea Whitener', 'selling_price' => 220, 'cost_price' => 195],
            ['id' => 3, 'name' => 'Shan Biryani Masala Double Pack', 'selling_price' => 150, 'cost_price' => 125],
            ['id' => 4, 'name' => 'Tapal Danedar Tea 450g', 'selling_price' => 650, 'cost_price' => 580],
        ];

        foreach ($prods as $p) {
            $product = Product::firstOrCreate(
                ['id' => $p['id']],
                [
                    'business_id' => $business->id,
                    'name' => $p['name'],
                    'selling_price' => $p['selling_price'],
                    'cost_price' => $p['cost_price'],
                    'sku' => 'SKU-' . $p['id'],
                    'unit' => 'Piece',
                    'is_active' => true
                ]
            );

            BranchInventory::firstOrCreate(
                ['business_id' => $business->id, 'branch_id' => $branch->id, 'product_id' => $product->id],
                ['quantity_on_hand' => 10000, 'reorder_level' => 10]
            );
        }

        // Assign Spatie Role
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($business->id);
        }

        $role = Role::firstOrCreate([
            'name' => 'Business Owner',
            'business_id' => $business->id,
            'guard_name' => 'web'
        ]);

        $user->assignRole($role);
    }
}
