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
     * makes the product, Packaging packs it. Massa Tomate's production makes
     * the finished products themselves (Fb. Tomate - Massa Tomate, Pelado,
     * Vinegar, Ketchup) in their basic unit, and packaging turns those, with
     * the packaging materials, into the packed ones — the same item types on
     * both sides. Bolacha's production still makes a pre-finished good that
     * packaging turns into FG Bolacha.
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
            ['name' => 'Preparation Massa Tomate', 'item_type_ids' => [11], 'output_item_type_ids' => [12]],   // RM → SFG Massa Tomate
            // SFG → Fb. Tomate - Massa Tomate, Pelado, Vinegar, Ketchup (basic unit)
            ['name' => 'Production Massa Tomate', 'item_type_ids' => [12], 'output_item_type_ids' => [4, 5, 6, 7]],
            // The same Fb. Tomate types (basic unit) + Packaging Materials → the same types (packed)
            ['name' => 'Packaging Massa Tomate', 'item_type_ids' => [4, 5, 6, 7, 13], 'output_item_type_ids' => [4, 5, 6, 7]],
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
