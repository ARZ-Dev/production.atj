<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Unit;
use App\Models\RawMaterial;

class RawMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $kg = Unit::where('symbol', 'kg')
            ->where('type', 'weight')
            ->firstOrFail();

        $g = Unit::where('symbol', 'g')
            ->where('type', 'weight')
            ->firstOrFail();

        $bag50kg = Unit::where('symbol', 'Bag 50kg')
            ->where('type', 'weight')
            ->firstOrFail();

        $bag100kg = Unit::where('symbol', 'Bag 100kg')
            ->where('type', 'weight')
            ->firstOrFail();

        $materials = [
            [
                'code' => 'RM-FLOUR',
                'name' => 'Flour',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $bag50kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'RM-SUGAR',
                'name' => 'Sugar',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $bag50kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'RM-SALT',
                'name' => 'Salt',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $bag50kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 50,
                'is_active' => true,
            ],
            [
                'code' => 'RM-RICE',
                'name' => 'Rice',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $bag50kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 150,
                'is_active' => true,
            ],
            [
                'code' => 'RM-WHEAT',
                'name' => 'Wheat',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $bag100kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 300,
                'is_active' => true,
            ],
            [
                'code' => 'RM-CORN',
                'name' => 'Corn',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $bag100kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 250,
                'is_active' => true,
            ],
            [
                'code' => 'RM-SEMOLINA',
                'name' => 'Semolina',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $bag50kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'RM-BREADCRUMBS',
                'name' => 'Breadcrumbs',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 25,
                'is_active' => true,
            ],
            [
                'code' => 'RM-COCOA-POWDER',
                'name' => 'Cocoa Powder',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 20,
                'is_active' => true,
            ],
            [
                'code' => 'RM-MILK-POWDER',
                'name' => 'Milk Powder',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $kg->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'RM-BAKING-POWDER',
                'name' => 'Baking Powder',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $g->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'RM-YEAST',
                'name' => 'Yeast',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $g->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'RM-VANILLA-POWDER',
                'name' => 'Vanilla Powder',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $g->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'RM-CINNAMON',
                'name' => 'Cinnamon',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $g->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'RM-BLACK-PEPPER',
                'name' => 'Black Pepper',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $g->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'RM-GARLIC-POWDER',
                'name' => 'Garlic Powder',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $g->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'RM-ONION-POWDER',
                'name' => 'Onion Powder',
                'base_unit_id' => $kg->id,
                'purchase_unit_id' => $g->id,
                'type' => 'solid',
                'density' => null,
                'reorder_point' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($materials as $material) {
            RawMaterial::updateOrCreate(
                ['code' => $material['code']],
                $material
            );
        }
    }
}
