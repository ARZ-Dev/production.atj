<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recipes = [
            ['name' => 'Recipe A', 'duration' => 60],
            ['name' => 'Recipe B', 'duration' => 90],
            ['name' => 'Recipe C', 'duration' => 120],
        ];

        foreach ($recipes as $recipe) {
            \App\Models\Recipe::updateOrCreate(
                ['name' => $recipe['name']],
                ['duration' => $recipe['duration']]
            );
        }
    }
}
