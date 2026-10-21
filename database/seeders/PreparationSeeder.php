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
     */
    public function run(): void
    {
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
            [
                'id' => 2,
                'name' => 'Bolacha P1',
                'department_id' => 5,            // Bolacha - Production
                'source_warehouse_ids' => [5],   // RM BOLACHA
                'fg_warehouse_id' => 7,          // SFG Bolacha
                'item_type_warehouses' => [],
                'event_type_ids' => [4],         // Preparation Bolacha
                'production_line_ids' => [2],    // Bolacha Production Line PL1
            ],
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
