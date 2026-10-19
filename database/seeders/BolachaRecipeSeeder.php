<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BolachaRecipeSeeder extends Seeder
{
    /**
     * Seeded by FactoryRecipeTypeSeeder: uses RM Bolacha, RM Massa Tomate
     * (the raw materials both factories share) and SFG Bolacha (Xarope),
     * makes SFG Bolacha.
     */
    private const RECIPE_TYPE_NAME = 'Preparation Bolacha';

    /**
     * Item ids below are the parent's (erp_auth), as a fresh auth-service
     * seed assigns them — this app can't read the parent's database on the
     * server, so they're written out. Keep them in step with auth-service's
     * item seeders.
     */
    private const PRODUCT_ITEM_TYPE_ID = 16; // SFG Bolacha

    /**
     * The products — doughs, creams and syrup — from auth-service's
     * BolachaSemiFinishedGoodItemSeeder: code => [item id, basic unit id
     * (KG), name]. The name is the recipe's. Xarope (999999999) is also an
     * input of other recipes.
     */
    private const PRODUCTS = [
        'SFGBL-01' => [213, 241, 'Maria'],
        'SFGBL-02' => [214, 242, 'Dona Jowna'],
        'SFGBL-03' => [215, 243, 'Maria Samakaka Azul'],
        'SFGBL-04' => [216, 244, 'Fix Fix'],
        'SFGBL-05' => [217, 245, 'Fix Fix Frango'],
        'SFGBL-06' => [218, 246, 'Fix Fix Picante'],
        'SFGBL-07' => [219, 247, 'Fix Fix Bife Picante'],
        'SFGBL-08' => [220, 248, 'Fix Fix Barbeque'],
        'SFGBL-09' => [221, 249, 'Fix Fix Azeitonas'],
        'SFGBL-10' => [222, 250, 'Football 100'],
        'SFGBL-11' => [223, 251, 'Tondo Leite'],
        'SFGBL-12' => [224, 252, 'Tondo New Massa'],
        'SFGBL-13' => [225, 253, 'Crème Tondo Vanilha'],
        'SFGBL-14' => [226, 254, 'Crème Tondo Morango'],
        'SFGBL-15' => [227, 255, 'Crème Tondo Chocolate'],
        'SFGBL-16' => [228, 256, 'Tondo Chocolate'],
        'SFGBL-17' => [229, 257, 'Football 100 Ch.'],
        'SFGBL-18' => [230, 258, 'Football Coconut'],
        'SFGBL-19' => [231, 259, 'Wafer Baunilha Creme'],
        'SFGBL-20' => [232, 260, 'Wafer Morango Creme'],
        'SFGBL-21' => [233, 261, 'Wafer Chocolate Creme'],
        'SFGBL-22' => [234, 262, 'Wafer Chocolate Duplo'],
        'SFGBL-23' => [235, 263, 'Wafer Coco Crème'],
        'SFGBL-24' => [236, 264, 'Massa de Wafer'],
        'SFGBL-25' => [237, 265, 'Massa de Wafer Escura'],
        'SFGBL-26' => [238, 266, 'Massa Waffi'],
        'SFGBL-27' => [239, 267, 'Waffi Crème Baunilha'],
        'SFGBL-28' => [240, 268, 'Waffi Crème Morango'],
        'SFGBL-29' => [241, 269, 'Waffi Crème Chocolate'],
        'SFGBL-30' => [242, 270, 'Waffi Crème Limao'],
        'SFGBL-31' => [243, 271, 'Waffi Crème Ginguba'],
        'SFGBL-32' => [244, 272, 'Waffi Crème Tutti Fruit'],
        '999999999' => [245, 273, 'Xarope'],
        'SFGBL-33' => [246, 274, 'Massa Happy Face'],
        'SFGBL-34' => [247, 275, 'Crème Happy Face Tutti Fruit'],
        'SFGBL-35' => [248, 276, 'Crème Happy Face Morango'],
        'SFGBL-36' => [249, 277, 'Crème Happy Face Ginguba'],
        'SFGBL-37' => [250, 278, 'Crème Happy Face Chocolate'],
        'SFGBL-38' => [251, 279, 'Crème Happy Face Limao'],
        'SFGBL-39' => [252, 280, 'Naya Cracker'],
        'SFGBL-40' => [253, 281, 'Naya Flora Leite'],
        'SFGBL-41' => [254, 282, 'Naya Petit Beurre Chocolate'],
        'SFGBL-42' => [255, 283, 'Fix Fix Digestivo'],
    ];

    /**
     * The raw materials the BOM uses: code => [item id, basic unit id (KG),
     * item type id, name]. Most are RM Bolacha (15), from auth-service's
     * BolachaRawMaterialItemSeeder; sugar, salt, citric acid and caramel
     * colouring are the tomato paste factory's RM Massa Tomate (11) items,
     * shared. RMBL-01 (Corante) is a placeholder code. The names are here for
     * reading only.
     */
    private const RAW_MATERIALS = [
        '990101003' => [57, 85, 11, 'Y- ACUCAR CRISTAL INDUSTRIAL'], // RM Massa Tomate
        '990101014' => [131, 159, 15, 'Y- Bicarbonato de Sodium'],
        '990101002' => [56, 84, 11, 'Y- SAL INDUSTRIAL FABRICA'], // RM Massa Tomate
        '990101020' => [132, 160, 15, 'Y -Lecitina de Soja ( SL 180209 )'],
        '990101018' => [133, 161, 15, 'Y- Gordura Vegetal Industrial'],
        '990101016' => [134, 162, 15, 'Y- Metabisulfito de Sodium (SMBS)'],
        '990101012' => [135, 163, 15, 'Y- Bicarbonato de Amonia'],
        '990101010' => [58, 86, 11, 'Y- ACIDO CITRICO'], // RM Massa Tomate
        '990100002' => [136, 164, 15, 'Y- Farinha de Trigo Industrial'],
        '990101017' => [137, 165, 15, 'Amido de Milho'],
        '990100009' => [138, 166, 15, 'Y-Farelo do Trigo'],
        '990101059' => [140, 168, 15, 'Coconut Flavor (TAKASAGO)'],
        '990101093' => [141, 169, 15, 'SKB SPECTA PAN ( ENZIMA)'],
        '990101074' => [142, 170, 15, 'Y- Cacau em Po'],
        '990101055' => [144, 172, 15, 'Chocolate Flavor (TAKASAGO)'],
        '990101040' => [145, 173, 15, 'Y- Semisweet Chocolate Flavor 2990A'],
        '990101028' => [147, 175, 15, 'Y- Milk vanilha flaver 12708A (GLUCOSO)'],
        '990101025' => [149, 177, 15, 'Y- Butter Flavor (45840A)'],
        '990101037' => [150, 178, 15, 'Y-Srawberry Flavor'],
        '990101008' => [152, 180, 15, '2 FLAVOR - MILK MICRON FLAVOR / KG'],
        '990101031' => [153, 181, 15, 'CONDENCE MILK FLAVOR'],
        '990101011' => [156, 184, 15, 'PIROFOSFATO ACIDO DE SODIO'],
        '990101062' => [158, 186, 15, 'Cream Flavor Takasago'],
        '990101082' => [160, 188, 15, 'Strawberry Flavor Takasago'],
        '401000005' => [41, 69, 11, 'COLOROCAO CARAMELO'], // RM Massa Tomate
        '999511585' => [162, 190, 15, 'COCONUT FLAVOR'],
        '990101047' => [164, 192, 15, 'Milk Flavor Powder'],
        '990101067' => [165, 193, 15, 'Lucta Aroma Chicken 03828A'],
        '990101068' => [166, 194, 15, 'Lucat Aroma Chilli Beef 33479'],
        '990101069' => [167, 195, 15, 'Lucat Aroma Chilli 49014'],
        '990101070' => [168, 196, 15, 'Olive Powder 202347'],
        '990101071' => [169, 197, 15, 'Black Olives 25263A'],
        'RMBL-01' => [170, 198, 15, 'Corante'],
        '990101094' => [171, 199, 15, 'Sabor Tutti Fruit'],
        '990101096' => [172, 200, 15, 'Sabor de Limão'],
        '999511774' => [173, 201, 15, 'Rolo Wafi Ginguba'],
        '988888889' => [174, 202, 15, 'Wafer Em po'],
        '988888888' => [175, 203, 15, 'Bolacha em po'],
    ];

    /**
     * "BOM of raw materials for biscuit factory": one recipe per product
     * column, keyed by the product's code, listing what one batch uses as
     * input code => quantity in KG, in the sheet's row order. Blank and zero
     * cells are left out. A batch makes the sum of its inputs — the sheet's
     * "Total" row, except Waffi Crème Limao (see its citric acid).
     */
    private const RECIPES = [
        'SFGBL-01' => [ // Maria
            '990101003' => 30,
            '990101014' => 0.5,
            '990101002' => 0.5,
            '990101020' => 0.6,
            '990101018' => 15,
            '990101016' => 0.15,
            '990101012' => 6,
            '990100002' => 150,
            '990101059' => 0.1,
            '990101093' => 0.015,
            '990101011' => 0.3,
            '990101062' => 0.15,
            '988888888' => 15,
            '999999999' => 8,
        ],
        'SFGBL-02' => [ // Dona Jowna
            '990101003' => 30,
            '990101014' => 0.6,
            '990101002' => 0.5,
            '990101020' => 0.6,
            '990101018' => 15,
            '990101016' => 0.15,
            '990101012' => 6,
            '990100002' => 150,
            '990101059' => 1,
            '990101093' => 0.015,
            '990101011' => 0.3,
            '999511585' => 1,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-03' => [ // Maria Samakaka Azul
            '990101003' => 30,
            '990101014' => 0.5,
            '990101002' => 0.5,
            '990101020' => 0.6,
            '990101018' => 15,
            '990101016' => 0.18,
            '990101012' => 6,
            '990100002' => 150,
            '990101093' => 0.015,
            '990101031' => 0.15,
            '990101011' => 0.3,
            '990101047' => 0.5,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-04' => [ // Fix Fix
            '990101003' => 10,
            '990101014' => 0.5,
            '990101002' => 3.5,
            '990101020' => 0.6,
            '990101018' => 15,
            '990101016' => 0.15,
            '990101012' => 7,
            '990100002' => 150,
            '990101028' => 0.3,
            '990101011' => 0.4,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-05' => [ // Fix Fix Frango
            '990101003' => 10,
            '990101014' => 0.5,
            '990101002' => 3.5,
            '990101020' => 0.6,
            '990101018' => 15,
            '990101016' => 0.15,
            '990101012' => 7,
            '990100002' => 150,
            '990101011' => 0.4,
            '990101067' => 1,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-06' => [ // Fix Fix Picante
            '990101003' => 10,
            '990101014' => 0.5,
            '990101002' => 3.5,
            '990101020' => 0.6,
            '990101018' => 15,
            '990101016' => 0.15,
            '990101012' => 7,
            '990100002' => 150,
            '990101011' => 0.4,
            '990101069' => 1,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-07' => [ // Fix Fix Bife Picante
            '990101003' => 10,
            '990101014' => 0.5,
            '990101002' => 3.5,
            '990101020' => 0.6,
            '990101018' => 15,
            '990101016' => 0.15,
            '990101012' => 7,
            '990100002' => 150,
            '990101011' => 0.4,
            '990101068' => 1,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-08' => [ // Fix Fix Barbeque
            '990101003' => 10,
            '990101014' => 0.5,
            '990101002' => 3.5,
            '990101020' => 0.6,
            '990101018' => 15,
            '990101016' => 0.15,
            '990101012' => 7,
            '990100002' => 150,
            '990101011' => 0.4,
            'RMBL-01' => 1,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-09' => [ // Fix Fix Azeitonas
            '990101003' => 10,
            '990101014' => 0.5,
            '990101002' => 3.5,
            '990101020' => 0.6,
            '990101018' => 15,
            '990101016' => 0.15,
            '990101012' => 7,
            '990100002' => 150,
            '990101011' => 0.4,
            '990101070' => 0.5,
            '990101071' => 0.5,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-10' => [ // Football 100
            '990101003' => 30,
            '990101014' => 0.3,
            '990101002' => 0.5,
            '990101020' => 0.6,
            '990101018' => 20,
            '990101012' => 4,
            '990100002' => 150,
            '990101025' => 0.15,
            '990101011' => 0.25,
            '999511585' => 0.1,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-11' => [ // Tondo Leite
            '990101003' => 20,
            '990101014' => 0.3,
            '990101002' => 0.15,
            '990101020' => 0.4,
            '990101018' => 15,
            '990101012' => 2,
            '990100002' => 100,
            '990101011' => 0.05,
            '990101047' => 0.25,
            '988888888' => 10,
            '999999999' => 6,
        ],
        'SFGBL-12' => [ // Tondo New Massa
            '990101003' => 18,
            '990101014' => 0.3,
            '990101002' => 0.4,
            '990101020' => 0.4,
            '990101018' => 15,
            '990101012' => 2.5,
            '990100002' => 100,
            '990101059' => 0.1,
            '990101047' => 0.25,
            '988888888' => 10,
            '999999999' => 6,
        ],
        'SFGBL-13' => [ // Crème Tondo Vanilha
            '990101003' => 30,
            '990101020' => 0.05,
            '990101018' => 13,
            '990101028' => 0.2,
        ],
        'SFGBL-14' => [ // Crème Tondo Morango
            '990101003' => 30,
            '990101020' => 0.05,
            '990101018' => 13,
            '990101010' => 0.02,
            '990101082' => 0.2,
            'RMBL-01' => 0.01,
        ],
        'SFGBL-15' => [ // Crème Tondo Chocolate
            '990101003' => 30,
            '990101020' => 0.05,
            '990101018' => 13,
            '990101074' => 2,
            '990101055' => 0.2,
        ],
        'SFGBL-16' => [ // Tondo Chocolate
            '990101003' => 20,
            '990101014' => 0.3,
            '990101002' => 0.3,
            '990101020' => 0.25,
            '990101018' => 15,
            '990101012' => 3.5,
            '990100002' => 100,
            '990101074' => 8,
            '990101055' => 0.3,
            '990101011' => 0.1,
            '401000005' => 0.35,
            '990101047' => 0.15,
            '988888888' => 10,
            '999999999' => 5,
        ],
        'SFGBL-17' => [ // Football 100 Ch.
            '990101003' => 30,
            '990101014' => 0.5,
            '990101002' => 0.5,
            '990101020' => 0.6,
            '990101018' => 20,
            '990101012' => 4,
            '990100002' => 150,
            '990101074' => 12,
            '990101055' => 0.45,
            '990101011' => 0.25,
            '401000005' => 0.25,
            '990101047' => 0.35,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-18' => [ // Football Coconut
            '990101003' => 30,
            '990101014' => 0.5,
            '990101002' => 0.5,
            '990101020' => 0.6,
            '990101018' => 20,
            '990101012' => 4,
            '990100002' => 150,
            '990101011' => 0.25,
            '999511585' => 0.6,
            '988888888' => 10,
            '999999999' => 8,
        ],
        'SFGBL-19' => [ // Wafer Baunilha Creme
            '990101003' => 15,
            '990101018' => 15,
            '990101028' => 0.13,
            '988888889' => 10,
        ],
        'SFGBL-20' => [ // Wafer Morango Creme
            '990101003' => 15,
            '990101018' => 15,
            '990101037' => 0.13,
            '988888889' => 10,
        ],
        'SFGBL-21' => [ // Wafer Chocolate Creme
            '990101003' => 15,
            '990101018' => 15,
            '990101074' => 1.2,
            '990101055' => 0.13,
            '988888889' => 10,
        ],
        'SFGBL-22' => [ // Wafer Chocolate Duplo
            '990101003' => 35,
            '990101018' => 38,
            '990101074' => 3,
            '990101055' => 0.25,
            '988888889' => 10,
        ],
        'SFGBL-23' => [ // Wafer Coco Crème
            '990101003' => 15,
            '990101018' => 15,
            '999511585' => 0.13,
            '988888889' => 10,
        ],
        'SFGBL-24' => [ // Massa de Wafer
            '990101014' => 0.2,
            '990101002' => 0.1,
            '990101020' => 0.18,
            '990101018' => 0.27,
            '990100002' => 25,
            '990101093' => 0.005,
        ],
        'SFGBL-25' => [ // Massa de Wafer Escura
            '990101014' => 0.18,
            '990101002' => 0.1,
            '990101020' => 0.26,
            '990101018' => 0.27,
            '990100002' => 25,
            '990101074' => 2,
            '988888889' => 5,
        ],
        'SFGBL-26' => [ // Massa Waffi
            '990101014' => 0.2,
            '990101002' => 0.1,
            '990101020' => 0.18,
            '990100002' => 25,
            '990101093' => 0.005,
        ],
        'SFGBL-27' => [ // Waffi Crème Baunilha
            '990101003' => 15,
            '990101018' => 15,
            '990101028' => 0.13,
            '988888889' => 10,
        ],
        'SFGBL-28' => [ // Waffi Crème Morango
            '990101003' => 15,
            '990101018' => 15,
            '990101010' => 0.02,
            '990101037' => 0.13,
            'RMBL-01' => 0.02,
            '988888889' => 12,
        ],
        'SFGBL-29' => [ // Waffi Crème Chocolate
            '990101003' => 15,
            '990101018' => 15,
            '990101074' => 3,
            '990101055' => 0.13,
            '988888889' => 13,
        ],
        'SFGBL-30' => [ // Waffi Crème Limao
            '990101003' => 15,
            '990101018' => 15,
            '990101010' => 0.025, // the sheet reads "0.025+"
            '990101096' => 0.13,
            '988888889' => 12,
        ],
        'SFGBL-31' => [ // Waffi Crème Ginguba
            '990101003' => 15,
            '990101018' => 15,
            '999511774' => 0.13,
            '988888889' => 10,
        ],
        'SFGBL-32' => [ // Waffi Crème Tutti Fruit
            '990101003' => 15,
            '990101018' => 15,
            '990101094' => 0.13,
            '988888889' => 12,
        ],
        '999999999' => [ // Xarope
            '990101003' => 300,
            '990101010' => 0.5,
        ],
        'SFGBL-33' => [ // Massa Happy Face
            '990101003' => 18,
            '990101014' => 0.3,
            '990101002' => 0.35,
            '990101020' => 0.4,
            '990101018' => 15,
            '990101012' => 2.5,
            '990100002' => 100,
            '990101059' => 0.1,
            '990101011' => 0.05,
            '990101047' => 0.25,
            '999999999' => 5,
        ],
        'SFGBL-34' => [ // Crème Happy Face Tutti Fruit
            '990101003' => 25,
            '990101002' => 0.03,
            '990101020' => 0.025,
            '990101018' => 13,
            '990101017' => 2,
            '990101096' => 0.2,
        ],
        'SFGBL-35' => [ // Crème Happy Face Morango
            '990101003' => 25,
            '990101002' => 0.03,
            '990101020' => 0.025,
            '990101018' => 13,
            '990101017' => 2,
            '990101037' => 0.2,
        ],
        'SFGBL-36' => [ // Crème Happy Face Ginguba
            '990101003' => 25,
            '990101002' => 0.03,
            '990101020' => 0.025,
            '990101018' => 13,
            '990101017' => 2,
            '999511774' => 0.2,
        ],
        'SFGBL-37' => [ // Crème Happy Face Chocolate
            '990101003' => 25,
            '990101002' => 0.03,
            '990101020' => 0.025,
            '990101018' => 13,
            '990101017' => 1,
            '990101074' => 2,
            '990101055' => 0.2,
        ],
        'SFGBL-38' => [ // Crème Happy Face Limao
            '990101003' => 25,
            '990101002' => 0.03,
            '990101020' => 0.025,
            '990101018' => 13,
            '990101017' => 2,
            '999511774' => 0.2,
        ],
        'SFGBL-39' => [ // Naya Cracker
            '990101003' => 6,
            '990101014' => 0.1,
            '990101002' => 2,
            '990101020' => 0.3,
            '990101018' => 10,
            '990101016' => 0.1,
            '990101012' => 1,
            '990100002' => 75,
            '990101025' => 0.2,
            '990101011' => 0.25,
            '988888888' => 3,
            '999999999' => 3.5,
        ],
        'SFGBL-40' => [ // Naya Flora Leite
            '990101003' => 10,
            '990101014' => 0.15,
            '990101002' => 0.3,
            '990101020' => 0.3,
            '990101018' => 8,
            '990101012' => 0.8,
            '990100002' => 50,
            '990101008' => 0.05,
            '990101011' => 0.2,
            '988888888' => 3,
            '999999999' => 3,
        ],
        'SFGBL-41' => [ // Naya Petit Beurre Chocolate
            '990101003' => 10,
            '990101014' => 0.15,
            '990101002' => 0.3,
            '990101020' => 0.2,
            '990101018' => 8,
            '990101012' => 0.8,
            '990100002' => 50,
            '990101074' => 2,
            '990101040' => 0.2,
            '990101011' => 0.2,
            '401000005' => 0.2,
            '988888888' => 3,
            '999999999' => 2,
        ],
        'SFGBL-42' => [ // Fix Fix Digestivo
            '990101003' => 25,
            '990101014' => 1.5,
            '990101002' => 1.5,
            '990101018' => 40,
            '990101016' => 0.04,
            '990101012' => 1.2,
            '990100002' => 150,
            '990100009' => 5,
            '988888888' => 10,
            '999999999' => 8,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * Every code a recipe names must have its ids above, the products must be
     * an item type the recipe type makes and the inputs one it uses —
     * otherwise the recipe form couldn't show them. Safe to re-run: each
     * recipe is matched on its type + product and each input on its item;
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
                    'notes' => null,
                ]);
                $recipe->deleted_at = null;
                $recipe->save();

                $existing = $recipe->inputs()->get()->keyBy('item_id');
                $keptIds = [];

                foreach ($inputs as $inputCode => $quantity) {
                    $input = $items[$inputCode];
                    $data = [
                        'item_type_id' => $input['item_type_id'],
                        'item_unit_id' => $input['unit_id'],
                        'quantity' => $quantity,
                        'notes' => null,
                    ];

                    $row = $existing->get($input['id']);
                    $row
                        ? $row->update($data)
                        : $row = $recipe->inputs()->create($data + ['item_id' => $input['id']]);

                    $keptIds[] = $row->id;
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

        foreach (self::PRODUCTS as $code => [$id, $unitId, $name]) {
            $items[$code] = ['id' => $id, 'name' => $name, 'item_type_id' => self::PRODUCT_ITEM_TYPE_ID, 'unit_id' => $unitId];
        }
        foreach (self::RAW_MATERIALS as $code => [$id, $unitId, $itemTypeId, $name]) {
            $items[$code] = ['id' => $id, 'name' => $name, 'item_type_id' => $itemTypeId, 'unit_id' => $unitId];
        }

        return $items;
    }

    /**
     * Refuse to seed anything unless every product and input a recipe names
     * is listed above, the products are of a type the recipe type makes, and
     * every input of a type it uses.
     */
    private function assertItemsFit(RecipeType $recipeType): void
    {
        $makes = array_map('intval', $recipeType->output_item_type_ids ?? []);
        $uses = array_map('intval', $recipeType->item_type_ids ?? []);
        $items = $this->items();
        $problems = [];

        if (!in_array(self::PRODUCT_ITEM_TYPE_ID, $makes, true)) {
            $problems[] = 'the products\' item type (' . self::PRODUCT_ITEM_TYPE_ID . ') is not one ' . self::RECIPE_TYPE_NAME . ' makes';
        }

        foreach (self::RECIPES as $productCode => $inputs) {
            if (!isset(self::PRODUCTS[$productCode])) {
                $problems[] = "product {$productCode} has no ids listed";
            }
            foreach (array_keys($inputs) as $inputCode) {
                if (!isset($items[$inputCode])) {
                    $problems[] = "input {$inputCode} (in {$productCode}) has no ids listed";
                } elseif (!in_array($items[$inputCode]['item_type_id'], $uses, true)) {
                    $problems[] = "input {$inputCode} (in {$productCode}) is item type {$items[$inputCode]['item_type_id']}, which " . self::RECIPE_TYPE_NAME . ' doesn\'t use';
                }
            }
        }

        if ($problems) {
            throw new RuntimeException('Cannot seed Bolacha recipes — ' . implode('; ', array_unique($problems)) . '.');
        }
    }
}
