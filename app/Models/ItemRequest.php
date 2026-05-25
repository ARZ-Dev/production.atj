<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function inputs()
    {
        return $this->hasMany(ItemRequestInput::class, 'request_id');
    }

    public function reportRawMaterials()
    {
        return $this->hasMany(ReportRawMaterial::class, 'raw_material_request_id');
    }

}
