<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(RawMaterialRequestItem::class, 'request_id');
    }
}
