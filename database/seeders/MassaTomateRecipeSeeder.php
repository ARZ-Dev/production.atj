<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeType;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MassaTomateRecipeSeeder extends Seeder
{
    private const RECIPE_TYPE_NAME = 'Production Massa Tomate';

    /**
     * What every recipe makes. Created in the parent by auth-service's
     * MassaTomatePreFinishedGoodItemSeeder, which has to run first.
     */
    private const OUTPUT_ITEM_TYPE_NAME = 'Pre Finished Goods Massa Tomate';

    /**
     * The raw materials the BOM uses — all "Raw Material Massa Tomate" in the
     * parent. Items are always matched on their code; the names are here for
     * reading and for error messages only.
     */
    private const RAW_MATERIALS = [
        '401000001' => 'MASSA TOMATE CONCETRADO 36-38° BRIX 1X240KG',
        '401000002' => 'FIBRA DE SOJA',
        '401000003' => 'AMIDO MODIFICADO',
        '401000004' => 'MONASCUS COR VERMELHO',
        '401000005' => 'COLOROCAO CARAMELO',
        '401000006' => 'SAL DE POTASSIO DE ACIDO SORBICO',
        '401000007' => 'Calcium chloride',
        '401000008' => 'SODIUM BENZOATE',
        '401000009' => 'LUCTAROMA KETCHUP 2011B',
        '401000020' => 'WATER ( FABRICA MASSA TOMATE ) / LT',
        '401000023' => 'Xanthan',
        '401000030' => 'ALLURA RED E 129 / KG',
        '401000031' => 'RED APPLE FLAVOR / KG',
        '401000032' => 'GRAPE FLAVOR / KG',
        '401000035' => 'Tomato Flavor',
        '401600001' => 'ACETIC ACID 99 %',
        '990100003' => 'FUBA DE MILHO AMARELA',
        '990101002' => 'Y- SAL INDUSTRIAL FABRICA',
        '990101003' => 'Y- ACUCAR CRISTAL INDUSTRIAL',
        '990101010' => 'Y- ACIDO CITRICO',
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
     * Item, unit and item type ids are resolved from the parent's database by
     * item code, so nothing here depends on the ids a given environment
     * assigned. Safe to re-run: the recipe type is matched on its name, each
     * recipe on its type + product, and each input on its item — values are
     * updated in place and inputs no longer in the BOM are removed.
     */
    public function run(): void
    {
        $parent = DB::connection('auth_service');

        $outputTypeId = $parent->table('item_types')
            ->whereNull('deleted_at')
            ->where('name', self::OUTPUT_ITEM_TYPE_NAME)
            ->value('id');

        if (!$outputTypeId) {
            throw new RuntimeException('Item type "' . self::OUTPUT_ITEM_TYPE_NAME . '" not found in the parent. '
                . 'Run auth-service\'s MassaTomatePreFinishedGoodItemSeeder first.');
        }

        $items = $this->parentItems($parent, [...array_keys(self::RECIPES), ...array_keys(self::RAW_MATERIALS)]);
        $this->assertItemsResolved($items, (int) $outputTypeId);

        $inputTypeIds = collect(self::RAW_MATERIALS)
            ->keys()
            ->map(fn ($code) => $items[$code]['item_type_id'])
            ->unique()->sort()->values()->all();

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($items, $outputTypeId, $inputTypeIds, &$created, &$updated) {
            $recipeType = RecipeType::withTrashed()->firstOrNew(['name' => self::RECIPE_TYPE_NAME]);
            $recipeType->fill([
                'item_type_ids' => $inputTypeIds,
                'side_item_type_ids' => [],
                'output_item_type_ids' => [(int) $outputTypeId],
            ]);
            $recipeType->deleted_at = null;
            $recipeType->save();

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
                    'item_type_id' => (int) $outputTypeId,
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
     * The parent's active items with these codes, each with its basic unit,
     * keyed by code. Items without a basic unit are left out.
     */
    private function parentItems(ConnectionInterface $parent, array $codes): array
    {
        return $parent->table('items as i')
            ->join('item_units as u', function ($join) {
                $join->on('u.item_id', '=', 'i.id')->where('u.basic', 1)->whereNull('u.deleted_at');
            })
            ->whereNull('i.deleted_at')
            ->whereIn('i.code', array_unique($codes))
            ->orderBy('u.id')
            ->get(['i.id', 'i.code', 'i.name', 'i.item_type_id', 'u.id as unit_id'])
            ->unique('code')
            ->mapWithKeys(fn ($row) => [$row->code => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'item_type_id' => (int) $row->item_type_id,
                'unit_id' => (int) $row->unit_id,
            ]])
            ->all();
    }

    /**
     * Refuse to seed anything unless every product and raw material resolved,
     * and every product is a pre-finished good.
     */
    private function assertItemsResolved(array $items, int $outputTypeId): void
    {
        $problems = [];

        foreach (array_keys(self::RECIPES) as $code) {
            if (!isset($items[$code])) {
                $problems[] = "product {$code} not found (or has no basic unit)";
            } elseif ($items[$code]['item_type_id'] !== $outputTypeId) {
                $problems[] = "product {$code} ({$items[$code]['name']}) is not a " . self::OUTPUT_ITEM_TYPE_NAME . ' item';
            }
        }

        foreach (self::RAW_MATERIALS as $code => $name) {
            if (!isset($items[$code])) {
                $problems[] = "raw material {$code} ({$name}) not found (or has no basic unit)";
            }
        }

        if ($problems) {
            throw new RuntimeException('Cannot seed Massa Tomate recipes — ' . implode('; ', $problems) . '.');
        }
    }
}
