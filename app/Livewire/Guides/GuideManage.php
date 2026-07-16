<?php

namespace App\Livewire\Guides;

use App\Models\Guide;
use App\Models\GuideBlock;
use App\Models\GuideSection;
use Livewire\Attributes\On;
use Livewire\Component;

class GuideManage extends Component
{
    public $guideId;

    // Section form
    public $section_id;
    public $section_name_en;
    public $section_name_pr;
    public $editingSection = false;

    // Block form
    public $block_id;
    public $block_section_id;
    public $title_en;
    public $title_pr;
    public $subtitle_en;
    public $subtitle_pr;
    public $content_en;
    public $content_pr;
    public $editingBlock = false;

    public function mount($id)
    {
        $this->guideId = $id;
        Guide::findOrFail($id);
    }

    // |--------------------------------------------------------------------------
    // | Sections
    // |--------------------------------------------------------------------------

    public function resetSectionForm()
    {
        $this->section_id = null;
        $this->section_name_en = '';
        $this->section_name_pr = '';
        $this->editingSection = false;
        $this->resetValidation();
    }

    public function createSection()
    {
        $this->resetSectionForm();
        $this->dispatch('openSectionModal');
    }

    public function editSection($id)
    {
        $this->resetSectionForm();

        $section = GuideSection::where('guide_id', $this->guideId)->findOrFail($id);
        $this->section_id = $section->id;
        $this->section_name_en = $section->name_en;
        $this->section_name_pr = $section->name_pr;
        $this->editingSection = true;

        $this->dispatch('openSectionModal');
    }

    public function saveSection()
    {
        $this->validate([
            'section_name_en' => 'required|string|max:255',
            'section_name_pr' => 'required|string|max:255',
        ]);

        if ($this->editingSection) {
            GuideSection::where('guide_id', $this->guideId)
                ->findOrFail($this->section_id)
                ->update([
                    'name_en' => $this->section_name_en,
                    'name_pr' => $this->section_name_pr,
                ]);
        } else {
            GuideSection::create([
                'guide_id' => $this->guideId,
                'name_en' => $this->section_name_en,
                'name_pr' => $this->section_name_pr,
                'sort_order' => (GuideSection::where('guide_id', $this->guideId)->max('sort_order') ?? 0) + 1,
            ]);
        }

        $this->resetSectionForm();
        $this->dispatch('closeSectionModal');
        $this->dispatch('guide-toast', message: 'Section saved successfully.');
    }

    #[On('deleteSection')]
    public function deleteSection($id)
    {
        GuideSection::where('guide_id', $this->guideId)->findOrFail($id)->delete();
        $this->dispatch('guide-toast', message: 'Section deleted successfully.');
    }

    public function moveSection($id, $direction)
    {
        $section = GuideSection::where('guide_id', $this->guideId)->findOrFail($id);

        $neighbor = GuideSection::where('guide_id', $this->guideId)
            ->when($direction === 'up',
                fn($q) => $q->where('sort_order', '<', $section->sort_order)->orderByDesc('sort_order'),
                fn($q) => $q->where('sort_order', '>', $section->sort_order)->orderBy('sort_order'))
            ->first();

        if ($neighbor) {
            [$section->sort_order, $neighbor->sort_order] = [$neighbor->sort_order, $section->sort_order];
            $section->save();
            $neighbor->save();
        }
    }

    // |--------------------------------------------------------------------------
    // | Blocks
    // |--------------------------------------------------------------------------

    public function resetBlockForm()
    {
        $this->block_id = null;
        $this->block_section_id = null;
        $this->title_en = '';
        $this->title_pr = '';
        $this->subtitle_en = '';
        $this->subtitle_pr = '';
        $this->content_en = '';
        $this->content_pr = '';
        $this->editingBlock = false;
        $this->resetValidation();
    }

    public function createBlock($sectionId)
    {
        $this->resetBlockForm();
        $this->block_section_id = $sectionId;

        $this->dispatch('openBlockModal', contentEn: '', contentPr: '');
    }

    public function editBlock($id)
    {
        $this->resetBlockForm();

        $block = GuideBlock::whereHas('section', fn($q) => $q->where('guide_id', $this->guideId))
            ->findOrFail($id);

        $this->block_id = $block->id;
        $this->block_section_id = $block->guide_section_id;
        $this->title_en = $block->title_en;
        $this->title_pr = $block->title_pr;
        $this->subtitle_en = $block->subtitle_en;
        $this->subtitle_pr = $block->subtitle_pr;
        $this->content_en = $block->content_en;
        $this->content_pr = $block->content_pr;
        $this->editingBlock = true;

        $this->dispatch('openBlockModal', contentEn: $this->content_en ?? '', contentPr: $this->content_pr ?? '');
    }

    public function saveBlock()
    {
        $this->validate([
            'block_section_id' => 'required|exists:guide_sections,id',
            'title_en' => 'required|string|max:255',
            'title_pr' => 'required|string|max:255',
            'subtitle_en' => 'nullable|string|max:255',
            'subtitle_pr' => 'nullable|string|max:255',
            'content_en' => 'nullable|string',
            'content_pr' => 'nullable|string',
        ]);

        $data = [
            'guide_section_id' => $this->block_section_id,
            'title_en' => $this->title_en,
            'title_pr' => $this->title_pr,
            'subtitle_en' => $this->subtitle_en,
            'subtitle_pr' => $this->subtitle_pr,
            'content_en' => $this->content_en,
            'content_pr' => $this->content_pr,
        ];

        if ($this->editingBlock) {
            GuideBlock::whereHas('section', fn($q) => $q->where('guide_id', $this->guideId))
                ->findOrFail($this->block_id)
                ->update($data);
        } else {
            $data['sort_order'] = (GuideBlock::where('guide_section_id', $this->block_section_id)->max('sort_order') ?? 0) + 1;
            GuideBlock::create($data);
        }

        $this->resetBlockForm();
        $this->dispatch('closeBlockModal');
        $this->dispatch('guide-toast', message: 'Block saved successfully.');
    }

    #[On('deleteBlock')]
    public function deleteBlock($id)
    {
        GuideBlock::whereHas('section', fn($q) => $q->where('guide_id', $this->guideId))
            ->findOrFail($id)
            ->delete();

        $this->dispatch('guide-toast', message: 'Block deleted successfully.');
    }

    public function moveBlock($id, $direction)
    {
        $block = GuideBlock::whereHas('section', fn($q) => $q->where('guide_id', $this->guideId))
            ->findOrFail($id);

        $neighbor = GuideBlock::where('guide_section_id', $block->guide_section_id)
            ->when($direction === 'up',
                fn($q) => $q->where('sort_order', '<', $block->sort_order)->orderByDesc('sort_order'),
                fn($q) => $q->where('sort_order', '>', $block->sort_order)->orderBy('sort_order'))
            ->first();

        if ($neighbor) {
            [$block->sort_order, $neighbor->sort_order] = [$neighbor->sort_order, $block->sort_order];
            $block->save();
            $neighbor->save();
        }
    }

    public function render()
    {
        $guide = Guide::with(['category', 'sections.blocks'])->findOrFail($this->guideId);

        return view('livewire.guides.guide-manage', [
            'guide' => $guide,
        ]);
    }
}
