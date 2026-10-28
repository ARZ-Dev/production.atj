<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BolachaLineRecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * One recipe per biscuit SKU, run on a biscuit line as one event across
     * its stations: Cutting → Cooking → (Creaming, for the two-part products)
     * → Packaging → Packing. Replaces BolachaProductionRecipeSeeder (dough →
     * packet) and BolachaPackagingRecipeSeeder (packets + tape → box), whose
     * figures it reads, since 2026-10-28.
     *
     * A batch is one box (the recipe's output, in the box unit), so a run is
     * planned in boxes. Per box:
     *
     *   Cutting    dough at the box's net weight (the shell's share for the
     *              two-part products) → that many kg; trimmings rework into
     *              the dough itself
     *   Cooking    → the same kg baked; rejects to waste, or rework into
     *              Bolacha em po
     *   Creaming   cream at the rest of the box's weight → the box's kg
     *   Packaging  → units-per-box packets (the product's basic unit, so
     *              loose packets left at End go into stock); rejects rework
     *              into Bolacha em po at a packet's weight
     *   Packing    tape at the sheet's kg per box → 1 box
     *
     * Film roll, carton and Ribbon + Ink are still not inputs (open item 60),
     * and the dough per box is the net weight: the sheet gives no bake loss.
     *
     * Fixed ids like the seeders it replaces; matched on recipe type + product,
     * so it updates their recipes in place; stages matched on name (each the
     * StationTypeSeeder type of that name, which must run first), inputs on
     * item. Refuses before writing if a product, its packaging row or an input
     * has no ids.
     */
    private const RECIPE_TYPE_NAME = 'Production Bolacha';

    /** Bolacha em po — broken biscuits are milled back into it. */
    private const POWDER = '988888888';

    public function run(): void
    {
        $recipeType = RecipeType::where('name', self::RECIPE_TYPE_NAME)->first();

        if (!$recipeType) {
            throw new RuntimeException('Recipe type "' . self::RECIPE_TYPE_NAME . '" not found — run FactoryRecipeTypeSeeder first.');
        }

        $this->assertDataFits($recipeType);

        [$powderItem, $powderUnit, $powderType] = BolachaRecipeSeeder::RAW_MATERIALS[self::POWDER];
        [$tapeItem, $tapeUnit, $tapeType]       = BolachaPackagingRecipeSeeder::TAPE;

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($recipeType, $powderItem, $powderUnit, $powderType, $tapeItem, $tapeUnit, $tapeType, &$created, &$updated) {
            foreach (BolachaProductionRecipeSeeder::RECIPES as $code => $row) {
                [$itemId, $basicUnitId, $boxUnitId, $itemTypeId, $name] = BolachaProductionRecipeSeeder::PRODUCTS[$code];
                [$unitsPerBox, $tapeKg]                                 = BolachaPackagingRecipeSeeder::RECIPES[$code];
                [$kgPacket, $shellCode, $creamCode, $family]            = $row + [2 => null, 3 => null];

                $boxKg   = round($kgPacket * $unitsPerBox, 4);
                $shellKg = $creamCode ? round($boxKg * BolachaProductionRecipeSeeder::SHELL_SHARE[$family], 4) : $boxKg;
                [$shellItem, $shellUnit] = BolachaProductionRecipeSeeder::SEMI_FINISHED[$shellCode];

                $recipe = Recipe::withTrashed()->firstOrNew([
                    'recipe_type_id' => $recipeType->id,
                    'item_id'        => $itemId,
                ]);
                $recipe->exists ? $updated++ : $created++;

                $recipe->fill([
                    'name'               => $name,
                    'item_type_id'       => $itemTypeId,
                    'item_unit_id'       => $boxUnitId,
                    'batch'              => 1,
                    'batch_weight'       => null,
                    'batch_volume'       => null,
                    'quantity_per_batch' => 1,
                    'balance_tolerance'  => null,
                    'reject_allowance'   => null,
                    'status'             => 1,
                    'notes'              => null,
                ]);
                $recipe->deleted_at = null;
                $recipe->save();

                $stages = [
                    'Cutting'   => ['output_quantity' => $shellKg, 'output_unit' => 'kg', 'reject_action' => 'rework', 'rework' => [$shellItem, $shellUnit, BolachaProductionRecipeSeeder::SEMI_FINISHED_ITEM_TYPE_ID, 1]],
                    'Cooking'   => ['output_quantity' => $shellKg, 'output_unit' => 'kg', 'reject_action' => 'waste', 'rework' => [$powderItem, $powderUnit, $powderType, 1]],
                    'Creaming'  => $creamCode ? ['output_quantity' => $boxKg, 'output_unit' => 'kg', 'reject_action' => 'waste', 'rework' => [$powderItem, $powderUnit, $powderType, 1]] : null,
                    'Packaging' => ['output_quantity' => $unitsPerBox, 'output_unit' => 'packets', 'output_item_unit_id' => $basicUnitId, 'reject_action' => 'rework', 'rework' => [$powderItem, $powderUnit, $powderType, $kgPacket]],
                    'Packing'   => ['output_quantity' => 1, 'output_unit' => 'box', 'reject_action' => 'waste', 'rework' => null],
                ];

                $stageIds = $this->syncStages($recipe, array_filter($stages));

                $inputs = [
                    [$shellItem, $shellUnit, BolachaProductionRecipeSeeder::SEMI_FINISHED_ITEM_TYPE_ID, $shellKg, 'Cutting'],
                    [$tapeItem, $tapeUnit, $tapeType, $tapeKg, 'Packing'],
                ];

                if ($creamCode) {
                    [$creamItem, $creamUnit] = BolachaProductionRecipeSeeder::SEMI_FINISHED[$creamCode];
                    $inputs[] = [$creamItem, $creamUnit, BolachaProductionRecipeSeeder::SEMI_FINISHED_ITEM_TYPE_ID, round($boxKg - $shellKg, 4), 'Creaming'];
                }

                $keep = [];
                foreach ($inputs as [$inputItem, $inputUnit, $inputType, $quantity, $stageName]) {
                    $data = [
                        'item_type_id'    => $inputType,
                        'item_unit_id'    => $inputUnit,
                        'quantity'        => $quantity,
                        'recipe_stage_id' => $stageIds[$stageName],
                        'notes'           => null,
                    ];
                    $input = $recipe->inputs()->where('item_id', $inputItem)->first();
                    $input
                        ? $input->update($data)
                        : $input = $recipe->inputs()->create($data + ['item_id' => $inputItem]);
                    $keep[] = $input->id;
                }

                $recipe->inputs()->whereKeyNot($keep)->delete();
            }
        });

        $this->command?->info(self::RECIPE_TYPE_NAME . ": {$created} line recipes created, {$updated} updated.");
    }

    /**
     * Create or update the stages by name, in this order, and remove the rest.
     *
     * @return array<string, int> stage name => id
     */
    private function syncStages(Recipe $recipe, array $stages): array
    {
        $ids      = [];
        $sequence = 0;

        foreach ($stages as $name => $stage) {
            [$reworkItem, $reworkUnit, $reworkType, $factor] = $stage['rework'] ?? [null, null, null, 1];

            $model = $recipe->stages()->withTrashed()->firstOrNew(['name' => $name]);
            $model->fill([
                'sequence'            => ++$sequence,
                'station_type_id'     => StationTypeSeeder::idOf($name),
                'output_quantity'     => $stage['output_quantity'],
                'output_unit'         => $stage['output_unit'],
                'output_item_unit_id' => $stage['output_item_unit_id'] ?? null,
                'reject_action'       => $stage['reject_action'],
                'rework_item_type_id' => $reworkType,
                'rework_item_id'      => $reworkItem,
                'rework_item_unit_id' => $reworkUnit,
                'rework_factor'       => $factor,
            ]);
            $model->deleted_at = null;
            $model->save();

            $ids[$name] = $model->id;
        }

        $recipe->stages()->whereNotIn('id', array_values($ids))->delete();

        return $ids;
    }

    private function assertDataFits(RecipeType $recipeType): void
    {
        $makes    = array_map('intval', $recipeType->output_item_type_ids ?? []);
        $uses     = array_map('intval', $recipeType->item_type_ids ?? []);
        $problems = [];

        foreach ([BolachaProductionRecipeSeeder::SEMI_FINISHED_ITEM_TYPE_ID, BolachaPackagingRecipeSeeder::TAPE[2]] as $typeId) {
            if (!in_array($typeId, $uses, true)) {
                $problems[] = "item type {$typeId} is not one " . self::RECIPE_TYPE_NAME . ' uses';
            }
        }

        if (!isset(BolachaRecipeSeeder::RAW_MATERIALS[self::POWDER])) {
            $problems[] = 'Bolacha em po (' . self::POWDER . ') has no ids listed';
        }

        foreach (BolachaProductionRecipeSeeder::RECIPES as $code => $row) {
            [, $shell, $cream, $family] = $row + [2 => null, 3 => null];

            if (!isset(BolachaProductionRecipeSeeder::PRODUCTS[$code])) {
                $problems[] = "product {$code} has no ids listed";
            } elseif (!in_array(BolachaProductionRecipeSeeder::PRODUCTS[$code][3], $makes, true)) {
                $problems[] = 'item type ' . BolachaProductionRecipeSeeder::PRODUCTS[$code][3] . " ({$code}) is not one " . self::RECIPE_TYPE_NAME . ' makes';
            }

            if (!isset(BolachaPackagingRecipeSeeder::RECIPES[$code])) {
                $problems[] = "product {$code} has no units per box / tape listed";
            }

            foreach (array_filter([$shell, $cream]) as $semiFinished) {
                if (!isset(BolachaProductionRecipeSeeder::SEMI_FINISHED[$semiFinished])) {
                    $problems[] = "semi-finished good {$semiFinished} (in {$code}) has no ids listed";
                }
            }

            if ($cream !== null && !isset(BolachaProductionRecipeSeeder::SHELL_SHARE[$family])) {
                $problems[] = "{$code} has no shell share for \"{$family}\"";
            }
        }

        if ($problems) {
            throw new RuntimeException('Cannot seed Bolacha line recipes — ' . implode('; ', array_unique($problems)) . '.');
        }
    }
}
