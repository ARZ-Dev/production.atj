<?php

namespace Database\Seeders;

use App\Models\Capacity;
use App\Models\Line;
use App\Models\PackagingCapacity;
use App\Models\Preparation;
use Database\Seeders\Concerns\ResolvesParentRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CapacitySeeder extends Seeder
{
    use ResolvesParentRecords;

    /**
     * Run the database seeds.
     *
     * Items are the parent's, looked up by code, and units by name on that
     * item; the item type is the item's own. Runs after PreparationSeeder,
     * LineSeeder and PackagingSeeder.
     *
     * A preparation / line keeps one capacity per item, in its basic unit
     * (the unit is the one it was entered in); a packaging keeps one per item
     * unit. Matched on those keys, so re-running updates in place.
     */
    public function run(): void
    {
        $capacities = [
            // Preparation 1 — Mixing Area
            ['model' => Preparation::class, 'id' => 1, 'item' => 'test', 'unit' => 'test', 'capacity' => 200],       // SFG BOLACA
            ['model' => Preparation::class, 'id' => 1, 'item' => '402000003', 'unit' => 'KG', 'capacity' => 3500],   // Tomato Paste Bulk Brix 22-24
            ['model' => Preparation::class, 'id' => 1, 'item' => 'MTTP', 'unit' => 'KG', 'capacity' => 100],         // Mama Tia Tomato Paste
            // Preparation 2 — Bolacha P1
            ['model' => Preparation::class, 'id' => 2, 'item' => 'SFGB', 'unit' => 'basic', 'capacity' => 200],      // Semi-Finished-Good 1 Bolacha
            // Line 1 — Filling Machine
            ['model' => Line::class, 'id' => 1, 'item' => '403800002', 'unit' => '450ML', 'capacity' => 1000],       // KETCHUP DOURADO 450 ML
            ['model' => Line::class, 'id' => 1, 'item' => 'terst prod', 'unit' => 'prod testr', 'capacity' => 2000], // test prod
            ['model' => Line::class, 'id' => 1, 'item' => 'PFGB', 'unit' => '1 piece', 'capacity' => 2000],          // Pre Finished Good Bolacha
            // Line 2 — Prod Boalcha L1
            ['model' => Line::class, 'id' => 2, 'item' => 'PFGB2', 'unit' => '10g', 'capacity' => 6500],             // Pre-Finished-Good 1 Bolacha
        ];

        $packagingCapacities = [
            ['packaging_id' => 1, 'item' => 'terst prod', 'unit' => 'prod testr', 'capacity' => 750],  // PK 1 Bolacha — test prod
            ['packaging_id' => 2, 'item' => 'terst prod', 'unit' => 'prod testr', 'capacity' => 2000], // PK 2 Bolacha — test prod
        ];

        $resolve = function (array $row) {
            $item = $this->parentItem($row['item']);

            return $row + [
                'item_id' => $item['id'] ?? null,
                'item_type_id' => $item['item_type_id'] ?? null,
                'item_unit_id' => $this->parentUnitId($row['item'], $row['unit']),
            ];
        };
        $capacities = array_map($resolve, $capacities);
        $packagingCapacities = array_map($resolve, $packagingCapacities);

        $this->failIfParentMissing();

        DB::transaction(function () use ($capacities, $packagingCapacities) {
            foreach ($capacities as $row) {
                Capacity::updateOrCreate(
                    ['capacityable_type' => $row['model'], 'capacityable_id' => $row['id'], 'item_id' => $row['item_id']],
                    ['item_type_id' => $row['item_type_id'], 'item_unit_id' => $row['item_unit_id'], 'capacity' => $row['capacity']]
                );
            }

            foreach ($packagingCapacities as $row) {
                PackagingCapacity::updateOrCreate(
                    ['packaging_id' => $row['packaging_id'], 'item_unit_id' => $row['item_unit_id']],
                    ['item_type_id' => $row['item_type_id'], 'item_id' => $row['item_id'], 'capacity' => $row['capacity']]
                );
            }
        });
    }
}
