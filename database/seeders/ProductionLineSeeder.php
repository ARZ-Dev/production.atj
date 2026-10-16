<?php

namespace Database\Seeders;

use App\Models\ProductionLine;
use Illuminate\Database\Seeder;

class ProductionLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Department and factory (warehouse) ids are the parent's, named in the
     * comment on each row. The preparations and lines under each production
     * line are attached by PreparationSeeder and LineSeeder.
     */
    public function run(): void
    {
        $productionLines = [
            [
                'id' => 1,
                'name' => 'Cans Tomato Paste Line',
                'department_id' => 14, // MT Production Department
                'factory_id' => 1,     // Massa Tomate Factory Warehouse
            ],
            [
                'id' => 2,
                'name' => 'Bolacha Production Line PL1',
                'department_id' => 5,  // Bolacha - Production
                'factory_id' => 4,     // Bolacha Factory
            ],
        ];

        foreach ($productionLines as $productionLine) {
            ProductionLine::withTrashed()->updateOrCreate(
                ['id' => $productionLine['id']],
                $productionLine + ['deleted_at' => null]
            );
        }
    }
}
