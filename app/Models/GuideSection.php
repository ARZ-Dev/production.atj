<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuideSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }

    public function blocks()
    {
        return $this->hasMany(GuideBlock::class)->orderBy('sort_order');
    }

    public function getName(string $lang = 'en'): string
    {
        return $lang === 'pr' ? ($this->name_pr ?: $this->name_en) : ($this->name_en ?: $this->name_pr);
    }
}
