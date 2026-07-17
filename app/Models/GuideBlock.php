<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuideBlock extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function section()
    {
        return $this->belongsTo(GuideSection::class, 'guide_section_id');
    }

    public function getTitle(string $lang = 'en'): ?string
    {
        return $lang === 'pr' ? ($this->title_pr ?: $this->title_en) : ($this->title_en ?: $this->title_pr);
    }

    public function getSubtitle(string $lang = 'en'): ?string
    {
        return $lang === 'pr' ? ($this->subtitle_pr ?: $this->subtitle_en) : ($this->subtitle_en ?: $this->subtitle_pr);
    }

    public function getContent(string $lang = 'en'): ?string
    {
        return $lang === 'pr' ? ($this->content_pr ?: $this->content_en) : ($this->content_en ?: $this->content_pr);
    }
}
