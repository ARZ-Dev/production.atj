<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * What a station does — Cutting, Cooking, Packaging … A recipe stage names
 * one, and runs on the machines of that type the line has. Its rate unit is
 * the default for its machines' rates.
 */
class StationType extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function stations()
    {
        return $this->hasMany(Station::class)->orderBy('name');
    }

    public function recipeStages()
    {
        return $this->hasMany(RecipeStage::class);
    }
}
