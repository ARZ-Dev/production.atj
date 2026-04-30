<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | VOLUME (Base = Liter)
        |--------------------------------------------------------------------------
        */
        $liter = Unit::updateOrCreate(
            ['symbol' => 'L', 'type' => 'volume'],
            [
                'name' => 'Liter',
                'base_unit_id' => null,
                'conversion_factor_to_base' => 1,
                'is_base' => true,
                'is_active' => true,
            ]
        );

        Unit::updateOrCreate(
            ['symbol' => 'mL', 'type' => 'volume'],
            [
                'name' => 'Milliliter',
                'base_unit_id' => $liter->id,
                'conversion_factor_to_base' => 0.001,
                'is_base' => false,
                'is_active' => true,
            ]
        );

        Unit::updateOrCreate(
            ['symbol' => 'gal', 'type' => 'volume'],
            [
                'name' => 'Gallon',
                'base_unit_id' => $liter->id,
                'conversion_factor_to_base' => 3.78,
                'is_base' => false,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | WEIGHT (Base = Kilogram)
        |--------------------------------------------------------------------------
        */
        $kg = Unit::updateOrCreate(
            ['symbol' => 'kg', 'type' => 'weight'],
            [
                'name' => 'Kilogram',
                'base_unit_id' => null,
                'conversion_factor_to_base' => 1,
                'is_base' => true,
                'is_active' => true,
            ]
        );

        Unit::updateOrCreate(
            ['symbol' => 'g', 'type' => 'weight'],
            [
                'name' => 'Gram',
                'base_unit_id' => $kg->id,
                'conversion_factor_to_base' => 0.001,
                'is_base' => false,
                'is_active' => true,
            ]
        );

        Unit::updateOrCreate(
            ['symbol' => 'ton', 'type' => 'weight'],
            [
                'name' => 'Ton',
                'base_unit_id' => $kg->id,
                'conversion_factor_to_base' => 1000,
                'is_base' => false,
                'is_active' => true,
            ]
        );

    }
}