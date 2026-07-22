<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventQuantity extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function event()
    {
        return $this->belongsTo(Event::class);
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
