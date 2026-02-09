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
            ['company_id' => 1, 'name' => 'Main Raw Warehouse'],
            ['company_id' => 1, 'name' => 'Line Raw Warehouse'],
            ['company_id' => 1, 'name' => 'Finished Goods Warehouse'],
        ];

        foreach ($warehouseTypes as $type) {
            WarehouseType::updateOrCreate($type);
        }
    }
}
