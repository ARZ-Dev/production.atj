<?php

namespace App\Livewire\EventTypes;

use App\Models\EventType;
use Livewire\Attributes\On;
use Livewire\Component;

class EventTypeIndex extends Component
{
    public $eventTypes;
    public $name;
    public $event_type_id;
    public $editing = false;

    public function mount()
    {
        authorizeRequest('production.eventType-list');
        $this->loadEventTypes();
    }

    public function loadEventTypes()
    {
        $this->eventTypes = EventType::orderBy('name')->get();
    }

    public function resetForm()
    {
        $this->event_type_id = null;
        $this->name = '';
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
        $this->editing = true;

        $this->dispatch('openModal');
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function submit()
    {
        $this->validate();

        if ($this->editing) {
            authorizeRequest('production.eventType-edit');
            EventType::findOrFail($this->event_type_id)->update(['name' => $this->name]);
        } else {
            authorizeRequest('production.eventType-create');
            EventType::create(['name' => $this->name]);
        }

        return redirect()->route('event-types')
            ->with('success', $this->editing ? 'Event Type updated successfully.' : 'Event Type created successfully.');
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.eventType-delete');
        EventType::findOrFail($id)->delete();

        return redirect()->route('event-types')->with('success', 'Event Type deleted successfully.');
    }

    public function render()
    {
        return view('livewire.event-types.event-type-index');
    }
}
