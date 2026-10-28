<?php

namespace Database\Seeders;

use App\Models\Line;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LineSeeder extends Seeder
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
     * A line is a whole continuous line run as one event; its machines are
     * its stations (LineStationSeeder).
     *
     * Massa Tomate's two can lines come from "Equipment and capacity Massa
     * tomate.xlsx" (sheet FAB MASSA TOMAT): one per filler, each ending in its
     * packaging machine.
     *
     * Bolacha's come from "Equipment and capacity Biscuit factory.xlsx" (sheet
     * FAB DE BOLACHA), one line per oven as the user chose on 2026-10-28 —
     * 2 × 1,200 kg/h, 1 × 2,000 kg/h, 2 × 1,000 kg/h — plus the wafer line.
     * Line 2 (Prod Boalcha L1 until then) is the first of them. They take dough
     * from SFG Bolacha and film, cartons and tape from RM BOLACHA, and put
     * boxes into FG BOLACHA.
     */
    public function run(): void
    {
        $bolacha = [
            'department_id' => 5,               // Bolacha - Production
            'source_warehouse_ids' => [7, 5],   // SFG Bolacha (dough), RM BOLACHA (packaging materials, rework)
            'fg_warehouse_id' => 6,             // FG BOLACHA
            'item_type_warehouses' => [],
            'event_type_ids' => [5],            // Production Bolacha
            'production_line_ids' => [2],       // Bolacha Production Line PL1
        ];

        $lines = [
            [
                'id' => 1,
                'name' => 'Can Line 70g',        // filler 360 cans/min → packer 600 cans/min
                'department_id' => 14,           // MT Production Department
                'source_warehouse_ids' => [2],   // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,          // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],    // item type id => one of source_warehouse_ids
                'event_type_ids' => [3],         // Production Massa Tomate
                'production_line_ids' => [1],    // Cans Tomato Paste Line
            ],
            [
                'id' => 3,
                'name' => 'Can Line 400g',       // filler 130 cans/min → packer 200 cans/min
                'department_id' => 14,           // MT Production Department
                'source_warehouse_ids' => [2],   // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,          // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],
                'event_type_ids' => [3],         // Production Massa Tomate
                'production_line_ids' => [1],    // Cans Tomato Paste Line
            ],
            ['id' => 2, 'name' => 'Biscuit Line 1'] + $bolacha, // oven 1,200 kg/h
            ['id' => 4, 'name' => 'Biscuit Line 2'] + $bolacha, // oven 1,200 kg/h
            ['id' => 5, 'name' => 'Biscuit Line 3'] + $bolacha, // oven 2,000 kg/h
            ['id' => 6, 'name' => 'Biscuit Line 4'] + $bolacha, // oven 1,000 kg/h
            ['id' => 7, 'name' => 'Biscuit Line 5'] + $bolacha, // oven 1,000 kg/h
            ['id' => 8, 'name' => 'Wafer Line'] + $bolacha,     // Franz Haas, 40 plates/min
        ];

        DB::transaction(function () use ($lines) {
            foreach ($lines as $row) {
                $line = Line::withTrashed()->updateOrCreate(['id' => $row['id']], [
                    'name' => $row['name'],
                    'department_id' => $row['department_id'],
                    // The scalar is the list's first entry, as the line popup saves it.
                    'sfg_warehouse_id' => $row['source_warehouse_ids'][0] ?? null,
                    'sfg_warehouse_ids' => $row['source_warehouse_ids'],
                    'fg_warehouse_id' => $row['fg_warehouse_id'],
                    'item_type_warehouses' => $row['item_type_warehouses'],
                    'deleted_at' => null,
                ]);
                $line->eventTypes()->sync($row['event_type_ids']);
                $line->productionLines()->sync($row['production_line_ids']);
            }
        });
    }
}
