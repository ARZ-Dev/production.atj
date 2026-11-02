<?php

namespace Database\Seeders;

use App\Models\ReportItem;
use App\Models\StockIn;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Testing stock: one approved Stock In per production warehouse, with 1,000 of
 * every active item the warehouse holds, in each of the item's units.
 *
 * The stock itself is put in the parent by auth-service's
 * ProductionTestStockSeeder (a seeder here can't call the inventory API), which
 * gives the same warehouses, items and units 1,000 on hand — so seed it first,
 * both databases fresh. These documents are what the warehouse activity shows
 * that stock coming from, which is why they're approved on creation and apply
 * nothing themselves.
 *
 * Parent ids are fixed, from a fresh auth-service seed, like the other
 * seeders: WAREHOUSES is auth-service's WarehouseSeeder (the warehouses of a
 * production department, with the item types each holds), ITEMS every active
 * item of those types with its units. Regenerate both when those seeders
 * change.
 *
 * Each document carries a fixed submission key, so a re-run finds it. Lines
 * already on it are left as they are and only missing ones are added — what
 * ProductionTestStockSeeder does with inventory rows — so after a warehouse
 * takes on another item type, re-running both keeps them in step. For testing
 * — comment its call out of DatabaseSeeder before seeding a real environment.
 */
class TestStockInSeeder extends Seeder
{
    public const QUANTITY = 1000;

    /** warehouse id => the item type ids it holds */
    public const WAREHOUSES = [
        1 => [11, 12, 13], // Massa Tomate Factory Warehouse
        2 => [4, 5, 6, 7, 11, 13], // P.S.A MASSA TOMATE
        3 => [11], // P.S.A GARRAFA VAZIA
        4 => [1, 11, 16, 17], // Bolacha Factory
        5 => [11, 13, 18], // RM BOLACHA
        6 => [1, 2, 3, 13, 18], // FG BOLACHA
        7 => [16], // SFG Bolacha
    ];

