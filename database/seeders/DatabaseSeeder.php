<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        // $this->call(UnitSeeder::class);
        // $this->call(RawMaterialSeeder::class);

        // $this->call(MachineTypeSeeder::class);
        // The event types configured by hand before 2026-10-16 — replaced by
        // FactoryEventTypeSeeder, which reuses their ids.
        // $this->call(EventTypeSeeder::class);
        $this->call(FactoryEventTypeSeeder::class);
        // The earlier test recipes: fixed ids (recipe types 1–3, recipes 1–2) that
        // would overwrite the Massa Tomate rows on a database already seeded.
        // $this->call(RecipeTypeSeeder::class);
        // $this->call(RecipeSeeder::class);
        $this->call(FactoryRecipeTypeSeeder::class);
        $this->call(MassaTomateRecipeSeeder::class);
        // Line recipes' stages name a station type.
        $this->call(StationTypeSeeder::class);
        // Since 2026-10-28 a production run is one event across its line's
        // stations: the line recipes (in stages) replace the production and
        // packaging recipes, reading their figures.
        // $this->call(MassaTomateProductionRecipeSeeder::class);
        $this->call(MassaTomateLineRecipeSeeder::class);
        $this->call(BolachaRecipeSeeder::class);
        // $this->call(BolachaProductionRecipeSeeder::class);
        // $this->call(BolachaPackagingRecipeSeeder::class);
        $this->call(BolachaLineRecipeSeeder::class);
        $this->call(ShiftSeeder::class);
        $this->call(ProductionLineSeeder::class);
        $this->call(PreparationSeeder::class);
        $this->call(LineSeeder::class);
        // Since 2026-10-28 (later) a line picks its machines from the stations list.
        // $this->call(LineStationSeeder::class);
        $this->call(StationSeeder::class);
        // Packing is the lines' last station now; no packaging lanes are seeded.
        // $this->call(PackagingSeeder::class);
        $this->call(CapacitySeeder::class);
    }
}
