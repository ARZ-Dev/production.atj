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
     * Massa Tomate's come from "Equipment and capacity Massa tomate.xlsx"
     * (sheet FAB MASSA TOMAT): its two filling rows. The pasteurizing tunnel
     * between them and the packers is left out — it has no capacity of its
     * own ("based on pasteurizing time") and nothing is scheduled on it.
     * Capacities are CapacitySeeder's.
     */
    public function run(): void
    {
        $lines = [
            [
                'id' => 1,
                'name' => 'Filling & Seaming 70g', // Allan (China), 1 machine, 360 cans/min
                'department_id' => 14,           // MT Production Department
                'source_warehouse_ids' => [2],   // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,          // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],    // item type id => one of source_warehouse_ids
                'event_type_ids' => [3],         // Production Massa Tomate
                'production_line_ids' => [1],    // Cans Tomato Paste Line
            ],
            [
                'id' => 3,
                'name' => 'Filling & Seaming 400g', // Allan (China), 1 machine, 130 cans/min
                'department_id' => 14,           // MT Production Department
                'source_warehouse_ids' => [2],   // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,          // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],
                'event_type_ids' => [3],         // Production Massa Tomate
                'production_line_ids' => [1],    // Cans Tomato Paste Line
            ],
            [
                'id' => 2,
                'name' => 'Prod Boalcha L1',
                'department_id' => 5,            // Bolacha - Production
                'source_warehouse_ids' => [7],   // SFG Bolacha
                'fg_warehouse_id' => 6,          // FG BOLACHA
                'item_type_warehouses' => [],
                'event_type_ids' => [5],         // Production Bolacha
                'production_line_ids' => [2],    // Bolacha Production Line PL1
            ],
        ];

        DB::transaction(function () use ($lines) {
            foreach ($lines as $row) {
                $line = Line::withTrashed()->updateOrCreate(['id' => $row['id']], [
                    'name' => $row['name'],
                    'department_id' => $row['department_id'],
                    // The scalar column is the default source: always the list's first entry.
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
