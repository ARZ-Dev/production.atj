<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportItem extends Model
{
    use HasFactory, SoftDeletes;


    protected $guarded = [];


    public function itemRequest()
    {
        return $this->belongsTo(ItemRequest::class);
    }

    public function stockIn()
    {
        return $this->belongsTo(StockIn::class);
    }

    public function stockOut()
    {
        return $this->belongsTo(StockOut::class);
    }
    public function waste()
    {
        return $this->belongsTo(Waste::class);
    }
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
