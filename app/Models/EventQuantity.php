<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventQuantity extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The Stock In / Waste document the unused part of this quantity was
     * filed on when the event ended. Only set on `input` rows of events whose
     * type is flagged `start_items_unverifiable`.
     */
    public function remainingDocument()
    {
        return $this->morphTo();
    }

    public function statusLog()
    {
        return $this->belongsTo(EventStatusLog::class, 'event_status_log_id');
    }

    public function pauseActivity()
    {
        return $this->belongsTo(EventPauseActivity::class, 'event_pause_activity_id');
    }

    public function recipeInput()
    {
        return $this->belongsTo(RecipeInput::class);
    }

    public function recipeSideProduct()
    {
        return $this->belongsTo(RecipeSideProduct::class);
    }
}
