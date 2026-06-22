<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function recipeType()
    {
        return $this->belongsTo(RecipeType::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function placeable()
    {
        return $this->morphTo();
    }
}
