<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function monthPlan()
    {
        return $this->belongsTo(MonthPlan::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Events from earlier days that end on this plan's day.
     */
    public function carryOverEvents()
    {
        return $this->hasMany(Event::class, 'to_plan_id');
    }
}
