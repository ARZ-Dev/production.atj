<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagingCapacity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'packaging_id' => 'integer',
        'item_type_id' => 'integer',
        'item_id'      => 'integer',
        'item_unit_id' => 'integer',
        'capacity'     => 'decimal:2',
    ];

    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }
}
