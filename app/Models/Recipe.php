<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{

    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function inputs()
    {
        return $this->hasMany(RecipeInput::class);
    }

    /**
     * The steps of a recipe run on a line, in order. A recipe with stages is
     * a line run: one event across the line's stations.
     */
    public function stages()
    {
        return $this->hasMany(RecipeStage::class)->orderBy('sequence')->orderBy('id');
    }

    public function sideProducts()
    {
        return $this->hasMany(RecipeSideProduct::class);
    }

    public function recipeType()
    {
        return $this->belongsTo(RecipeType::class);
    }

}
