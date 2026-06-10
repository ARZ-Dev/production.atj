<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capacity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'capacity'     => 'decimal:2',
        'item_type_id' => 'integer',
        'item_id'      => 'integer',
    ];

    public function capacityable()
    {
        return $this->morphTo();
    }
}
