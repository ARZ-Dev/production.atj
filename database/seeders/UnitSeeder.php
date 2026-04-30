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
                'name' => 'Litter',
                'base_unit_id' => null,
                'conversion_factor_to_base' => 1,
                'is_base' => true,
                'is_active' => true,
            ]
        );

        Unit::updateOrCreate(
            ['symbol' => 'ML', 'type' => 'volume'],
            [
                'name' => 'Millilitter',
                'base_unit_id' => $liter->id,
                'conversion_factor_to_base' => 0.001,
                'is_base' => false,
                'is_active' => true,
            ]
        );

        Unit::updateOrCreate(
            ['symbol' => 'Gallon', 'type' => 'volume'],
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
            ['symbol' => 'Bag 50kg', 'type' => 'weight'],
            [
                'name' => 'Bag 50kg',
                'base_unit_id' => $kg->id,
                'conversion_factor_to_base' => 50,
                'is_base' => false,
                'is_active' => true,
            ]
        );

        Unit::updateOrCreate(
            ['symbol' => 'Bag 100kg', 'type' => 'weight'],
            [
                'name' => 'Bag 100kg',
                'base_unit_id' => $kg->id,
                'conversion_factor_to_base' => 100,
                'is_base' => false,
                'is_active' => true,
            ]
        );

    }
}