    /** item id => [item type id, [unit ids]] */
    public const ITEMS = [
        // Fb. Bolacha - Hard (1)
        265 => [1, [304, 305]], // 066103017 BOLACHA AGUA E SAL FIX FIX 24X50 GR
        266 => [1, [306, 307]], // 066103006 BOLACHA AGUA E SAL FIX FIX 24X40 GR
        267 => [1, [308, 309]], // 066103012 BOLACHA AGUA E SAL FIX FIX BEEF PICANTE 24X50 GR
        268 => [1, [310, 311]], // 066103009 BOLACHA AGUA E SAL FIX FIX GALINHA 24X50 GR
        269 => [1, [312, 313]], // 066103018 BOLACHA AGUA E SAL PETROLINHO 24X50 GR
        270 => [1, [314, 315]], // 066103010 BOLACHA AGUA E SAL FIX FIX PICANTE 24X50 GR
        271 => [1, [316, 317]], // 066101011 SUPER MARIA TRADICIONAL 48X40GR
        272 => [1, [318, 319]], // 066101012 SUPER MARIA TRADICIONAL 24X40GR
        273 => [1, [320, 321]], // 066101020 SUPER MARIA SAMAKAKA BRANCO 24X40 GR
        274 => [1, [322, 323]], // 066101019 SUPER MARIA SAMAKAKA AZUL 24X40 GR
        275 => [1, [324, 325]], // 066101022 BOLACHA MARIA DONA JOANA 24X40 GR
        276 => [1, [326, 327]], // 066101021 BOLACHA MARIA DOURADO 48X55 GR
        299 => [1, [372, 373]], // 066103011 BOLACHA AGUA E SAL FIX FIX BARBECUE 24X50 GR
        300 => [1, [374, 375]], // 066106001 BOLACHA CRACKERS NAYA 30 GR
        304 => [1, [382, 383]], // 066103013 BOLACHA AGUA E SAL FIX FIX AZEITONA 24X50 GR
        308 => [1, [390, 391]], // 066103019 BOLACHA FIX FIX DIGESTIVO 100X25 GR
        309 => [1, [392, 393]], // FGBL-01 NAYA MINI FLORA LEITE 36X50 GR
        310 => [1, [394, 395]], // FGBL-02 NAYA MINI PETIT BEURRE CHOCOLATE 36X50 GR

        // Fb. Bolacha - Soft (2)
        277 => [2, [328, 329]], // 066101029 TONDO LEITE 48X45 GR
        278 => [2, [330, 331]], // 066101030 TONDO SANDWICH LEITE / BAUNILHA 36X50 GR
        279 => [2, [332, 333]], // 066101031 TONDO SANDWICH MORANGO 36X50 GR
        280 => [2, [334, 335]], // 066101032 TONDO SANDWICH CHOCOLATE 36X50 GR
        281 => [2, [336, 337]], // 066101033 GIRABOLA CARNAVAL DO BRAZIL 20X90 GR
        282 => [2, [338, 339]], // 066101017 SUPER FOOTBALL 20X90 GR
        283 => [2, [340, 341]], // 066101026 SUPER FOOTBALL PETROLINHO 100% VEGETAL 20X90 GR
        295 => [2, [364, 365]], // 066101028 TONDO CHOCOLATE 48X45 GR
        296 => [2, [366, 367]], // 066101015 GIRABOLA 20X90 GR
        297 => [2, [368, 369]], // 066101024 SUPER FOOTBALL COCO 20X90GR
        298 => [2, [370, 371]], // 066101025 SUPER FOOTBALL CHOCOLATE 20X90 GR
        305 => [2, [384, 385]], // 066101034 FIX FIX FELIZ CHOCOLATE SANDWICH 100X30 GR
        306 => [2, [386, 387]], // 066101035 FIX FIX FELIZ MORANGO SANDWICH 100X30 GR
        307 => [2, [388, 389]], // 066101036 FIX FIX FELIZ TUTTI FRUTTI SANDWICH 100X30 GR

        // Fb. Bolacha - Wafer (3)
        284 => [3, [342, 343]], // 066105016 SUPER WAFER MORANGO 25 GR
        285 => [3, [344, 345]], // 066105017 SUPER WAFER CHOCOLATE 25 GR
        286 => [3, [346, 347]], // 066105025 SUPER WAFER PETROLINHO CHOCOLATE 48X25 GR
        287 => [3, [348, 349]], // 066105027 SUPER WAFER PETROLINHO MORANGO 48X25 GR
        288 => [3, [350, 351]], // 066105026 SUPER WAFER PETROLINHO BAUNILHA 48X25 GR
        289 => [3, [352, 353]], // 066105000 FIX FIX - WAFFI GINGUBA 48X15 GR
        290 => [3, [354, 355]], // 066105002 FIX FIX - WAFFI TUTTI FRUTTI 48X15 GR
        291 => [3, [356, 357]], // 066105001 FIX FIX - WAFFI LIMAO 48X15 GR
        292 => [3, [358, 359]], // 066105004 FIX FIX - WAFFI MORANGO 48X15 GR
        293 => [3, [360, 361]], // 066105003 FIX FIX - WAFFI BAUNILHA 48X15 GR
        294 => [3, [362, 363]], // 066105005 FIX FIX - WAFFI CHOCOLATE 48X15 GR
        301 => [3, [376, 377]], // 066105018 SUPER WAFER BAUNILHA 25 GR
        302 => [3, [378, 379]], // 066105019 SUPER WAFER CHOCOLATE DUPLO 25 GR
        303 => [3, [380, 381]], // 066105020 SUPER WAFER COCO 25 GR

        // Fb. Tomate - Massa Tomate (4)
        1 => [4, [1, 2]], // 403000001 MASSA TOMATE MAMA TIA LATA 70 GR
        2 => [4, [3, 4]], // 403000002 MASSA TOMATE DOURADO LATA 70 GR
        3 => [4, [5, 6]], // 403000003 MASSA TOMATE MAMA TIA LATA ABRI FACIL 400 GR
        4 => [4, [7, 8]], // 403000004 MASSA TOMATE DOURADO LATA ABRI FACIL 400 GR
        5 => [4, [9, 10]], // 403000005 MASSA TOMATE MAMA TIA PACOTE 70 GR
        6 => [4, [11, 12]], // 403000006 MASSA TOMATE DOURADO PACOTE 70 GR
        7 => [4, [13, 14]], // 403000007 MASSA TOMATE MAMA TIA PACOTE 400 GR
        8 => [4, [15, 16]], // 403000008 MASSA TOMATE DOURADO PACOTE 400 GR
        9 => [4, [17, 18]], // 403000009 MASSA TOMATE DIANA LATA 70 GR
        10 => [4, [19, 20]], // 403000012 MASSA TOMATE DOURADO LATA 70 GR (EASY OPEN)
        11 => [4, [21, 22]], // 403000013 MASSA TOMATE TIA ROSA LATA ABRI FACIL 400 GR
        12 => [4, [23, 24]], // 403000014 MASSA TOMATE YAMMI LATA ABRI FACIL 400 GR
        13 => [4, [25, 26]], // 403000015 MASSA TOMATE YAMMI LATA 210 GR
        14 => [4, [27, 28]], // 403000016 MASSA TOMATE MAMA TIA LATA ABRI FACIL 330 GR
        17 => [4, [33, 34]], // 403000017 MASSA TOMATE MAMA TIA BALDE 1 KG
        18 => [4, [35, 36]], // 403000018 MASSA TOMATE MAMA TIA LATA 70 GR
        121 => [4, [149]], // 403000021 MASSA TOMATE SUPER CHEF LATA 50x70 GR
        256 => [4, [284, 285]], // 403000019 MASSA TOMATE YOKABEL LATA ABRI FACIL 24X400 GR
        257 => [4, [286, 287]], // 403000022 MASSA TOMATE SUPER CHEF LATA ABRI FACIL 24X400 GR
        258 => [4, [288, 289]], // 403000101 MASSA TOMATE ADAL 50X70 GR
        259 => [4, [290, 291]], // 403000102 MASSA TOMATE ADAL LATA ABRI FACIL 24X400 GR

        // Fb. Tomate - Pelado (5)
        21 => [5, [41, 42]], // 403900002 TOMATE PELADO MAMATIA 400 GR
        22 => [5, [43, 44]], // 403900003 TOMATE PELADO MAMATIA 400 GR
        263 => [5, [300, 301]], // 403900001 Tomato Peelado Mamatia 24X400 GR
        264 => [5, [302, 303]], // 403900004 Tomato Peelado Yokabel 24X400 GR

        // Fb. Tomate - Vinegar (6)
        25 => [6, [47, 48]], // 403600001 VINAGRE DE CEREAL MAMATIA GARRAFA 500ML
        28 => [6, [296, 297]], // 403600003 VINAGRE DE MACA MAMATIA GARRAFA 500ML
        260 => [6, [292, 293]], // 403600010 CEREAL VINEGAR BOM APETITE 500 ML ( 1 X 12 BOTTLES)
        261 => [6, [294, 295]], // 403600012 VEGETAL VINEGAR VELI 500 ML ( 1 X 12 BOTTLES)
        262 => [6, [298, 299]], // 403600011 APPLE VINEGAR BOM APETITE 500 ML ( 1 X 12 BOTTLES)

        // Fb. Tomate - Ketchup (7)
        15 => [7, [29, 30]], // 403800002 KETCHUP DOURADO 450 ML

        // Raw Materials (11)
        33 => [11, [57, 58]], // RM-FLOUR Flour
        34 => [11, [59, 60]], // RM-SUGAR Sugar
        35 => [11, [61, 62]], // RM-Salt Salt
        37 => [11, [65]], // 401000001 MASSA TOMATE CONCETRADO 36-38° BRIX 1X240KG
        38 => [11, [66]], // 401000002 FIBRA DE SOJA
        39 => [11, [67]], // 401000003 AMIDO MODIFICADO
        40 => [11, [68]], // 401000004 MONASCUS COR VERMELHO
        41 => [11, [69]], // 401000005 COLOROCAO CARAMELO
        42 => [11, [70]], // 401000006 SAL DE POTASSIO DE ACIDO SORBICO
        43 => [11, [71]], // 401000007 Calcium chloride
        44 => [11, [72]], // 401000008 SODIUM BENZOATE
        45 => [11, [73]], // 401000009 LUCTAROMA KETCHUP 2011B
        46 => [11, [74]], // 401000020 WATER ( FABRICA MASSA TOMATE ) / LT
        47 => [11, [75]], // 401000023 Xanthan
        48 => [11, [76]], // 401000030 ALLURA RED E 129 / KG
        49 => [11, [77]], // 401000031 RED APPLE FLAVOR / KG
        50 => [11, [78]], // 401000032 GRAPE FLAVOR / KG
        51 => [11, [79]], // 401000035 Tomato Flavor
        52 => [11, [80]], // 401600001 ACETIC ACID 99 %
        53 => [11, [81]], // 401900001 Fresh tomato
        54 => [11, [82]], // 990100003 FUBA DE MILHO AMARELA
        55 => [11, [83]], // 990101001 SAL EM PO/ KG
        56 => [11, [84]], // 990101002 Y- SAL INDUSTRIAL FABRICA
        57 => [11, [85]], // 990101003 Y- ACUCAR CRISTAL INDUSTRIAL
        58 => [11, [86]], // 990101010 Y- ACIDO CITRICO
        131 => [11, [159]], // 990101014 Y- Bicarbonato de Sodium
        132 => [11, [160]], // 990101020 Y -Lecitina de Soja ( SL 180209 )
        133 => [11, [161]], // 990101018 Y- Gordura Vegetal Industrial
        134 => [11, [162]], // 990101016 Y- Metabisulfito de Sodium (SMBS)
        135 => [11, [163]], // 990101012 Y- Bicarbonato de Amonia
        136 => [11, [164]], // 990100002 Y- Farinha de Trigo Industrial
        137 => [11, [165]], // 990101017 Amido de Milho
        138 => [11, [166]], // 990100009 Y-Farelo do Trigo
        139 => [11, [167]], // 990101009 Leite em Pó
        140 => [11, [168]], // 990101059 Coconut Flavor (TAKASAGO)
        141 => [11, [169]], // 990101093 SKB SPECTA PAN ( ENZIMA)
        142 => [11, [170]], // 990101074 Y- Cacau em Po
        143 => [11, [171]], // 990101013 Vanilla Flavor
        144 => [11, [172]], // 990101055 Chocolate Flavor (TAKASAGO)
        145 => [11, [173]], // 990101040 Y- Semisweet Chocolate Flavor 2990A
        146 => [11, [174]], // 990101035 Mixed Essence
        147 => [11, [175]], // 990101028 Y- Milk vanilha flaver 12708A (GLUCOSO)
        148 => [11, [176]], // 990101030 Y- Cream Flavour 25670A
        149 => [11, [177]], // 990101025 Y- Butter Flavor (45840A)
        150 => [11, [178]], // 990101037 Y-Srawberry Flavor
        151 => [11, [179]], // 990101049 malt expact liquid
        152 => [11, [180]], // 990101008 2 FLAVOR - MILK MICRON FLAVOR / KG
        153 => [11, [181]], // 990101031 CONDENCE MILK FLAVOR
        154 => [11, [182]], // 990101032 PURE FLAVOR
        155 => [11, [183]], // 990101033 CHOCORICH FLAVOR
        156 => [11, [184]], // 990101011 PIROFOSFATO ACIDO DE SODIO
        157 => [11, [185]], // 990101088 Butter Flavor Takasago
        158 => [11, [186]], // 990101062 Cream Flavor Takasago
        159 => [11, [187]], // 990101061 Butter Condensed Milk Takasago
        160 => [11, [188]], // 990101082 Strawberry Flavor Takasago
        161 => [11, [189]], // 990101022 OLEO VEGETAL
        162 => [11, [190]], // 999511585 COCONUT FLAVOR
        163 => [11, [191]], // 999511630 Honey Flavor
        164 => [11, [192]], // 990101047 Milk Flavor Powder
        165 => [11, [193]], // 990101067 Lucta Aroma Chicken 03828A
        166 => [11, [194]], // 990101068 Lucat Aroma Chilli Beef 33479
        167 => [11, [195]], // 990101069 Lucat Aroma Chilli 49014
        168 => [11, [196]], // 990101070 Olive Powder 202347
        169 => [11, [197]], // 990101071 Black Olives 25263A
        170 => [11, [198]], // RMBL-01 Corante
        171 => [11, [199]], // 990101094 Sabor Tutti Fruit
        172 => [11, [200]], // 990101096 Sabor de Limão
        173 => [11, [201]], // 999511774 Rolo Wafi Ginguba
        174 => [11, [202]], // 988888889 Wafer Em po
        175 => [11, [203]], // 988888888 Bolacha em po

        // SFG Massa Tomate (12)
        119 => [12, [147]], // 402000003 Tomato Paste Bulk Brix 22-24
        120 => [12, [148]], // MTTP Mama Tia Tomato Paste
        122 => [12, [150]], // SFGMT-01 Mamatia
        123 => [12, [151]], // SFGMT-02 Dourado
        124 => [12, [152]], // SFGMT-03 Yokabel
        125 => [12, [153]], // SFGMT-04 Super Chef
        126 => [12, [154]], // SFGMT-05 Adal
        127 => [12, [155]], // SFGMT-06 Cereal
        128 => [12, [156]], // SFGMT-07 Apple
        129 => [12, [157]], // SFGMT-08 Kup Dourado
        130 => [12, [158]], // SFGMT-09 Sauce of Peeled Tomato

        // Packaging Materials Massa Tomate (13)
        36 => [13, [63, 64]], // P-Plastic Role Plastic Role
        59 => [13, [87]], // 401100008 STICKERS TOMATE PELADO MAMATIA 400 GR
        60 => [13, [88]], // 401100011 STICKERS MASSA TOMATE MAMATIA 400 GR
        61 => [13, [89]], // 401100012 STICKERS MASSA TOMATE DOURADO 400 GR
        62 => [13, [90]], // 401100015 STICKERS MASSA TOMATE YOKABEL 400 GR
        63 => [13, [91]], // 401100016 TAMPA DE BALDE DE 1 KG
        64 => [13, [92]], // 401100017 STICKERS YOKABEL TOMATE PELADO 400 GR
        65 => [13, [93]], // 401100018 STICKERS MASSA TOMATE SUPER CHEF 400 GR
        66 => [13, [94]], // 401100019 STICKERS TOMATE PELADO MAMATIA 450 GR
        67 => [13, [95]], // 401100020 STICKERS MASSA TOMATE MAMATIA 450 GR
        68 => [13, [96]], // 401200002 PACOTE DE MASSA TOMATE 70 GR MAMA TIA
        69 => [13, [97]], // 401200005 BALDE DE MASSSA TOMATE 1 KG
        70 => [13, [98]], // 401500006 TAMPAS NORMAIS P/ LATA DE 70 GR
        71 => [13, [99]], // 401500019 TAMPAS NORMAIS P/ LATA DE 400 GR
        72 => [13, [100]], // 401500021 TAMPAS NORMAIS P/ LATA DE 450 GR-73mm-SILVER
        73 => [13, [101]], // 401600003 GARRAFA DE VINAGRE 500 ML
        74 => [13, [102]], // 401600007 STICKERS VINAGRE MAMATIA CEREAL 500 ML
        75 => [13, [103]], // 401600008 STICKERS VINAGRE MAMATIA MACA 500 ML
        76 => [13, [104]], // 401600009 BARCODE VINAGRE MAMATIA MACA
        77 => [13, [105]], // 401600010 BARCODE VINAGRE MAMATIA CEREAL
        78 => [13, [106]], // 401600013 ROLO PARA EMBALAR / KG
        79 => [13, [107]], // 401600016 STICKERS VINAGRE BOM APETITE MACA 500 ML
        80 => [13, [108]], // 401600017 STICKERS VINAGRE BOM APETITE CEREAL 500 ML
        81 => [13, [109]], // 401600018 TAMPA VERDE 20 MM
        82 => [13, [110]], // 401600019 TAMPA BRANCA / VERMELHA 20 MM
        83 => [13, [111]], // 401600024 STICKERS VINAGRE VEGETAL VELI 500 ML
        84 => [13, [112]], // 401800002 GARRAFA DE KETCHUP 500 G
        85 => [13, [113]], // 401800006 STICKERS KETCHUP DOURADO 450 ML (ATRAS)
        86 => [13, [114]], // 401800007 STICKERS KETCHUP DOURADO 450 ML (FRENTE)
        87 => [13, [115]], // 401800009 TAMPA DE GARRAFA DE KETCHUP
        88 => [13, [116]], // 403500001 LATA VAZIA MAMA TIA 70 GR
        89 => [13, [117]], // 403500002 LATA VAZIA DOURADO 70 GR
        90 => [13, [118]], // 403500005 LATA VAZIA SIMPLES 400 GR
        91 => [13, [119]], // 403500012 LATA VAZIA SUPER CHEF 70 GR
        92 => [13, [120]], // 403500013 LATA VAZIA SIMPLE 450 G 110mm X 73mm NE
        93 => [13, [121]], // 601116077 CAIXA MASSA TOMATE MAMA TIA 50X70G
        94 => [13, [122]], // 601116078 CAIXA MASSA TOMATE MAMA TIA 24X400G
        95 => [13, [123]], // 601116112 CAIXA PACOTE MASSA TOMATE MAMA TIA 50X70G
        96 => [13, [124]], // 601116116 SEPARADORE CAIXA PACOTE DE MASSA TOMATE 50X70G
        97 => [13, [125]], // 601116117 SEPARADORE CAIXA PACOTE DE MASSA TOMATE 50X70G
        98 => [13, [126]], // 601116118 SEPARADORE CAIXA PACOTE DE MASSA TOMATE 12X400G
        99 => [13, [127]], // 601116119 SEPARADORE CAIXA PACOTE DE MASSA TOMATE 12X400G
        100 => [13, [128]], // 601116416 CAIXA TOMATE PELADO MAMA TIA 24X400G
        101 => [13, [129]], // 601116453 BARQUETE VINAGRE CEREAL 12X500ML
        102 => [13, [130]], // 601116466 Caixa Yummi 210 G
        103 => [13, [131]], // 601116467 Caixa Yummi 400 G
        104 => [13, [132]], // 601116546 BARQUETE TOMATE PELADO 12X400 GR
        105 => [13, [133]], // 601116547 BARQUETE TOMATE PELADO 6X400 GR
        106 => [13, [134]], // 601116705 CAIXA MASSA TOMATE YOKABEL 24X400G
        107 => [13, [135]], // 601116709 CAIXA MASSA TOMATE MAMATIA 6X1KG
        108 => [13, [136]], // 601116738 BARQUETE VINAGRE BOM APETITE APPLE 12X500ML
        109 => [13, [137]], // 601116739 BARQUETE VINAGRE BOM APETITE CEREAL 12X500ML
        110 => [13, [138]], // 601116741 CAIXA TOMATE PELADO YOKABEL 24X400G
        111 => [13, [139]], // 601116807 CAIXA MASSA TOMATE SUPER CHEF 50X70G
        112 => [13, [140]], // 601116808 CAIXA MASSA TOMATE DOURADO NOVO 50X70G
        113 => [13, [141]], // 601116809 CAIXA MASSA TOMATE SUPER CHEF 24X400G
        114 => [13, [142]], // 601116810 CAIXA MASSA TOMATE DOURADO NOVO 24X400G
        115 => [13, [143]], // 601116900 CAIXA TOMATE PELADO MAMA TIA 24X450G
        116 => [13, [144]], // 601116930 CAIXA MASSA TOMATE MAMA TIA 24X450G
        117 => [13, [145]], // 601116931 BARQUETE VINAGRE VELI VEGETAL 12X500ML
        118 => [13, [146]], // 990102005 Y- FITA COLA

        // SFG Bolacha (16)
        213 => [16, [241]], // SFGBL-01 Maria
        214 => [16, [242]], // SFGBL-02 Dona Jowna
        215 => [16, [243]], // SFGBL-03 Maria Samakaka Azul
        216 => [16, [244]], // SFGBL-04 Fix Fix
        217 => [16, [245]], // SFGBL-05 Fix Fix Frango
        218 => [16, [246]], // SFGBL-06 Fix Fix Picante
        219 => [16, [247]], // SFGBL-07 Fix Fix Bife Picante
        220 => [16, [248]], // SFGBL-08 Fix Fix Barbeque
        221 => [16, [249]], // SFGBL-09 Fix Fix Azeitonas
        222 => [16, [250]], // SFGBL-10 Football 100
        223 => [16, [251]], // SFGBL-11 Tondo Leite
        224 => [16, [252]], // SFGBL-12 Tondo New Massa
        225 => [16, [253]], // SFGBL-13 Crème Tondo Vanilha
        226 => [16, [254]], // SFGBL-14 Crème Tondo Morango
        227 => [16, [255]], // SFGBL-15 Crème Tondo Chocolate
        228 => [16, [256]], // SFGBL-16 Tondo Chocolate
        229 => [16, [257]], // SFGBL-17 Football 100 Ch.
        230 => [16, [258]], // SFGBL-18 Football Coconut
        231 => [16, [259]], // SFGBL-19 Wafer Baunilha Creme
        232 => [16, [260]], // SFGBL-20 Wafer Morango Creme
        233 => [16, [261]], // SFGBL-21 Wafer Chocolate Creme
        234 => [16, [262]], // SFGBL-22 Wafer Chocolate Duplo
        235 => [16, [263]], // SFGBL-23 Wafer Coco Crème
        236 => [16, [264]], // SFGBL-24 Massa de Wafer
        237 => [16, [265]], // SFGBL-25 Massa de Wafer Escura
        238 => [16, [266]], // SFGBL-26 Massa Waffi
        239 => [16, [267]], // SFGBL-27 Waffi Crème Baunilha
        240 => [16, [268]], // SFGBL-28 Waffi Crème Morango
        241 => [16, [269]], // SFGBL-29 Waffi Crème Chocolate
        242 => [16, [270]], // SFGBL-30 Waffi Crème Limao
        243 => [16, [271]], // SFGBL-31 Waffi Crème Ginguba
        244 => [16, [272]], // SFGBL-32 Waffi Crème Tutti Fruit
        245 => [16, [273]], // 999999999 Xarope
        246 => [16, [274]], // SFGBL-33 Massa Happy Face
        247 => [16, [275]], // SFGBL-34 Crème Happy Face Tutti Fruit
        248 => [16, [276]], // SFGBL-35 Crème Happy Face Morango
        249 => [16, [277]], // SFGBL-36 Crème Happy Face Ginguba
        250 => [16, [278]], // SFGBL-37 Crème Happy Face Chocolate
        251 => [16, [279]], // SFGBL-38 Crème Happy Face Limao
        252 => [16, [280]], // SFGBL-39 Naya Cracker
        253 => [16, [281]], // SFGBL-40 Naya Flora Leite
        254 => [16, [282]], // SFGBL-41 Naya Petit Beurre Chocolate
        255 => [16, [283]], // SFGBL-42 Fix Fix Digestivo

        // Packaging Materials Bolacha (18)
        176 => [18, [204]], // 999511698 Y- Rolo Durado Chocolate Wafer 50gm
        177 => [18, [205]], // 999511699 Y- Rolo Durado Strawberry Wafer 50gm
        178 => [18, [206]], // 999511707 Y- W Choclate duplo 50gm
        179 => [18, [207]], // 999511709 Y-ROLO Dourado Vanilha Flavor 50G
        180 => [18, [208]], // 999511696 Y- Rolo Footebal 90g
        181 => [18, [209]], // 999511687 Y- Rolo Super Homem 65g
        182 => [18, [210]], // 999511748 Y-ROLO DE FAWER BAUNILHA 50 G
        183 => [18, [211]], // 999511749 Y-ROLO DE GLUCOSO 30 G
        184 => [18, [212]], // 999511750 Y- Rolo Novo Super Glucoso 45 G
        185 => [18, [213]], // 999511777 Y- Rolo Wafer Chocolate 33 G
        186 => [18, [214]], // 999511785 Y-ROLO DE WAFER STRAWBERRY MORANGO 50G
        187 => [18, [215]], // 999511722 Y- Rolo Super Palanca 32g
        188 => [18, [216]], // 999511863 Y- Rolo Super Glucose (Azul) 30g
        189 => [18, [217]], // 999511745 Y- Rolo Super Glucoso 30g
        190 => [18, [218]], // 999511704 ROLO WAFER BAUNILHA 33
        191 => [18, [219]], // 999511741 Y- Rolo Transparente 270 mm
        192 => [18, [220]], // 999511742 Y- Rolo Transparente 225 MM
        193 => [18, [221]], // 999511743 Y- Rolo Transparente 185 MM
        194 => [18, [222]], // 999511744 Y-ROLO GIRABOLA 100 G
        195 => [18, [223]], // 999511746 Y-ROLO TRANSPARENTE 275 MM
        196 => [18, [224]], // 999511747 Y-ROLO FIX FIX 185 MM
        197 => [18, [225]], // 601111027 Y- Caixa Cartao B.S Wafer Chocolate 24X130g
        198 => [18, [226]], // 601115916 Y-CAIXA B S CORACAO 36X50G
        199 => [18, [227]], // 601111071 Y- Caixa B.S. Coracao 48X50 GR CS
        200 => [18, [228]], // 601115628 Y- Caixa Cartao B .S Agua E Sal 48X55 G
        201 => [18, [229]], // 601115678 Y-CAIXA DE WAFER 36X65G
        202 => [18, [230]], // 601105004 Y- Film Etirable
        203 => [18, [231]], // 624000074 Y- Saco de Lixo p/ Contentor 120L 70x100 N10
        204 => [18, [232]], // 999611005 Y- Bidon Plastico de 25L Vazio
        205 => [18, [233]], // 999611006 Y- Sacos de sal de 20 klg Vazío
        206 => [18, [234]], // 999611088 Y- Pallet Plastico 1200x1000x150
        207 => [18, [235]], // 999611045 Y-SACO P/GELO 5KG 36X46 N100
        208 => [18, [236]], // 501110002 ROLO DE SACO LAMINATE 280 MM
        209 => [18, [237]], // 501110003 ROLO DE SACO LAMINATE 340 MM
        210 => [18, [238]], // 501120002 ROLO DE SACO LDPE 680 MM
        211 => [18, [239]], // 501110001 ROLO DE SACO LAMINATE 220 MM
        212 => [18, [240]], // 999511719 ROLO CORAÇÃO 50G
    ];

