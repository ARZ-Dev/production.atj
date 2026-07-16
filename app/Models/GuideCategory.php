<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuideCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function guides()
    {
        return $this->hasMany(Guide::class);
    }

    public function getName(string $lang = 'en'): string
    {
        return $lang === 'pr' ? ($this->name_pr ?: $this->name_en) : ($this->name_en ?: $this->name_pr);
    }
}
