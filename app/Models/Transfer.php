<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];


 
    public function reportItems()
    {
        return $this->hasMany(ReportItem::class, 'transfer_id');
    }

    public function itemRequest()
    {
        return $this->belongsTo(ItemRequest::class);
    }
}
