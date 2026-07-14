<?php

namespace Database\Seeders;

use App\Models\EventType;
use App\Models\RecipeType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecipeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RecipeType::create([
            'id' => 1,
            'name' => 'Preparation MT',
            'item_type_ids' => [11],
            'side_item_type_ids' => [14],
            'output_item_type_ids' => [12],
        ]);

        RecipeType::create([
            'id' => 2,
            'name' => 'Production MT',
            'item_type_ids' => [12],
            'side_item_type_ids' => [14],
            'output_item_type_ids' => [7],
        ]);
    }
}
