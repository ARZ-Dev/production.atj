<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    public function warehouseType()
    {
        return $this->belongsTo(WarehouseType::class,'warehouse_type_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class,'company_id');
    }

    public function factory()
    {
        return $this->belongsTo(Factory::class,'factory_id');
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class,'production_line_id');
    }
}
