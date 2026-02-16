<?php

namespace Database\Seeders;

use App\Models\WarehouseType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouseTypes = [
            ['company_id' => 1, 'name' => 'Main Raw Materials Warehouse'],
            ['company_id' => 1, 'name' => 'Line Raw Materials Warehouse'],
            ['company_id' => 1, 'name' => 'Spare Parts Warehouse'],
            ['company_id' => 1, 'name' => 'Semi Finished Goods Warehouse'],
            ['company_id' => 1, 'name' => 'Finished Goods Warehouse'],
        ];

        foreach ($warehouseTypes as $type) {
            WarehouseType::updateOrCreate($type);
        }
    }
}
