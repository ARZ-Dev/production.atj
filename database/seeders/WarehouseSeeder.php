<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [1,1,'Main Raw Warehouse'],
            [1,2,'Line Raw Warehouse'],
            [1,3,'Finished Goods Warehouse'],
        ];
        foreach ($warehouses as $w) {
            Warehouse::updateOrCreate([
                'company_id' => $w[0],
                'warehouse_type_id' => $w[1],
                'name' => $w[2],
            ]);
        }
    }
}
