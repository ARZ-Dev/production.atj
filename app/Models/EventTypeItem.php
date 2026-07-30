<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTypeItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }
}
