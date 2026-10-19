<?php

namespace Database\Seeders;

use App\Models\Preparation;
use Database\Seeders\Concerns\ResolvesParentRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreparationSeeder extends Seeder
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
        $preparations = [
            [
                'id' => 1,
                'name' => 'Mixing Area',
                'department' => 'MT Production Department',
                'source_warehouses' => ['P.S.A MASSA TOMATE'],
                'fg_warehouse' => 'P.S.A MASSA TOMATE',
                'item_type_warehouses' => [],    // item type => one of source_warehouses
                'event_type_ids' => [1, 2, 4],   // Cleaning, Preparation MT, Prep BOLACHA
                'production_line_ids' => [],
            ],
            [
                'id' => 2,
                'name' => 'Bolacha P1',
                'department' => 'Bolacha - Production',
                'source_warehouses' => ['RM BOLACHA'],
                'fg_warehouse' => 'SFG Bolacha',
                'item_type_warehouses' => [],
                'event_type_ids' => [4],         // Prep BOLACHA
                'production_line_ids' => [2],    // Bolacha Production Line PL1
            ],
        ];

        $rows = [];
        foreach ($preparations as $preparation) {
            $sourceIds = array_map(fn ($name) => $this->warehouseId($name), $preparation['source_warehouses']);

            $rows[] = [
                'record' => [
                    'name' => $preparation['name'],
                    'department_id' => $this->departmentId($preparation['department']),
                    // The scalar column is the default source: always the list's first entry.
                    'rm_warehouse_id' => $sourceIds[0] ?? null,
                    'rm_warehouse_ids' => $sourceIds,
                    'fg_warehouse_id' => $this->warehouseId($preparation['fg_warehouse']),
                    'item_type_warehouses' => $this->warehouseRouting($preparation['item_type_warehouses']),
                    'deleted_at' => null,
                ],
            ] + $preparation;
        }

        $this->failIfParentMissing();

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $preparation = Preparation::withTrashed()->updateOrCreate(['id' => $row['id']], $row['record']);
                $preparation->eventTypes()->sync($row['event_type_ids']);
                $preparation->productionLines()->sync($row['production_line_ids']);
            }
        });
    }
}
