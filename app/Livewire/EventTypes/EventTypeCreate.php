<?php

namespace App\Livewire\EventTypes;

use App\Models\Company;
use App\Models\EventType;
use App\Models\Recipe;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class EventTypeCreate extends Component
{
    use AuthorizesRequests;

    public $id;
    public $name;
    public $has_recipe;
    public $duration;
    public $recipe_id;
    public $recipes = [];
    public $editing = false;
    public $companies = [];
    public $company_id;

    public function mount($id = null)
    {
        $this->authorize('eventType-create');
        
        if(auth()->user()->hasRole('Super Admin')) {
            $this->companies = Company::all();
        } else {
            $this->company_id = auth()->user()->company_id;
            $this->companies = Company::where('id', auth()->user()->company_id)->get();
            $this->recipes = Recipe::where('company_id', auth()->user()->company_id)->get();
        }

        if($id){
            $this->editing = true;
            $eventType = EventType::findOrFail($id);
            $this->name = $eventType->name;
            $this->has_recipe = $eventType->has_recipe;
            $this->duration = $eventType->duration;
            $this->recipe_id = $eventType->recipe_id;
            $this->company_id = $eventType->company_id;
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'has_recipe' => 'boolean',
            'recipe_id' => 'nullable|exists:recipes,id',
            'company_id' => 'required|exists:companies,id',
        ];
    }

    public function submit()
    {
        $this->validate();

        if($this->editing){
            $eventType = EventType::findOrFail($this->id);
            $this->authorize('eventType-edit');
            $eventType->update([
                'name' => $this->name,
                'has_recipe' => $this->has_recipe,
                'duration' => $this->duration,
                'recipe_id' => $this->recipe_id,
            ]);
            return to_route('event-types')->with('success', 'Event Type updated successfully.');
        }else{
            EventType::create([
                'name' => $this->name,
                'has_recipe' => $this->has_recipe,
                'duration' => $this->duration,
                'recipe_id' => $this->recipe_id,
                'company_id' => $this->company_id,
            ]);
            return to_route('event-types')->with('success', 'Event Type created successfully.');
        }
    }
    public function render()
    {
        return view('livewire.event-types.event-type-create');
    }
}
