<?php

namespace App\Models;

use App\Models\Concerns\RoutesWarehouses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Packaging extends Model
{
    use HasFactory, RoutesWarehouses, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'department_id'        => 'integer',
        'factory_id'           => 'integer',
        'source_warehouse_ids' => 'array',
        'item_type_warehouses' => 'array',
    ];

    /** Packagings consume from a list of warehouses like preparations and lines. */
    public function sourceWarehouseColumn(): string
    {
        return 'source_warehouse_id';
    }

    public function capacities()
    {
        return $this->hasMany(PackagingCapacity::class);
    }

    public function eventTypes()
    {
        return $this->belongsToMany(EventType::class, 'event_type_packaging');
    }
}
