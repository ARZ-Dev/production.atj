<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An hourly count of a line run: the final station's counter reading, what
 * that made since the previous count, and the target for that time.
 */
class EventCount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'counted_at' => 'datetime',
        'counter'    => 'float',
        'quantity'   => 'float',
        'target'     => 'float',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
