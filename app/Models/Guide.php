<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guide extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(GuideCategory::class, 'guide_category_id');
    }

    public function sections()
    {
        return $this->hasMany(GuideSection::class)->orderBy('sort_order');
    }

    public function getName(string $lang = 'en'): string
    {
        return $lang === 'pr' ? ($this->name_pr ?: $this->name_en) : ($this->name_en ?: $this->name_pr);
    }
}
