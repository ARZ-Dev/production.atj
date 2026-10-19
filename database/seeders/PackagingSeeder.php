<?php

namespace Database\Seeders;

use App\Models\Packaging;
use Database\Seeders\Concerns\ResolvesParentRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackagingSeeder extends Seeder
{
    use ResolvesParentRecords;

    /**
     * Run the database seeds.
     *
     * Departments, warehouses (the factory included) and item types are the
     * parent's, looked up by name. Event types are this app's own rows, by
     * id — runs after EventTypeSeeder.
     */
    public function run(): void
    {
        $packagings = [
            [
                'id' => 1,
                'name' => 'PK 1 Bolacha',
                'department' => 'Bolacha - Production',
                'factory' => 'Bolacha Factory',
                'source_warehouses' => ['FG BOLACHA', 'RM BOLACHA'],
                'fg_warehouse' => 'FG BOLACHA',
                'item_type_warehouses' => [      // item type => one of source_warehouses
                    'Packaging Materials Bolacha' => 'RM BOLACHA',
                ],
                'event_type_ids' => [6],         // Bolacha Packaging Test
            ],
            [
                'id' => 2,
                'name' => 'PK 2 Bolacha',
                'department' => 'Bolacha - Production',
                'factory' => 'Bolacha Factory',
                'source_warehouses' => ['FG BOLACHA', 'RM BOLACHA'],
                'fg_warehouse' => 'FG BOLACHA',
                'item_type_warehouses' => [],
                'event_type_ids' => [6],         // Bolacha Packaging Test
            ],
        ];

        $rows = [];
        foreach ($packagings as $packaging) {
            $sourceIds = array_map(fn ($name) => $this->warehouseId($name), $packaging['source_warehouses']);

            $rows[] = [
                'record' => [
                    'name' => $packaging['name'],
                    'department_id' => $this->departmentId($packaging['department']),
                    'factory_id' => $this->warehouseId($packaging['factory']),
                    // The scalar column is the default source: always the list's first entry.
                    'source_warehouse_id' => $sourceIds[0] ?? null,
                    'source_warehouse_ids' => $sourceIds,
                    'fg_warehouse_id' => $this->warehouseId($packaging['fg_warehouse']),
                    'item_type_warehouses' => $this->warehouseRouting($packaging['item_type_warehouses']),
                    'deleted_at' => null,
                ],
            ] + $packaging;
        }

        $this->failIfParentMissing();

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $packaging = Packaging::withTrashed()->updateOrCreate(['id' => $row['id']], $row['record']);
                $packaging->eventTypes()->sync($row['event_type_ids']);
            }
        });
    }
}
