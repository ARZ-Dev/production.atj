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
            ['company_id' => 1, 'name' => 'Recipe A', 'duration' => 60],
            ['company_id' => 1, 'name' => 'Recipe B', 'duration' => 90],
            ['company_id' => 1, 'name' => 'Recipe C', 'duration' => 120],
        ];

        foreach ($recipes as $recipe) {
            \App\Models\Recipe::updateOrCreate(
                ['name' => $recipe['name'], 'company_id' => $recipe['company_id']],
                ['duration' => $recipe['duration']]
            );
        }
    }
}
