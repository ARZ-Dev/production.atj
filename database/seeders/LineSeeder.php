<?php

namespace Database\Seeders;

use App\Models\Line;
use Illuminate\Database\Seeder;

class LineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Department and warehouse ids are the parent's, named in the comment on
     * each row. Runs after EventTypeSeeder and ProductionLineSeeder, whose
     * rows it attaches.
     */
    public function run(): void
    {
        $lines = [
            [
                'id' => 1,
                'name' => 'Filling Machine',
                'department_id' => 14,           // MT Production Department
                'sfg_warehouse_ids' => [2],      // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,          // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],    // item type id => one of sfg_warehouse_ids
                'event_type_ids' => [2, 3],      // Preparation MT, Direct Production
                'production_line_ids' => [1],    // Cans Tomato Paste Line
            ],
            [
                'id' => 2,
                'name' => 'Prod Boalcha L1',
                'department_id' => 5,            // Bolacha - Production
                'sfg_warehouse_ids' => [7],      // SFG Bolacha
                'fg_warehouse_id' => 6,          // FG BOLACHA
                'item_type_warehouses' => [],
                'event_type_ids' => [5],         // PROD BOLACHA
                'production_line_ids' => [2],    // Bolacha Production Line PL1
            ],
        ];

        foreach ($lines as $row) {
            $line = Line::withTrashed()->updateOrCreate(['id' => $row['id']], [
                'name' => $row['name'],
                'department_id' => $row['department_id'],
                // The scalar column is the default source: always the list's first entry.
                'sfg_warehouse_id' => $row['sfg_warehouse_ids'][0] ?? null,
                'sfg_warehouse_ids' => $row['sfg_warehouse_ids'],
                'fg_warehouse_id' => $row['fg_warehouse_id'],
                'item_type_warehouses' => $row['item_type_warehouses'],
                'deleted_at' => null,
            ]);

            $line->eventTypes()->sync($row['event_type_ids']);
            $line->productionLines()->sync($row['production_line_ids']);
        }
    }
}
