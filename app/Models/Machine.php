<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function machineType()
    {
        return $this->belongsTo(MachineType::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
