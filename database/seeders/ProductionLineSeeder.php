<?php

namespace Database\Seeders;

use App\Models\ProductionLine;
use Database\Seeders\Concerns\ResolvesParentRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionLineSeeder extends Seeder
{
    use ResolvesParentRecords;

    /**
     * Run the database seeds.
     *
     * Departments and factories (warehouses) are the parent's, looked up by
     * name. The preparations and lines under each production line are
     * attached by PreparationSeeder and LineSeeder.
     */
    public function run(): void
    {
        $productionLines = [
            [
                'id' => 1,
                'name' => 'Cans Tomato Paste Line',
                'department' => 'MT Production Department',
                'factory' => 'Massa Tomate Factory Warehouse',
            ],
            [
                'id' => 2,
                'name' => 'Bolacha Production Line PL1',
                'department' => 'Bolacha - Production',
                'factory' => 'Bolacha Factory',
            ],
        ];

        $rows = array_map(fn ($line) => [
            'id' => $line['id'],
            'name' => $line['name'],
            'department_id' => $this->departmentId($line['department']),
            'factory_id' => $this->warehouseId($line['factory']),
        ], $productionLines);

        $this->failIfParentMissing();

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                ProductionLine::withTrashed()->updateOrCreate(['id' => $row['id']], $row + ['deleted_at' => null]);
            }
        });
    }
}
