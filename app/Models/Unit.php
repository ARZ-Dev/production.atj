<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function reportRawMaterials()
    {
        return $this->hasMany(ReportRawMaterial::class, 'unit_id');
    }

    public function recipeInputs()
    {
        return $this->hasMany(RecipeInput::class,'raw_material_unit_id');
    }
}
