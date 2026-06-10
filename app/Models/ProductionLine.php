<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function preparations()
    {
        return $this->belongsToMany(Preparation::class, 'production_line_preparation');
    }

    public function lines()
    {
        return $this->belongsToMany(Line::class, 'line_production_line');
    }
}
