<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeInput extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    /** On a recipe with stages: the stage that uses this input. */
    public function stage()
    {
        return $this->belongsTo(RecipeStage::class, 'recipe_stage_id');
    }

}
