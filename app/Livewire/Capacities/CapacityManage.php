<?php

namespace App\Livewire\Capacities;

use App\Services\ApiService;
use Livewire\Component;

class CapacityManage extends Component
{
    public string $modelType;   // 'preparation' | 'line'
    public int    $modelId;
    public string $modelName = '';

    public array $itemTypes = [];
    public array $sections  = [];  // [ ['item_type_id', 'item_type_name', 'items', 'capacityRows'] ]

    public ?int $selected_item_type_id = null;

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

        $existingTypeIds = $model->capacities()
            ->distinct()
            ->pluck('item_type_id')
            ->all();

        foreach ($existingTypeIds as $typeId) {
            $this->addSection((int) $typeId);
        }
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

    public function availableItemTypes(): array
    {
        $used = collect($this->sections)->pluck('item_type_id')->all();

        return array_values(array_filter(
            $this->itemTypes,
            fn ($type) => !in_array($type['id'], $used)
        ));
    }

    protected function addSection(int $typeId): void
    {
        $type = collect($this->itemTypes)->firstWhere('id', $typeId);

        if (!$type) {
            return;
        }

        $items = $this->api->get('/v1/items', [
            'item_type' => $type['name'],
            'is_active' => true,
        ])['data'] ?? [];

        $existing = $this->resolveModel()
            ->capacities()
            ->where('item_type_id', $typeId)
            ->pluck('capacity', 'item_id')
            ->toArray();

        $capacityRows = [];
        foreach ($items as $item) {
            $capacityRows[$item['id']] = $existing[$item['id']] ?? '';
        }

        $this->sections[] = [
            'item_type_id'   => $typeId,
            'item_type_name' => $type['name'],
            'items'          => $items,
            'capacityRows'   => $capacityRows,
        ];
    }

    public function addItemType(): void
    {
        if (!$this->selected_item_type_id) {
            return;
        }

        if (collect($this->sections)->contains('item_type_id', $this->selected_item_type_id)) {
            $this->selected_item_type_id = null;
            return;
        }

        $this->addSection($this->selected_item_type_id);
        $this->selected_item_type_id = null;

        $this->dispatch('itemTypesUpdated', itemTypes: $this->availableItemTypes());
    }

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->sections as $index => $section) {
            foreach (array_keys($section['capacityRows']) as $itemId) {
                $rules["sections.{$index}.capacityRows.{$itemId}"] = 'nullable|numeric|min:0';
            }
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate();

        $model = $this->resolveModel();

        foreach ($this->sections as $section) {
            $typeId = $section['item_type_id'];

            $model->capacities()->where('item_type_id', $typeId)->delete();

            foreach ($section['capacityRows'] as $itemId => $capacity) {
                if ($capacity === '' || $capacity === null) {
                    continue;
                }

                $model->capacities()->create([
                    'item_type_id' => $typeId,
                    'item_id'      => $itemId,
                    'capacity'     => $capacity,
                ]);
            }
        }

        session()->flash('success', 'Capacity saved successfully.');
    }

    public function render()
    {
        return view('livewire.capacities.capacity-manage');
    }
}
