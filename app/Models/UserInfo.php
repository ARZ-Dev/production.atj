<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_shift_manager' => 'boolean',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function preparations()
    {
        return $this->belongsToMany(Preparation::class);
    }

    public function lines()
    {
        return $this->belongsToMany(Line::class);
    }
}
