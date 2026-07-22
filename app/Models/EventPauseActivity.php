<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventPauseActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The pause log this activity was recorded under.
     */
    public function statusLog()
    {
        return $this->belongsTo(EventStatusLog::class, 'event_status_log_id');
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Items consumed while carrying out this emergency event.
     */
    public function quantities()
    {
        return $this->hasMany(EventQuantity::class, 'event_pause_activity_id');
    }
}
