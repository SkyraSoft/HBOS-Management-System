<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Canonical Permissions
        $permissions = [
            'manage business settings',
            'manage branches',
            'manage users',
            'view full reports',
            'manage branch settings',
            'manage local inventory',
            'view branch reports',
            'create sales',
            'view own sales',
            'create customers',
            'view products',
            'manage products',
            'view categories',
            'manage categories',
            'view brands',
            'manage brands',
            'view inventory',
            'adjust inventory',
            'transfer inventory',
            'view stock movements',
            // Phase 4 Permissions
            'view suppliers',
            'manage suppliers',
            'view purchases',
            'manage purchases',
            'return purchases',
            'record supplier payments',
            // Phase 5 Permissions
            'view sales',
            'create sales',
            'manage sales',
            'return sales',
            'override sale price'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Define Canonical Roles and Assign Permissions
        
        // Business Owner: Granted all permissions (including all Phase 5 sales permissions)
        $ownerRole = Role::firstOrCreate(['name' => 'Business Owner']);
        $ownerRole->syncPermissions(Permission::all());

        // Branch Manager: Granted branch-level sales & purchase rights, but NOT Business-wide supplier payment or price override rights
        $managerRole = Role::firstOrCreate(['name' => 'Branch Manager']);
        $managerRole->syncPermissions([
            'manage branch settings',
            'manage local inventory',
            'view branch reports',
            'create sales',
            'view own sales',
            'create customers',
            'view products',
            'manage products',
            'view categories',
            'manage categories',
            'view brands',
            'manage brands',
            'view inventory',
            'adjust inventory',
            'transfer inventory',
            'view stock movements',
            // Phase 4 Branch Manager Permissions
            'view suppliers',
            'view purchases',
            'manage purchases',
            'return purchases',
            // Phase 5 Branch Manager Permissions
            'view sales',
            'manage sales',
            'return sales'
        ]);

        // Salesperson: POS-centric role, allowed to view and create sales, but NOT manage/cancel, return, or override prices
        $salespersonRole = Role::firstOrCreate(['name' => 'Salesperson']);
        $salespersonRole->syncPermissions([
            'view sales',
            'create sales',
            'view own sales',
            'create customers',
            'view products',
            'view categories',
            'view brands',
            'view inventory',
        ]);
    }
}
