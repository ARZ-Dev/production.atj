<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventTypes = [
            ['name' => 'Preparation', 'company_id' => 1],
            ['name' => 'Production', 'company_id' => 1],
            ['name' => 'Maintenance', 'company_id' => 1],
            ['name' => 'Cleaning', 'company_id' => 1],
        ];

        foreach ($eventTypes as $eventType) {
            \App\Models\EventType::create($eventType);
        }
    }
}
