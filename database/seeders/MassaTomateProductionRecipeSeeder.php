<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MassaTomateProductionRecipeSeeder extends Seeder
{
    /**
     * Seeded by FactoryRecipeTypeSeeder: uses SFG Massa Tomate, makes the
     * Fb. Tomate finished goods (Massa Tomate, Pelado, Vinegar, Ketchup) in
     * their basic unit.
     */
    private const RECIPE_TYPE_NAME = 'Production Massa Tomate';

    /**
     * Item ids below are the parent's (erp_auth), as a fresh auth-service
     * seed assigns them — this app can't read the parent's database on the
     * server, so they're written out. Keep them in step with auth-service's
     * item seeders.
     */
    public const SEMI_FINISHED_ITEM_TYPE_ID = 12; // SFG Massa Tomate

    /**
     * What the products are made from — MassaTomateRecipeSeeder's products,
     * from auth-service's MassaTomateBomSemiFinishedGoodItemSeeder:
     * code => [item id, basic unit id (KG), name].
     */
    public const SEMI_FINISHED = [
        'SFGMT-01' => [122, 150, 'Mamatia'],
        'SFGMT-02' => [123, 151, 'Dourado'],
        'SFGMT-03' => [124, 152, 'Yokabel'],
        'SFGMT-04' => [125, 153, 'Super Chef'],
        'SFGMT-05' => [126, 154, 'Adal'],
        'SFGMT-06' => [127, 155, 'Cereal'],
        'SFGMT-07' => [128, 156, 'Apple'],
        'SFGMT-08' => [129, 157, 'Kup Dourado'],
        'SFGMT-09' => [130, 158, 'Sauce of Peeled Tomato'],
    ];

    /**
     * The products, from auth-service's ItemSeeder,
     * MassaTomateFinishedGoodItemSeeder (403000021) and
     * MassaTomateSkuItemSeeder (256–264, and 403600003's units):
     * code => [item id, basic unit id, item type id, recipe name]. The
     * recipe is named with the sheet's description, which names the case;
     * the comment gives the basic unit and, where it differs, the parent's
     * own name for the item.
     */
    public const PRODUCTS = [
        '403000001' => [1, 1, 4, 'MASSA TOMATE MAMA TIA LATA 50X70 GR'], // 70g · MASSA TOMATE MAMA TIA LATA 70 GR
        '403000003' => [3, 5, 4, 'MASSA TOMATE MAMA TIA LATA ABRI FACIL 24X400 GR'], // 400g · MASSA TOMATE MAMA TIA LATA ABRI FACIL 400 GR
        '403000005' => [5, 9, 4, 'MASSA TOMATE MAMA TIA PACOTE 50X70 GR'], // 70g · MASSA TOMATE MAMA TIA PACOTE 70 GR
        '403000007' => [7, 13, 4, 'MASSA TOMATE MAMA TIA PACOTE 12X400 GR'], // 400g · MASSA TOMATE MAMA TIA PACOTE 400 GR
        '403000017' => [17, 33, 4, 'MASSA TOMATE MAMA TIA BALDE 6X1KG'], // 1KG · MASSA TOMATE MAMA TIA BALDE 1 KG
        '403000018' => [18, 35, 4, 'MASSA TOMATE MAMA TIA LATA 25X70 GR'], // 70g · MASSA TOMATE MAMA TIA LATA 70 GR
        '403000002' => [2, 3, 4, 'MASSA TOMATE DOURADO LATA 50X70 GR'], // 70g · MASSA TOMATE DOURADO LATA 70 GR
        '403000004' => [4, 7, 4, 'MASSA TOMATE DOURADO LATA ABRI FACIL 24X400 GR'], // 400g · MASSA TOMATE DOURADO LATA ABRI FACIL 400 GR
        '403000006' => [6, 11, 4, 'MASSA TOMATE DOURADO PACOTE 50X70 GR'], // 70g · MASSA TOMATE DOURADO PACOTE 70 GR
        '403000008' => [8, 15, 4, 'MASSA TOMATE DOURADO PACOTE 12X400 GR'], // 400g · MASSA TOMATE DOURADO PACOTE 400 GR
        '403000019' => [256, 284, 4, 'MASSA TOMATE YOKABEL LATA ABRI FACIL 24X400 GR'], // 400g
        '403000021' => [121, 149, 4, 'MASSA TOMATE SUPER CHEF 50X70 GR'], // KG · MASSA TOMATE SUPER CHEF LATA 50x70 GR
        '403000022' => [257, 286, 4, 'MASSA TOMATE SUPER CHEF LATA ABRI FACIL 24X400 GR'], // 400g
        '403000101' => [258, 288, 4, 'MASSA TOMATE ADAL 50X70 GR'], // 70g
        '403000102' => [259, 290, 4, 'MASSA TOMATE ADAL LATA ABRI FACIL 24X400 GR'], // 400g
        '403600001' => [25, 47, 6, 'CEREAL VINEGAR MAMATIA 500 ML ( 1 X 12 BOTTLES)'], // 500ML · VINAGRE DE CEREAL MAMATIA GARRAFA 500ML
        '403600010' => [260, 292, 6, 'CEREAL VINEGAR BOM APETITE 500 ML ( 1 X 12 BOTTLES)'], // 500ML
        '403600012' => [261, 294, 6, 'VEGETAL VINEGAR VELI 500 ML ( 1 X 12 BOTTLES)'], // 500ML
        '403600003' => [28, 296, 6, 'APPLE VINEGAR MAMATIA 500 ML ( 1 X 12 BOTTLES)'], // 500ML · VINAGRE DE MACA MAMATIA GARRAFA 500ML
        '403600011' => [262, 298, 6, 'APPLE VINEGAR BOM APETITE 500 ML ( 1 X 12 BOTTLES)'], // 500ML
        '403800002' => [15, 29, 7, 'KETCHUP DOURADO 12 X 450 ML'], // 450ML · KETCHUP DOURADO 450 ML
        '403900001' => [263, 300, 5, 'Tomato Peelado Mamatia 24X400 GR'], // 400g
        '403900002' => [21, 41, 5, 'Tomato Peelado Mamatia 12X400 GR'], // 400g · TOMATE PELADO MAMATIA 400 GR
        '403900003' => [22, 43, 5, 'Tomato Peelado Mamatia 6X400 GR'], // 400g · TOMATE PELADO MAMATIA 400 GR
        '403900004' => [264, 302, 5, 'Tomato Peelado Yokabel 24X400 GR'], // 400g
    ];

    /**
     * "SKU's produced for different brands" (SKU's TP.xlsx), every row in
     * sheet order: product code => [what it's made from (the "Recipe"
     * column), quantity per batch in the product's basic unit — the first
     * "case config" column, units per case — and the semi-finished good's
     * quantity in KG — the sheet's "net weight kg/case"]. So a batch is one
     * case. Ketchup's "Dourado" is the ketchup BOM recipe, Kup Dourado, and
     * peeled tomato's "Same recipe" is Sauce of Peeled Tomato.
     */
    public const RECIPES = [
        '403000001' => ['SFGMT-01', 50, 3.5], // Tomato paste · Mamatia
        '403000003' => ['SFGMT-01', 24, 9.6], // Tomato paste · Mamatia
        '403000005' => ['SFGMT-01', 50, 3.35], // Tomato paste · Mamatia
        '403000007' => ['SFGMT-01', 12, 4.8], // Tomato paste · Mamatia
        '403000017' => ['SFGMT-01', 6, 6], // Tomato paste · Mamatia
        '403000018' => ['SFGMT-01', 25, 1.75], // Tomato paste · Mamatia
        '403000002' => ['SFGMT-02', 50, 3.5], // Tomato paste · Dourado
        '403000004' => ['SFGMT-02', 24, 9.6], // Tomato paste · Dourado
        '403000006' => ['SFGMT-02', 50, 3.35], // Tomato paste · Dourado
        '403000008' => ['SFGMT-02', 12, 4.8], // Tomato paste · Dourado
        '403000019' => ['SFGMT-03', 24, 9.6], // Tomato paste · Yokabel
        '403000021' => ['SFGMT-04', 50, 3.5], // Tomato paste · Super Chef
        '403000022' => ['SFGMT-04', 24, 9.6], // Tomato paste · Super Chef
        '403000101' => ['SFGMT-05', 50, 3.5], // Tomato paste · Adal
        '403000102' => ['SFGMT-05', 24, 9.6], // Tomato paste · Adal
        '403600001' => ['SFGMT-06', 12, 6], // Vinegar · Cereal
        '403600010' => ['SFGMT-06', 12, 6], // Vinegar · Cereal
        '403600012' => ['SFGMT-06', 12, 6], // Vinegar · Cereal
        '403600003' => ['SFGMT-07', 12, 6], // Vinegar · Apple
        '403600011' => ['SFGMT-07', 12, 6], // Vinegar · Apple
        '403800002' => ['SFGMT-08', 12, 6], // Ketchup · Dourado
        '403900001' => ['SFGMT-09', 24, 9.6], // Peeled tomato · Same recipe
        '403900002' => ['SFGMT-09', 12, 4.8], // Peeled tomato · Same recipe
        '403900003' => ['SFGMT-09', 6, 2.4], // Peeled tomato · Same recipe
        '403900004' => ['SFGMT-09', 24, 9.6], // Peeled tomato · Same recipe
    ];

    /**
     * Run the database seeds.
     *
     * One recipe per product: its basic unit, a case of it per batch, one
     * input — its semi-finished good. Every code must have its
     * ids above, the products must be of an item type the recipe type makes
     * and the inputs one it uses — otherwise the recipe form couldn't show
     * them. Safe to re-run: each recipe is matched on its type + product and
     * its input on the item; values are updated in place and any other input
     * is removed.
     */
    public function run(): void
    {
        $recipeType = RecipeType::where('name', self::RECIPE_TYPE_NAME)->first();

        if (!$recipeType) {
            throw new RuntimeException('Recipe type "' . self::RECIPE_TYPE_NAME . '" not found — run FactoryRecipeTypeSeeder first.');
        }

        $this->assertItemsFit($recipeType);

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($recipeType, &$created, &$updated) {
            foreach (self::RECIPES as $productCode => [$semiFinishedCode, $perBatch, $quantity]) {
                [$itemId, $unitId, $itemTypeId, $name] = self::PRODUCTS[$productCode];
                [$inputItemId, $inputUnitId] = self::SEMI_FINISHED[$semiFinishedCode];

                $recipe = Recipe::withTrashed()->firstOrNew([
                    'recipe_type_id' => $recipeType->id,
                    'item_id' => $itemId,
                ]);
                $recipe->exists ? $updated++ : $created++;

                $recipe->fill([
                    'name' => $name,
                    'item_type_id' => $itemTypeId,
                    'item_unit_id' => $unitId,
                    'batch' => 1,
                    'batch_weight' => null,
                    'batch_volume' => null,
                    'quantity_per_batch' => $perBatch,
                    'status' => 1,
                    'notes' => null,
                ]);
                $recipe->deleted_at = null;
                $recipe->save();

                $data = [
                    'item_type_id' => self::SEMI_FINISHED_ITEM_TYPE_ID,
                    'item_unit_id' => $inputUnitId,
                    'quantity' => $quantity,
                    'notes' => null,
                ];
                $input = $recipe->inputs()->where('item_id', $inputItemId)->first();
                $input
                    ? $input->update($data)
                    : $input = $recipe->inputs()->create($data + ['item_id' => $inputItemId]);

                $recipe->inputs()->whereKeyNot($input->id)->delete();
            }
        });

        $this->command?->info(self::RECIPE_TYPE_NAME . ": {$created} recipes created, {$updated} updated.");
    }

    /**
     * Refuse to seed anything unless every product and semi-finished good a
     * recipe names is listed above, the products are of types the recipe
     * type makes, and the semi-finished goods of one it uses.
     */
    private function assertItemsFit(RecipeType $recipeType): void
    {
        $makes = array_map('intval', $recipeType->output_item_type_ids ?? []);
        $uses = array_map('intval', $recipeType->item_type_ids ?? []);
        $problems = [];

        if (!in_array(self::SEMI_FINISHED_ITEM_TYPE_ID, $uses, true)) {
            $problems[] = 'the semi-finished goods\' item type (' . self::SEMI_FINISHED_ITEM_TYPE_ID . ') is not one ' . self::RECIPE_TYPE_NAME . ' uses';
        }

        foreach (self::RECIPES as $productCode => [$semiFinishedCode]) {
            if (!isset(self::PRODUCTS[$productCode])) {
                $problems[] = "product {$productCode} has no ids listed";
            } elseif (!in_array(self::PRODUCTS[$productCode][2], $makes, true)) {
                $problems[] = 'item type ' . self::PRODUCTS[$productCode][2] . " ({$productCode}) is not one " . self::RECIPE_TYPE_NAME . ' makes';
            }
            if (!isset(self::SEMI_FINISHED[$semiFinishedCode])) {
                $problems[] = "semi-finished good {$semiFinishedCode} (in {$productCode}) has no ids listed";
            }
        }

        if ($problems) {
            throw new RuntimeException('Cannot seed Massa Tomate production recipes — ' . implode('; ', array_unique($problems)) . '.');
        }
    }
}
