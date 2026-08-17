<?php

namespace App\Models;

use App\Models\Concerns\RoutesWarehouses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Line extends Model
{
    use HasFactory, RoutesWarehouses, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'sfg_warehouse_ids'    => 'array',
        'item_type_warehouses' => 'array',
    ];

    /** Lines consume semi-finished goods. */
    public function sourceWarehouseColumn(): string
    {
        return 'sfg_warehouse_id';
    }

    public function capacities()
    {
        return $this->morphMany(Capacity::class, 'capacityable');
    }

    public function eventTypes()
    {
        return $this->belongsToMany(EventType::class, 'event_type_line');
    }

    public function productionLines()
    {
        return $this->belongsToMany(ProductionLine::class, 'line_production_line');
    }
}
