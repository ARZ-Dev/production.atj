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
     * both sides. Bolacha's does the same with Fb. Bolacha - Hard, Soft and
     * Wafer: production makes the packet, packaging the box.
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
            // The whole can line as one run, its recipes in stages (filling & seaming,
            // pasteurizing, packing): SFG + Packaging Materials → Fb. Tomate - Massa
            // Tomate, Pelado, Vinegar, Ketchup, in cases
            ['name' => 'Production Massa Tomate', 'item_type_ids' => [12, 13], 'output_item_type_ids' => [4, 5, 6, 7]],
            // Retired 2026-10-28 — packing is the line's last station now:
            // ['name' => 'Packaging Massa Tomate', 'item_type_ids' => [4, 5, 6, 7, 13], 'output_item_type_ids' => [4, 5, 6, 7]],
            // RM Bolacha + the RM Massa Tomate items both factories share + SFG (Xarope) → SFG Bolacha
            ['name' => 'Preparation Bolacha', 'item_type_ids' => [15, 11, 16], 'output_item_type_ids' => [16]],
            // The whole biscuit line as one run, its recipes in stages (cutting, cooking,
            // creaming, packaging, packing): SFG + Packaging Materials Bolacha + Massa
            // Tomate's (the shared tape) → Fb. Bolacha - Hard, Soft, Wafer, in boxes
            ['name' => 'Production Bolacha', 'item_type_ids' => [16, 18, 13], 'output_item_type_ids' => [1, 2, 3]],
            // Retired 2026-10-28 — packing is the line's last station now:
            // ['name' => 'Packaging Bolacha', 'item_type_ids' => [1, 2, 3, 18, 13], 'output_item_type_ids' => [1, 2, 3]],
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
