<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A machine (or a set of identical machines) on a line, in the order the
 * product passes it. Matched by name to the stages of the recipes run on the
 * line; its rate is per machine, in the unit the stage counts its output in.
 */
class LineStation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'rate'          => 'float',
        'machine_count' => 'integer',
        'sequence'      => 'integer',
    ];

    public function line()
    {
        return $this->belongsTo(Line::class);
    }

    /** Every machine of the station together, per hour; null when no rate is set. */
    public function hourlyRate(?int $machines = null): ?float
    {
        if (!$this->rate || $this->rate <= 0) {
            return null;
        }

        $perHour = $this->rate * ($this->rate_period === 'minute' ? 60 : 1);

        return $perHour * max(0, $machines ?? max(1, (int) $this->machine_count));
    }
}
