<?php

namespace Database\Seeders;

use App\Models\ProductionLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Department and factory (warehouse) ids are the parent's (erp_auth), as
     * a fresh auth-service seed assigns them — this app can't read the
     * parent's database on the server, so they're written out; keep them in
     * step with auth-service's DepartmentSeeder and WarehouseSeeder. The
     * preparations and lines under each production line are attached by
     * PreparationSeeder and LineSeeder.
     */
    public function run(): void
    {
        $productionLines = [
            [
                'id' => 1,
                'name' => 'Cans Tomato Paste Line',
                'department_id' => 14,           // MT Production Department
                'factory_id' => 1,               // Massa Tomate Factory Warehouse
            ],
            [
                'id' => 2,
                'name' => 'Bolacha Production Line PL1',
                'department_id' => 5,            // Bolacha - Production
                'factory_id' => 4,               // Bolacha Factory
            ],
        ];

        DB::transaction(function () use ($productionLines) {
            foreach ($productionLines as $row) {
                ProductionLine::withTrashed()->updateOrCreate(['id' => $row['id']], $row + ['deleted_at' => null]);
            }
        });
    }
}
