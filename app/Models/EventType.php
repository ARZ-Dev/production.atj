<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventType extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'has_recipe' => 'boolean',
        'item_type_ids' => 'array',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function lines()
    {
        return $this->belongsToMany(Line::class, 'event_type_line');
    }

    public function preparations()
    {
        return $this->belongsToMany(Preparation::class, 'event_type_preparation');
    }
}
