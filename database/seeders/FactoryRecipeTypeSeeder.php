<?php

namespace Database\Seeders;

use App\Models\RecipeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FactoryRecipeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * The same three steps for each factory, over the parent's item types:
     * Preparation turns raw materials into semi-finished goods, Production
     * makes the pre-finished good, Packaging packs it into a finished good.
     * Massa Tomate's production works straight from raw materials — its BOM
     * has no semi-finished stage.
     *
     * Item type ids are the parent's (erp_auth), as a fresh auth-service
     * seed assigns them — this app can't read the parent's database on the
     * server, so they're written out; keep them in step with auth-service's
     * ItemTypeSeeder.
     *
     * Matched on name, deleted rows included, so re-running updates in place.
     */
    public function run(): void
    {
        $recipeTypes = [
            //['name' => 'Preparation Massa Tomate', 'item_type_ids' => [11], 'output_item_type_ids' => [12]],    // RM → SFG Massa Tomate
            ['name' => 'Production Massa Tomate', 'item_type_ids' => [11], 'output_item_type_ids' => [19]],     // RM → PFG Massa Tomate
            ['name' => 'Packaging Massa Tomate', 'item_type_ids' => [19, 13], 'output_item_type_ids' => [20]],  // PFG + Packaging Materials → FG Massa Tomate
            // RM Bolacha + the RM Massa Tomate items both factories share + SFG (Xarope) → SFG Bolacha
            ['name' => 'Preparation Bolacha', 'item_type_ids' => [15, 11, 16], 'output_item_type_ids' => [16]],
            ['name' => 'Production Bolacha', 'item_type_ids' => [16], 'output_item_type_ids' => [17]],          // SFG → PFG Bolacha
            ['name' => 'Packaging Bolacha', 'item_type_ids' => [17, 18], 'output_item_type_ids' => [21]],       // PFG + Packaging Materials → FG Bolacha
        ];

        DB::transaction(function () use ($recipeTypes) {
            foreach ($recipeTypes as $row) {
                $recipeType = RecipeType::withTrashed()->firstOrNew(['name' => $row['name']]);
                $recipeType->fill($row + ['side_item_type_ids' => []]);
                $recipeType->deleted_at = null;
                $recipeType->save();
            }
        });
    }
}
