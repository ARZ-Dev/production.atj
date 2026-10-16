<?php

namespace Database\Seeders;

use App\Models\Packaging;
use Illuminate\Database\Seeder;

class PackagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Department, factory and warehouse ids are the parent's, named in the
     * comment on each row. Runs after EventTypeSeeder, whose rows it attaches.
     */
    public function run(): void
    {
        $packagings = [
            [
                'id' => 1,
                'name' => 'PK 1 Bolacha',
                'department_id' => 5,              // Bolacha - Production
                'factory_id' => 4,                 // Bolacha Factory
                'source_warehouse_ids' => [6, 5],  // FG BOLACHA, RM BOLACHA
                'fg_warehouse_id' => 6,            // FG BOLACHA
                'item_type_warehouses' => [
                    18 => 5,                       // Packaging Bolacha => RM BOLACHA
                ],
                'event_type_ids' => [6],           // Bolacha Packaging Test
            ],
            [
                'id' => 2,
                'name' => 'PK 2 Bolacha',
                'department_id' => 5,              // Bolacha - Production
                'factory_id' => 4,                 // Bolacha Factory
                'source_warehouse_ids' => [6, 5],  // FG BOLACHA, RM BOLACHA
                'fg_warehouse_id' => 6,            // FG BOLACHA
                'item_type_warehouses' => [],
                'event_type_ids' => [6],           // Bolacha Packaging Test
            ],
        ];

        foreach ($packagings as $row) {
            $packaging = Packaging::withTrashed()->updateOrCreate(['id' => $row['id']], [
                'name' => $row['name'],
                'department_id' => $row['department_id'],
                'factory_id' => $row['factory_id'],
                // The scalar column is the default source: always the list's first entry.
                'source_warehouse_id' => $row['source_warehouse_ids'][0] ?? null,
                'source_warehouse_ids' => $row['source_warehouse_ids'],
                'fg_warehouse_id' => $row['fg_warehouse_id'],
                'item_type_warehouses' => $row['item_type_warehouses'],
                'deleted_at' => null,
            ]);

            $packaging->eventTypes()->sync($row['event_type_ids']);
        }
    }
}
