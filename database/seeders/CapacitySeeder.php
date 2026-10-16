<?php

namespace Database\Seeders;

use App\Models\Capacity;
use App\Models\Line;
use App\Models\PackagingCapacity;
use App\Models\Preparation;
use Illuminate\Database\Seeder;

class CapacitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Item, unit and item type ids are the parent's, named in the comment on
     * each row. Runs after PreparationSeeder, LineSeeder and PackagingSeeder.
     *
     * A preparation / line keeps one capacity per item, in its basic unit
     * (item_unit_id is the unit it was entered in); a packaging keeps one per
     * item unit. Matched on those keys, so re-running updates in place.
     */
    public function run(): void
    {
        $capacities = [
            // Preparation 1 — Mixing Area
            ['model' => Preparation::class, 'id' => 1, 'item_type_id' => 12, 'item_id' => 122, 'item_unit_id' => 150, 'capacity' => 200],  // test — SFG BOLACA
            ['model' => Preparation::class, 'id' => 1, 'item_type_id' => 12, 'item_id' => 119, 'item_unit_id' => 147, 'capacity' => 3500], // 402000003 — Tomato Paste Bulk Brix 22-24 (KG)
            ['model' => Preparation::class, 'id' => 1, 'item_type_id' => 12, 'item_id' => 120, 'item_unit_id' => 148, 'capacity' => 100],  // MTTP — Mama Tia Tomato Paste (KG)
            // Preparation 2 — Bolacha P1
            ['model' => Preparation::class, 'id' => 2, 'item_type_id' => 16, 'item_id' => 129, 'item_unit_id' => 157, 'capacity' => 200],  // SFGB — Semi-Finished-Good 1 Bolacha
            // Line 1 — Filling Machine
            ['model' => Line::class, 'id' => 1, 'item_type_id' => 7, 'item_id' => 15, 'item_unit_id' => 29, 'capacity' => 1000],     // 403800002 — KETCHUP DOURADO 450 ML
            ['model' => Line::class, 'id' => 1, 'item_type_id' => 1, 'item_id' => 123, 'item_unit_id' => 151, 'capacity' => 2000],   // terst prod — test prod
            ['model' => Line::class, 'id' => 1, 'item_type_id' => 14, 'item_id' => 124, 'item_unit_id' => 152, 'capacity' => 2000],  // PFGB — Pre Finished Good Bolacha
            // Line 2 — Prod Boalcha L1
            ['model' => Line::class, 'id' => 2, 'item_type_id' => 17, 'item_id' => 130, 'item_unit_id' => 158, 'capacity' => 6500],  // PFGB2 — Pre-Finished-Good 1 Bolacha
        ];

        foreach ($capacities as $row) {
            Capacity::updateOrCreate(
                ['capacityable_type' => $row['model'], 'capacityable_id' => $row['id'], 'item_id' => $row['item_id']],
                ['item_type_id' => $row['item_type_id'], 'item_unit_id' => $row['item_unit_id'], 'capacity' => $row['capacity']]
            );
        }

        $packagingCapacities = [
            ['packaging_id' => 1, 'item_type_id' => 1, 'item_id' => 123, 'item_unit_id' => 151, 'capacity' => 750],  // PK 1 Bolacha — test prod
            ['packaging_id' => 2, 'item_type_id' => 1, 'item_id' => 123, 'item_unit_id' => 151, 'capacity' => 2000], // PK 2 Bolacha — test prod
        ];

        foreach ($packagingCapacities as $row) {
            PackagingCapacity::updateOrCreate(
                ['packaging_id' => $row['packaging_id'], 'item_unit_id' => $row['item_unit_id']],
                ['item_type_id' => $row['item_type_id'], 'item_id' => $row['item_id'], 'capacity' => $row['capacity']]
            );
        }
    }
}
