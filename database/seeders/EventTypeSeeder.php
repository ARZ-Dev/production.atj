<?php

namespace Database\Seeders;

use App\Models\EventType;
use Database\Seeders\Concerns\ResolvesParentRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EventTypeSeeder extends Seeder
{
    use ResolvesParentRecords;

    /**
     * Run the database seeds.
     *
     * Item types are the parent's, looked up by name. Event types are matched
     * on their own id, so re-running updates in place.
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
                'item_types' => ['RM Bolacha'],
            ],
            [
                'id' => 2,
                'name' => 'Preparation MT',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_types' => ['SFG Massa Tomate'],
            ],
            [
                'id' => 3,
                'name' => 'Direct Production',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => true,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_types' => ['Fb. Tomate - Ketchup'],
            ],
            [
                'id' => 4,
                'name' => 'Prep BOLACHA',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_types' => ['SFG Bolacha'],
            ],
            [
                'id' => 5,
                'name' => 'PROD BOLACHA',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => 6, // Bolacha Packaging Test
                'duration' => null,
                'item_types' => ['PFG Bolacha'],
            ],
            [
                'id' => 6,
                'name' => 'Bolacha Packaging Test',
                'color' => '#818cf8',
                'has_recipe' => true,
                'start_items_unverifiable' => false,
                'companion_event_type_id' => null,
                'duration' => null,
                'item_types' => ['Packaging Materials Bolacha', 'Fb. Bolacha - Hard'],
            ],
        ];

        $rows = array_map(fn ($eventType) => Arr::except($eventType, 'item_types') + [
            'item_type_ids' => array_map(fn ($name) => $this->itemTypeId($name), $eventType['item_types']),
        ], $eventTypes);

        $this->failIfParentMissing();

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                EventType::withTrashed()->updateOrCreate(
                    ['id' => $row['id']],
                    Arr::except($row, 'companion_event_type_id') + ['recipe_id' => null, 'deleted_at' => null]
                );
            }

            // A companion is another row of this list, so link them once every row exists.
            foreach ($rows as $row) {
                EventType::whereKey($row['id'])->update(['companion_event_type_id' => $row['companion_event_type_id']]);
            }
        });
    }
}
