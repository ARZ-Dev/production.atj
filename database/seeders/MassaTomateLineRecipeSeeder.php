<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MassaTomateLineRecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * One recipe per Massa Tomate SKU, run on a can line as one event across
     * its stations: Filling & Seaming → Pasteurizing → Packing. Replaces
     * MassaTomateProductionRecipeSeeder (SFG → cans, in the basic unit), whose
     * figures it reads, since 2026-10-28.
     *
     * A batch is one case: the recipe makes 1 case (the item's case unit) from
     * the sheet's net weight of paste. Every stage puts out the case's cans, so
     * the filler's and packer's cans/min are the line's rates whatever the case
     * size; the last station is counted in cases at End. Cans left over after
     * the tunnel go into stock loose, in the basic unit.
     *
     * Super Chef 403000021 has no case unit (its only unit is KG), so it keeps
     * the old shape — 50 KG a batch — until the item gets a can unit (open
     * item 57).
     *
     * Matched on recipe type + product, so it updates the old recipes in place;
     * stages on name (each the StationTypeSeeder type of that name, which must
     * run first), the input on its item. Refuses before writing if a
     * product has no ids or its case unit doesn't hold the sheet's cans.
     */
    private const RECIPE_TYPE_NAME = 'Production Massa Tomate';

    /** code => [case unit id, units per case] — the parent's item_units rows. */
    private const CASE_UNITS = [
        '403000001' => [2, 50],   // 50X70G
        '403000003' => [6, 24],   // 24X400G
        '403000005' => [10, 50],  // 50X70G
        '403000007' => [14, 12],  // 12X400G
        '403000017' => [34, 6],   // 6X1KG
        '403000018' => [36, 25],  // 25X70G
        '403000002' => [4, 50],   // 50X70G
        '403000004' => [8, 24],   // 24X400G
        '403000006' => [12, 50],  // 50X70G
        '403000008' => [16, 12],  // 12X400G
        '403000019' => [285, 24], // 24X400G
        '403000021' => null,      // KG only
        '403000022' => [287, 24], // 24X400G
        '403000101' => [289, 50], // 50X70G
        '403000102' => [291, 24], // 24X400G
        '403600001' => [48, 12],  // 12X500ML
        '403600010' => [293, 12], // 12X500ML
        '403600012' => [295, 12], // 12X500ML
        '403600003' => [297, 12], // 12X500ML
        '403600011' => [299, 12], // 12X500ML
        '403800002' => [30, 12],  // 12X450ML
        '403900001' => [301, 24], // 24X400G
        '403900002' => [42, 12],  // 12X400G
        '403900003' => [44, 6],   // 6X400G
        '403900004' => [303, 24], // 24X400G
    ];

    public function run(): void
    {
        $recipeType = RecipeType::where('name', self::RECIPE_TYPE_NAME)->first();

        if (!$recipeType) {
            throw new RuntimeException('Recipe type "' . self::RECIPE_TYPE_NAME . '" not found — run FactoryRecipeTypeSeeder first.');
        }

        $this->assertDataFits($recipeType);

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($recipeType, &$created, &$updated) {
            foreach (MassaTomateProductionRecipeSeeder::RECIPES as $code => [$sfgCode, $perCase, $kg]) {
                [$itemId, $basicUnitId, $itemTypeId, $name] = MassaTomateProductionRecipeSeeder::PRODUCTS[$code];
                [$sfgItem, $sfgUnit]                        = MassaTomateProductionRecipeSeeder::SEMI_FINISHED[$sfgCode];
                $caseUnit                                   = self::CASE_UNITS[$code][0] ?? null;

                $recipe = Recipe::withTrashed()->firstOrNew([
                    'recipe_type_id' => $recipeType->id,
                    'item_id'        => $itemId,
                ]);
                $recipe->exists ? $updated++ : $created++;

                $recipe->fill([
                    'name'               => $name,
                    'item_type_id'       => $itemTypeId,
                    'item_unit_id'       => $caseUnit ?? $basicUnitId,
                    'batch'              => 1,
                    'batch_weight'       => null,
                    'batch_volume'       => null,
                    'quantity_per_batch' => $caseUnit ? 1 : $perCase,
                    'balance_tolerance'  => null,
                    'reject_allowance'   => null,
                    'status'             => 1,
                    'notes'              => null,
                ]);
                $recipe->deleted_at = null;
                $recipe->save();

                $stageIds = $this->syncStages($recipe, [
                    'Filling & Seaming' => ['output_quantity' => $perCase, 'output_item_unit_id' => $basicUnitId],
                    'Pasteurizing'      => ['output_quantity' => $perCase, 'output_item_unit_id' => $basicUnitId, 'stock_leftovers' => (bool) $caseUnit],
                    'Packing'           => $caseUnit
                        ? ['output_quantity' => 1, 'output_item_unit_id' => $caseUnit]
                        : ['output_quantity' => $perCase, 'output_item_unit_id' => $basicUnitId],
                ]);

                $data = [
                    'item_type_id'    => MassaTomateProductionRecipeSeeder::SEMI_FINISHED_ITEM_TYPE_ID,
                    'item_unit_id'    => $sfgUnit,
                    'quantity'        => $kg,
                    'recipe_stage_id' => $stageIds['Filling & Seaming'],
                    'notes'           => null,
                ];
                $input = $recipe->inputs()->where('item_id', $sfgItem)->first();
                $input
                    ? $input->update($data)
                    : $input = $recipe->inputs()->create($data + ['item_id' => $sfgItem]);

                $recipe->inputs()->whereKeyNot($input->id)->delete();
            }
        });

        $this->command?->info(self::RECIPE_TYPE_NAME . ": {$created} line recipes created, {$updated} updated.");
    }

    /** @return array<string, int> stage name => id */
    private function syncStages(Recipe $recipe, array $stages): array
    {
        $ids      = [];
        $sequence = 0;

        foreach ($stages as $name => $stage) {
            $model = $recipe->stages()->withTrashed()->firstOrNew(['name' => $name]);
            $model->fill([
                'sequence'            => ++$sequence,
                'station_type_id'     => StationTypeSeeder::idOf($name),
                'output_quantity'     => $stage['output_quantity'],
                'output_item_unit_id' => $stage['output_item_unit_id'],
                'stock_leftovers'     => $stage['stock_leftovers'] ?? false,
                // Bad seams, dented or leaking cans: thrown away.
                'reject_action'       => 'waste',
                'rework_item_type_id' => null,
                'rework_item_id'      => null,
                'rework_item_unit_id' => null,
                'rework_factor'       => 1,
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

        if (!in_array(MassaTomateProductionRecipeSeeder::SEMI_FINISHED_ITEM_TYPE_ID, $uses, true)) {
            $problems[] = 'SFG Massa Tomate is not one ' . self::RECIPE_TYPE_NAME . ' uses';
        }

        foreach (MassaTomateProductionRecipeSeeder::RECIPES as $code => [$sfgCode, $perCase]) {
            if (!isset(MassaTomateProductionRecipeSeeder::PRODUCTS[$code])) {
                $problems[] = "product {$code} has no ids listed";
            } elseif (!in_array(MassaTomateProductionRecipeSeeder::PRODUCTS[$code][2], $makes, true)) {
                $problems[] = "{$code}'s item type is not one " . self::RECIPE_TYPE_NAME . ' makes';
            }

            if (!array_key_exists($code, self::CASE_UNITS)) {
                $problems[] = "product {$code} has no case unit listed";
            } elseif (self::CASE_UNITS[$code] !== null && self::CASE_UNITS[$code][1] !== $perCase) {
                $problems[] = "{$code}'s case unit holds " . self::CASE_UNITS[$code][1] . " cans, the sheet says {$perCase}";
            }

            if (!isset(MassaTomateProductionRecipeSeeder::SEMI_FINISHED[$sfgCode])) {
                $problems[] = "semi-finished good {$sfgCode} (in {$code}) has no ids listed";
            }
        }

        if ($problems) {
            throw new RuntimeException('Cannot seed Massa Tomate line recipes — ' . implode('; ', array_unique($problems)) . '.');
        }
    }
}
