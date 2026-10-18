<?php

namespace Database\Seeders;

use App\Models\RecipeType;
use Database\Seeders\Concerns\ResolvesParentRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FactoryRecipeTypeSeeder extends Seeder
{
    use ResolvesParentRecords;

    /**
     * Run the database seeds.
     *
     * The same three steps for each factory, over the parent's item types
     * (looked up by name): Preparation turns raw materials into semi-finished
     * goods, Production makes the pre-finished good, Packaging packs it into
     * a finished good. Massa Tomate's production works straight from raw
     * materials — its BOM has no semi-finished stage.
     *
     * Matched on name, deleted rows included, so re-running updates in place.
     */
    public function run(): void
    {
        $recipeTypes = [
            ['name' => 'Preparation Massa Tomate', 'uses' => ['RM Massa Tomate'], 'makes' => ['SFG Massa Tomate']],
            ['name' => 'Production Massa Tomate', 'uses' => ['RM Massa Tomate'], 'makes' => ['PFG Massa Tomate']],
            ['name' => 'Packaging Massa Tomate', 'uses' => ['PFG Massa Tomate', 'Packaging Materials Massa Tomate'], 'makes' => ['FG Massa Tomate']],
            ['name' => 'Preparation Bolacha', 'uses' => ['RM Bolacha'], 'makes' => ['SFG Bolacha']],
            ['name' => 'Production Bolacha', 'uses' => ['SFG Bolacha'], 'makes' => ['PFG Bolacha']],
            ['name' => 'Packaging Bolacha', 'uses' => ['PFG Bolacha', 'Packaging Materials Bolacha'], 'makes' => ['FG Bolacha']],
        ];

        $rows = array_map(fn ($type) => [
            'name' => $type['name'],
            'item_type_ids' => array_map(fn ($name) => $this->itemTypeId($name), $type['uses']),
            'side_item_type_ids' => [],
            'output_item_type_ids' => array_map(fn ($name) => $this->itemTypeId($name), $type['makes']),
        ], $recipeTypes);

        $this->failIfParentMissing();

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $recipeType = RecipeType::withTrashed()->firstOrNew(['name' => $row['name']]);
                $recipeType->fill($row);
                $recipeType->deleted_at = null;
                $recipeType->save();
            }
        });
    }
}
