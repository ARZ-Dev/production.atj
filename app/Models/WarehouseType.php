<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseType extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    

    public function company()
    {
        return $this->belongsTo(Company::class,'company_id');
    }
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class,'warehouse_type_id');
    }

    public function productionLines()
    {
        return $this->hasMany(ProductionLine::class,'warehouse_type_id');
    }   
}
