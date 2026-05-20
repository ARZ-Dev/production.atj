<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreperationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


    public function preperationItemUnits()
    {
        return $this->hasMany(PreperationItemUnit::class);
    }
}
