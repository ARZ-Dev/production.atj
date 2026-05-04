<?php

namespace App\Livewire\EventTypes;

use App\Models\EventType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class EventTypeIndex extends Component
{
    use AuthorizesRequests;

    public $eventTypes;
    public $name;
    public $duration;
    public $event_type_id;
    public $editing = false;

    public function mount()
    {
        authorizeRequest('production.eventType-list');
        
        $this->loadEventTypes();

    }

    public function loadEventTypes()
    {
        if (authUser()->hasRole('Super Admin')) {
            $this->eventTypes = EventType::all();
        } else {
        }
    }

    public function resetForm()
    {
        $this->event_type_id = null;
        $this->name = '';
        $this->duration = '';
        $this->editing = false;
        $this->resetValidation();
    }

    public function create()
    {
        authorizeRequest('production.eventType-create');
        $this->resetForm();
        $this->dispatch('openModal');
    }

    public function edit($id)
    {
        authorizeRequest('production.eventType-edit');
        $this->resetForm();

        $eventType = EventType::findOrFail($id);
        $this->event_type_id = $eventType->id;
        $this->name = $eventType->name;
        $this->duration = $eventType->duration;
        $this->editing = true;

        $this->dispatch('openModal');
    }

    

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        /*if ($this->has_recipe) {
            $rules['recipe_id'] = 'required|exists:recipes,id';
        } else {
            $rules['duration'] = 'required|integer|min:1';
        }*/

        return $rules;
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            //'duration' => $this->has_recipe ? null : $this->duration,
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
