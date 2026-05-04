<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportRawMaterial extends Model
{
    use HasFactory, SoftDeletes;


    protected $guarded = [];


    public function rawMaterialRequest()
    {
        return $this->belongsTo(RawMaterialRequest::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockIn()
    {
        return $this->belongsTo(RawMaterialStockIn::class);
    }

    public function stockOut()
    {
        return $this->belongsTo(RawMaterialStockOut::class);
    }
    public function waste()
    {
        return $this->belongsTo(RawMaterialWaste::class);
    }
    public function transfer()
    {
        return $this->belongsTo(RawMaterialTransfer::class);
    }
}
