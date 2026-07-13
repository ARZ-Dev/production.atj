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
        $this->call(RecipeTypeSeeder::class);
        $this->call(ShiftSeeder::class);
    }
}
