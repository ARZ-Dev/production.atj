<?php

namespace Database\Seeders;

use App\Models\Preparation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreparationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Department, warehouse and item type ids are the parent's (erp_auth), as
     * a fresh auth-service seed assigns them — this app can't read the
     * parent's database on the server, so they're written out; keep them in
     * step with auth-service's seeders. Event types and production lines are
     * this app's own rows — runs after FactoryEventTypeSeeder and ProductionLineSeeder.
     *
     * Massa Tomate's come from "Equipment and capacity Massa tomate.xlsx"
     * (sheet FAB MASSA TOMAT): its Mixing row. Capacities are CapacitySeeder's.
     *
     * Bolacha's are the mixers and tanks of the factory's line drawings of
     * 2026-11-04, each under the production line it feeds: two Laser mixers
     * per line (ZM 500 on Lines 01–04, ZM 1100 on Line 05 — the equipment
     * sheet's 8 and 2), Naya Vertical's own ZM 500, and the wafer line's dough
     * and cream tanks. Each is named after its mixer station (StationSeeder).
     * They mix from RM BOLACHA into SFG Bolacha, and have no capacities (the
     * sheet gives kg a batch, not a rate), so a planned mix takes 20 minutes.
     * They replace "Bolacha P1" (id 2), which served every line.
     */
    public function run(): void
    {
        $bolacha = [
            'department_id' => 5,                // Bolacha - Production
            'source_warehouse_ids' => [5],       // RM BOLACHA
            'fg_warehouse_id' => 7,              // SFG Bolacha
            'item_type_warehouses' => [],
            'event_type_ids' => [4],             // Preparation Bolacha
        ];

        $preparations = [
            [
                'id' => 1,
                'name' => 'Mixing',              // Allan (China), 1 line, 3.5 t/h
                'department_id' => 14,           // MT Production Department
                'source_warehouse_ids' => [2],   // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,          // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],    // item type id => one of source_warehouse_ids
                'event_type_ids' => [1, 2],      // Cleaning, Preparation Massa Tomate
                'production_line_ids' => [1],    // Cans Tomato Paste Line
            ],
            ['id' => 2, 'name' => 'Mixer ZM 500 1', 'production_line_ids' => [2]] + $bolacha,     // Line 01
            ['id' => 3, 'name' => 'Mixer ZM 500 2', 'production_line_ids' => [2]] + $bolacha,
            ['id' => 4, 'name' => 'Mixer ZM 500 3', 'production_line_ids' => [3]] + $bolacha,     // Line 02
            ['id' => 5, 'name' => 'Mixer ZM 500 4', 'production_line_ids' => [3]] + $bolacha,
            ['id' => 6, 'name' => 'Mixer ZM 500 5', 'production_line_ids' => [4]] + $bolacha,     // Line 03
            ['id' => 7, 'name' => 'Mixer ZM 500 6', 'production_line_ids' => [4]] + $bolacha,
            ['id' => 8, 'name' => 'Mixer ZM 500 7', 'production_line_ids' => [5]] + $bolacha,     // Line 04
            ['id' => 9, 'name' => 'Mixer ZM 500 8', 'production_line_ids' => [5]] + $bolacha,
            ['id' => 10, 'name' => 'Mixer ZM 1100 1', 'production_line_ids' => [6]] + $bolacha,   // Line 05
            ['id' => 11, 'name' => 'Mixer ZM 1100 2', 'production_line_ids' => [6]] + $bolacha,
            ['id' => 12, 'name' => 'Mixer ZM 500 9', 'production_line_ids' => [7]] + $bolacha,    // Naya Vertical
            ['id' => 13, 'name' => 'Wafer Dough Tank', 'production_line_ids' => [8]] + $bolacha,  // Wafer
            ['id' => 14, 'name' => 'Wafer Cream Tank', 'production_line_ids' => [8]] + $bolacha,
        ];

        DB::transaction(function () use ($preparations) {
            foreach ($preparations as $row) {
                $preparation = Preparation::withTrashed()->updateOrCreate(['id' => $row['id']], [
                    'name' => $row['name'],
                    'department_id' => $row['department_id'],
                    // The scalar column is the default source: always the list's first entry.
                    'rm_warehouse_id' => $row['source_warehouse_ids'][0] ?? null,
                    'rm_warehouse_ids' => $row['source_warehouse_ids'],
                    'fg_warehouse_id' => $row['fg_warehouse_id'],
                    'item_type_warehouses' => $row['item_type_warehouses'],
                    'deleted_at' => null,
                ]);
                $preparation->eventTypes()->sync($row['event_type_ids']);
                $preparation->productionLines()->sync($row['production_line_ids']);
            }
        });
    }
}
