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
     * factory's line drawings of 2026-11-04 (LINE 01 … 05, NAYA VERTICAL,
     * WAFER): each line forms the biscuits (Lines 01, 02 and 05 on the three
     * lamination lines, 03 and 04 on a rotary moulder), bakes them in its Laser
     * oven — Ovens 1–5 on Lines 01–05 in the sheet's order, as chosen on
     * 2026-10-28 — then packs (wraps) and packages (boxes) them. Naya Vertical
     * has a lamination line, an oven and a ZM 500 mixer of its own that the
     * sheet doesn't list (4th, 6th and 9th); the rotary moulders aren't in the
     * sheet either; none of them has a rate. The wafer line is the Franz Haas
     * oven and its cream station; its 40 plates/min the wafer recipes' kg
     * can't use.
     *
     * The drawings show one packing and one packaging station per line but not
     * which of the 20 wrappers stands where, so each line has a stand-in for
     * both, without a rate (the user's choice, 2026-11-04), and the wrappers
     * are on no line: swap them in on each line's Stations page once known.
     * The wrappers' rate is the low end of their sheet range (the user's
     * choice, 2026-10-29) — the range, packaging style and what each uses are
     * in their notes; HEBEI and the lamination lines have no figure in the
     * sheet. The mixers are preparation equipment, each with a preparation of
     * its name (PreparationSeeder; the sheet gives kg a batch, not a rate).
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

        // ── Bolacha: ovens and the wafer line ───────────────────────────────
        10 => ['Oven 1', 'Cooking', 'Laser', 1200, 'hour', '4 zones, cyclothermal.', 'bolacha'],
        11 => ['Oven 2', 'Cooking', 'Laser', 1200, 'hour', '4 zones, cyclothermal.', 'bolacha'],
        12 => ['Oven 3', 'Cooking', 'Laser', 2000, 'hour', '5 zones: 2 direct flame, 3 cyclothermal.', 'bolacha'],
        13 => ['Oven 4', 'Cooking', 'Laser', 1000, 'hour', '3 zones, cyclothermal.', 'bolacha'],
        14 => ['Oven 5', 'Cooking', 'Laser', 1000, 'hour', '3 zones, cyclothermal.', 'bolacha'],
        15 => ['Franz Haas Oven', 'Cooking', 'Franz Haas', null, 'minute', 'The wafer line: 40 plates/minute — the plates per box are not known, so no rate in the wafer recipes\' kg.', 'bolacha'],

        // ── Bolacha: lamination lines ───────────────────────────────────────
        16 => ['Lamination Line 1', 'Cutting', 'Laser', null, 'hour', null, 'bolacha'],
        17 => ['Lamination Line 2', 'Cutting', 'Laser', null, 'hour', null, 'bolacha'],
        18 => ['Lamination Line 3', 'Cutting', 'Laser', null, 'hour', null, 'bolacha'],

        // ── Bolacha: packaging machines (wrappers) ──────────────────────────
        19 => ['Flow Wrap 1', 'Packing', 'Flow Wrap Technology', 90, 'minute', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        20 => ['Flow Wrap 2', 'Packing', 'Flow Wrap Technology', 90, 'minute', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        21 => ['Flow Wrap 3', 'Packing', 'Flow Wrap Technology', 90, 'minute', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        22 => ['Flow Wrap 4', 'Packing', 'Flow Wrap Technology', 90, 'minute', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        23 => ['Flow Wrap 5', 'Packing', 'Flow Wrap Technology', 90, 'minute', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        24 => ['Flow Wrap 6', 'Packing', 'Flow Wrap Technology', 90, 'minute', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        25 => ['Flow Wrap 7', 'Packing', 'Flow Wrap Technology', 90, 'minute', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        26 => ['Flow Wrap 8', 'Packing', 'Flow Wrap Technology', 90, 'minute', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        27 => ['Cavana 1', 'Packing', 'Cavana 05NKZ', 90, 'minute', '90–120 pcs/min · Edge · BOPP, tape, box, ribbon.', 'bolacha'],
        28 => ['Cavana 2', 'Packing', 'Cavana 05NKZ', 90, 'minute', '90–120 pcs/min · Edge · BOPP, tape, box, ribbon.', 'bolacha'],
        29 => ['Cavana 3', 'Packing', 'Cavana 05NKZ', 90, 'minute', '90–120 pcs/min · Edge · BOPP, tape, box, ribbon.', 'bolacha'],
        30 => ['HEBEI 1', 'Packing', 'HEBEI HBEP30', null, 'minute', 'No capacity given · X-fold · BOPP, tape, box, ribbon.', 'bolacha'],
        31 => ['HEBEI 2', 'Packing', 'HEBEI HBEP30', null, 'minute', 'No capacity given · X-fold · BOPP, tape, box, ribbon.', 'bolacha'],
        32 => ['HEBEI 3', 'Packing', 'HEBEI HBEP30', null, 'minute', 'No capacity given · X-fold · BOPP, tape, box, ribbon.', 'bolacha'],
        33 => ['DACHUAN 1', 'Packing', 'DACHUAN DC250B', 100, 'minute', '100–110 pcs/min · pile folding · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        34 => ['DACHUAN 2', 'Packing', 'DACHUAN DC250B', 100, 'minute', '100–110 pcs/min · pile folding · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        35 => ['DACHUAN 3', 'Packing', 'DACHUAN DC250B', 100, 'minute', '100–110 pcs/min · pile folding · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        36 => ['PAKONA 1', 'Packing', 'PAKONA PK70VL', 55, 'minute', '55–60 pcs/min · vertical folding · BOPP, tape, box, ribbon.', 'bolacha'],
        37 => ['PAKONA 2', 'Packing', 'PAKONA PK70VL', 55, 'minute', '55–60 pcs/min · vertical folding · BOPP, tape, box, ribbon.', 'bolacha'],
        38 => ['PAKONA 3', 'Packing', 'PAKONA PK70VL', 55, 'minute', '55–60 pcs/min · vertical folding · BOPP, tape, box, ribbon.', 'bolacha'],

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
        49 => ['Franz Haas Cream Station', 'Creaming', 'Franz Haas', null, 'minute', 'Creams the wafer sheets, from the cream tank.', 'bolacha'],
        50 => ['Rotary Molding 1', 'Cutting', null, null, 'hour', 'Line 03\'s former, in its drawing.', 'bolacha'],
        51 => ['Rotary Molding 2', 'Cutting', null, null, 'hour', 'Line 04\'s former, in its drawing.', 'bolacha'],
        52 => ['Lamination Line 4', 'Cutting', 'Laser', null, 'hour', 'Naya Vertical\'s, in its drawing; the equipment sheet lists 3.', 'bolacha'],
        53 => ['Oven 6', 'Cooking', 'Laser', null, 'hour', 'Naya Vertical\'s, in its drawing; the equipment sheet lists 5 ovens, so no rate.', 'bolacha'],
        54 => ['Mixer ZM 500 9', 'Mixing', 'Laser ZM 500', null, 'hour', '250 kg a batch. Naya Vertical\'s, in its drawing; the equipment sheet lists 8.', 'bolacha'],

        // ── Bolacha: stand-ins for each line's packing and packaging ────────
        55 => ['Line 01 Packing', 'Packing', null, null, 'minute', self::PACKING_STAND_IN, 'bolacha'],
        56 => ['Line 01 Packaging', 'Packaging', null, null, 'minute', self::PACKAGING_STAND_IN, 'bolacha'],
        57 => ['Line 02 Packing', 'Packing', null, null, 'minute', self::PACKING_STAND_IN, 'bolacha'],
        58 => ['Line 02 Packaging', 'Packaging', null, null, 'minute', self::PACKAGING_STAND_IN, 'bolacha'],
        59 => ['Line 03 Packing', 'Packing', null, null, 'minute', self::PACKING_STAND_IN, 'bolacha'],
        60 => ['Line 03 Packaging', 'Packaging', null, null, 'minute', self::PACKAGING_STAND_IN, 'bolacha'],
        61 => ['Line 04 Packing', 'Packing', null, null, 'minute', self::PACKING_STAND_IN, 'bolacha'],
        62 => ['Line 04 Packaging', 'Packaging', null, null, 'minute', self::PACKAGING_STAND_IN, 'bolacha'],
        63 => ['Line 05 Packing', 'Packing', null, null, 'minute', self::PACKING_STAND_IN, 'bolacha'],
        64 => ['Line 05 Packaging', 'Packaging', null, null, 'minute', self::PACKAGING_STAND_IN, 'bolacha'],
        65 => ['Naya Vertical Packing', 'Packing', null, null, 'minute', self::PACKING_STAND_IN, 'bolacha'],
        66 => ['Naya Vertical Packaging', 'Packaging', null, null, 'minute', self::PACKAGING_STAND_IN, 'bolacha'],
        67 => ['Wafer Packing', 'Packing', null, null, 'minute', self::PACKING_STAND_IN, 'bolacha'],
        68 => ['Wafer Packaging', 'Packaging', null, null, 'minute', self::PACKAGING_STAND_IN, 'bolacha'],
    ];

    private const PACKING_STAND_IN   = 'Stand-in: which of the 20 wrappers packs on this line isn\'t known yet.';
    private const PACKAGING_STAND_IN = 'Stand-in: the line\'s boxing station, as its drawing shows it.';

    /** line id => its stations, in the order the product passes them. */
    public const LINES = [
        1 => [2, 4, 5],          // Can Line 70g: filler, tunnel, packer
        3 => [3, 4, 6],          // Can Line 400g
        2 => [16, 10, 55, 56],   // Biscuit Line 1 (LINE 01): Lamination Line 1, Oven 1, packing, packaging
        4 => [17, 11, 57, 58],   // Biscuit Line 2 (LINE 02): Lamination Line 2, Oven 2
        5 => [50, 12, 59, 60],   // Biscuit Line 3 (LINE 03): Rotary Molding 1, Oven 3
        6 => [51, 13, 61, 62],   // Biscuit Line 4 (LINE 04): Rotary Molding 2, Oven 4
        7 => [18, 14, 63, 64],   // Biscuit Line 5 (LINE 05): Lamination Line 3, Oven 5
        9 => [52, 53, 65, 66],   // Naya Vertical Line: Lamination Line 4, Oven 6
        8 => [15, 49, 67, 68],   // Wafer Line (WAFER): Franz Haas oven, cream station
    ];

    public function run(): void
    {
        // A line takes stations of its own department: refuse before writing.
        $wrong = [];

        foreach (self::LINES as $lineId => $stationIds) {
            $line = Line::withTrashed()->findOrFail($lineId);

            foreach ($stationIds as $stationId) {
                if (self::FACTORIES[self::STATIONS[$stationId][6]][0] !== (int) $line->department_id) {
                    $wrong[] = self::STATIONS[$stationId][0] . " on {$line->name}";
                }
            }
        }

        if ($wrong) {
            throw new RuntimeException('Stations of another department than their line: ' . implode('; ', $wrong) . '.');
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
        });
    }
}
