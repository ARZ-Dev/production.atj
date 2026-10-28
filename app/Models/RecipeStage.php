<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One step of a recipe run on a line (Cutting, Cooking, Packaging, Packing …),
 * matched by name to the line's stations. output_quantity is what the stage
 * makes per batch, in output_unit: the station's rate is read in that unit.
 */
class RecipeStage extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'output_quantity' => 'float',
        'rework_factor'   => 'float',
        'sequence'        => 'integer',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function inputs()
    {
        return $this->hasMany(RecipeInput::class);
    }

    /** Rejects can be reworked only when the stage says into what. */
    public function canRework(): bool
    {
        return (bool) ($this->rework_item_id && $this->rework_item_unit_id);
    }
}
