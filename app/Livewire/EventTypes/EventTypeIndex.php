<?php

namespace App\Livewire\EventTypes;

use App\Models\Company;
use App\Models\EventType;
use App\Models\Recipe;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class EventTypeIndex extends Component
{
    use AuthorizesRequests;

    public $eventTypes;
    public $companies = [];
    public $recipes = [];
    public $company_id;
    public $name;
    public $has_recipe = false;
    public $duration;
    public $recipe_id;
    public $event_type_id;
    public $editing = false;

    public function mount()
    {
        $this->authorize('eventType-list');
        $this->loadEventTypes();

        if (auth()->user()->hasRole('Super Admin')) {
            $this->companies = Company::all();
        } else {
            $this->company_id = auth()->user()->company_id;
            $this->companies = Company::where('id', auth()->user()->company_id)->get();
            $this->recipes = Recipe::where('company_id', auth()->user()->company_id)->get();
        }
    }

    public function loadEventTypes()
    {
        if (auth()->user()->hasRole('Super Admin')) {
            $this->eventTypes = EventType::all();
        } else {
            $this->eventTypes = EventType::where('company_id', auth()->user()->company_id)->get();
        }
    }

    public function resetForm()
    {
        $this->event_type_id = null;
        $this->name = '';
        $this->has_recipe = false;
        $this->duration = '';
        $this->recipe_id = '';
        $this->editing = false;
        if (!auth()->user()->hasRole('Super Admin')) {
            $this->company_id = auth()->user()->company_id;
        } else {
            $this->company_id = '';
        }
        $this->resetValidation();
    }

    public function create()
    {
        $this->authorize('eventType-create');
        $this->resetForm();
        $this->dispatch('openModal');
    }

    public function edit($id)
    {
        $this->authorize('eventType-edit');
        $this->resetForm();

        $eventType = EventType::findOrFail($id);
        $this->event_type_id = $eventType->id;
        $this->company_id = $eventType->company_id;
        $this->name = $eventType->name;
        $this->has_recipe = $eventType->has_recipe;
        $this->duration = $eventType->duration;
        $this->recipe_id = $eventType->recipe_id;
        $this->editing = true;

        // Load recipes for the selected company
        if ($this->company_id) {
            $this->recipes = Recipe::where('company_id', $this->company_id)->get();
        }

        $this->dispatch('openModal');
    }

    public function updatedCompanyId($value)
    {
        if ($value) {
            $this->recipes = Recipe::where('company_id', $value)->get();
        } else {
            $this->recipes = [];
        }
        $this->recipe_id = '';
        $this->dispatch('refreshRecipePicker');
    }

    public function updatedHasRecipe($value)
    {
        if (!$value) {
            $this->recipe_id = '';
        } else {
            $this->duration = '';
        }
        $this->dispatch('refreshRecipePicker');
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'has_recipe' => 'boolean',
            'company_id' => 'required|exists:companies,id',
        ];

        if ($this->has_recipe) {
            $rules['recipe_id'] = 'required|exists:recipes,id';
        } else {
            $rules['duration'] = 'required|integer|min:1';
        }

        return $rules;
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'has_recipe' => $this->has_recipe,
            'duration' => $this->has_recipe ? null : $this->duration,
            'recipe_id' => $this->has_recipe ? $this->recipe_id : null,
            'company_id' => $this->company_id,
        ];

        if ($this->editing) {
            $eventType = EventType::findOrFail($this->event_type_id);
            $this->authorize('eventType-edit');
            $eventType->update($data);

        
        } else {
            $this->authorize('eventType-create');
            EventType::create($data);

        }

        $this->loadEventTypes();
        $this->dispatch('closeModal');
        $this->resetForm();

        return to_route('event-types')->with('success', $this->editing ? 'Event Type updated successfully.' : 'Event Type created successfully.');
    }

    #[On('delete')]
    public function delete($id)
    {
        $eventType = EventType::findOrFail($id);
        $this->authorize('eventType-delete');
        $eventType->delete();

        $this->loadEventTypes();
        session()->flash('success', 'Event Type deleted successfully.');
    }

    public function render()
    {
        return view('livewire.event-types.event-type-index');
    }
}