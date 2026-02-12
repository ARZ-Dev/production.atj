<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function warehouseTypes()
    {
        return $this->hasMany(WarehouseType::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
    
}
