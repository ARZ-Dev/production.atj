<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BolachaPackagingRecipeSeeder extends Seeder
{
    /**
     * Seeded by FactoryRecipeTypeSeeder: uses the Fb. Bolacha finished goods
     * (packets) and packaging materials, makes the same types packed — the
     * box.
     */
    private const RECIPE_TYPE_NAME = 'Packaging Bolacha';

    /**
     * The tape every box is closed with — 990102005 Y- FITA COLA, a Massa
     * Tomate packaging material both factories share: [item id, basic unit
     * id (KG), item type id (Packaging Materials Massa Tomate)]. Ids are the
     * parent's (erp_auth), as a fresh auth-service seed assigns them; keep
     * them in step with auth-service's item seeders.
     */
    private const TAPE = [118, 146, 13];

    /**
     * "SKU's produced for different brands" (SKU FAB BOLACHA.xlsx), its
     * "Packaging Items to be consumed": code => [units per box, tape per box
     * (kg)]. A batch is one box, made from its packets and its tape. The
     * products' ids are BolachaProductionRecipeSeeder::PRODUCTS.
     *
     * The sheet also gives each packet's BOPP film and each box's weight
     * (in the comments), but not which film roll or which box — the parent
     * has a few printed rolls and five boxes, none matching most products —
     * so neither is an input yet. "Ribbon + Ink" is only marked "Used".
     */
    private const RECIPES = [
        '066103017' => [24, 0.001],   // film 0.001 kg/packet, box 0.2 kg
        '066103006' => [24, 0.001],   // film 0.0009 kg/packet, box 0.15 kg
        '066103012' => [24, 0.001],   // film 0.001 kg/packet, box 0.2 kg
        '066103009' => [24, 0.001],   // film 0.001 kg/packet, box 0.2 kg
        '066103018' => [24, 0.001],   // film 0.001 kg/packet, box 0.2 kg
        '066103010' => [24, 0.001],   // film 0.001 kg/packet, box 0.2 kg
        '066101011' => [48, 0.001],   // film 0.0012 kg/packet, box 0.255 kg
        '066101012' => [24, 0.001],   // film 0.0012 kg/packet, box 0.2 kg
        '066101020' => [24, 0.001],   // film 0.0012 kg/packet, box 0.2 kg
        '066101019' => [24, 0.001],   // film 0.0012 kg/packet, box 0.2 kg
        '066101022' => [24, 0.001],   // film 0.0012 kg/packet, box 0.2 kg
        '066101021' => [48, 0.0017],  // film 0.0012 kg/packet, box 0.295 kg
        '066101029' => [48, 0.0011],  // film 0.0012 kg/packet, box 0.175 kg
        '066101030' => [36, 0.0011],  // film 0.0012 kg/packet, box 0.175 kg
        '066101031' => [36, 0.0011],  // film 0.0012 kg/packet, box 0.175 kg
        '066101032' => [36, 0.0011],  // film 0.0012 kg/packet, box 0.175 kg
        '066101033' => [20, 0.001],   // film 0.0015 kg/packet, box 0.2 kg
        '066101017' => [20, 0.001],   // film 0.0015 kg/packet, box 0.2 kg
        '066101026' => [20, 0.001],   // film 0.0015 kg/packet, box 0.2 kg
        '066105016' => [48, 0.0011],  // film 0.0015 kg/packet, box 0.19 kg
        '066105017' => [48, 0.0011],  // film 0.0015 kg/packet, box 0.19 kg
        '066105025' => [48, 0.0011],  // film 0.0015 kg/packet, box 0.19 kg
        '066105027' => [48, 0.0011],  // film 0.0015 kg/packet, box 0.19 kg
        '066105026' => [48, 0.0011],  // film 0.0015 kg/packet, box 0.19 kg
        '066105000' => [48, 0.0009],  // film 0.001 kg/packet, box 0.15 kg
        '066105002' => [48, 0.0009],  // film 0.001 kg/packet, box 0.15 kg
        '066105001' => [48, 0.0009],  // film 0.001 kg/packet, box 0.15 kg
        '066105004' => [48, 0.0009],  // film 0.001 kg/packet, box 0.15 kg
        '066105003' => [48, 0.0009],  // film 0.001 kg/packet, box 0.15 kg
        '066105005' => [48, 0.0009],  // film 0.001 kg/packet, box 0.15 kg
        '066101028' => [48, 0.0011],  // film 0.0012 kg/packet, box 0.175 kg
        '066101015' => [20, 0.001],   // film 0.0015 kg/packet, box 0.2 kg
        '066101024' => [20, 0.001],   // film 0.0015 kg/packet, box 0.2 kg
        '066101025' => [20, 0.001],   // film 0.0015 kg/packet, box 0.2 kg
        '066103011' => [24, 0.001],   // film 0.001 kg/packet, box 0.2 kg
        '066106001' => [36, 0.0017],  // film 0.001 kg/packet, box 0.225 kg
        '066105018' => [48, 0.0011],  // film 0.0015 kg/packet, box 0.19 kg
        '066105019' => [48, 0.0011],  // film 0.0015 kg/packet, box 0.19 kg
        '066105020' => [48, 0.0011],  // film 0.0015 kg/packet, box 0.19 kg
        '066103013' => [24, 0.001],   // film 0.0012 kg/packet, box 0.2 kg
        '066101034' => [100, 0.0018], // film 0.001 kg/packet, box 0.215 kg
        '066101035' => [100, 0.0018], // film 0.001 kg/packet, box 0.215 kg
        '066101036' => [100, 0.0018], // film 0.001 kg/packet, box 0.215 kg
        '066103019' => [100, 0.0018], // film 0.0009 kg/packet, box 0.285 kg
        'FGBL-01' => [36, 0.0017],    // film 0.001 kg/packet, box 0.225 kg
        'FGBL-02' => [36, 0.0017],    // film 0.001 kg/packet, box 0.225 kg
    ];

    /**
     * Run the database seeds.
     *
     * Matched on recipe type + product, deleted rows included; the inputs on
     * their item, those no longer listed deleted. Re-running changes
     * nothing, and other recipes are left alone.
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
            foreach (self::RECIPES as $productCode => [$perBox, $tapeKg]) {
                [$itemId, $unitId, $boxUnitId, $itemTypeId, $name] = BolachaProductionRecipeSeeder::PRODUCTS[$productCode];
                [$tapeItemId, $tapeUnitId, $tapeItemTypeId] = self::TAPE;

                $recipe = Recipe::withTrashed()->firstOrNew([
                    'recipe_type_id' => $recipeType->id,
                    'item_id' => $itemId,
                ]);
                $recipe->exists ? $updated++ : $created++;

                $recipe->fill([
                    'name' => 'PACKING ' . $name,
                    'item_type_id' => $itemTypeId,
                    'item_unit_id' => $boxUnitId,
                    'batch' => 1,
                    'batch_weight' => null,
                    'batch_volume' => null,
                    'quantity_per_batch' => 1,
                    'status' => 1,
                    'notes' => null,
                ]);
                $recipe->deleted_at = null;
                $recipe->save();

                $keep = [];
                foreach ([[$itemId, $itemTypeId, $unitId, $perBox], [$tapeItemId, $tapeItemTypeId, $tapeUnitId, $tapeKg]] as [$inputItemId, $inputItemTypeId, $inputUnitId, $quantity]) {
                    $data = [
                        'item_type_id' => $inputItemTypeId,
                        'item_unit_id' => $inputUnitId,
                        'quantity' => $quantity,
                        'notes' => null,
                    ];
                    $input = $recipe->inputs()->where('item_id', $inputItemId)->first();
                    $input
                        ? $input->update($data)
                        : $input = $recipe->inputs()->create($data + ['item_id' => $inputItemId]);
                    $keep[] = $input->id;
                }

                $recipe->inputs()->whereKeyNot($keep)->delete();
            }
        });

        $this->command?->info(self::RECIPE_TYPE_NAME . ": {$created} recipes created, {$updated} updated.");
    }

    /**
     * Refuse to seed anything unless every product has ids listed and is of
     * a type the recipe type both uses and makes, and the tape's type is one
     * it uses.
     */
    private function assertItemsFit(RecipeType $recipeType): void
    {
        $makes = array_map('intval', $recipeType->output_item_type_ids ?? []);
        $uses = array_map('intval', $recipeType->item_type_ids ?? []);
        $problems = [];

        if (!in_array(self::TAPE[2], $uses, true)) {
            $problems[] = 'the tape\'s item type (' . self::TAPE[2] . ') is not one ' . self::RECIPE_TYPE_NAME . ' uses';
        }

        foreach (array_keys(self::RECIPES) as $productCode) {
            $itemTypeId = BolachaProductionRecipeSeeder::PRODUCTS[$productCode][3] ?? null;

            if ($itemTypeId === null) {
                $problems[] = "product {$productCode} has no ids listed";
            } elseif (!in_array($itemTypeId, $makes, true) || !in_array($itemTypeId, $uses, true)) {
                $problems[] = "item type {$itemTypeId} ({$productCode}) is not one " . self::RECIPE_TYPE_NAME . ' both uses and makes';
            }
        }

        if ($problems) {
            throw new RuntimeException('Cannot seed Bolacha packaging recipes — ' . implode('; ', array_unique($problems)) . '.');
        }
    }
}
