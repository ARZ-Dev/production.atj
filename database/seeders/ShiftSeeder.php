<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $shifts = [
                ['name' => 'Morning Shift', 'company_id' => 1 , 'from_time' => '06:00:00', 'to_time' => '14:00:00'],
                ['name' => 'Afternoon Shift', 'company_id' => 1 , 'from_time' => '14:00:00', 'to_time' => '22:00:00'],
                ['name' => 'Night Shift', 'company_id' => 1 , 'from_time' => '22:00:00', 'to_time' => '06:00:00'],
            ];
    
            foreach ($shifts as $shift) {
                \App\Models\Shift::create($shift);
            }
    }
}
