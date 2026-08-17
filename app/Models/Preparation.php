<?php

namespace App\Models;

use App\Models\Concerns\RoutesWarehouses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Preparation extends Model
{
    use HasFactory, RoutesWarehouses, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'rm_warehouse_ids'     => 'array',
        'item_type_warehouses' => 'array',
    ];

    /** Preparations consume raw materials. */
    public function sourceWarehouseColumn(): string
    {
        return 'rm_warehouse_id';
    }

    public function capacities()
    {
        return $this->morphMany(Capacity::class, 'capacityable');
    }

    public function eventTypes()
    {
        return $this->belongsToMany(EventType::class, 'event_type_preparation');
    }

    public function productionLines()
    {
        return $this->belongsToMany(ProductionLine::class, 'production_line_preparation');
    }
}
