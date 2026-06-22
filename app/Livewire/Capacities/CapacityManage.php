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
    public array $itemUnits = [];  // item_id => [ ['id','name','symbol','basic','formula'], ... ]

    public array $removedTypeIds = [];

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
            ->get()
            ->keyBy('item_id');

        $capacityRows = [];
        foreach ($items as $item) {
            $itemId = $item['id'];

            if (!isset($this->itemUnits[$itemId])) {
                $this->itemUnits[$itemId] = $this->api->get("/v1/items/{$itemId}")['data']['units'] ?? [];
            }

            $units      = $this->itemUnits[$itemId];
            $basicUnit  = collect($units)->firstWhere('basic', true);
            $existingRow = $existing->get($itemId);

            $capacityRows[$itemId] = [
                'unit_id' => $existingRow->item_unit_id ?? ($basicUnit['id'] ?? ($units[0]['id'] ?? null)),
                'value'   => $existingRow ? (string) $existingRow->capacity : '',
            ];
        }

        $this->sections[] = [
            'item_type_id'   => $typeId,
            'item_type_name' => $type['name'],
            'items'          => $items,
            'capacityRows'   => $capacityRows,
        ];
    }

    public function addItemType(int $typeId): void
    {
        if (collect($this->sections)->contains('item_type_id', $typeId)) {
            return;
        }

        $this->addSection($typeId);
        $this->removedTypeIds = array_values(array_diff($this->removedTypeIds, [$typeId]));
    }

    public function removeItemType(int $index): void
    {
        $section = $this->sections[$index] ?? null;

        if (!$section) {
            return;
        }

        $this->removedTypeIds[] = $section['item_type_id'];

        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);
    }

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->sections as $index => $section) {
            foreach (array_keys($section['capacityRows']) as $itemId) {
                $rules["sections.{$index}.capacityRows.{$itemId}.value"]   = 'nullable|numeric|min:0';
                $rules["sections.{$index}.capacityRows.{$itemId}.unit_id"] = 'nullable|integer';
            }
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate();

        $model = $this->resolveModel();

        foreach ($this->removedTypeIds as $typeId) {
            $model->capacities()->where('item_type_id', $typeId)->delete();
        }

        foreach ($this->sections as $section) {
            $typeId = $section['item_type_id'];

            $model->capacities()->where('item_type_id', $typeId)->delete();

            foreach ($section['capacityRows'] as $itemId => $row) {
                $value = $row['value'] ?? '';

                if ($value === '' || $value === null) {
                    continue;
                }

                $model->capacities()->create([
                    'item_type_id' => $typeId,
                    'item_id'      => $itemId,
                    'item_unit_id' => $row['unit_id'] ?? null,
                    'capacity'     => $value,
                ]);
            }
        }

        $this->removedTypeIds = [];

        session()->flash('success', 'Capacity saved successfully.');
    }

    public function render()
    {
        return view('livewire.capacities.capacity-manage');
    }
}
