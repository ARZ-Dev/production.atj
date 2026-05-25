<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockIn extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

   public function reportItems()
    {
        return $this->hasMany(ReportItem::class, 'stock_in_id');
    }

}
