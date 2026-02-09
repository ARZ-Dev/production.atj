<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'ArzGt',
                'phone' => '+1234567890',
                'description' => 'A leading company in tech solutions.',
                'address' => '123 Tech Avenue, Silicon City'
            ],
        ];

        foreach ($companies as $companyData) {
            Company::updateOrCreate($companyData);
        }
    }
}
