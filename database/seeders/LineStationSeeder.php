<?php

namespace Database\Seeders;

use App\Models\LineStation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LineStationSeeder extends Seeder
{
    /**
     * Superseded 2026-10-28 (later that day) by StationTypeSeeder + StationSeeder: stations are
     * machines of their own now, which a line picks, and line_stations /
     * LineStation are gone — this file is kept as the record, its call
     * commented out of DatabaseSeeder, and won't run any more.
     *
     * The machines of each line, in the order the product passes them. A
     * recipe's stages run on the stations of the same name, and a station's
     * rate is per machine in the unit its stage counts its output in (cans,
     * kg, packets, boxes). A station without a rate doesn't limit the line.
     *
     * Massa Tomate — "Equipment and capacity Massa tomate.xlsx": the fillers
     * (360 / 130 cans/min) and packaging machines (600 / 200 cans/min) with
     * their rates; the pasteurizing tunnel between them without one ("based
     * on pasteurizing time"). The label machines (2 × 22 and 1 × 70 cans/min)
     * are left out: shared by both lines, slower than any of them, and which
     * products get a label isn't known.
     *
     * Bolacha — "Equipment and capacity Biscuit factory.xlsx": each line's
     * oven at its sheet rate in kg of biscuits an hour. The sheet doesn't say
     * which of the 3 lamination lines or the 20 wrappers serve which oven, so
     * Cutting, Packaging and Packing have no rate yet (the user's choice,
     * 2026-10-28): the oven sets the line's speed until they're assigned on the
     * line's Stations page. Creaming is where the two-part products' cream
     * goes in. The wafer line's 40 plates/min can't be used as a rate — the
     * wafer recipes count kg, and the plates per box aren't known.
     *
     * Matched on line + name, so re-running updates in place; a station a
     * line no longer lists is removed.
     */
    public function run(): void
    {
        $canLine = fn(string $size, float $filler, float $packer) => [
            ['name' => 'Filling & Seaming', 'machine' => "Filling & seaming {$size} — Allan (China)", 'machine_count' => 1, 'rate' => $filler, 'rate_period' => 'minute', 'rate_unit' => 'cans'],
            ['name' => 'Pasteurizing', 'machine' => 'Pasteurizing tunnel — Allan (China)', 'machine_count' => 1, 'rate' => null, 'rate_period' => 'minute', 'rate_unit' => 'cans'],
            ['name' => 'Packing', 'machine' => "Packaging machine {$size} — Allan (China)", 'machine_count' => 1, 'rate' => $packer, 'rate_period' => 'minute', 'rate_unit' => 'cans'],
        ];

        $biscuitLine = fn(string $oven, ?float $ovenRate, string $unit = 'kg') => [
            ['name' => 'Cutting', 'machine' => 'Lamination & cutting', 'machine_count' => 1, 'rate' => null, 'rate_period' => 'hour', 'rate_unit' => 'kg'],
            ['name' => 'Cooking', 'machine' => $oven, 'machine_count' => 1, 'rate' => $ovenRate, 'rate_period' => 'hour', 'rate_unit' => $unit],
            ['name' => 'Creaming', 'machine' => null, 'machine_count' => 1, 'rate' => null, 'rate_period' => 'hour', 'rate_unit' => 'kg'],
            ['name' => 'Packaging', 'machine' => 'Flow wrappers', 'machine_count' => 1, 'rate' => null, 'rate_period' => 'minute', 'rate_unit' => 'packets'],
            ['name' => 'Packing', 'machine' => null, 'machine_count' => 1, 'rate' => null, 'rate_period' => 'hour', 'rate_unit' => 'boxes'],
        ];

        $stations = [
            1 => $canLine('70g', 360, 600),   // Can Line 70g
            3 => $canLine('400g', 130, 200),  // Can Line 400g
            2 => $biscuitLine('Oven — Laser, 4 zones cyclothermal', 1200),                         // Biscuit Line 1
            4 => $biscuitLine('Oven — Laser, 4 zones cyclothermal', 1200),                         // Biscuit Line 2
            5 => $biscuitLine('Oven — Laser, 5 zones (2 direct flame, 3 cyclothermal)', 2000),     // Biscuit Line 3
            6 => $biscuitLine('Oven — Laser, 3 zones cyclothermal', 1000),                         // Biscuit Line 4
            7 => $biscuitLine('Oven — Laser, 3 zones cyclothermal', 1000),                         // Biscuit Line 5
            8 => $biscuitLine('Wafer line — Franz Haas (40 plates/min)', null),                    // Wafer Line
        ];

        DB::transaction(function () use ($stations) {
            foreach ($stations as $lineId => $rows) {
                $kept = [];

                foreach (array_values($rows) as $i => $row) {
                    $kept[] = LineStation::updateOrCreate(
                        ['line_id' => $lineId, 'name' => $row['name']],
                        $row + ['sequence' => $i + 1]
                    )->id;
                }

                LineStation::where('line_id', $lineId)->whereNotIn('id', $kept)->delete();
            }
        });
    }
}
