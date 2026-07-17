<?php

namespace App\Livewire\Guides;

use App\Models\Guide;
use App\Models\GuideCategory;
use Livewire\Attributes\On;
use Livewire\Component;

class GuideIndex extends Component
{
    public $guides;
    public $categories;
    public $guide_category_id;
    public $name_en;
    public $name_pr;
    public $guide_id;
    public $editing = false;

    public function mount()
    {
        $this->categories = GuideCategory::orderBy('name_en')->get();
        $this->loadGuides();
    }

    public function loadGuides()
    {
        $this->guides = Guide::with('category')->withCount('sections')->orderBy('name_en')->get();
    }

    public function resetForm()
    {
        $this->guide_id = null;
        $this->guide_category_id = null;
        $this->name_en = '';
        $this->name_pr = '';
        $this->editing = false;
        $this->resetValidation();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('openModal');
    }

    public function edit($id)
    {
        $this->resetForm();

        $guide = Guide::findOrFail($id);
        $this->guide_id = $guide->id;
        $this->guide_category_id = $guide->guide_category_id;
        $this->name_en = $guide->name_en;
        $this->name_pr = $guide->name_pr;
        $this->editing = true;

        $this->dispatch('openModal');
    }

    protected function rules()
    {
        return [
            'guide_category_id' => 'required|exists:guide_categories,id',
            'name_en' => 'required|string|max:255',
            'name_pr' => 'required|string|max:255',
        ];
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'guide_category_id' => $this->guide_category_id,
            'name_en' => $this->name_en,
            'name_pr' => $this->name_pr,
        ];

        if ($this->editing) {
            Guide::findOrFail($this->guide_id)->update($data);
        } else {
            Guide::create($data);
        }

        return redirect()->route('guides')
            ->with('success', $this->editing ? 'Guide updated successfully.' : 'Guide created successfully.');
    }

    #[On('delete')]
    public function delete($id)
    {
        Guide::findOrFail($id)->delete();

        return redirect()->route('guides')->with('success', 'Guide deleted successfully.');
    }

    public function render()
    {
        return view('livewire.guides.guide-index');
    }
}
