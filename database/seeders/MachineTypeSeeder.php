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
            ['company_id' => 1, 'name' => 'Preparation'],
            ['company_id' => 1, 'name' => 'Assembly'],
            ['company_id' => 1, 'name' => 'Finishing'],
            ['company_id' => 1, 'name' => 'Inspection'],
        ];

        foreach ($machineTypes as $type) {
           MachineType::updateOrCreate(['name' => $type['name'], 'company_id' => $type['company_id']], $type);
        }
    }
}
