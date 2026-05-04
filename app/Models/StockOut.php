<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOut extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


    public function reportRawMaterials()
    {
        return $this->hasMany(ReportRawMaterial::class, 'stock_out_id');
    }

}
