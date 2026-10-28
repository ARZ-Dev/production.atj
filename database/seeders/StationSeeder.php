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
     * A station's rate is per machine, in the unit its stage counts.
     *
     * Massa Tomate — "Equipment and capacity Massa tomate.xlsx" (all Allan,
     * China, but the label machines): the mixing line (preparation equipment,
     * on no line — the Mixing preparation runs it), each can line's filler and
     * packaging machine at their sheet rates, the one pasteurizing tunnel on
     * both can lines (no rate: "based on pasteurizing time"), and the label
     * machines, on no line: shared by both lines and which products get a
     * label isn't known.
     *
     * Bolacha — "Equipment and capacity Biscuit factory.xlsx": the five ovens,
     * one per Biscuit Line (the user's choice, 2026-10-28), and the Franz Haas
     * wafer line on the Wafer Line, whose 40 plates/min the wafer recipes' kg
     * can't use. The sheet doesn't say which of the 3 lamination lines or the
     * 20 wrappers serve which oven, so they're on no line and have no rate
     * (the user's choice): attach them on each line's Stations page once
     * known. The packing-machine figures (pcs/min, packaging style, what each
     * uses) are kept in their notes. The mixers are preparation equipment.
     *
     * Each station is in its sheet's department and factory (FACTORIES —
     * the parent's ids, the same pair the factory's production line has).
     *
     * Fixed ids, matched on id (soft-deleted ones restored), so re-running
     * updates in place. Each listed line ends up with exactly its machines
     * here, in this order; lines not listed are left alone.
     *
     * @var array<int, array{0: string, 1: string, 2: ?string, 3: ?float, 4: string, 5: ?string, 6: ?string, 7: string}>
     *      id => [name, type, manufacturer, rate, per (hour|minute), rate unit, notes, factory]
     */
    public const STATIONS = [
        // ── Massa Tomate ────────────────────────────────────────────────────
        1  => ['Mixing Line', 'Mixing', 'Allan (China)', 3500, 'hour', 'kg', '3.5 t/h.', 'massa_tomate'],
        2  => ['Filling & Seaming 70g', 'Filling & Seaming', 'Allan (China)', 360, 'minute', 'cans', null, 'massa_tomate'],
        3  => ['Filling & Seaming 400g', 'Filling & Seaming', 'Allan (China)', 130, 'minute', 'cans', null, 'massa_tomate'],
        4  => ['Pasteurizing Tunnel', 'Pasteurizing', 'Allan (China)', null, 'minute', 'cans', 'Based on pasteurizing time.', 'massa_tomate'],
        5  => ['Packaging Machine 70g', 'Packing', 'Allan (China)', 600, 'minute', 'cans', 'Uses cans, cartons, tape.', 'massa_tomate'],
        6  => ['Packaging Machine 400g', 'Packing', 'Allan (China)', 200, 'minute', 'cans', 'Uses cans, cartons, tape.', 'massa_tomate'],
        7  => ['Label Machine Low Speed 1', 'Labelling', 'China', 22, 'minute', 'cans', 'Uses stickers.', 'massa_tomate'],
        8  => ['Label Machine Low Speed 2', 'Labelling', 'China', 22, 'minute', 'cans', 'Uses stickers.', 'massa_tomate'],
        9  => ['Label Machine High Speed', 'Labelling', 'China', 70, 'minute', 'cans', 'Uses stickers.', 'massa_tomate'],

        // ── Bolacha: ovens and the wafer line ───────────────────────────────
        10 => ['Oven 1', 'Cooking', 'Laser', 1200, 'hour', 'kg', '4 zones, cyclothermal.', 'bolacha'],
        11 => ['Oven 2', 'Cooking', 'Laser', 1200, 'hour', 'kg', '4 zones, cyclothermal.', 'bolacha'],
        12 => ['Oven 3', 'Cooking', 'Laser', 2000, 'hour', 'kg', '5 zones: 2 direct flame, 3 cyclothermal.', 'bolacha'],
        13 => ['Oven 4', 'Cooking', 'Laser', 1000, 'hour', 'kg', '3 zones, cyclothermal.', 'bolacha'],
        14 => ['Oven 5', 'Cooking', 'Laser', 1000, 'hour', 'kg', '3 zones, cyclothermal.', 'bolacha'],
        15 => ['Franz Haas Wafer Line', 'Cooking', 'Franz Haas', null, 'minute', null, '40 plates/minute — the plates per box are not known, so no rate in the wafer recipes\' kg.', 'bolacha'],

        // ── Bolacha: lamination lines ───────────────────────────────────────
        16 => ['Lamination Line 1', 'Cutting', 'Laser', null, 'hour', 'kg', null, 'bolacha'],
        17 => ['Lamination Line 2', 'Cutting', 'Laser', null, 'hour', 'kg', null, 'bolacha'],
        18 => ['Lamination Line 3', 'Cutting', 'Laser', null, 'hour', 'kg', null, 'bolacha'],

        // ── Bolacha: packaging machines (wrappers) ──────────────────────────
        19 => ['Flow Wrap 1', 'Packaging', 'Flow Wrap Technology', null, 'minute', 'packets', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        20 => ['Flow Wrap 2', 'Packaging', 'Flow Wrap Technology', null, 'minute', 'packets', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        21 => ['Flow Wrap 3', 'Packaging', 'Flow Wrap Technology', null, 'minute', 'packets', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        22 => ['Flow Wrap 4', 'Packaging', 'Flow Wrap Technology', null, 'minute', 'packets', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        23 => ['Flow Wrap 5', 'Packaging', 'Flow Wrap Technology', null, 'minute', 'packets', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        24 => ['Flow Wrap 6', 'Packaging', 'Flow Wrap Technology', null, 'minute', 'packets', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        25 => ['Flow Wrap 7', 'Packaging', 'Flow Wrap Technology', null, 'minute', 'packets', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        26 => ['Flow Wrap 8', 'Packaging', 'Flow Wrap Technology', null, 'minute', 'packets', '90–110 pcs/min · Edge · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        27 => ['Cavana 1', 'Packaging', 'Cavana 05NKZ', null, 'minute', 'packets', '90–120 pcs/min · Edge · BOPP, tape, box, ribbon.', 'bolacha'],
        28 => ['Cavana 2', 'Packaging', 'Cavana 05NKZ', null, 'minute', 'packets', '90–120 pcs/min · Edge · BOPP, tape, box, ribbon.', 'bolacha'],
        29 => ['Cavana 3', 'Packaging', 'Cavana 05NKZ', null, 'minute', 'packets', '90–120 pcs/min · Edge · BOPP, tape, box, ribbon.', 'bolacha'],
        30 => ['HEBEI 1', 'Packaging', 'HEBEI HBEP30', null, 'minute', 'packets', 'No capacity given · X-fold · BOPP, tape, box, ribbon.', 'bolacha'],
        31 => ['HEBEI 2', 'Packaging', 'HEBEI HBEP30', null, 'minute', 'packets', 'No capacity given · X-fold · BOPP, tape, box, ribbon.', 'bolacha'],
        32 => ['HEBEI 3', 'Packaging', 'HEBEI HBEP30', null, 'minute', 'packets', 'No capacity given · X-fold · BOPP, tape, box, ribbon.', 'bolacha'],
        33 => ['DACHUAN 1', 'Packaging', 'DACHUAN DC250B', null, 'minute', 'packets', '100–110 pcs/min · pile folding · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        34 => ['DACHUAN 2', 'Packaging', 'DACHUAN DC250B', null, 'minute', 'packets', '100–110 pcs/min · pile folding · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        35 => ['DACHUAN 3', 'Packaging', 'DACHUAN DC250B', null, 'minute', 'packets', '100–110 pcs/min · pile folding · BOPP, tape, box, ribbon, ink.', 'bolacha'],
        36 => ['PAKONA 1', 'Packaging', 'PAKONA PK70VL', null, 'minute', 'packets', '55–60 pcs/min · vertical folding · BOPP, tape, box, ribbon.', 'bolacha'],
        37 => ['PAKONA 2', 'Packaging', 'PAKONA PK70VL', null, 'minute', 'packets', '55–60 pcs/min · vertical folding · BOPP, tape, box, ribbon.', 'bolacha'],
        38 => ['PAKONA 3', 'Packaging', 'PAKONA PK70VL', null, 'minute', 'packets', '55–60 pcs/min · vertical folding · BOPP, tape, box, ribbon.', 'bolacha'],

        // ── Bolacha: mixers (preparation equipment) ─────────────────────────
        39 => ['Mixer ZM 500 1', 'Mixing', 'Laser ZM 500', null, 'hour', 'kg', '250 kg a batch.', 'bolacha'],
        40 => ['Mixer ZM 500 2', 'Mixing', 'Laser ZM 500', null, 'hour', 'kg', '250 kg a batch.', 'bolacha'],
        41 => ['Mixer ZM 500 3', 'Mixing', 'Laser ZM 500', null, 'hour', 'kg', '250 kg a batch.', 'bolacha'],
        42 => ['Mixer ZM 500 4', 'Mixing', 'Laser ZM 500', null, 'hour', 'kg', '250 kg a batch.', 'bolacha'],
        43 => ['Mixer ZM 500 5', 'Mixing', 'Laser ZM 500', null, 'hour', 'kg', '250 kg a batch.', 'bolacha'],
        44 => ['Mixer ZM 500 6', 'Mixing', 'Laser ZM 500', null, 'hour', 'kg', '250 kg a batch.', 'bolacha'],
        45 => ['Mixer ZM 500 7', 'Mixing', 'Laser ZM 500', null, 'hour', 'kg', '250 kg a batch.', 'bolacha'],
        46 => ['Mixer ZM 500 8', 'Mixing', 'Laser ZM 500', null, 'hour', 'kg', '250 kg a batch.', 'bolacha'],
        47 => ['Mixer ZM 1100 1', 'Mixing', 'Laser ZM 1100', null, 'hour', 'kg', '500 kg a batch.', 'bolacha'],
        48 => ['Mixer ZM 1100 2', 'Mixing', 'Laser ZM 1100', null, 'hour', 'kg', '500 kg a batch.', 'bolacha'],
    ];

    /** line id => its stations, in the order the product passes them. */
    public const LINES = [
        1 => [2, 4, 5],   // Can Line 70g: filler, tunnel, packer
        3 => [3, 4, 6],   // Can Line 400g
        2 => [10],        // Biscuit Line 1: Oven 1
        4 => [11],        // Biscuit Line 2: Oven 2
        5 => [12],        // Biscuit Line 3: Oven 3
        6 => [13],        // Biscuit Line 4: Oven 4
        7 => [14],        // Biscuit Line 5: Oven 5
        8 => [15],        // Wafer Line: Franz Haas
    ];

    public function run(): void
    {
        // A line takes stations of its own department: refuse before writing.
        $wrong = [];

        foreach (self::LINES as $lineId => $stationIds) {
            $line = Line::withTrashed()->findOrFail($lineId);

            foreach ($stationIds as $stationId) {
                if (self::FACTORIES[self::STATIONS[$stationId][7]][0] !== (int) $line->department_id) {
                    $wrong[] = self::STATIONS[$stationId][0] . " on {$line->name}";
                }
            }
        }

        if ($wrong) {
            throw new RuntimeException('Stations of another department than their line: ' . implode('; ', $wrong) . '.');
        }

        DB::transaction(function () {
            foreach (self::STATIONS as $id => [$name, $type, $manufacturer, $rate, $period, $unit, $notes, $factory]) {
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
                    'rate_unit'       => $unit,
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
