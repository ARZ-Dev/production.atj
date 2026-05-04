<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


 
    public function reportRawMaterials()
    {
        return $this->hasMany(ReportRawMaterial::class,'transfer_id');
    }

    public function rawMaterialRequest()
    {
        return $this->belongsTo(RawMaterialRequest::class);
    }
}
