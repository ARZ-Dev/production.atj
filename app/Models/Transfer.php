<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


 
    public function reportRawMaterials()
    {
        return $this->hasMany(ReportRawMaterial::class);
    }

    public function rawMaterialRequest()
    {
        return $this->belongsTo(RawMaterialRequest::class);
    }
}
