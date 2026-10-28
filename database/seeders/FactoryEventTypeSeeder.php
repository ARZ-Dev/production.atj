<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FactoryEventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * One event type per step of each factory — Preparation (a mix, its own
     * event) and Production (the whole line as one run: its recipes have
     * stages, one per station) — named like FactoryRecipeTypeSeeder's recipe
     * types, plus Cleaning, the one type without a recipe (which is what the
     * emergency event / station stop dropdown lists).
     *
     * Replaces EventTypeSeeder. The ids follow the types they replace —
     * 2 Preparation MT, 3 Direct Production, 4 Prep BOLACHA, 5 PROD BOLACHA —
     * so the lanes PreparationSeeder and LineSeeder allow them on keep the
     * same kind of work.
     *
     * Packaging Bolacha (6) and Packaging Massa Tomate (7) are retired since
     * 2026-10-28: packing is a station of the line now, not an event of its
     * own. They are soft-deleted when present, so their ids aren't reused.
     *
     * `item_type_ids` is what the add-event popup offers to make — the
     * recipe type's output.
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
                'duration' => null,
                'item_type_ids' => [12],          // SFG Massa Tomate
            ],
            [
                'id' => 3,
                'name' => 'Production Massa Tomate',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'duration' => null,
                'item_type_ids' => [4, 5, 6, 7],  // Fb. Tomate - Massa Tomate, Pelado, Vinegar, Ketchup
            ],

            // Bolacha
            [
                'id' => 4,
                'name' => 'Preparation Bolacha',
                'color' => '#f59e0b',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'duration' => null,
                'item_type_ids' => [16],          // SFG Bolacha
            ],
            [
                'id' => 5,
                'name' => 'Production Bolacha',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'duration' => null,
                'item_type_ids' => [1, 2, 3],     // Fb. Bolacha - Hard, Soft, Wafer
            ],
        ];

        // Packaging Bolacha, Packaging Massa Tomate — packing is a station now.
        $retired = [6, 7];

        DB::transaction(function () use ($eventTypes, $retired) {
            foreach ($eventTypes as $row) {
                EventType::withTrashed()->updateOrCreate(
                    ['id' => $row['id']],
                    $row + ['recipe_id' => null, 'deleted_at' => null]
                );
            }

            EventType::whereIn('id', $retired)->get()->each->delete();
        });
    }
}
