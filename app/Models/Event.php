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

    /**
     * The plan of the day this event ends on, when it crosses midnight.
     */
    public function toPlan()
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
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

    public function statusLogs()
    {
        return $this->hasMany(EventStatusLog::class);
    }

    public function quantities()
    {
        return $this->hasMany(EventQuantity::class);
    }

    public function pauseActivities()
    {
        return $this->hasMany(EventPauseActivity::class);
    }

    /** On a preparation event (a mix): the line run it makes dough for. */
    public function feedsEvent()
    {
        return $this->belongsTo(Event::class, 'feeds_event_id');
    }

    /** On a line run: the mixes planned to feed it. */
    public function feedingEvents()
    {
        return $this->hasMany(Event::class, 'feeds_event_id')->orderBy('from_time');
    }

    /** On a line run: its hourly counts, oldest first. */
    public function counts()
    {
        return $this->hasMany(EventCount::class)->orderBy('counted_at')->orderBy('id');
    }

    /**
     * Events still in play on a live plan — planned or running, not ended.
     * Deleting the lane (or production line) one of these sits on would drop
     * it off the board without unplacing it, and strand any stock it holds.
     */
    public function scopeNotEnded($query)
    {
        return $query
            ->where(fn($q) => $q->whereNull('status')->orWhere('status', '!=', 'terminated'))
            ->whereHas('plan');
    }

    /**
     * Refusal message when $count events still sit on a lane about to be
     * deleted, or null when there are none.
     */
    public static function laneInUseMessage(int $count, string $what): ?string
    {
        if (!$count) {
            return null;
        }

        return "{$count} event" . ($count === 1 ? ' is' : 's are') . " still on {$what} (planned or running). "
            . 'Unplace or end ' . ($count === 1 ? 'it' : 'them') . ' first.';
    }
}
