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
     *
     * Bolacha has one per line of the factory's drawings of 2026-11-04 (LINE
     * 01 … 05, NAYA VERTICAL, WAFER), each holding its line and the mixers or
     * tanks that feed only it — a run's mixes are planned on its production
     * line's preparations. Until then one production line (id 2, "Bolacha
     * Production Line PL1") held every Bolacha lane.
     */
    public function run(): void
    {
        $bolacha = [
            'department_id' => 5,                // Bolacha - Production
            'factory_id' => 4,                   // Bolacha Factory
        ];

        $productionLines = [
            [
                'id' => 1,
                'name' => 'Cans Tomato Paste Line',
                'department_id' => 14,           // MT Production Department
                'factory_id' => 1,               // Massa Tomate Factory Warehouse
            ],
            ['id' => 2, 'name' => 'Line 01'] + $bolacha,
            ['id' => 3, 'name' => 'Line 02'] + $bolacha,
            ['id' => 4, 'name' => 'Line 03'] + $bolacha,
            ['id' => 5, 'name' => 'Line 04'] + $bolacha,
            ['id' => 6, 'name' => 'Line 05'] + $bolacha,
            ['id' => 7, 'name' => 'Naya Vertical'] + $bolacha,
            ['id' => 8, 'name' => 'Wafer'] + $bolacha,
        ];

        DB::transaction(function () use ($productionLines) {
            foreach ($productionLines as $row) {
                ProductionLine::withTrashed()->updateOrCreate(['id' => $row['id']], $row + ['deleted_at' => null]);
            }
        });
    }
}
