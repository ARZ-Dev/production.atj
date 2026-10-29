<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One step of a recipe run on a line (Cutting, Cooking, Packaging, Packing …).
 * It names a station type and runs on the line's machines of that type; name
 * is the type's name, kept as written when the stage was saved.
 * output_quantity is what the stage makes per batch, in one of the product's
 * units (output_item_unit_id) or in kg when that is null. The machines' rates
 * are in the basic unit, so a box or case converts with its unit's formula.
 * stock_leftovers: what's left on the stage at End goes into stock.
 */
class RecipeStage extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'output_quantity' => 'float',
        'rework_factor'   => 'float',
        'stock_leftovers' => 'boolean',
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

    public function stationType()
    {
        return $this->belongsTo(StationType::class)->withTrashed();
    }

    /** Rejects can be reworked only when the stage says into what. */
    public function canRework(): bool
    {
        return (bool) ($this->rework_item_id && $this->rework_item_unit_id);
    }
}
