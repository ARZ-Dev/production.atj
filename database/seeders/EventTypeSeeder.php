<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EventType::create([
            'id' => 1,
            'recipe_id' => null,
            'name' => 'Cleaning',
            'color' => '#818cf8',
            'has_recipe' => 0,
            'duration' => 40,
            'item_type_ids' => [15],
        ]);

        EventType::create([
            'id' => 2,
            'recipe_id' => null,
            'name' => 'Preparation MT',
            'color' => '#818cf8',
            'has_recipe' => 1,
            'duration' => null,
            'item_type_ids' => [7],
        ]);
    }
}
