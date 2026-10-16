<?php

namespace Database\Seeders;

use App\Models\Preparation;
use Illuminate\Database\Seeder;

class PreparationSeeder extends Seeder
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
        $preparations = [
            [
                'id' => 1,
                'name' => 'Mixing Area',
                'department_id' => 14,           // MT Production Department
                'rm_warehouse_ids' => [2],       // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,          // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],    // item type id => one of rm_warehouse_ids
                'event_type_ids' => [1, 2, 4],   // Cleaning, Preparation MT, Prep BOLACHA
                'production_line_ids' => [],
            ],
            [
                'id' => 2,
                'name' => 'Bolacha P1',
                'department_id' => 5,            // Bolacha - Production
                'rm_warehouse_ids' => [5],       // RM BOLACHA
                'fg_warehouse_id' => 7,          // SFG Bolacha
                'item_type_warehouses' => [],
                'event_type_ids' => [4],         // Prep BOLACHA
                'production_line_ids' => [2],    // Bolacha Production Line PL1
            ],
        ];

        foreach ($preparations as $row) {
            $preparation = Preparation::withTrashed()->updateOrCreate(['id' => $row['id']], [
                'name' => $row['name'],
                'department_id' => $row['department_id'],
                // The scalar column is the default source: always the list's first entry.
                'rm_warehouse_id' => $row['rm_warehouse_ids'][0] ?? null,
                'rm_warehouse_ids' => $row['rm_warehouse_ids'],
                'fg_warehouse_id' => $row['fg_warehouse_id'],
                'item_type_warehouses' => $row['item_type_warehouses'],
                'deleted_at' => null,
            ]);

            $preparation->eventTypes()->sync($row['event_type_ids']);
            $preparation->productionLines()->sync($row['production_line_ids']);
        }
    }
}
