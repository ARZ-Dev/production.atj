<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['company_id' => 1, 'name' => 'Super Admin'],
        ];

        foreach ($roles as $role) {
            $role = Role::updateOrCreate(['name' => $role['name']], ['company_id' => $role['company_id']]);
        }
    }
}
;
