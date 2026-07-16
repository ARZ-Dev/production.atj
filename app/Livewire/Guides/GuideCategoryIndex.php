<?php

namespace App\Livewire\Guides;

use App\Models\GuideCategory;
use Livewire\Attributes\On;
use Livewire\Component;

class GuideCategoryIndex extends Component
{
    public $categories;
    public $name_en;
    public $name_pr;
    public $guide_category_id;
    public $editing = false;

    public function mount()
    {
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $this->categories = GuideCategory::withCount('guides')->orderBy('name_en')->get();
    }

    public function resetForm()
    {
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

        $category = GuideCategory::findOrFail($id);
        $this->guide_category_id = $category->id;
        $this->name_en = $category->name_en;
        $this->name_pr = $category->name_pr;
        $this->editing = true;

        $this->dispatch('openModal');
    }

    protected function rules()
    {
        return [
            'name_en' => 'required|string|max:255',
            'name_pr' => 'required|string|max:255',
        ];
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'name_en' => $this->name_en,
            'name_pr' => $this->name_pr,
        ];

        if ($this->editing) {
            GuideCategory::findOrFail($this->guide_category_id)->update($data);
        } else {
            GuideCategory::create($data);
        }

        return redirect()->route('guide-categories')
            ->with('success', $this->editing ? 'Guide Category updated successfully.' : 'Guide Category created successfully.');
    }

    #[On('delete')]
    public function delete($id)
    {
        GuideCategory::findOrFail($id)->delete();

        return redirect()->route('guide-categories')->with('success', 'Guide Category deleted successfully.');
    }

    public function render()
    {
        return view('livewire.guides.guide-category-index');
    }
}
