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
            ['name' => 'Preparation'],
            ['name' => 'Production'],
            ['name' => 'Maintenance'],
            ['name' => 'Cleaning'],
        ];

        foreach ($eventTypes as $eventType) {
            \App\Models\EventType::create($eventType);
        }
    }
}
