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

    public function preperationItem()
    {
        return $this->belongsTo(PreperationItem::class);
    }

    public function preperationItemUnit()
    {
        return $this->belongsTo(PreperationItemUnit::class);
    }


}
