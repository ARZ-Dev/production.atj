<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeType extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'item_type_ids' => 'array',
        'side_item_type_ids' => 'array',
        'output_item_type_ids' => 'array',
    ];

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }
}
