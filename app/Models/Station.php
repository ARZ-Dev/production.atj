<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A machine — Oven 1, Flow Wrap 3 … — of one station type, with its own rate
 * (per hour or minute, in its rate unit, else its type's), in a department
 * and its factory (department_id / factory_id: the parent's department and
 * warehouse of type factory, as on a production line). A line is made of
 * the machines attached to it, from its own department; its rate at a stage
 * is the sum of its machines of that stage's type.
 */
class Station extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'rate'          => 'float',
        'department_id' => 'integer',
        'factory_id'    => 'integer',
    ];

    public function type()
    {
        return $this->belongsTo(StationType::class, 'station_type_id')->withTrashed();
    }

    public function lines()
    {
        return $this->belongsToMany(Line::class, 'line_station')->withPivot('sequence')->withTimestamps();
    }

    /** Per hour; null when no rate is set. */
    public function hourlyRate(): ?float
    {
        if (!$this->rate || $this->rate <= 0) {
            return null;
        }

        return $this->rate * ($this->rate_period === 'minute' ? 60 : 1);
    }

    public function rateUnit(): ?string
    {
        return $this->rate_unit ?: $this->type?->rate_unit;
    }

    /** "360 cans/min", or null when no rate is set. */
    public function rateLabel(): ?string
    {
        if (!$this->rate || $this->rate <= 0) {
            return null;
        }

        $rate = rtrim(rtrim(number_format($this->rate, 4), '0'), '.');

        return trim($rate . ' ' . $this->rateUnit()) . '/' . ($this->rate_period === 'minute' ? 'min' : 'h');
    }
}
