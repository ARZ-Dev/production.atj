<?php

namespace App\Livewire\Guides;

use App\Models\Guide;
use App\Models\GuideCategory;
use Livewire\Component;

class GuideView extends Component
{
    public $selectedGuideId = null;
    public string $lang = 'en';

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
    }

    public function backToList()
    {
        $this->selectedGuideId = null;
    }

    public function setLang(string $lang)
    {
        if (in_array($lang, ['en', 'pr'])) {
            $this->lang = $lang;
        }
    }

    public function render()
    {
        $guide = null;
        $categories = collect();

        if ($this->selectedGuideId) {
            $guide = Guide::with(['category', 'sections.blocks'])->find($this->selectedGuideId);

            if (!$guide) {
                $this->selectedGuideId = null;
            }
        }

        if (!$guide) {
            $categories = GuideCategory::with(['guides' => fn($q) => $q->withCount('sections')->orderBy('name_en')])
                ->orderBy('name_en')
                ->get();
        }

        return view('livewire.guides.guide-view', [
            'guide' => $guide,
            'categories' => $categories,
        ]);
    }
}
