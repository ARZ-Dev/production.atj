<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FactoryEventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * One event type per step of each factory — Preparation, Production and
     * Packaging, named like FactoryRecipeTypeSeeder's recipe types — plus
     * Cleaning, the one type without a recipe (which is what the emergency
     * event dropdown lists). Each Production type is executed with its
     * factory's Packaging type, so a production run and its packaging start
     * and end together.
     *
     * Replaces EventTypeSeeder. The ids follow the types they replace —
     * 2 Preparation MT, 3 Direct Production, 4 Prep BOLACHA, 5 PROD BOLACHA,
     * 6 Bolacha Packaging Test — so the lanes PreparationSeeder, LineSeeder
     * and PackagingSeeder allow them on keep the same kind of work; Packaging
     * Massa Tomate is new (7).
     *
     * `item_type_ids` is what the add-event popup offers to make — the
     * recipe type's output — and the Packaging types add their packaging
     * materials, so a packaging's warehouse routing table has a row for them.
     *
     * Item type ids are the parent's (erp_auth), as a fresh auth-service
     * seed assigns them — this app can't read the parent's database on the
     * server, so they're written out; keep them in step with auth-service's
     * ItemTypeSeeder. Event types are matched on their own id, so re-running
     * updates in place.
     */
    public function run(): void
    {
        $eventTypes = [
            [
                'id' => 1,
                'name' => 'Cleaning',
                'color' => '#818cf8',
                'has_recipe' => false,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => 40,
                'item_type_ids' => [15],          // RM Bolacha
            ],

            // Massa Tomate
            [
                'id' => 2,
                'name' => 'Preparation Massa Tomate',
                'color' => '#f59e0b',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_type_ids' => [12],          // SFG Massa Tomate
            ],
            [
                'id' => 3,
                'name' => 'Production Massa Tomate',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => 7,   // Packaging Massa Tomate
                'duration' => null,
                'item_type_ids' => [4],           // Fb. Tomate - Massa Tomate
            ],
            [
                'id' => 7,
                'name' => 'Packaging Massa Tomate',
                'color' => '#10b981',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_type_ids' => [4, 13],       // Fb. Tomate - Massa Tomate, Packaging Materials Massa Tomate
            ],

            // Bolacha
            [
                'id' => 4,
                'name' => 'Preparation Bolacha',
                'color' => '#f59e0b',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_type_ids' => [16],          // SFG Bolacha
            ],
            [
                'id' => 5,
                'name' => 'Production Bolacha',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => 6,   // Packaging Bolacha
                'duration' => null,
                'item_type_ids' => [17],          // PFG Bolacha
            ],
            [
                'id' => 6,
                'name' => 'Packaging Bolacha',
                'color' => '#10b981',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_type_ids' => [21, 18],      // FG Bolacha, Packaging Materials Bolacha
            ],
        ];

        DB::transaction(function () use ($eventTypes) {
            foreach ($eventTypes as $row) {
                EventType::withTrashed()->updateOrCreate(
                    ['id' => $row['id']],
                    Arr::except($row, 'companion_event_type_id') + ['recipe_id' => null, 'deleted_at' => null]
                );
            }

            // A companion is another row of this list, so link them once every row exists.
            foreach ($eventTypes as $row) {
                EventType::whereKey($row['id'])->update(['companion_event_type_id' => $row['companion_event_type_id']]);
            }
        });
    }
}
