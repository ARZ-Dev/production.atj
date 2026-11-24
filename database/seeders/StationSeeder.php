<?php

namespace Database\Seeders;

use App\Models\Line;
use App\Models\Station;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StationSeeder extends Seeder
{
    /** factory => [department id, factory warehouse id] in the parent. */
    public const FACTORIES = [
        'massa_tomate' => [14, 1],   // MT Production Department · Massa Tomate Factory Warehouse
        'bolacha'      => [5, 4],    // Bolacha - Production · Bolacha Factory
    ];

    /**
     * Every machine of the two equipment sheets, and the lines they're on.
     * A station's rate is per machine, in the basic unit of what its stage
     * makes (packets, cans …), kg for the dough stages and the mixers.
     *
     * Massa Tomate — "Equipment and capacity Massa tomate.xlsx" (all Allan,
     * China, but the label machines): the mixing line (preparation equipment,
     * on no line — the Mixing preparation runs it), each can line's filler and
     * packaging machine (it boxes the cans: type Packaging) at their sheet
     * rates, the one pasteurizing tunnel on both can lines (no rate: "based on
     * pasteurizing time"), and the label machines, on no line: shared by both
     * lines and which products get a label isn't known.
     *
     * Bolacha — "Equipment and capacity Biscuit factory.xlsx", placed by the
     * factory's line drawings of 2026-11-16 (LINE 01 … 06, WAFER LINE), which
     * replace those of 2026-11-04. Each biscuit line forms the biscuits (Lines
     * 01, 02 and 05 on the sheet's three Laser lamination lines, 03 and 04 on
     * a Laser rotary moulder, 06 on an Angobake lamination line), bakes them in
     * its oven — Laser Ovens 1–5 on Lines 01–05; Line 06's is an Angobake — then
     * packs (wraps) them on the
     * drawing's wrappers and packages (boxes) them on its taping machines. The
     * wafer line bakes, creams, cools and cuts on its Franz Haas machines
     * ("HASSE" in the drawing), then packs and packages the same way.
     *
     * The wrappers, as the drawings name them: Flow Wraps on Lines 01, 02 and
     * 04 (by their numbers, 247–254 — the sheet doesn't number them), the
     * Cavanas on Line 05, two PAKONAs on Line 06, two DACHUANs ("DAUCHUAN
     * MACHINERY") on Line 03 and one on the wafer line, and three "BAPM"s —
     * BAPM08 / BAPM07 on Line 03, BA/PM/09 on the wafer line — a make the sheet
     * doesn't have, taken to be its three HEBEIs. The wafer line's Packing 02
     * is an "… AF300 FLOW" (its make's name too small to read even in the clear
     * copy), which the sheet doesn't list (93); the sheet's third PAKONA is on
     * no drawing, so on no line.
     *
     * Capacities — the drawings carry them in green (sent 2026-11-20, a clear
     * copy 2026-11-24). Stored as rates: the ovens, 1,200 / 1,200 / 1,000 /
     * 1,000 / 2,000 / 300 kg/h on Lines 01–06 (until 2026-11-20 Lines 03 and 05
     * had the sheet's 2,000 and 1,000 the other way round, as placed in sheet
     * order on 2026-10-28, and Line 06's Angobake none), and the wrappers, at
     * the low end of each drawing's range as the user chose for the sheet's on
     * 2026-10-29 — Flow Wrap 90–110 → 90, Cavana 90–120 → 90, DACHUAN and
     * BAPM / HEBEI 100–110 → 100 (HEBEI had none before), PAKONA 55–60 → 55,
     * the AF300 100–110 → 100. So on most lines the wrappers, not the oven, set
     * the speed. In the notes only, since none is a per-hour rate in the
     * stage's unit: the mixers' and tanks' kg a batch, the wafer oven's sheets
     * a minute, the refrigeration's sheets held and the cutter's cuts a
     * minute. No capacity anywhere for the lamination lines, rotary moulders,
     * the cream machine, Cavana 03 (its sheet figure stays) or the taping
     * machines (model MH-FJ-1AW).
     *
     * The mixers and tanks are preparation equipment, on no line; each mixer
     * has a preparation of its name (PreparationSeeder; the sheet gives kg a
     * batch, not a rate).
     *
     * Each station is in its sheet's department and factory (FACTORIES —
     * the parent's ids, the same pair the factory's production line has).
     *
     * Fixed ids, matched on id (soft-deleted ones restored), so re-running
     * updates in place. Each listed line ends up with exactly its machines
     * here, in this order; lines not listed are left alone.
     *
     * @var array<int, array{0: string, 1: string, 2: ?string, 3: ?float, 4: string, 5: ?string, 6: string}>
     *      id => [name, type, manufacturer, rate, per (hour|minute), notes, factory]
     */
    public const STATIONS = [
        // ── Massa Tomate ────────────────────────────────────────────────────
        1  => ['Mixing Line', 'Mixing', 'Allan (China)', 3500, 'hour', '3.5 t/h.', 'massa_tomate'],
        2  => ['Filling & Seaming 70g', 'Filling & Seaming', 'Allan (China)', 360, 'minute', null, 'massa_tomate'],
        3  => ['Filling & Seaming 400g', 'Filling & Seaming', 'Allan (China)', 130, 'minute', null, 'massa_tomate'],
        4  => ['Pasteurizing Tunnel', 'Pasteurizing', 'Allan (China)', null, 'minute', 'Based on pasteurizing time.', 'massa_tomate'],
        5  => ['Packaging Machine 70g', 'Packaging', 'Allan (China)', 600, 'minute', 'Uses cans, cartons, tape.', 'massa_tomate'],
        6  => ['Packaging Machine 400g', 'Packaging', 'Allan (China)', 200, 'minute', 'Uses cans, cartons, tape.', 'massa_tomate'],
        7  => ['Label Machine Low Speed 1', 'Labelling', 'China', 22, 'minute', 'Uses stickers.', 'massa_tomate'],
        8  => ['Label Machine Low Speed 2', 'Labelling', 'China', 22, 'minute', 'Uses stickers.', 'massa_tomate'],
        9  => ['Label Machine High Speed', 'Labelling', 'China', 70, 'minute', 'Uses stickers.', 'massa_tomate'],

        // ── Bolacha: ovens ──────────────────────────────────────────────────
        10 => ['Oven 1', 'Cooking', 'Laser', 1200, 'hour', '4 zones, cyclothermal. Laser 1221044 in Line 01\'s drawing.', 'bolacha'],
        11 => ['Oven 2', 'Cooking', 'Laser', 1200, 'hour', '4 zones, cyclothermal. Laser 1201001 in Line 02\'s drawing.', 'bolacha'],
        // Lines 03 and 05: 1,000 and 2,000 kg/h in their drawings — the other way round from the sheet's order.
        12 => ['Oven 3', 'Cooking', 'Laser', 1000, 'hour', '3 zones, cyclothermal. 1,000 kg/h in Line 03\'s drawing.', 'bolacha'],
        13 => ['Oven 4', 'Cooking', 'Laser', 1000, 'hour', '3 zones, cyclothermal.', 'bolacha'],
        14 => ['Oven 5', 'Cooking', 'Laser', 2000, 'hour', '5 zones: 2 direct flame, 3 cyclothermal. 2,000 kg/h in Line 05\'s drawing.', 'bolacha'],
        15 => ['Franz Haas Oven', 'Cooking', 'Franz Haas', null, 'minute', 'The wafer line: 40 sheets/minute (sheet and drawing) — the sheets per box are not known, so no rate in the wafer recipes\' kg.', 'bolacha'],

        // ── Bolacha: lamination lines ───────────────────────────────────────
        16 => ['Lamination Line 1', 'Cutting', 'Laser', null, 'hour', 'Laser 1221042 in Line 01\'s drawing.', 'bolacha'],
        17 => ['Lamination Line 2', 'Cutting', 'Laser', null, 'hour', 'Laser 1241005 in Line 02\'s drawing.', 'bolacha'],
        18 => ['Lamination Line 3', 'Cutting', 'Laser', null, 'hour', 'Laser LIHF1500 in Line 05\'s drawing.', 'bolacha'],

        // ── Bolacha: packing machines (wrappers) ────────────────────────────
        // Flow Wrap 1–8 until 2026-11-16, renamed by the drawings' numbers.
        19 => ['Flow Wrap 247', 'Packing', 'Flow Wrap Technology', 90, 'minute', self::FLOW_WRAP, 'bolacha'],
        20 => ['Flow Wrap 248', 'Packing', 'Flow Wrap Technology', 90, 'minute', self::FLOW_WRAP, 'bolacha'],
        21 => ['Flow Wrap 249', 'Packing', 'Flow Wrap Technology', 90, 'minute', self::FLOW_WRAP, 'bolacha'],
        22 => ['Flow Wrap 250', 'Packing', 'Flow Wrap Technology', 90, 'minute', self::FLOW_WRAP, 'bolacha'],
        23 => ['Flow Wrap 251', 'Packing', 'Flow Wrap Technology', 90, 'minute', self::FLOW_WRAP, 'bolacha'],
        24 => ['Flow Wrap 252', 'Packing', 'Flow Wrap Technology', 90, 'minute', self::FLOW_WRAP, 'bolacha'],
        25 => ['Flow Wrap 253', 'Packing', 'Flow Wrap Technology', 90, 'minute', self::FLOW_WRAP, 'bolacha'],
        26 => ['Flow Wrap 254', 'Packing', 'Flow Wrap Technology', 90, 'minute', self::FLOW_WRAP, 'bolacha'],
        27 => ['Cavana 1', 'Packing', 'Cavana 05NKZ', 90, 'minute', self::CAVANA, 'bolacha'],
        28 => ['Cavana 2', 'Packing', 'Cavana 05NKZ', 90, 'minute', self::CAVANA, 'bolacha'],
        29 => ['Cavana 3', 'Packing', 'Cavana 05NKZ', 90, 'minute', self::CAVANA, 'bolacha'],
        // HEBEI 1–3 until 2026-11-24, renamed by the drawings' tags.
        30 => ['HEBEI BAPM08', 'Packing', 'HEBEI HBEP30', 100, 'minute', self::HEBEI . ' Line 03\'s Packing 01.', 'bolacha'],
        31 => ['HEBEI BAPM07', 'Packing', 'HEBEI HBEP30', 100, 'minute', self::HEBEI . ' Line 03\'s Packing 04.', 'bolacha'],
        32 => ['HEBEI BAPM09', 'Packing', 'HEBEI HBEP30', 100, 'minute', self::HEBEI . ' The wafer line\'s Packing 01 ("BA/PM/09").', 'bolacha'],
        33 => ['DACHUAN 1', 'Packing', 'DACHUAN DC250B', 100, 'minute', self::DACHUAN . ' Line 03\'s Packing 02.', 'bolacha'],
        34 => ['DACHUAN 2', 'Packing', 'DACHUAN DC250B', 100, 'minute', self::DACHUAN . ' Line 03\'s Packing 03.', 'bolacha'],
        35 => ['DACHUAN 3', 'Packing', 'DACHUAN DC250B', 100, 'minute', self::DACHUAN . ' The wafer line\'s Packing 03.', 'bolacha'],
        36 => ['PAKONA 1', 'Packing', 'PAKONA PK70VL', 55, 'minute', self::PAKONA . ' Line 06\'s Packing 01.', 'bolacha'],
        37 => ['PAKONA 2', 'Packing', 'PAKONA PK70VL', 55, 'minute', self::PAKONA . ' Line 06\'s Packing 02.', 'bolacha'],
        38 => ['PAKONA 3', 'Packing', 'PAKONA PK70VL', 55, 'minute', '55–60 pcs/min (sheet) · vertical folding · BOPP, tape, box, ribbon. On no drawing, so on no line (until 2026-11-24 it stood in for the wafer line\'s Packing 02).', 'bolacha'],

        // ── Bolacha: mixers (preparation equipment) ─────────────────────────
        39 => ['Mixer ZM 500 1', 'Mixing', 'Laser ZM 500', null, 'hour', '250 kg a batch.', 'bolacha'],
        40 => ['Mixer ZM 500 2', 'Mixing', 'Laser ZM 500', null, 'hour', '250 kg a batch.', 'bolacha'],
        41 => ['Mixer ZM 500 3', 'Mixing', 'Laser ZM 500', null, 'hour', '250 kg a batch.', 'bolacha'],
        42 => ['Mixer ZM 500 4', 'Mixing', 'Laser ZM 500', null, 'hour', '250 kg a batch.', 'bolacha'],
        43 => ['Mixer ZM 500 5', 'Mixing', 'Laser ZM 500', null, 'hour', '250 kg a batch.', 'bolacha'],
        44 => ['Mixer ZM 500 6', 'Mixing', 'Laser ZM 500', null, 'hour', '250 kg a batch.', 'bolacha'],
        45 => ['Mixer ZM 500 7', 'Mixing', 'Laser ZM 500', null, 'hour', '250 kg a batch.', 'bolacha'],
        46 => ['Mixer ZM 500 8', 'Mixing', 'Laser ZM 500', null, 'hour', '250 kg a batch.', 'bolacha'],
        47 => ['Mixer ZM 1100 1', 'Mixing', 'Laser ZM 1100', null, 'hour', '500 kg a batch.', 'bolacha'],
        48 => ['Mixer ZM 1100 2', 'Mixing', 'Laser ZM 1100', null, 'hour', '500 kg a batch.', 'bolacha'],

        // ── Bolacha: in the line drawings, not in the equipment sheet ───────
        // "Franz Haas Cream Station" until 2026-11-16.
        49 => ['Franz Haas Cream Machine', 'Creaming', 'Franz Haas WSTM-FOX', null, 'minute', 'Creams the wafer sheets, from the cream tank. No capacity in the drawing.', 'bolacha'],
        50 => ['Rotary Molding 1', 'Cutting', 'Laser', null, 'hour', 'Line 03\'s former ("Laser Rotary" in its drawing).', 'bolacha'],
        51 => ['Rotary Molding 2', 'Cutting', 'Laser', null, 'hour', 'Line 04\'s former ("Laser Rotary" in its drawing).', 'bolacha'],
        52 => ['Lamination Line 4', 'Cutting', 'Angobake', null, 'hour', 'Line 06\'s, in its drawing; the equipment sheet lists 3, all Laser.', 'bolacha'],
        53 => ['Oven 6', 'Cooking', 'Angobake', 300, 'hour', 'Line 06\'s; not in the equipment sheet. 300 kg/h in its drawing.', 'bolacha'],
        69 => ['Franz Haas Refrigeration Machine', 'Refrigeration', 'Franz Haas', null, 'minute', 'Cools the creamed wafer sheets. Holds 180 sheets (drawing) — a buffer, not a rate.', 'bolacha'],
        70 => ['Franz Haas Cutting Machine', 'Cutting', 'Franz Haas', null, 'minute', 'Cuts the cooled wafer sheets into pieces. 10–14 cuts/minute (drawing) — no rate in kg.', 'bolacha'],
        71 => ['Wafer Dough Mixer', 'Mixing', 'Franz Haas ATM120', null, 'hour', 'Mixes the wafer batter into the dough tank. 165 kg a batch (drawing).', 'bolacha'],
        72 => ['Wafer Dough Tank', 'Mixing', 'Franz Haas ATM120', null, 'hour', 'Holds the batter and feeds the oven. 190 kg (drawing).', 'bolacha'],
        73 => ['Wafer Cream Mixer', 'Mixing', 'Franz Haas CAB190 MZ-M', null, 'hour', 'Mixes the wafer cream for the cream tank. 220 kg a batch (drawing).', 'bolacha'],
        74 => ['Wafer Cream Tank', 'Mixing', 'Franz Haas CAB190 MZ-M', null, 'hour', 'Holds the cream and feeds the cream machine. 220 kg (drawing).', 'bolacha'],
        93 => ['AF300 Flow Wrap', 'Packing', null, 100, 'minute', 'The wafer line\'s Packing 02: "… AF300 FLOW" in its drawing (the make\'s name too small to read), not in the equipment sheet. 100–110 pcs/min (drawing).', 'bolacha'],

        // ── Bolacha: taping machines, one per wrapper or pair ───────────────
        75 => ['Line 01 Taping Machine 1', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        76 => ['Line 01 Taping Machine 2', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        77 => ['Line 01 Taping Machine 3', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        78 => ['Line 02 Taping Machine 1', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        79 => ['Line 02 Taping Machine 2', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        80 => ['Line 02 Taping Machine 3', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        81 => ['Line 03 Taping Machine 1', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        82 => ['Line 03 Taping Machine 2', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        83 => ['Line 03 Taping Machine 3', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        84 => ['Line 03 Taping Machine 4', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        85 => ['Line 04 Taping Machine 1', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        86 => ['Line 04 Taping Machine 2', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        87 => ['Line 05 Taping Machine 1', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        88 => ['Line 05 Taping Machine 2', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        89 => ['Line 05 Taping Machine 3', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        90 => ['Line 06 Taping Machine 1', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        91 => ['Wafer Taping Machine 1', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
        92 => ['Wafer Taping Machine 2', 'Packaging', 'MH-FJ-1AW', null, 'minute', self::TAPING, 'bolacha'],
    ];

    /**
     * Stations of the 2026-11-04 drawings these replace, soft-deleted (and
     * taken off their lines) by every run: Naya Vertical's own mixer — the
     * new drawings give Line 06 two of the sheet's eight ZM 500s — and the
     * stand-ins for each line's packing and packaging (55–68), now the
     * drawings' wrappers and taping machines. Their ids aren't reused.
     */
    public const RETIRED = [54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68];

    private const FLOW_WRAP = '90–110 pcs/min (sheet and drawings) · Edge · BOPP, tape, box, ribbon, ink.';
    private const CAVANA    = '90–120 pcs/min (sheet; Line 05\'s drawing for Cavana 01 / 02) · Edge · BOPP, tape, box, ribbon. "ZER05NKZXL" in the drawing.';
    private const HEBEI     = '100–110 pcs/min in the drawings (the sheet gives none) · X-fold · BOPP, tape, box, ribbon. "BAPM" in the drawings, a make the sheet doesn\'t have — taken to be its three HEBEIs.';
    private const DACHUAN   = '100–110 pcs/min (sheet and drawings) · pile folding · BOPP, tape, box, ribbon, ink.';
    private const PAKONA    = '55–60 pcs/min (sheet and Line 06\'s drawing) · vertical folding · BOPP, tape, box, ribbon.';
    private const TAPING    = 'Tapes the boxes shut. Model MH-FJ-1AW (drawings); no capacity in the drawings or the sheet, so no rate.';

    /** line id => its stations, in the order the product passes them (each stage's machines top to bottom in its drawing). */
    public const LINES = [
        1 => [2, 4, 5],                                  // Can Line 70g: filler, tunnel, packer
        3 => [3, 4, 6],                                  // Can Line 400g
        2 => [16, 10, 25, 26, 24, 75, 76, 77],           // Biscuit Line 1 (LINE 01): Lamination Line 1, Oven 1, Flow Wrap 253, 254, 252, 3 taping machines
        4 => [17, 11, 21, 19, 23, 78, 79, 80],           // Biscuit Line 2 (LINE 02): Lamination Line 2, Oven 2, Flow Wrap 249, 247, 251, 3 taping machines
        5 => [50, 12, 30, 33, 34, 31, 81, 82, 83, 84],   // Biscuit Line 3 (LINE 03): Rotary Molding 1, Oven 3, HEBEI 1, DACHUAN 1, 2, HEBEI 2, 4 taping machines
        6 => [51, 13, 22, 20, 85, 86],                   // Biscuit Line 4 (LINE 04): Rotary Molding 2, Oven 4, Flow Wrap 250, 248, 2 taping machines
        7 => [18, 14, 27, 28, 29, 87, 88, 89],           // Biscuit Line 5 (LINE 05): Lamination Line 3, Oven 5, Cavana 1–3, 3 taping machines
        9 => [52, 53, 36, 37, 90],                       // Biscuit Line 6 (LINE 06, vertical): Lamination Line 4, Oven 6, PAKONA 1, 2, 1 taping machine
        8 => [15, 49, 69, 70, 32, 93, 35, 91, 92],       // Wafer Line: oven, cream, refrigeration, cutting, Packing 01 HEBEI BAPM09, 02 AF300, 03 DACHUAN 3, 2 taping machines
    ];

    public function run(): void
    {
        // Refuse before writing: a line takes stations of its own department,
        // a retired station is listed nowhere, and each id is listed once.
        $problems = [];

        foreach (self::LINES as $lineId => $stationIds) {
            $line = Line::withTrashed()->findOrFail($lineId);

            foreach ($stationIds as $stationId) {
                if (!isset(self::STATIONS[$stationId])) {
                    $problems[] = "station #{$stationId} on {$line->name} isn't in STATIONS";
                } elseif (self::FACTORIES[self::STATIONS[$stationId][6]][0] !== (int) $line->department_id) {
                    $problems[] = self::STATIONS[$stationId][0] . " on {$line->name} is of another department";
                }
            }

            if (count($stationIds) !== count(array_unique($stationIds))) {
                $problems[] = "{$line->name} lists a station twice";
            }
        }

        foreach (self::RETIRED as $id) {
            if (isset(self::STATIONS[$id])) {
                $problems[] = "station #{$id} is both in STATIONS and retired";
            }
        }

        if ($problems) {
            throw new RuntimeException('StationSeeder: ' . implode('; ', $problems) . '.');
        }

        DB::transaction(function () {
            foreach (self::STATIONS as $id => [$name, $type, $manufacturer, $rate, $period, $notes, $factory]) {
                [$departmentId, $factoryId] = self::FACTORIES[$factory];

                $station = Station::withTrashed()->firstOrNew(['id' => $id]);
                $station->fill([
                    'station_type_id' => StationTypeSeeder::idOf($type),
                    'department_id'   => $departmentId,
                    'factory_id'      => $factoryId,
                    'name'            => $name,
                    'manufacturer'    => $manufacturer,
                    'rate'            => $rate,
                    'rate_period'     => $period,
                    'notes'           => $notes,
                ]);
                $station->deleted_at = null;
                $station->save();
            }

            foreach (self::LINES as $lineId => $stationIds) {
                Line::withTrashed()->findOrFail($lineId)->stations()->sync(
                    collect($stationIds)->mapWithKeys(fn($id, $i) => [$id => ['sequence' => $i + 1]])->all()
                );
            }

            // Off every line, then soft-deleted (nothing to do on a fresh database).
            DB::table('line_station')->whereIn('station_id', self::RETIRED)->delete();
            Station::whereIn('id', self::RETIRED)->delete();
        });
    }
}