    public function run(): void
    {
        $created = 0;
        $kept    = 0;
        $lines   = 0;

        DB::transaction(function () use (&$created, &$kept, &$lines) {
            foreach (self::WAREHOUSES as $warehouseId => $itemTypeIds) {
                $key     = self::submissionKey($warehouseId);
                $stockIn = StockIn::withTrashed()->where('submission_key', $key)->first();

                // Deleted by hand: left alone.
                if ($stockIn?->trashed()) {
                    $kept++;
                    continue;
                }

                if (!$stockIn) {
                    $stockIn = StockIn::create([
                        'warehouse_id'   => $warehouseId,
                        'status'         => 'approved',
                        'notes'          => 'Testing stock, seeded by TestStockInSeeder: '
                            . number_format(self::QUANTITY) . ' of every active item this warehouse holds, in each of its units.',
                        'submission_key' => $key,
                    ]);
                    $created++;
                } else {
                    $kept++;
                }

                $have = ReportItem::withTrashed()
                    ->where('stock_in_id', $stockIn->id)
                    ->get(['item_id', 'item_unit_id'])
                    ->mapWithKeys(fn($line) => ["{$line->item_id}:{$line->item_unit_id}" => true]);

                $now  = now();
                $rows = [];

                foreach (self::ITEMS as $itemId => [$itemTypeId, $unitIds]) {
                    if (!in_array($itemTypeId, $itemTypeIds, true)) {
                        continue;
                    }

                    foreach ($unitIds as $unitId) {
                        if (isset($have["{$itemId}:{$unitId}"])) {
                            continue;
                        }

                        $rows[] = [
                            'stock_in_id'  => $stockIn->id,
                            'warehouse_id' => $warehouseId,
                            'item_id'      => $itemId,
                            'item_unit_id' => $unitId,
                            'quantity'     => self::QUANTITY,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    ReportItem::insert($chunk);
                }

                $lines += count($rows);
            }
        });

        $this->command?->info("Testing stock: {$created} approved Stock In documents created, {$kept} already there; {$lines} lines added.");
    }

    /** The fixed key that marks a warehouse's testing Stock In. */
    public static function submissionKey(int $warehouseId): string
    {
        return sprintf('7e57570c-0000-4000-8000-%012d', $warehouseId);
    }
}
