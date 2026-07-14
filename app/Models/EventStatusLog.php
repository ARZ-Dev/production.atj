<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventStatusLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The event type describing why the event was paused
     * (Cleaning, Maintenance, …).
     */
    public function pauseEventType()
    {
        return $this->belongsTo(EventType::class, 'pause_event_type_id');
    }

    public function quantities()
    {
        return $this->hasMany(EventQuantity::class);
    }
}
