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
     * → Packing → Packaging — the wafers without Cutting, their batter going
     * from the tank into the oven, as the factory's wafer drawing shows.
     * Replaces BolachaProductionRecipeSeeder (dough → packet) and
     * BolachaPackagingRecipeSeeder (packets + tape → box), whose figures it
     * reads, since 2026-10-28.
     *
     * A batch is one box (the recipe's output, in the box unit), so a run is
     * planned in boxes. Per box:
     *
     *   Cutting    the box's dough (below) → that many kg, raw; trimmings
     *              rework into the dough itself
     *   Cooking    → the box's net weight baked (the shell's share for the
     *              two-part products; a wafer's batter is its input here);
     *              rejects to waste, or rework into Bolacha em po
     *   Creaming   cream at the rest of the box's weight → the box's kg
     *   Packing    wraps → units-per-box packets (the product's basic unit,
     *              so loose packets left at End go into stock); rejects
     *              rework into Bolacha em po at a packet's weight
     *   Packaging  tape at the sheet's kg per box → 1 box
     *
     * Packing wraps and Packaging boxes, in that order, as the line drawings of
     * 2026-11-04 have it (the other way round until then).
     *
     * The dough is the raw weight of the box's pieces (PIECES) for a one-part
     * biscuit whose pieces weigh more raw than its packet does baked — the
     * oven bakes the difference off. Otherwise it's the baked weight, as it
     * was for every product until 2026-11-04. Each recipe's notes spell out
     * its pieces and its dough. Film roll, carton and Ribbon + Ink are still
     * not inputs (open item 60).
     *
     * Fixed ids like the seeders it replaces; matched on recipe type + product,
     * so it updates their recipes in place; stages matched on name (each the
     * StationTypeSeeder type of that name, which must run first), inputs on
     * item. Refuses before writing if a product, its packaging row, its pieces
     * or an input has no ids.
     */
    private const RECIPE_TYPE_NAME = 'Production Bolacha';

    /** Bolacha em po — broken biscuits are milled back into it. */
    private const POWDER = '988888888';

    /**
     * "SKU FAB BOLACHA (2).xlsx" (2026-11-04): code => [pieces a packet, grams
     * a piece raw — the sheet's "Weight /Pcs Crue"]. Sandwiches are counted
     * whole, as the sheet does ("5pcs sandwich"); Naya Crackers' 0 / 0 is
     * null. The comment is the sheet's description and a packet's pieces raw
     * against its net weight: above it for the hard biscuits, equal or below
     * for the soft ones and the wafers, whose piece weight reads like the
     * finished one.
     */
    public const PIECES = [
        '066103017' => [12, 4.8],    // Agua E Sal Fix Fix Normal · 57.6 g a packet raw, 50 g net
        '066103006' => [10, 4.8],    // Agua E Sal Fix Fix Normal · 48 g a packet raw, 40 g net
        '066103012' => [12, 4.8],    // Agua E Sal Fix Fix Beef Picante · 57.6 g a packet raw, 50 g net
        '066103009' => [12, 4.8],    // Agua E Salfix Fix Galinha · 57.6 g a packet raw, 50 g net
        '066103018' => [12, 4.8],    // Agua E Sal Fix Fix Petrelinho · 57.6 g a packet raw, 50 g net
        '066103010' => [12, 4.8],    // Agua E Sal Fix Fix Picante · 57.6 g a packet raw, 50 g net
        '066101011' => [11, 4],      // Super Maria Tradicional · 44 g a packet raw, 40 g net
        '066101012' => [11, 4],      // Super Maria Tradicional · 44 g a packet raw, 40 g net
        '066101020' => [11, 4],      // Super Maria Samakaka Branca · 44 g a packet raw, 40 g net
        '066101019' => [11, 4],      // Super Maria Samakaka Azul · 44 g a packet raw, 40 g net
        '066101022' => [11, 4],      // Maria Dona Joana · 44 g a packet raw, 40 g net
        '066101021' => [9, 6.5],     // Super Maria Durado · 58.5 g a packet raw, 55 g net
        '066101029' => [11, 4],      // Tondo Leite · 44 g a packet raw, 45 g net
        '066101030' => [5, 4.5],     // Tondo Sandwitch Leite/Baunilha · 5 sandwiches, 4.5 g a piece; 50 g a packet
        '066101031' => [5, 4.5],     // Tondo Sandwitch Morango · 5 sandwiches, 4.5 g a piece; 50 g a packet
        '066101032' => [5, 4.5],     // Tondo Sandwitch Chocolate · 5 sandwiches, 4.5 g a piece; 50 g a packet
        '066101033' => [15, 6],      // Girabola Carnaval Do Brazil · 90 g a packet raw, 90 g net
        '066101017' => [15, 6],      // Super Football · 90 g a packet raw, 90 g net
        '066101026' => [15, 6],      // Super Footbal Petrolinho · 90 g a packet raw, 90 g net
        '066105016' => [2, 12.5],    // Super Wafer Morango · 25 g a packet raw, 25 g net
        '066105017' => [2, 12.5],    // Super Wafer Chocolate · 25 g a packet raw, 25 g net
        '066105025' => [2, 12.5],    // Super Wafer Pet Chocolate · 25 g a packet raw, 25 g net
        '066105027' => [2, 12.5],    // Super Wafer Pet Morango · 25 g a packet raw, 25 g net
        '066105026' => [2, 12.5],    // Super Wafer Pet Baunilha · 25 g a packet raw, 25 g net
        '066105000' => [1, 14],      // Fix Fix Waffi Gingumba · 14 g a packet raw, 15 g net
        '066105002' => [1, 14],      // Fix Fix Waffi Tutti Frutti · 14 g a packet raw, 15 g net
        '066105001' => [1, 14],      // Fix Fix Waffi Limao · 14 g a packet raw, 15 g net
        '066105004' => [1, 14],      // Fix Fix Waffi Morango · 14 g a packet raw, 15 g net
        '066105003' => [1, 14],      // Fix Fix Waffi Baunilha · 14 g a packet raw, 15 g net
        '066105005' => [1, 14],      // Fix Fix Waffi Chocolate · 14 g a packet raw, 15 g net
        '066101028' => [11, 4],      // Tondo Chocolate · 44 g a packet raw, 45 g net
        '066101015' => [15, 6],      // Girabola · 90 g a packet raw, 90 g net
        '066101024' => [15, 6],      // Super Football Coco · 90 g a packet raw, 90 g net
        '066101025' => [15, 6],      // Super Football Chocolate · 90 g a packet raw, 90 g net
        '066103011' => [12, 4.8],    // Agua E Sal Fix Fix Barbecue · 57.6 g a packet raw, 50 g net
        '066106001' => [null, null], // Naya Crackers · the sheet gives 0 / 0
        '066105018' => [2, 12.5],    // Super Wafer Baunilha · 25 g a packet raw, 25 g net
        '066105019' => [2, 12.5],    // Super Wafer Chocolate Duplo · 25 g a packet raw, 25 g net
        '066105020' => [2, 12.5],    // Super Wafer Coco · 25 g a packet raw, 25 g net
        '066103013' => [12, 4.8],    // Agua E Sal Fix Fix Azeitona · 57.6 g a packet raw, 50 g net
        '066101034' => [5, 4.5],     // Fix Fix Feliz Sandwitch Chocolate · 5 sandwiches, 4.5 g a piece; 30 g a packet
        '066101035' => [5, 4.5],     // Fix Fix Felix Sandwitch Morango · 5 sandwiches, 4.5 g a piece; 30 g a packet
        '066101036' => [5, 4.5],     // Fix Fix Felix Sandwitch Tutti Frutti · 5 sandwiches, 4.5 g a piece; 30 g a packet
        '066103019' => [4, 7],       // Fix Fix Digestive · 28 g a packet raw, 25 g net
        'FGBL-01' => [14, 3.8],      // Naya Mini Flora Leite · 53.2 g a packet raw, 50 g net
        'FGBL-02' => [14, 3.8],      // Naya Mini Petit Beurre Chocolate · 53.2 g a packet raw, 50 g net
    ];

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
                [$pieces, $pieceGrams]                                  = self::PIECES[$code];

                $boxKg   = round($kgPacket * $unitsPerBox, 4);
                $shellKg = $creamCode ? round($boxKg * BolachaProductionRecipeSeeder::SHELL_SHARE[$family], 4) : $boxKg;
                $rawKg   = $this->rawDoughPerBox($kgPacket, $unitsPerBox, $pieces, $pieceGrams, $creamCode);
                $doughKg = $rawKg ?? $shellKg;
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
                    'notes'              => $this->notesFor($kgPacket, $unitsPerBox, $pieces, $pieceGrams, $family, $doughKg, $boxKg, $rawKg !== null),
                ]);
                $recipe->deleted_at = null;
                $recipe->save();

                // A wafer's batter goes from its tank straight into the oven.
                $doughStage = $family === 'wafer' ? 'Cooking' : 'Cutting';

                $stages = [
                    'Cutting'   => $doughStage === 'Cutting' ? ['output_quantity' => $doughKg, 'reject_action' => 'rework', 'rework' => [$shellItem, $shellUnit, BolachaProductionRecipeSeeder::SEMI_FINISHED_ITEM_TYPE_ID, 1]] : null,
                    'Cooking'   => ['output_quantity' => $shellKg, 'reject_action' => 'waste', 'rework' => [$powderItem, $powderUnit, $powderType, 1]],
                    'Creaming'  => $creamCode ? ['output_quantity' => $boxKg, 'reject_action' => 'waste', 'rework' => [$powderItem, $powderUnit, $powderType, 1]] : null,
                    'Packing'   => ['output_quantity' => $unitsPerBox, 'output_item_unit_id' => $basicUnitId, 'stock_leftovers' => true, 'reject_action' => 'rework', 'rework' => [$powderItem, $powderUnit, $powderType, $kgPacket]],
                    'Packaging' => ['output_quantity' => 1, 'output_item_unit_id' => $boxUnitId, 'reject_action' => 'waste', 'rework' => null],
                ];

                $stageIds = $this->syncStages($recipe, array_filter($stages));

                $inputs = [
                    [$shellItem, $shellUnit, BolachaProductionRecipeSeeder::SEMI_FINISHED_ITEM_TYPE_ID, $doughKg, $doughStage],
                    [$tapeItem, $tapeUnit, $tapeType, $tapeKg, 'Packaging'],
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
     * A box's dough at its pieces' raw weight, for a one-part biscuit whose
     * pieces weigh more raw than its packet does baked; null otherwise.
     */
    private function rawDoughPerBox(float $kgPacket, int $unitsPerBox, ?int $pieces, ?float $pieceGrams, ?string $creamCode): ?float
    {
        if ($creamCode || !$pieces || !$pieceGrams) {
            return null;
        }

        $rawKgPacket = round($pieces * $pieceGrams / 1000, 6);

        return $rawKgPacket > $kgPacket ? round($rawKgPacket * $unitsPerBox, 4) : null;
    }

    /**
     * The recipe's notes: what a packet holds and what a box takes.
     */
    private function notesFor(float $kgPacket, int $unitsPerBox, ?int $pieces, ?float $pieceGrams, ?string $family, float $doughKg, float $boxKg, bool $raw): string
    {
        $fmt   = fn(float $n) => rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');
        $notes = [];

        if ($pieces && $pieceGrams) {
            $notes[] = match ($family) {
                'sandwich' => "{$pieces} sandwiches a packet, {$fmt($pieceGrams)} g a piece.",
                'wafer'    => $pieces . ($pieces === 1 ? ' piece' : ' pieces') . " a packet at {$fmt($pieceGrams)} g.",
                default    => "{$pieces} pieces a packet at {$fmt($pieceGrams)} g raw — {$fmt($pieces * $pieceGrams)} g, against {$fmt($kgPacket * 1000)} g baked.",
            };
        }

        $notes[] = $family
            ? "A box of {$unitsPerBox} takes {$fmt($doughKg)} kg of " . ($family === 'wafer' ? 'wafer' : 'biscuit') . " and {$fmt($boxKg - $doughKg)} kg of cream."
            : "A box of {$unitsPerBox} takes {$fmt($doughKg)} kg of dough — " . ($raw ? 'its pieces\' raw weight.' : 'its baked weight.');

        return implode(' ', $notes);
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
                'output_item_unit_id' => $stage['output_item_unit_id'] ?? null,   // null: kg
                'stock_leftovers'     => $stage['stock_leftovers'] ?? false,
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

            if (!array_key_exists($code, self::PIECES)) {
                $problems[] = "product {$code} has no pieces listed";
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
