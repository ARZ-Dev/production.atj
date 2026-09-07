<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeInput;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Item ids, unit ids and item type ids are resolved from the parent
     * service (erp_auth) by the item code shown in the comment on each row.
     */
    public function run(): void
    {
        $recipes = [
            [
                'id' => 1,
                'recipe_type_id' => 1,
                'name' => 'Preparation for Tomato Paste Bulk Brix 22-24',
                // 402000003 - Tomato Paste Bulk Brix 22-24
                'item_type_id' => 12,
                'item_id' => 119,
                'item_unit_id' => 147,
                'batch' => 1,
                'batch_weight' => null,
                'batch_volume' => null,
                'quantity_per_batch' => 924.7300,
                'status' => 1,
                'notes' => null,
                'inputs' => [
                    // 401000001 - MASSA TOMATE CONCETRADO 36-38° BRIX 1X240KG
                    ['id' => 1, 'item_type_id' => 11, 'item_id' => 37, 'item_unit_id' => 65, 'quantity' => 138.7500],
                    // 401000004 - MONASCUS COR VERMELHO
                    ['id' => 2, 'item_type_id' => 11, 'item_id' => 40, 'item_unit_id' => 68, 'quantity' => 0.7000],
                    // 401000006 - SAL DE POTASSIO DE ACIDO SORBICO
                    ['id' => 3, 'item_type_id' => 11, 'item_id' => 42, 'item_unit_id' => 70, 'quantity' => 0.4400],
                    // 401000008 - SODIUM BENZOATE
                    ['id' => 4, 'item_type_id' => 11, 'item_id' => 44, 'item_unit_id' => 72, 'quantity' => 0.4400],
                    // 401000020 - WATER ( FABRICA MASSA TOMATE ) / LT
                    ['id' => 5, 'item_type_id' => 11, 'item_id' => 46, 'item_unit_id' => 74, 'quantity' => 590.0000],
                    // 401000035 - Tomato Flavor
                    ['id' => 6, 'item_type_id' => 11, 'item_id' => 51, 'item_unit_id' => 79, 'quantity' => 0.7400],
                    // 990100003 - FUBA DE MILHO AMARELA
                    ['id' => 7, 'item_type_id' => 11, 'item_id' => 54, 'item_unit_id' => 82, 'quantity' => 100.9700],
                    // 990101002 - Y- SAL INDUSTRIAL FABRICA
                    ['id' => 8, 'item_type_id' => 11, 'item_id' => 56, 'item_unit_id' => 84, 'quantity' => 17.9800],
                    // 990101003 - Y- ACUCAR CRISTAL INDUSTRIAL
                    ['id' => 9, 'item_type_id' => 11, 'item_id' => 57, 'item_unit_id' => 85, 'quantity' => 73.1500],
                    // 990101010 - Y- ACIDO CITRICO
                    ['id' => 10, 'item_type_id' => 11, 'item_id' => 58, 'item_unit_id' => 86, 'quantity' => 1.5600],
                ],
            ],
            [
                'id' => 2,
                'recipe_type_id' => 2,
                'name' => 'Production for MASSA TOMATE SUPER CHEF LATA 50x70 GR',
                // 403000021 - MASSA TOMATE SUPER CHEF LATA 50x70 GR
                'item_type_id' => 4,
                'item_id' => 121,
                'item_unit_id' => 149,
                'batch' => 1,
                'batch_weight' => null,
                'batch_volume' => null,
                'quantity_per_batch' => 1618.0000,
                'status' => 1,
                'notes' => null,
                'inputs' => [
                    // 401500006 - TAMPAS NORMAIS P/ LATA DE 70 GR
                    ['id' => 11, 'item_type_id' => 13, 'item_id' => 70, 'item_unit_id' => 98, 'quantity' => 81303.0000],
                    // 403500012 - LATA VAZIA SUPER CHEF 70 GR
                    ['id' => 12, 'item_type_id' => 13, 'item_id' => 91, 'item_unit_id' => 119, 'quantity' => 81219.0000],
                    // 601116807 - CAIXA MASSA TOMATE SUPER CHEF 50X70G
                    ['id' => 13, 'item_type_id' => 13, 'item_id' => 111, 'item_unit_id' => 139, 'quantity' => 1619.0000],
                    // 402000003 - Tomato Paste Bulk Brix 22-24 (the output of recipe 1)
                    ['id' => 14, 'item_type_id' => 12, 'item_id' => 119, 'item_unit_id' => 147, 'quantity' => 5803.0000],
                    // 990102005 - Y- FITA COLA
                    ['id' => 15, 'item_type_id' => 13, 'item_id' => 118, 'item_unit_id' => 146, 'quantity' => 2.5000],
                ],
            ],
        ];

        foreach ($recipes as $recipe) {
            $inputs = $recipe['inputs'];
            unset($recipe['inputs']);

            Recipe::updateOrCreate(['id' => $recipe['id']], $recipe);

            foreach ($inputs as $input) {
                $input['recipe_id'] = $recipe['id'];
                $input['notes'] = null;

                RecipeInput::updateOrCreate(['id' => $input['id']], $input);
            }
        }
    }
}
