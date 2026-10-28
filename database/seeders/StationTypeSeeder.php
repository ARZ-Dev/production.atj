<?php

namespace Database\Seeders;

use App\Models\StationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StationTypeSeeder extends Seeder
{
    /**
     * What a station does. A recipe stage names one and runs on the line's
     * stations of it; the rate unit is the default for its stations' rates.
     *
     * Fixed ids, in the order the 2026_10_29 migration creates them from the
     * old per-line stations (so a migrated database and a fresh one agree),
     * then Labelling and Mixing, which no line had. Order is the position in
     * a line, for the dropdowns. Matched on id, so re-running renames in
     * place (soft-deleted rows restored).
     *
     * @var array<int, array{0: string, 1: int, 2: ?string, 3: ?string}> id => [name, order, rate unit, description]
     */
    public const TYPES = [
        1 => ['Filling & Seaming', 50, 'cans', 'Fills the cans and seams the lids on.'],
        2 => ['Pasteurizing', 60, 'cans', 'Heats the sealed cans; runs on pasteurizing time.'],
        3 => ['Packing', 90, 'boxes', 'Puts the packets or cans into boxes / cartons.'],
        4 => ['Cutting', 20, 'kg', 'Laminates the dough sheet and cuts the biscuits.'],
        5 => ['Cooking', 30, 'kg', 'Bakes — the ovens and the wafer line.'],
        6 => ['Creaming', 40, 'kg', 'Puts the cream between two-part products.'],
        7 => ['Packaging', 80, 'packets', 'Wraps the biscuits into packets (flow wrap, folding).'],
        8 => ['Labelling', 70, 'cans', 'Puts the labels on the cans.'],
        9 => ['Mixing', 10, 'kg', 'Mixes doughs, creams and paste — preparation equipment.'],
    ];

    /** The fixed id of a type by its name. */
    public static function idOf(string $name): int
    {
        foreach (self::TYPES as $id => [$typeName]) {
            if ($typeName === $name) {
                return $id;
            }
        }

        throw new RuntimeException("Station type \"{$name}\" isn't in StationTypeSeeder::TYPES.");
    }

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::TYPES as $id => [$name, $sequence, $unit, $description]) {
                $type = StationType::withTrashed()->firstOrNew(['id' => $id]);
                $type->fill([
                    'name'        => $name,
                    'sequence'    => $sequence,
                    'rate_unit'   => $unit,
                    'description' => $description,
                ]);
                $type->deleted_at = null;
                $type->save();
            }
        });
    }
}
