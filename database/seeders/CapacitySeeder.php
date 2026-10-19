<?php

namespace Database\Seeders;

use App\Models\Capacity;
use App\Models\Line;
use App\Models\PackagingCapacity;
use App\Models\Preparation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CapacitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Item type, item and unit ids are the parent's (erp_auth), as a fresh
     * auth-service seed assigns them — this app can't read the parent's
     * database on the server, so they're written out; keep them in step with
     * auth-service's item seeders. The item type is the item's own. Runs
     * after PreparationSeeder, LineSeeder and PackagingSeeder.
     *
     * A preparation / line keeps one capacity per item, in its basic unit
     * (the unit is the one it was entered in); a packaging keeps one per item
     * unit. Matched on those keys, so re-running updates in place.
     */
    public function run(): void
    {
        $capacities = [
            // Preparation 1 — Mixing Area
            ['model' => Preparation::class, 'id' => 1, 'item_type_id' => 12, 'item_id' => 122, 'item_unit_id' => 150, 'capacity' => 200],   // test — SFG BOLACA, unit "test"
            ['model' => Preparation::class, 'id' => 1, 'item_type_id' => 12, 'item_id' => 119, 'item_unit_id' => 147, 'capacity' => 3500],  // 402000003 — Tomato Paste Bulk Brix 22-24, KG
            ['model' => Preparation::class, 'id' => 1, 'item_type_id' => 12, 'item_id' => 120, 'item_unit_id' => 148, 'capacity' => 100],   // MTTP — Mama Tia Tomato Paste, KG
            // Preparation 2 — Bolacha P1
            ['model' => Preparation::class, 'id' => 2, 'item_type_id' => 16, 'item_id' => 129, 'item_unit_id' => 157, 'capacity' => 200],   // SFGB — Semi-Finished-Good 1 Bolacha, basic
            // Line 1 — Filling Machine
            ['model' => Line::class, 'id' => 1, 'item_type_id' => 7, 'item_id' => 15, 'item_unit_id' => 29, 'capacity' => 1000],      // 403800002 — KETCHUP DOURADO 450 ML, 450ML
            ['model' => Line::class, 'id' => 1, 'item_type_id' => 1, 'item_id' => 123, 'item_unit_id' => 151, 'capacity' => 2000],    // terst prod — test prod, prod testr
            ['model' => Line::class, 'id' => 1, 'item_type_id' => 17, 'item_id' => 124, 'item_unit_id' => 152, 'capacity' => 2000],   // PFGB — Pre Finished Good Bolacha, 1 piece
            // Line 2 — Prod Boalcha L1
            ['model' => Line::class, 'id' => 2, 'item_type_id' => 17, 'item_id' => 130, 'item_unit_id' => 158, 'capacity' => 6500],   // PFGB2 — Pre-Finished-Good 1 Bolacha, 10g
        ];

        $packagingCapacities = [
            ['packaging_id' => 1, 'item_type_id' => 1, 'item_id' => 123, 'item_unit_id' => 151, 'capacity' => 750],   // PK 1 Bolacha — terst prod (test prod), prod testr
            ['packaging_id' => 2, 'item_type_id' => 1, 'item_id' => 123, 'item_unit_id' => 151, 'capacity' => 2000],  // PK 2 Bolacha — terst prod (test prod), prod testr
        ];

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
