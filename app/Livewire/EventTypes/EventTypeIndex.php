<?php

namespace App\Livewire\EventTypes;

use App\Models\EventType;
use App\Services\ApiService;
use Livewire\Attributes\On;
use Livewire\Component;

class EventTypeIndex extends Component
{
    public $eventTypes;
    public $name;
    public $event_type_id;
    public $editing = false;
    public bool $has_recipe = false;
    public $duration = null;
    public string $color = '#818cf8';
    public $item_type_ids = [];
    public $itemTypes = [];
    public $itemTypesMap = [];

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount()
    {
        authorizeRequest('production.eventType-list');
        $this->itemTypes = $this->api->get('/v1/item-types')['data'] ?? [];
        $this->itemTypesMap = collect($this->itemTypes)->keyBy('id')->map(fn($t) => $t['name'])->toArray();
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
        $this->has_recipe = false;
        $this->duration = null;
        $this->color = '#818cf8';
        $this->item_type_ids = [];
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
        $this->has_recipe = (bool) $eventType->has_recipe;
        $this->duration = $eventType->duration;
        $this->color = $eventType->color ?? '#818cf8';
        $this->item_type_ids = $eventType->item_type_ids ?? [];
        $this->editing = true;

        $this->dispatch('openModal');
    }

    public function updatedHasRecipe($value)
    {
        if ($value) {
            $this->duration = null;
        }
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'has_recipe' => 'boolean',
            'duration' => 'required_if:has_recipe,false|nullable|integer|min:1',
            'color' => 'required|string|max:7',
            'item_type_ids' => 'nullable|array',
        ];
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'has_recipe' => $this->has_recipe,
            'duration' => $this->has_recipe ? null : $this->duration,
            'color' => $this->color,
            'item_type_ids' => array_map('intval', $this->item_type_ids ?? []),
        ];

        if ($this->editing) {
            authorizeRequest('production.eventType-edit');
            EventType::findOrFail($this->event_type_id)->update($data);
        } else {
            authorizeRequest('production.eventType-create');
            EventType::create($data);
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
