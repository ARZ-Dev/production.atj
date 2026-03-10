<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


    public function factory()
    {
        return $this->belongsTo(Factory::class);
    }

    public function machineType()
    {
        return $this->belongsTo(MachineType::class);
    }

    public function machines()
    {
        return $this->hasMany(Machine::class);
    }

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }
}
