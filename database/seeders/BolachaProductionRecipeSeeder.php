<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BolachaProductionRecipeSeeder extends Seeder
{
    /**
     * Seeded by FactoryRecipeTypeSeeder: uses SFG Bolacha, makes the
     * Fb. Bolacha finished goods (Hard, Soft, Wafer) in their basic unit —
     * the packet.
     */
    private const RECIPE_TYPE_NAME = 'Production Bolacha';

    /**
     * Item ids below are the parent's (erp_auth), as a fresh auth-service
     * seed assigns them — this app can't read the parent's database on the
     * server, so they're written out. Keep them in step with auth-service's
     * item seeders.
     */
    private const SEMI_FINISHED_ITEM_TYPE_ID = 16; // SFG Bolacha

    /**
     * What the products are made from — BolachaRecipeSeeder's products, from
     * auth-service's BolachaSemiFinishedGoodItemSeeder: code => [item id,
     * basic unit id (KG)].
     */
    private const SEMI_FINISHED = [
        'SFGBL-01' => [213, 241], // Maria (KG)
        'SFGBL-02' => [214, 242], // Dona Jowna (KG)
        'SFGBL-03' => [215, 243], // Maria Samakaka Azul (KG)
        'SFGBL-04' => [216, 244], // Fix Fix (KG)
        'SFGBL-05' => [217, 245], // Fix Fix Frango (KG)
        'SFGBL-06' => [218, 246], // Fix Fix Picante (KG)
        'SFGBL-07' => [219, 247], // Fix Fix Bife Picante (KG)
        'SFGBL-08' => [220, 248], // Fix Fix Barbeque (KG)
        'SFGBL-09' => [221, 249], // Fix Fix Azeitonas (KG)
        'SFGBL-10' => [222, 250], // Football 100 (KG)
        'SFGBL-11' => [223, 251], // Tondo Leite (KG)
        'SFGBL-12' => [224, 252], // Tondo New Massa (KG)
        'SFGBL-13' => [225, 253], // Crème Tondo Vanilha (KG)
        'SFGBL-14' => [226, 254], // Crème Tondo Morango (KG)
        'SFGBL-15' => [227, 255], // Crème Tondo Chocolate (KG)
        'SFGBL-16' => [228, 256], // Tondo Chocolate (KG)
        'SFGBL-17' => [229, 257], // Football 100 Ch. (KG)
        'SFGBL-18' => [230, 258], // Football Coconut (KG)
        'SFGBL-19' => [231, 259], // Wafer Baunilha Creme (KG)
        'SFGBL-20' => [232, 260], // Wafer Morango Creme (KG)
        'SFGBL-21' => [233, 261], // Wafer Chocolate Creme (KG)
        'SFGBL-22' => [234, 262], // Wafer Chocolate Duplo (KG)
        'SFGBL-23' => [235, 263], // Wafer Coco Crème (KG)
        'SFGBL-24' => [236, 264], // Massa de Wafer (KG)
        'SFGBL-25' => [237, 265], // Massa de Wafer Escura (KG)
        'SFGBL-26' => [238, 266], // Massa Waffi (KG)
        'SFGBL-27' => [239, 267], // Waffi Crème Baunilha (KG)
        'SFGBL-28' => [240, 268], // Waffi Crème Morango (KG)
        'SFGBL-29' => [241, 269], // Waffi Crème Chocolate (KG)
        'SFGBL-30' => [242, 270], // Waffi Crème Limao (KG)
        'SFGBL-31' => [243, 271], // Waffi Crème Ginguba (KG)
        'SFGBL-32' => [244, 272], // Waffi Crème Tutti Fruit (KG)
        'SFGBL-33' => [246, 274], // Massa Happy Face (KG)
        'SFGBL-34' => [247, 275], // Crème Happy Face Tutti Fruit (KG)
        'SFGBL-35' => [248, 276], // Crème Happy Face Morango (KG)
        'SFGBL-37' => [250, 278], // Crème Happy Face Chocolate (KG)
        'SFGBL-39' => [252, 280], // Naya Cracker (KG)
        'SFGBL-40' => [253, 281], // Naya Flora Leite (KG)
        'SFGBL-41' => [254, 282], // Naya Petit Beurre Chocolate (KG)
        'SFGBL-42' => [255, 283], // Fix Fix Digestivo (KG)
    ];

    /**
     * The share of a two-part product's net weight that is its shell, the
     * rest being the cream. The sheet gives only the total, so these are
     * typical figures, not the factory's (2026-10-27): a cream wafer is
     * about 70 % cream, a sandwich biscuit about 25 %.
     */
    private const SHELL_SHARE = [
        'wafer' => 0.30,    // Super Wafer, Waffi: wafer sheet + cream
        'sandwich' => 0.75, // Tondo Sandwich, Fix Fix Feliz: biscuit + cream
    ];

    /**
     * The products, from auth-service's BolachaSkuItemSeeder: code => [item
     * id, basic unit id, box unit id, item type id, name]. The recipe is
     * named after the item. BolachaPackagingRecipeSeeder reads this list
     * too.
     */
    public const PRODUCTS = [
        '066103017' => [265, 304, 305, 1, 'BOLACHA AGUA E SAL FIX FIX 24X50 GR'],              // 50GR · 24X50GR
        '066103006' => [266, 306, 307, 1, 'BOLACHA AGUA E SAL FIX FIX 24X40 GR'],              // 40GR · 24X40GR
        '066103012' => [267, 308, 309, 1, 'BOLACHA AGUA E SAL FIX FIX BEEF PICANTE 24X50 GR'], // 50GR · 24X50GR
        '066103009' => [268, 310, 311, 1, 'BOLACHA AGUA E SAL FIX FIX GALINHA 24X50 GR'],      // 50GR · 24X50GR
        '066103018' => [269, 312, 313, 1, 'BOLACHA AGUA E SAL PETROLINHO 24X50 GR'],           // 50GR · 24X50GR
        '066103010' => [270, 314, 315, 1, 'BOLACHA AGUA E SAL FIX FIX PICANTE 24X50 GR'],      // 50GR · 24X50GR
        '066101011' => [271, 316, 317, 1, 'SUPER MARIA TRADICIONAL 48X40GR'],                  // 40GR · 48X40GR
        '066101012' => [272, 318, 319, 1, 'SUPER MARIA TRADICIONAL 24X40GR'],                  // 40GR · 24X40GR
        '066101020' => [273, 320, 321, 1, 'SUPER MARIA SAMAKAKA BRANCO 24X40 GR'],             // 40GR · 24X40GR
        '066101019' => [274, 322, 323, 1, 'SUPER MARIA SAMAKAKA AZUL 24X40 GR'],               // 40GR · 24X40GR
        '066101022' => [275, 324, 325, 1, 'BOLACHA MARIA DONA JOANA 24X40 GR'],                // 40GR · 24X40GR
        '066101021' => [276, 326, 327, 1, 'BOLACHA MARIA DOURADO 48X55 GR'],                   // 55GR · 48X55GR
        '066101029' => [277, 328, 329, 2, 'TONDO LEITE 48X45 GR'],                             // 45GR · 48X45GR
        '066101030' => [278, 330, 331, 2, 'TONDO SANDWICH LEITE / BAUNILHA 36X50 GR'],         // 50GR · 36X50GR
        '066101031' => [279, 332, 333, 2, 'TONDO SANDWICH MORANGO 36X50 GR'],                  // 50GR · 36X50GR
        '066101032' => [280, 334, 335, 2, 'TONDO SANDWICH CHOCOLATE 36X50 GR'],                // 50GR · 36X50GR
        '066101033' => [281, 336, 337, 2, 'GIRABOLA CARNAVAL DO BRAZIL 20X90 GR'],             // 90GR · 20X90GR
        '066101017' => [282, 338, 339, 2, 'SUPER FOOTBALL 20X90 GR'],                          // 90GR · 20X90GR
        '066101026' => [283, 340, 341, 2, 'SUPER FOOTBALL PETROLINHO 100% VEGETAL 20X90 GR'],  // 90GR · 20X90GR
        '066105016' => [284, 342, 343, 3, 'SUPER WAFER MORANGO 25 GR'],                        // 25GR · 48X25GR
        '066105017' => [285, 344, 345, 3, 'SUPER WAFER CHOCOLATE 25 GR'],                      // 25GR · 48X25GR
        '066105025' => [286, 346, 347, 3, 'SUPER WAFER PETROLINHO CHOCOLATE 48X25 GR'],        // 25GR · 48X25GR
        '066105027' => [287, 348, 349, 3, 'SUPER WAFER PETROLINHO MORANGO 48X25 GR'],          // 25GR · 48X25GR
        '066105026' => [288, 350, 351, 3, 'SUPER WAFER PETROLINHO BAUNILHA 48X25 GR'],         // 25GR · 48X25GR
        '066105000' => [289, 352, 353, 3, 'FIX FIX - WAFFI GINGUBA 48X15 GR'],                 // 15GR · 48X15GR
        '066105002' => [290, 354, 355, 3, 'FIX FIX - WAFFI TUTTI FRUTTI 48X15 GR'],            // 15GR · 48X15GR
        '066105001' => [291, 356, 357, 3, 'FIX FIX - WAFFI LIMAO 48X15 GR'],                   // 15GR · 48X15GR
        '066105004' => [292, 358, 359, 3, 'FIX FIX - WAFFI MORANGO 48X15 GR'],                 // 15GR · 48X15GR
        '066105003' => [293, 360, 361, 3, 'FIX FIX - WAFFI BAUNILHA 48X15 GR'],                // 15GR · 48X15GR
        '066105005' => [294, 362, 363, 3, 'FIX FIX - WAFFI CHOCOLATE 48X15 GR'],               // 15GR · 48X15GR
        '066101028' => [295, 364, 365, 2, 'TONDO CHOCOLATE 48X45 GR'],                         // 45GR · 48X45GR
        '066101015' => [296, 366, 367, 2, 'GIRABOLA 20X90 GR'],                                // 90GR · 20X90GR
        '066101024' => [297, 368, 369, 2, 'SUPER FOOTBALL COCO 20X90GR'],                      // 90GR · 20X90GR
        '066101025' => [298, 370, 371, 2, 'SUPER FOOTBALL CHOCOLATE 20X90 GR'],                // 90GR · 20X90GR
        '066103011' => [299, 372, 373, 1, 'BOLACHA AGUA E SAL FIX FIX BARBECUE 24X50 GR'],     // 50GR · 24X50GR
        '066106001' => [300, 374, 375, 1, 'BOLACHA CRACKERS NAYA 30 GR'],                      // 30GR · 36X30GR
        '066105018' => [301, 376, 377, 3, 'SUPER WAFER BAUNILHA 25 GR'],                       // 25GR · 48X25GR
        '066105019' => [302, 378, 379, 3, 'SUPER WAFER CHOCOLATE DUPLO 25 GR'],                // 25GR · 48X25GR
        '066105020' => [303, 380, 381, 3, 'SUPER WAFER COCO 25 GR'],                           // 25GR · 48X25GR
        '066103013' => [304, 382, 383, 1, 'BOLACHA AGUA E SAL FIX FIX AZEITONA 24X50 GR'],     // 50GR · 24X50GR
        '066101034' => [305, 384, 385, 2, 'FIX FIX FELIZ CHOCOLATE SANDWICH 100X30 GR'],       // 30GR · 100X30GR
        '066101035' => [306, 386, 387, 2, 'FIX FIX FELIZ MORANGO SANDWICH 100X30 GR'],         // 30GR · 100X30GR
        '066101036' => [307, 388, 389, 2, 'FIX FIX FELIZ TUTTI FRUTTI SANDWICH 100X30 GR'],    // 30GR · 100X30GR
        '066103019' => [308, 390, 391, 1, 'BOLACHA FIX FIX DIGESTIVO 100X25 GR'],              // 25GR · 100X25GR
        'FGBL-01' => [309, 392, 393, 1, 'NAYA MINI FLORA LEITE 36X50 GR'],                     // 50GR · 36X50GR
        'FGBL-02' => [310, 394, 395, 1, 'NAYA MINI PETIT BEURRE CHOCOLATE 36X50 GR'],          // 50GR · 36X50GR
    ];

    /**
     * "SKU's produced for different brands" (SKU FAB BOLACHA.xlsx), one
     * recipe per product: code => [net weight per packet (kg), SFG, and for a
     * two-part product its cream and family]. A batch is one packet, made
     * from its net weight of dough (the sheet's net weight per box ÷ units
     * per box) — split by SHELL_SHARE between shell and cream for a two-part
     * product. Packing the packets into boxes is BolachaPackagingRecipeSeeder's.
     *
     * The sheet's "Recipe" column names a flavour, not an SFG, so the SFG is
     * matched by name. Super Maria Tradicional, Dourado and Samakaka Branca
     * use the Maria dough; Girabola and the plain Footballs Football 100;
     * Fix Fix Normal and Petrolinho Fix Fix; the Super Wafers Massa de Wafer
     * — Chocolate Duplo the dark one — and the Tondo sandwiches Tondo New
     * Massa. Crème Happy Face Ginguba and Limao make no product here.
     */
    private const RECIPES = [
        '066103017' => [0.05, 'SFGBL-04'],                         // Fix Fix · 50 g a packet, 24 a box
        '066103006' => [0.04, 'SFGBL-04'],                         // Fix Fix · 40 g a packet, 24 a box
        '066103012' => [0.05, 'SFGBL-07'],                         // Fix Fix Bife Picante · 50 g a packet, 24 a box
        '066103009' => [0.05, 'SFGBL-05'],                         // Fix Fix Frango · 50 g a packet, 24 a box
        '066103018' => [0.05, 'SFGBL-04'],                         // Fix Fix · 50 g a packet, 24 a box
        '066103010' => [0.05, 'SFGBL-06'],                         // Fix Fix Picante · 50 g a packet, 24 a box
        '066101011' => [0.04, 'SFGBL-01'],                         // Maria · 40 g a packet, 48 a box
        '066101012' => [0.04, 'SFGBL-01'],                         // Maria · 40 g a packet, 24 a box
        '066101020' => [0.04, 'SFGBL-01'],                         // Maria · 40 g a packet, 24 a box
        '066101019' => [0.04, 'SFGBL-03'],                         // Maria Samakaka Azul · 40 g a packet, 24 a box
        '066101022' => [0.04, 'SFGBL-02'],                         // Dona Jowna · 40 g a packet, 24 a box
        '066101021' => [0.055, 'SFGBL-01'],                        // Maria · 55 g a packet, 48 a box
        '066101029' => [0.045, 'SFGBL-11'],                        // Tondo Leite · 45 g a packet, 48 a box
        '066101030' => [0.05, 'SFGBL-12', 'SFGBL-13', 'sandwich'], // Tondo New Massa + Crème Tondo Vanilha · 50 g a packet, 36 a box
        '066101031' => [0.05, 'SFGBL-12', 'SFGBL-14', 'sandwich'], // Tondo New Massa + Crème Tondo Morango · 50 g a packet, 36 a box
        '066101032' => [0.05, 'SFGBL-12', 'SFGBL-15', 'sandwich'], // Tondo New Massa + Crème Tondo Chocolate · 50 g a packet, 36 a box
        '066101033' => [0.09, 'SFGBL-10'],                         // Football 100 · 90 g a packet, 20 a box
        '066101017' => [0.09, 'SFGBL-10'],                         // Football 100 · 90 g a packet, 20 a box
        '066101026' => [0.09, 'SFGBL-10'],                         // Football 100 · 90 g a packet, 20 a box
        '066105016' => [0.025, 'SFGBL-24', 'SFGBL-20', 'wafer'],   // Massa de Wafer + Wafer Morango Creme · 25 g a packet, 48 a box
        '066105017' => [0.025, 'SFGBL-24', 'SFGBL-21', 'wafer'],   // Massa de Wafer + Wafer Chocolate Creme · 25 g a packet, 48 a box
        '066105025' => [0.025, 'SFGBL-24', 'SFGBL-21', 'wafer'],   // Massa de Wafer + Wafer Chocolate Creme · 25 g a packet, 48 a box
        '066105027' => [0.025, 'SFGBL-24', 'SFGBL-20', 'wafer'],   // Massa de Wafer + Wafer Morango Creme · 25 g a packet, 48 a box
        '066105026' => [0.025, 'SFGBL-24', 'SFGBL-19', 'wafer'],   // Massa de Wafer + Wafer Baunilha Creme · 25 g a packet, 48 a box
        '066105000' => [0.015, 'SFGBL-26', 'SFGBL-31', 'wafer'],   // Massa Waffi + Waffi Crème Ginguba · 15 g a packet, 48 a box
        '066105002' => [0.015, 'SFGBL-26', 'SFGBL-32', 'wafer'],   // Massa Waffi + Waffi Crème Tutti Fruit · 15 g a packet, 48 a box
        '066105001' => [0.015, 'SFGBL-26', 'SFGBL-30', 'wafer'],   // Massa Waffi + Waffi Crème Limao · 15 g a packet, 48 a box
        '066105004' => [0.015, 'SFGBL-26', 'SFGBL-28', 'wafer'],   // Massa Waffi + Waffi Crème Morango · 15 g a packet, 48 a box
        '066105003' => [0.015, 'SFGBL-26', 'SFGBL-27', 'wafer'],   // Massa Waffi + Waffi Crème Baunilha · 15 g a packet, 48 a box
        '066105005' => [0.015, 'SFGBL-26', 'SFGBL-29', 'wafer'],   // Massa Waffi + Waffi Crème Chocolate · 15 g a packet, 48 a box
        '066101028' => [0.045, 'SFGBL-16'],                        // Tondo Chocolate · 45 g a packet, 48 a box
        '066101015' => [0.09, 'SFGBL-10'],                         // Football 100 · 90 g a packet, 20 a box
        '066101024' => [0.09, 'SFGBL-18'],                         // Football Coconut · 90 g a packet, 20 a box
        '066101025' => [0.09, 'SFGBL-17'],                         // Football 100 Ch. · 90 g a packet, 20 a box
        '066103011' => [0.05, 'SFGBL-08'],                         // Fix Fix Barbeque · 50 g a packet, 24 a box
        '066106001' => [0.03, 'SFGBL-39'],                         // Naya Cracker · 30 g a packet, 36 a box
        '066105018' => [0.025, 'SFGBL-24', 'SFGBL-19', 'wafer'],   // Massa de Wafer + Wafer Baunilha Creme · 25 g a packet, 48 a box
        '066105019' => [0.025, 'SFGBL-25', 'SFGBL-22', 'wafer'],   // Massa de Wafer Escura + Wafer Chocolate Duplo · 25 g a packet, 48 a box
        '066105020' => [0.025, 'SFGBL-24', 'SFGBL-23', 'wafer'],   // Massa de Wafer + Wafer Coco Crème · 25 g a packet, 48 a box
        '066103013' => [0.05, 'SFGBL-09'],                         // Fix Fix Azeitonas · 50 g a packet, 24 a box
        '066101034' => [0.03, 'SFGBL-33', 'SFGBL-37', 'sandwich'], // Massa Happy Face + Crème Happy Face Chocolate · 30 g a packet, 100 a box
        '066101035' => [0.03, 'SFGBL-33', 'SFGBL-35', 'sandwich'], // Massa Happy Face + Crème Happy Face Morango · 30 g a packet, 100 a box
        '066101036' => [0.03, 'SFGBL-33', 'SFGBL-34', 'sandwich'], // Massa Happy Face + Crème Happy Face Tutti Fruit · 30 g a packet, 100 a box
        '066103019' => [0.025, 'SFGBL-42'],                        // Fix Fix Digestivo · 25 g a packet, 100 a box
        'FGBL-01' => [0.05, 'SFGBL-40'],                           // Naya Flora Leite · 50 g a packet, 36 a box
        'FGBL-02' => [0.05, 'SFGBL-41'],                           // Naya Petit Beurre Chocolate · 50 g a packet, 36 a box
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
            foreach (self::RECIPES as $productCode => $row) {
                [$itemId, $unitId, , $itemTypeId, $name] = self::PRODUCTS[$productCode];

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
                    'quantity_per_batch' => 1,
                    'status' => 1,
                    'notes' => null,
                ]);
                $recipe->deleted_at = null;
                $recipe->save();

                $keep = [];
                foreach (self::inputs($row) as $semiFinishedCode => $quantity) {
                    [$inputItemId, $inputUnitId] = self::SEMI_FINISHED[$semiFinishedCode];
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
                    $keep[] = $input->id;
                }

                $recipe->inputs()->whereKeyNot($keep)->delete();
            }
        });

        $this->command?->info(self::RECIPE_TYPE_NAME . ": {$created} recipes created, {$updated} updated.");
    }

    /**
     * A recipe's SFG inputs: code => kg per batch.
     */
    private static function inputs(array $row): array
    {
        [$kg, $shell, $cream, $family] = $row + [2 => null, 3 => null];

        if ($cream === null) {
            return [$shell => $kg];
        }

        $shellKg = round($kg * self::SHELL_SHARE[$family], 4);

        return [$shell => $shellKg, $cream => round($kg - $shellKg, 4)];
    }

    /**
     * Refuse to seed anything unless every product and semi-finished good a
     * recipe names is listed above, every two-part product's family has a
     * share, the products are of types the recipe type makes, and the
     * semi-finished goods of one it uses.
     */
    private function assertItemsFit(RecipeType $recipeType): void
    {
        $makes = array_map('intval', $recipeType->output_item_type_ids ?? []);
        $uses = array_map('intval', $recipeType->item_type_ids ?? []);
        $problems = [];

        if (!in_array(self::SEMI_FINISHED_ITEM_TYPE_ID, $uses, true)) {
            $problems[] = 'the semi-finished goods\' item type (' . self::SEMI_FINISHED_ITEM_TYPE_ID . ') is not one ' . self::RECIPE_TYPE_NAME . ' uses';
        }

        foreach (self::RECIPES as $productCode => $row) {
            [, $shell, $cream, $family] = $row + [2 => null, 3 => null];

            if (!isset(self::PRODUCTS[$productCode])) {
                $problems[] = "product {$productCode} has no ids listed";
            } elseif (!in_array(self::PRODUCTS[$productCode][3], $makes, true)) {
                $problems[] = 'item type ' . self::PRODUCTS[$productCode][3] . " ({$productCode}) is not one " . self::RECIPE_TYPE_NAME . ' makes';
            }
            foreach (array_filter([$shell, $cream]) as $semiFinishedCode) {
                if (!isset(self::SEMI_FINISHED[$semiFinishedCode])) {
                    $problems[] = "semi-finished good {$semiFinishedCode} (in {$productCode}) has no ids listed";
                }
            }
            if ($cream !== null && !isset(self::SHELL_SHARE[$family])) {
                $problems[] = "{$productCode} has no shell share for \"{$family}\"";
            }
        }

        if ($problems) {
            throw new RuntimeException('Cannot seed Bolacha production recipes — ' . implode('; ', array_unique($problems)) . '.');
        }
    }
}
