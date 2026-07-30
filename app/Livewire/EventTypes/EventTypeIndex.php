<?php

namespace App\Livewire\EventTypes;

use App\Models\EventType;
use App\Models\EventTypeItem;
use App\Services\ApiService;
use Illuminate\Support\Facades\DB;
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

    // Items (with unit + qty) for a non-recipe event type, grouped by item
    // type in the view. Each row: item_type_id, item_type_name, item_id,
    // item_name, units[], item_unit_id, quantity.
    public array $eventTypeItems = [];

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
        $this->eventTypeItems = [];
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

        $eventType = EventType::with('eventTypeItems')->findOrFail($id);
        $this->event_type_id = $eventType->id;
        $this->name = $eventType->name;
        $this->has_recipe = (bool) $eventType->has_recipe;
        $this->duration = $eventType->duration;
        $this->color = $eventType->color ?? '#818cf8';
        $this->item_type_ids = $eventType->item_type_ids ?? [];
        $this->editing = true;

        // Seed the items table with the saved unit/quantity per item.
        $saved = $eventType->eventTypeItems
            ->keyBy('item_id')
            ->map(fn($r) => ['item_unit_id' => $r->item_unit_id, 'quantity' => $r->quantity])
            ->all();

        $this->buildEventTypeItems($saved);

        $this->dispatch('openModal');
    }

    public function updatedHasRecipe($value)
    {
        if ($value) {
            $this->duration = null;
        }

        // Recipe event types don't carry a fixed items list.
        $this->buildEventTypeItems();
    }

    public function updatedItemTypeIds(): void
    {
        $this->buildEventTypeItems();
    }

    /**
     * Rebuild the items table from the selected item types, preserving any
     * unit/quantity the user (or a saved record) already provided per item.
     */
    protected function buildEventTypeItems(?array $saved = null): void
    {
        if ($this->has_recipe || empty($this->item_type_ids)) {
            $this->eventTypeItems = [];
            return;
        }

        // Previously entered values to keep across rebuilds, keyed by item_id.
        $previous = $saved ?? collect($this->eventTypeItems)
            ->keyBy('item_id')
            ->map(fn($r) => ['item_unit_id' => $r['item_unit_id'] ?? null, 'quantity' => $r['quantity'] ?? null])
            ->all();

        $rows = [];

        foreach ($this->item_type_ids as $typeId) {
            $typeId   = (int) $typeId;
            $typeName = $this->itemTypesMap[$typeId] ?? "Item Type {$typeId}";

            $items = $this->api->get('/v1/items', ['item_type' => $typeName, 'is_active' => true])['data'] ?? [];

            foreach ($items as $item) {
                $units = $item['units'] ?? [];
                $basic = collect($units)->firstWhere('basic', true) ?: ($units[0] ?? null);
                $prev  = $previous[$item['id']] ?? null;

                $rows[] = [
                    'item_type_id'   => $typeId,
                    'item_type_name' => $typeName,
                    'item_id'        => $item['id'],
                    'item_name'      => $item['name'] ?? "Item #{$item['id']}",
                    'units'          => collect($units)->map(fn($u) => [
                        'id'    => $u['id'],
                        'label' => ($u['name'] ?? '') . (!empty($u['symbol']) ? " ({$u['symbol']})" : ''),
                    ])->values()->all(),
                    'item_unit_id'   => $prev['item_unit_id'] ?? ($basic['id'] ?? null),
                    'quantity'       => $prev['quantity'] ?? null,
                ];
            }
        }

        $this->eventTypeItems = $rows;
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

        DB::transaction(function () use ($data) {
            if ($this->editing) {
                authorizeRequest('production.eventType-edit');
                $et = EventType::findOrFail($this->event_type_id);
                $et->update($data);
            } else {
                authorizeRequest('production.eventType-create');
                $et = EventType::create($data);
            }

            $this->syncEventTypeItems($et);
        });

        return redirect()->route('event-types')
            ->with('success', $this->editing ? 'Event Type updated successfully.' : 'Event Type created successfully.');
    }

    /**
     * Persist the standard items (item/unit/quantity) for a non-recipe event
     * type — only the rows the user gave a quantity for. Recipe event types
     * keep no items list.
     */
    protected function syncEventTypeItems(EventType $eventType): void
    {
        $eventType->eventTypeItems()->delete();

        if ($this->has_recipe) {
            return;
        }

        foreach ($this->eventTypeItems as $row) {
            $quantity = $row['quantity'] ?? null;

            if ($quantity === null || $quantity === '' || (float) $quantity <= 0) {
                continue;
            }

            $eventType->eventTypeItems()->create([
                'item_type_id' => $row['item_type_id'] ?? null,
                'item_id'      => $row['item_id'],
                'item_unit_id' => $row['item_unit_id'] ?? null,
                'quantity'     => (float) $quantity,
            ]);
        }
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
