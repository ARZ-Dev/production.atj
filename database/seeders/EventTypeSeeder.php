<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Item type ids are the parent's (erp_auth.item_types), named in the
     * comment on each row. Matched on id, so re-running updates in place.
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
                'item_type_ids' => [15], // RM Bolacha
            ],
            [
                'id' => 2,
                'name' => 'Preparation MT',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_type_ids' => [12], // Semi-Finished Goods
            ],
            [
                'id' => 3,
                'name' => 'Direct Production',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => true,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_type_ids' => [7], // Fb. Tomate - Ketchup
            ],
            [
                'id' => 4,
                'name' => 'Prep BOLACHA',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_type_ids' => [16], // SFG Bolacha
            ],
            [
                'id' => 5,
                'name' => 'PROD BOLACHA',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => 6, // Bolacha Packaging Test
                'duration' => null,
                'item_type_ids' => [17], // PREFG Bolacha
            ],
            [
                'id' => 6,
                'name' => 'Bolacha Packaging Test',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_type_ids' => [18, 1], // Packaging Bolacha, Fb. Bolacha - Hard
            ],
        ];

        foreach ($eventTypes as $eventType) {
            EventType::withTrashed()->updateOrCreate(
                ['id' => $eventType['id']],
                Arr::except($eventType, 'companion_event_type_id') + ['recipe_id' => null, 'deleted_at' => null]
            );
        }

        // A companion is another row of this list, so link them once every row exists.
        foreach ($eventTypes as $eventType) {
            EventType::whereKey($eventType['id'])->update(['companion_event_type_id' => $eventType['companion_event_type_id']]);
        }
    }
}
