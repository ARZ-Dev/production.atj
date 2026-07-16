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

    public function quantities()
    {
        return $this->hasMany(EventQuantity::class);
    }

    /**
     * Activities recorded while the event was paused under this log
     * (Cleaning, Maintenance, …).
     */
    public function pauseActivities()
    {
        return $this->hasMany(EventPauseActivity::class);
    }
}
