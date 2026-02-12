<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [

            // Company Page
            'company-list',
            'company-create',
            'company-edit',
            'company-view',
            'company-delete',
            'company-viewAll',

            // Users Page
            'user-list',
            'user-create',
            'user-edit',
            'user-view',
            'user-delete',
            'user-viewAll',

            // Roles Page
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',

            // Permissions Page
            'permission-list',

            // Factories Page
            'factory-list',
            'factory-create',
            'factory-edit',
            'factory-view',
            'factory-delete',

            // Warehouse Types Page
            'warehouseType-list',
            'warehouseType-create',
            'warehouseType-edit',
            'warehouseType-view',
            'warehouseType-delete',

            // Warehouses Page
            'warehouse-list',
            'warehouse-create',
            'warehouse-edit',
            'warehouse-view',
            'warehouse-delete',

            // Production Lines Page
            'productionLine-list',
            'productionLine-create',
            'productionLine-edit',
            'productionLine-view',
            'productionLine-delete',

            // Machine Types Page
            'machineType-list',
            'machineType-create',
            'machineType-edit',
            'machineType-view',
            'machineType-delete',

            // Machines Page
            'machine-list',
            'machine-create',
            'machine-edit',
            'machine-view',
            'machine-delete',

            // Shifts Page
            'shift-list',
            'shift-create',
            'shift-edit',
            'shift-view',
            'shift-delete',



        ];
        
        $permissionsIds = [];
        foreach ($permissions as $permission) {
            $createdPermission = Permission::updateOrCreate(['name' => $permission]);
            $permissionsIds[] = $createdPermission->id;
        }

        $adminRole = Role::find(1);
        if($adminRole!=null){
            $adminRole->syncPermissions($permissionsIds);
        }
    }
}
