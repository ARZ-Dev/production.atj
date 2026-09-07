<?php

namespace Database\Seeders;

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
        $recipeTypes = [
            [
                'id' => 1,
                'name' => 'Preparation - Mixing of tomato paste',
                'item_type_ids' => [11],
                'side_item_type_ids' => [],
                'output_item_type_ids' => [12],
            ],
            [
                'id' => 2,
                'name' => 'Production of FG 70g can',
                'item_type_ids' => [13, 12],
                'side_item_type_ids' => [],
                'output_item_type_ids' => [4],
            ],
            [
                'id' => 3,
                'name' => 'Production Plastico',
                'item_type_ids' => [11],
                'side_item_type_ids' => [],
                'output_item_type_ids' => [14],
            ],
        ];

        foreach ($recipeTypes as $recipeType) {
            RecipeType::updateOrCreate(
                ['id' => $recipeType['id']],
                $recipeType
            );
        }
    }
}
