<?php

namespace Database\Seeders;

use App\Models\MachineType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MachineTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $machineTypes =[
            ['name' => 'Preparation'],
            ['name' => 'Assembly'],
            ['name' => 'Finishing'],
            ['name' => 'Inspection'],
        ];

        foreach ($machineTypes as $type) {
           MachineType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
