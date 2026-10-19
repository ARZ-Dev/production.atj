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
        $this->call(EventTypeSeeder::class);
        // The earlier test recipes: fixed ids (recipe types 1–3, recipes 1–2) that
        // would overwrite the Massa Tomate rows on a database already seeded.
        // $this->call(RecipeTypeSeeder::class);
        // $this->call(RecipeSeeder::class);
        $this->call(FactoryRecipeTypeSeeder::class);
        $this->call(MassaTomateRecipeSeeder::class);
        $this->call(BolachaRecipeSeeder::class);
        $this->call(ShiftSeeder::class);
        $this->call(ProductionLineSeeder::class);
        $this->call(PreparationSeeder::class);
        $this->call(LineSeeder::class);
        $this->call(PackagingSeeder::class);
        $this->call(CapacitySeeder::class);
    }
}
