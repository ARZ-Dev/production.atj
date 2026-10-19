<?php

namespace Database\Seeders;

use App\Models\Line;
use Database\Seeders\Concerns\ResolvesParentRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LineSeeder extends Seeder
{
    use ResolvesParentRecords;

    /**
     * Run the database seeds.
     *
     * Departments, warehouses and item types are the parent's, looked up by
     * name. Event types and production lines are this app's own rows, by id —
     * runs after EventTypeSeeder and ProductionLineSeeder.
     */
    public function run(): void
    {
        $lines = [
            [
                'id' => 1,
                'name' => 'Filling Machine',
                'department' => 'MT Production Department',
                'source_warehouses' => ['P.S.A MASSA TOMATE'],
                'fg_warehouse' => 'P.S.A MASSA TOMATE',
                'item_type_warehouses' => [],    // item type => one of source_warehouses
                'event_type_ids' => [2, 3],      // Preparation MT, Direct Production
                'production_line_ids' => [1],    // Cans Tomato Paste Line
            ],
            [
                'id' => 2,
                'name' => 'Prod Boalcha L1',
                'department' => 'Bolacha - Production',
                'source_warehouses' => ['SFG Bolacha'],
                'fg_warehouse' => 'FG BOLACHA',
                'item_type_warehouses' => [],
                'event_type_ids' => [5],         // PROD BOLACHA
                'production_line_ids' => [2],    // Bolacha Production Line PL1
            ],
        ];

        $rows = [];
        foreach ($lines as $line) {
            $sourceIds = array_map(fn ($name) => $this->warehouseId($name), $line['source_warehouses']);

            $rows[] = [
                'record' => [
                    'name' => $line['name'],
                    'department_id' => $this->departmentId($line['department']),
                    // The scalar column is the default source: always the list's first entry.
                    'sfg_warehouse_id' => $sourceIds[0] ?? null,
                    'sfg_warehouse_ids' => $sourceIds,
                    'fg_warehouse_id' => $this->warehouseId($line['fg_warehouse']),
                    'item_type_warehouses' => $this->warehouseRouting($line['item_type_warehouses']),
                    'deleted_at' => null,
                ],
            ] + $line;
        }

        $this->failIfParentMissing();

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $line = Line::withTrashed()->updateOrCreate(['id' => $row['id']], $row['record']);
                $line->eventTypes()->sync($row['event_type_ids']);
                $line->productionLines()->sync($row['production_line_ids']);
            }
        });
    }
}
