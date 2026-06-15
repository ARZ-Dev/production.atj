<?php

namespace App\Livewire\Capacities;

use App\Models\Capacity;
use App\Services\ApiService;
use Livewire\Component;

class CapacityManage extends Component
{
    public string $modelType;   // 'preparation' | 'line'
    public int    $modelId;
    public string $modelName = '';

    public array $itemTypes = [];
    public array $items     = [];

    public ?int  $selected_item_type_id = null;
    public array $capacityRows          = [];  // [item_id => capacity_value]
    public bool  $typeLocked            = false;

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api, string $modelType, int $id): void
    {
        $this->modelType = $modelType;
        $this->modelId   = $id;

        $this->itemTypes = $api->get('/v1/item-types')['data'] ?? [];

        $modelClass      = $this->resolveModelClass();
        $model           = $modelClass::findOrFail($id);
        $this->modelName = $model->name;
    }

    protected function resolveModelClass(): string
    {
        return match($this->modelType) {
            'preparation' => \App\Models\Preparation::class,
            'line'        => \App\Models\Line::class,
            default       => abort(404),
        };
    }

    protected function resolveModel()
    {
        return $this->resolveModelClass()::findOrFail($this->modelId);
    }

    public function updatedSelectedItemTypeId(?int $value): void
    {
        $this->items        = [];
        $this->capacityRows = [];
        $this->typeLocked   = (bool) $value;

        if (!$value) {
            return;
        }

        // Find type name for the API call
        $type = collect($this->itemTypes)->firstWhere('id', $value);
        if (!$type) {
            return;
        }

        $this->items = $this->api->get('/v1/items', [
            'item_type' => $type['name'],
            'is_active' => true,
        ])['data'] ?? [];

        // Pre-fill existing capacities
        $existing = $this->resolveModel()
            ->capacities()
            ->where('item_type_id', $value)
            ->pluck('capacity', 'item_id')
            ->toArray();

        foreach ($this->items as $item) {
            $this->capacityRows[$item['id']] = $existing[$item['id']] ?? '';
        }
    }

    public function changeItemType(): void
    {
        $this->selected_item_type_id = null;
        $this->items                 = [];
        $this->capacityRows          = [];
        $this->typeLocked            = false;

        $this->dispatch('itemTypeReset');
    }

    protected function rules(): array
    {
        $rules = [
            'selected_item_type_id' => 'required|integer',
            'capacityRows'          => 'required|array',
        ];

        foreach (array_keys($this->capacityRows) as $itemId) {
            $rules["capacityRows.{$itemId}"] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate();

        $model      = $this->resolveModel();
        $typeId     = $this->selected_item_type_id;

        // Delete rows for this item type first, then re-insert
        $model->capacities()->where('item_type_id', $typeId)->delete();

        foreach ($this->capacityRows as $itemId => $capacity) {
            if ($capacity === '' || $capacity === null) {
                continue;
            }

            $model->capacities()->create([
                'item_type_id' => $typeId,
                'item_id'      => $itemId,
                'capacity'     => $capacity,
            ]);
        }

        session()->flash('success', 'Capacity saved successfully.');
    }

    public function render()
    {
        return view('livewire.capacities.capacity-manage');
    }
}
