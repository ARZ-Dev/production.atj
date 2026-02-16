<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
