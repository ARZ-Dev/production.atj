<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MassaTomateRecipeSeeder extends Seeder
{
    /**
     * Seeded by FactoryRecipeTypeSeeder: uses RM Massa Tomate, makes PFG Massa
     * Tomate (whose items auth-service's MassaTomatePreFinishedGoodItemSeeder
     * creates).
     */
    private const RECIPE_TYPE_NAME = 'Production Massa Tomate';

    /**
     * Item ids below are the parent's (erp_auth), as a fresh auth-service
     * seed assigns them — this app can't read the parent's database on the
     * server, so they're written out. Keep them in step with auth-service's
     * item seeders.
     */
    private const PRODUCT_ITEM_TYPE_ID = 19; // PFG Massa Tomate

    /**
     * The products, from auth-service's MassaTomatePreFinishedGoodItemSeeder:
     * code => [item id, basic unit id (KG), name]. The name is the recipe's.
     */
    private const PRODUCTS = [
        'PFGMT-01' => [131, 160, 'Mamatia'],
        'PFGMT-02' => [132, 161, 'Dourado'],
        'PFGMT-03' => [133, 162, 'Yokabel'],
        'PFGMT-04' => [134, 163, 'Super Chef'],
        'PFGMT-05' => [135, 164, 'Adal'],
        'PFGMT-06' => [136, 165, 'Cereal'],
        'PFGMT-07' => [137, 166, 'Apple'],
        'PFGMT-08' => [138, 167, 'Kup Dourado'],
        'PFGMT-09' => [139, 168, 'Sauce of Peeled Tomato'],
    ];

    private const RAW_MATERIAL_ITEM_TYPE_ID = 11; // RM Massa Tomate

    /**
     * The raw materials the BOM uses, from auth-service's
     * MassaTomateRawMaterialItemSeeder: code => [item id, basic unit id
     * (KG; water LT), name]. The names are here for reading only.
     */
    private const RAW_MATERIALS = [
        '401000001' => [37, 65, 'MASSA TOMATE CONCETRADO 36-38° BRIX 1X240KG'],
        '401000002' => [38, 66, 'FIBRA DE SOJA'],
        '401000003' => [39, 67, 'AMIDO MODIFICADO'],
        '401000004' => [40, 68, 'MONASCUS COR VERMELHO'],
        '401000005' => [41, 69, 'COLOROCAO CARAMELO'],
        '401000006' => [42, 70, 'SAL DE POTASSIO DE ACIDO SORBICO'],
        '401000007' => [43, 71, 'Calcium chloride'],
        '401000008' => [44, 72, 'SODIUM BENZOATE'],
        '401000009' => [45, 73, 'LUCTAROMA KETCHUP 2011B'],
        '401000020' => [46, 74, 'WATER ( FABRICA MASSA TOMATE ) / LT'],
        '401000023' => [47, 75, 'Xanthan'],
        '401000030' => [48, 76, 'ALLURA RED E 129 / KG'],
        '401000031' => [49, 77, 'RED APPLE FLAVOR / KG'],
        '401000032' => [50, 78, 'GRAPE FLAVOR / KG'],
        '401000035' => [51, 79, 'Tomato Flavor'],
        '401600001' => [52, 80, 'ACETIC ACID 99 %'],
        '990100003' => [54, 82, 'FUBA DE MILHO AMARELA'],
        '990101002' => [56, 84, 'Y- SAL INDUSTRIAL FABRICA'],
        '990101003' => [57, 85, 'Y- ACUCAR CRISTAL INDUSTRIAL'],
        '990101010' => [58, 86, 'Y- ACIDO CITRICO'],
    ];

    /**
     * "BOM of raw materials for tomato paste factory": one recipe per product
     * column, keyed by the product's item code, listing what one batch uses as
     * raw material code => quantity in that item's basic unit (KG; the parent
     * keeps water in LT, which the sheet counts 1:1 as KG). A batch makes the
     * sum of its inputs — the sheet's "total" row.
     */
    private const RECIPES = [
        'PFGMT-01' => [ // Mamatia
            '401000001' => 476,
            '401000004' => 2.4,
            '401000006' => 1.5,
            '401000008' => 1.5,
            '401000020' => 2000,
            '401000035' => 2.7,
            '990100003' => 346,
            '990101002' => 64,
            '990101003' => 250,
            '990101010' => 5.2,
        ],
        'PFGMT-02' => [ // Dourado
            '401000001' => 476,
            '401000002' => 143,
            '401000003' => 67,
            '401000004' => 2.5,
            '401000006' => 1.7,
            '401000008' => 1.7,
            '401000020' => 2100,
            '401000035' => 7,
            '990101002' => 64,
            '990101003' => 336,
            '990101010' => 5,
        ],
        'PFGMT-03' => [ // Yokabel
            '401000001' => 476,
            '401000004' => 2.4,
            '401000006' => 1.5,
            '401000008' => 1.5,
            '401000020' => 2000,
            '401000035' => 2.7,
            '990100003' => 346,
            '990101002' => 64,
            '990101003' => 250,
            '990101010' => 5.2,
        ],
        'PFGMT-04' => [ // Super Chef
            '401000001' => 476,
            '401000002' => 117,
            '401000003' => 50,
            '401000004' => 2,
            '401000006' => 1.3,
            '401000008' => 1.3,
            '401000020' => 1455,
            '401000035' => 5.5,
            '990101002' => 50,
            '990101003' => 226,
            '990101010' => 3.9,
        ],
        'PFGMT-05' => [ // Adal
            '401000001' => 476,
            '401000002' => 162,
            '401000003' => 76,
            '401000004' => 2.7,
            '401000006' => 1.7,
            '401000008' => 1.7,
            '401000020' => 2060,
            '401000035' => 3.2,
            '990101002' => 71,
            '990101003' => 405,
            '990101010' => 5.7,
        ],
        'PFGMT-06' => [ // Cereal
            '401000005' => 0.03,
            '401000020' => 440,
            '401000032' => 0.07,
            '401600001' => 13.5,
            '990101003' => 2.5,
        ],
        'PFGMT-07' => [ // Apple
            '401000005' => 0.09,
            '401000020' => 440,
            '401000031' => 0.07,
            '401600001' => 13.5,
            '990101003' => 2.5,
        ],
        'PFGMT-08' => [ // Kup Dourado
            '401000001' => 56,
            '401000003' => 4.7,
            '401000006' => 0.4,
            '401000008' => 0.19,
            '401000009' => 1.9,
            '401000020' => 244,
            '401000023' => 1.77,
            '401000030' => 0.012,
            '401600001' => 1.9,
            '990101002' => 7.5,
            '990101003' => 75,
            '990101010' => 0.56,
        ],
        'PFGMT-09' => [ // Sauce of Peeled Tomato
            '401000001' => 48.1,
            '401000002' => 2,
            '401000004' => 0.104,
            '401000006' => 0.802,
            '401000007' => 0.642,
            '401000008' => 0.802,
            '401000020' => 341,
            '401000023' => 0.642,
            '401000035' => 0.481,
            '990101002' => 0.4,
            '990101003' => 4.4,
            '990101010' => 0.611,
        ],
    ];

    /**
     * Carried over from the cell comments on the sheet.
     */
    private const NOTES = [
        'PFGMT-09' => 'Sauce is around 55% of the finished product\'s weight and fresh tomato the other 45%, '
            . 'with around 30% waste. Fresh tomato is not in this recipe: the BOM gives no quantity for it.',
    ];

    /**
     * Run the database seeds.
     *
     * Every code a recipe names must have its ids above, the products must be
     * an item type the recipe type makes and the inputs one it uses —
     * otherwise the recipe form couldn't show them. Safe to re-run:
     * each recipe is matched on its type + product and each input on its item;
     * values are updated in place and inputs no longer in the BOM are removed.
     */
    public function run(): void
    {
        $recipeType = RecipeType::where('name', self::RECIPE_TYPE_NAME)->first();

        if (!$recipeType) {
            throw new RuntimeException('Recipe type "' . self::RECIPE_TYPE_NAME . '" not found — run FactoryRecipeTypeSeeder first.');
        }

        $this->assertItemsFit($recipeType);
        $items = $this->items();

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($items, $recipeType, &$created, &$updated) {
            foreach (self::RECIPES as $productCode => $inputs) {
                $product = $items[$productCode];
                $total = round(array_sum($inputs), 4);

                $recipe = Recipe::withTrashed()->firstOrNew([
                    'recipe_type_id' => $recipeType->id,
                    'item_id' => $product['id'],
                ]);
                $recipe->exists ? $updated++ : $created++;

                $recipe->fill([
                    'name' => $product['name'],
                    'item_type_id' => $product['item_type_id'],
                    'item_unit_id' => $product['unit_id'],
                    'batch' => 1,
                    'batch_weight' => $total,
                    'batch_volume' => null,
                    'quantity_per_batch' => $total,
                    'status' => 1,
                    'notes' => self::NOTES[$productCode] ?? null,
                ]);
                $recipe->deleted_at = null;
                $recipe->save();

                $existing = $recipe->inputs()->get()->keyBy('item_id');
                $keptIds = [];

                foreach ($inputs as $rawCode => $quantity) {
                    $raw = $items[$rawCode];
                    $data = [
                        'item_type_id' => $raw['item_type_id'],
                        'item_unit_id' => $raw['unit_id'],
                        'quantity' => $quantity,
                        'notes' => null,
                    ];

                    $input = $existing->get($raw['id']);
                    $input
                        ? $input->update($data)
                        : $input = $recipe->inputs()->create($data + ['item_id' => $raw['id']]);

                    $keptIds[] = $input->id;
                }

                $recipe->inputs()->whereNotIn('id', $keptIds)->delete();
            }
        });

        $this->command?->info(self::RECIPE_TYPE_NAME . ": {$created} recipes created, {$updated} updated.");
    }

    /**
     * The products and raw materials as code => ['id', 'name', 'item_type_id', 'unit_id'].
     */
    private function items(): array
    {
        $items = [];

        foreach ([[self::PRODUCTS, self::PRODUCT_ITEM_TYPE_ID], [self::RAW_MATERIALS, self::RAW_MATERIAL_ITEM_TYPE_ID]] as [$list, $itemTypeId]) {
            foreach ($list as $code => [$id, $unitId, $name]) {
                $items[$code] = ['id' => $id, 'name' => $name, 'item_type_id' => $itemTypeId, 'unit_id' => $unitId];
            }
        }

        return $items;
    }

    /**
     * Refuse to seed anything unless every product and raw material a recipe
     * names is listed above, the products are of a type the recipe type
     * makes, and the raw materials of a type it uses.
     */
    private function assertItemsFit(RecipeType $recipeType): void
    {
        $makes = array_map('intval', $recipeType->output_item_type_ids ?? []);
        $uses = array_map('intval', $recipeType->item_type_ids ?? []);
        $problems = [];

        if (!in_array(self::PRODUCT_ITEM_TYPE_ID, $makes, true)) {
            $problems[] = 'the products\' item type (' . self::PRODUCT_ITEM_TYPE_ID . ') is not one ' . self::RECIPE_TYPE_NAME . ' makes';
        }
        if (!in_array(self::RAW_MATERIAL_ITEM_TYPE_ID, $uses, true)) {
            $problems[] = 'the raw materials\' item type (' . self::RAW_MATERIAL_ITEM_TYPE_ID . ') is not one ' . self::RECIPE_TYPE_NAME . ' uses';
        }

        foreach (self::RECIPES as $productCode => $inputs) {
            if (!isset(self::PRODUCTS[$productCode])) {
                $problems[] = "product {$productCode} has no ids listed";
            }
            foreach (array_keys($inputs) as $rawCode) {
                if (!isset(self::RAW_MATERIALS[$rawCode])) {
                    $problems[] = "raw material {$rawCode} (in {$productCode}) has no ids listed";
                }
            }
        }

        if ($problems) {
            throw new RuntimeException('Cannot seed Massa Tomate recipes — ' . implode('; ', $problems) . '.');
        }
    }
}
