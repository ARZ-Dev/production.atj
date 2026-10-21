<?php

namespace Database\Seeders;

use App\Models\Packaging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Department, warehouse (the factory included) and item type ids are the
     * parent's (erp_auth), as a fresh auth-service seed assigns them — this
     * app can't read the parent's database on the server, so they're written
     * out; keep them in step with auth-service's seeders. Event types are
     * this app's own rows — runs after FactoryEventTypeSeeder.
     *
     * Massa Tomate's come from "Equipment and capacity Massa tomate.xlsx"
     * (sheet FAB MASSA TOMAT): its two packaging-machine rows (cans, cartons,
     * tape). The label machines are left out. Capacities are CapacitySeeder's.
     */
    public function run(): void
    {
        $packagings = [
            [
                'id' => 1,
                'name' => 'PK 1 Bolacha',
                'department_id' => 5,               // Bolacha - Production
                'factory_id' => 4,                  // Bolacha Factory
                'source_warehouse_ids' => [6, 5],   // FG BOLACHA, RM BOLACHA
                'fg_warehouse_id' => 6,             // FG BOLACHA
                'item_type_warehouses' => [         // item type id => one of source_warehouse_ids
                    18 => 5,                        // Packaging Materials Bolacha => RM BOLACHA
                ],
                'event_type_ids' => [6],            // Packaging Bolacha
            ],
            [
                'id' => 2,
                'name' => 'PK 2 Bolacha',
                'department_id' => 5,               // Bolacha - Production
                'factory_id' => 4,                  // Bolacha Factory
                'source_warehouse_ids' => [6, 5],   // FG BOLACHA, RM BOLACHA
                'fg_warehouse_id' => 6,             // FG BOLACHA
                'item_type_warehouses' => [],
                'event_type_ids' => [6],            // Packaging Bolacha
            ],
            [
                'id' => 3,
                'name' => 'Packaging Machine 70g',  // Allan (China), 1 machine, 600 cans/min
                'department_id' => 14,              // MT Production Department
                'factory_id' => 1,                  // Massa Tomate Factory Warehouse
                'source_warehouse_ids' => [2],      // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,             // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],
                'event_type_ids' => [7],            // Packaging Massa Tomate
            ],
            [
                'id' => 4,
                'name' => 'Packaging Machine 400g', // Allan (China), 1 machine, 200 cans/min
                'department_id' => 14,              // MT Production Department
                'factory_id' => 1,                  // Massa Tomate Factory Warehouse
                'source_warehouse_ids' => [2],      // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,             // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],
                'event_type_ids' => [7],            // Packaging Massa Tomate
            ],
        ];

        DB::transaction(function () use ($packagings) {
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
        });
    }
}
