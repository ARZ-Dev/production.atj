<?php

namespace App\Livewire\Guides;

use App\Models\Guide;
use App\Models\GuideCategory;
use Livewire\Component;

class GuideView extends Component
{
    public $selectedGuideId = null;
    public string $lang = 'en';
    public string $search = '';

    public function mount($id = null)
    {
        if ($id) {
            Guide::findOrFail($id);
            $this->selectedGuideId = (int) $id;
        }
    }

    public function selectGuide($id)
    {
        Guide::findOrFail($id);
        $this->selectedGuideId = (int) $id;
        $this->dispatch('guide-selected');
    }

    public function setLang(string $lang)
    {
        if (in_array($lang, ['en', 'pr'])) {
            $this->lang = $lang;
        }
    }

    public function render()
    {
        $categories = GuideCategory::with(['guides' => function ($q) {
                $q->orderBy('name_en');

                if (trim($this->search) !== '') {
                    $term = '%' . trim($this->search) . '%';
                    $q->where(fn($qq) => $qq->where('name_en', 'like', $term)->orWhere('name_pr', 'like', $term));
                }
            }])
            ->orderBy('name_en')
            ->get()
            ->filter(fn($category) => $category->guides->isNotEmpty());

        $guide = $this->selectedGuideId
            ? Guide::with(['category', 'sections.blocks'])->find($this->selectedGuideId)
            : null;

        if ($this->selectedGuideId && !$guide) {
            $this->selectedGuideId = null;
        }

        return view('livewire.guides.guide-view', [
            'guide' => $guide,
            'categories' => $categories,
        ])->layout('components.layouts.guide');
    }
}
