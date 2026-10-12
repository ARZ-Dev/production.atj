<?php

namespace App\Livewire\Packagings;

use App\Livewire\Concerns\BuildsItemTypeRouting;
use App\Models\EventType;
use App\Models\Packaging;
use App\Services\ApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PackagingCreate extends Component
{
    // Warehouse per item type, under the event-type picker — shared with the
    // preparation / line popups.
    use BuildsItemTypeRouting;

    #[Locked]
    public ?int $packagingId = null;
    #[Locked]
    public bool $editing = false;

    // Form fields
    public string $name          = '';
    public ?int   $department_id = null;
    public ?int   $factory_id    = null;
    #[Locked]
    public array  $item_type_ids = [];   // set only through setItemTypes()

    // Plan-board setup — the same shape as preparations and lines: the event
    // types allowed on this packaging's lane, where their consumed items come
    // from (a list, the first being the default) and where output goes.
    public array  $source_warehouse_ids = [];
    public ?int   $fg_warehouse_id      = null;
    #[Locked]
    public array  $selectedEventTypes   = [];   // set only through onEventTypesChange()

    #[Locked]
    public array $departments = [];
    #[Locked]
    public array $factories   = [];   // warehouses of type "factory" in the selected department
    #[Locked]
    public array $departmentWarehouses = [];          // every warehouse of the department
    #[Locked]
    public array $departmentInternalWarehouses = [];  // its internal subset
    #[Locked]
    public array $eventTypes = [];

    #[Locked]
    public array $itemTypes = [];     // [['id', 'name', 'item_count'], …] sorted by name
    #[Locked]
    public array $catalog   = [];     // item_type_id => [['id', 'name', 'code', 'units' => [...]], …]
    #[Locked]
    public bool  $catalogLoaded = false;

    // Keyed by item_unit_id — a stable domain id, never a position. wire:model paths
    // embed this key, and Livewire captures the path when the directive initialises,
    // so a positional key would go stale as soon as an item type is deselected.
    // Values of deselected item types are kept here so re-ticking one restores them;
    // only the units currently shown are validated and saved.
    public array $capacities = [];

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(?int $id = null): void
    {
        authorizeRequest($id ? 'production.packaging-edit' : 'production.packaging-create');

        $this->departments = $this->api->get('/v1/departments', ['module' => 'production', 'filter' => 'production'])['data'] ?? [];
        $this->eventTypes  = EventType::orderBy('name')->get(['id', 'name', 'color'])->toArray();
        $this->loadCatalog();

        if ($id) {
            $packaging = Packaging::with('capacities', 'eventTypes')->findOrFail($id);

            $this->packagingId   = $packaging->id;
            $this->editing       = true;
            $this->name          = $packaging->name;
            $this->department_id = $packaging->department_id;
            $this->factory_id    = $packaging->factory_id;
            $this->loadDepartmentWarehouses($this->department_id);

            $this->source_warehouse_ids = $packaging->sourceWarehouseIds();
            $this->fg_warehouse_id      = $packaging->fg_warehouse_id;
            $this->selectedEventTypes   = $packaging->eventTypes->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->loadItemTypeRouting($packaging->item_type_warehouses, $this->selectedEventTypes);

            // Group by the item's *current* type, falling back to the stored one,
            // so an item the parent has since moved shows up under its new type.
            $unitTypes = $this->unitTypeIndex();
            $typeIds   = [];

            foreach ($packaging->capacities as $capacity) {
                $this->capacities[$capacity->item_unit_id] = format_quantity($capacity->capacity);
                $typeIds[] = $unitTypes[$capacity->item_unit_id] ?? $capacity->item_type_id;
            }

            $this->item_type_ids = $this->knownTypeIds($typeIds);
        }

        $this->fillBlankCapacities();
    }

    /**
     * Every active item with its units, grouped locally by item_type_id. One call
     * rather than one per type: `/v1/items?item_type=` matches on the type *name*,
     * and several parent type names carry trailing spaces.
     */
    protected function loadCatalog(): void
    {
        $types    = $this->api->get('/v1/item-types');
        $response = $this->api->get('/v1/items', ['is_active' => true]);

        $this->catalogLoaded = !empty($types['success']) && !empty($response['success']);

        $catalog = [];
        foreach ($response['data'] ?? [] as $item) {
            $typeId = (int) ($item['item_type_id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }

            $units = collect($item['units'] ?? [])
                ->map(fn ($unit) => [
                    'id'      => (int) $unit['id'],
                    'name'    => trim((string) $unit['name']),
                    'symbol'  => trim((string) ($unit['symbol'] ?? '')),
                    'basic'   => !empty($unit['basic']),
                    'formula' => (float) ($unit['formula'] ?? 0),
                ])
                ->sortByDesc('basic')   // basic unit first; stable for the rest
                ->values()
                ->all();

            $catalog[$typeId][] = [
                'id'    => (int) $item['id'],
                'name'  => trim((string) $item['name']),
                'code'  => $item['code'] ?? null,
                'units' => $units,
            ];
        }

        foreach ($catalog as &$items) {
            usort($items, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));
        }
        unset($items);

        $this->catalog   = $catalog;
        $this->itemTypes = collect($types['data'] ?? [])
            ->map(fn ($type) => [
                'id'         => (int) $type['id'],
                'name'       => trim((string) $type['name']),
                'item_count' => count($catalog[(int) $type['id']] ?? []),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** item_unit_id => item_type_id for every unit in the catalog. */
    protected function unitTypeIndex(): array
    {
        $index = [];
        foreach ($this->catalog as $typeId => $items) {
            foreach ($items as $item) {
                foreach ($item['units'] as $unit) {
                    $index[$unit['id']] = (int) $typeId;
                }
            }
        }

        return $index;
    }

    /** The given ids that exist in the parent, in item-type list order. */
    protected function knownTypeIds(array $ids): array
    {
        $ids = array_map('intval', $ids);

        return array_values(array_filter(
            array_column($this->itemTypes, 'id'),
            fn ($id) => in_array($id, $ids, true)
        ));
    }

    /** Item types currently ticked, with their items, in display order. */
    protected function selectedGroups(): array
    {
        $groups = [];
        foreach ($this->itemTypes as $type) {
            if (in_array($type['id'], $this->item_type_ids, true)) {
                $groups[$type['id']] = [
                    'name'  => $type['name'],
                    'items' => $this->catalog[$type['id']] ?? [],
                ];
            }
        }

        return $groups;
    }

    /** item_unit_id => metadata for every unit row currently on screen. */
    protected function visibleUnits(): array
    {
        $units = [];
        foreach ($this->selectedGroups() as $typeId => $group) {
            foreach ($group['items'] as $item) {
                foreach ($item['units'] as $unit) {
                    $units[$unit['id']] = [
                        'item_type_id' => (int) $typeId,
                        'item_id'      => $item['id'],
                        'label'        => "{$item['name']} ({$unit['name']})",
                    ];
                }
            }
        }

        return $units;
    }

    protected function fillBlankCapacities(): void
    {
        foreach (array_keys($this->visibleUnits()) as $unitId) {
            if (!array_key_exists($unitId, $this->capacities)) {
                $this->capacities[$unitId] = '';
            }
        }
    }

    public function setItemTypes(array $ids): void
    {
        $this->item_type_ids = $this->knownTypeIds($ids);
        $this->fillBlankCapacities();
        $this->resetValidation('item_type_ids');
    }

    public function onDepartmentChange(?int $deptId): void
    {
        $this->department_id        = $deptId ?: null;
        $this->factory_id           = null;
        $this->source_warehouse_ids = [];
        $this->fg_warehouse_id      = null;

        $this->loadDepartmentWarehouses($this->department_id);

        $this->dispatch('packagingDepartmentLoaded',
            factories: $this->factories,
            warehouses: $this->departmentWarehouses,
            internalWarehouses: $this->departmentInternalWarehouses,
        );
    }

    /** Rebuild the item-type routing table whenever the event types change. */
    public function onEventTypesChange(array $eventTypeIds): void
    {
        $this->selectedEventTypes = EventType::whereIn('id', array_filter(array_map('intval', $eventTypeIds)))
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->buildItemTypeRouting($this->selectedEventTypes);
        $this->resetValidation('selectedEventTypes');
    }

    /**
     * One call for the department's warehouses, split three ways: its factories
     * (the Factory picker), all of them (consumed-from picker and routing table —
     * consuming from an external store is legitimate) and the internal ones
     * (finished goods), exactly as the preparation / line popups split them.
     */
    protected function loadDepartmentWarehouses(?int $deptId): void
    {
        $all = $deptId
            ? collect($this->api->get('/v1/warehouses', ['department_id' => $deptId])['data'] ?? [])
                ->filter(fn ($wh) => (int) ($wh['department_id'] ?? $wh['department']['id'] ?? 0) === $deptId)
                ->values()
            : collect();

        $slim = fn ($wh) => ['id' => (int) $wh['id'], 'name' => $wh['name']];

        $this->factories                    = $all->filter(fn ($wh) => !empty($wh['type']['is_factory']))->map($slim)->values()->all();
        $this->departmentWarehouses         = $all->map($slim)->all();
        $this->departmentInternalWarehouses = $all->filter(fn ($wh) => !empty($wh['type']['is_internal']))->map($slim)->values()->all();
    }

    /** Strip Cleave.js thousands separators (e.g. "1,000") before validation and use. */
    protected function sanitizeCapacities(): void
    {
        foreach ($this->capacities as $unitId => $value) {
            $this->capacities[$unitId] = trim(str_replace(',', '', (string) ($value ?? '')));
        }
    }

    protected function rules(): array
    {
        $rules = [
            'name'          => 'required|string|max:255',
            'department_id' => 'required|integer',
            'factory_id'    => ['required', 'integer', Rule::in(array_column($this->factories, 'id'))],

            'source_warehouse_ids'   => 'required|array|min:1',
            'source_warehouse_ids.*' => ['integer', Rule::in(array_column($this->departmentWarehouses, 'id'))],
            'fg_warehouse_id'        => ['required', 'integer', Rule::in(array_column($this->departmentInternalWarehouses, 'id'))],
            'selectedEventTypes'     => 'required|array|min:1',
            'selectedEventTypes.*'   => 'exists:event_types,id',
        ];

        // Built per row rather than with a wildcard: only the units on screen count,
        // and the stored values of deselected item types must not block a save.
        foreach (array_keys($this->visibleUnits()) as $unitId) {
            $rules["capacities.{$unitId}"] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'factory_id.in'             => 'Select a factory belonging to the chosen department.',
            'source_warehouse_ids.*.in' => 'Select warehouses belonging to the chosen department.',
            'fg_warehouse_id.in'        => 'Select an internal warehouse of the chosen department.',
        ];
    }

    protected function validationAttributes(): array
    {
        $attributes = [
            'department_id'        => 'department',
            'factory_id'           => 'factory',
            'source_warehouse_ids' => 'consume-from warehouses',
            'fg_warehouse_id'      => 'finished goods warehouse',
            'selectedEventTypes'   => 'allowed event types',
        ];

        foreach ($this->visibleUnits() as $unitId => $meta) {
            $attributes["capacities.{$unitId}"] = "capacity for {$meta['label']}";
        }

        return $attributes;
    }

    public function submit()
    {
        authorizeRequest($this->editing ? 'production.packaging-edit' : 'production.packaging-create');

        if (!$this->catalogLoaded) {
            // Saving against an empty catalog would read as "no item types selected"
            // and wipe every capacity this packaging has.
            $this->addError('item_type_ids', 'Items could not be loaded from the auth service, so capacities can\'t be saved right now. Reload the page and try again.');
            return null;
        }

        $this->sanitizeCapacities();
        $this->validate();

        $rows = [];
        foreach ($this->visibleUnits() as $unitId => $meta) {
            $value = $this->capacities[$unitId] ?? '';

            // Blank and zero both mean "this packaging doesn't handle this unit".
            if ($value === '' || (float) $value <= 0) {
                continue;
            }

            $rows[] = [
                'item_type_id' => $meta['item_type_id'],
                'item_id'      => $meta['item_id'],
                'item_unit_id' => $unitId,
                'capacity'     => $value,
            ];
        }

        $knownUnitIds = array_keys($this->unitTypeIndex());
        $sourceIds    = array_values(array_unique(array_map('intval', $this->source_warehouse_ids)));

        DB::transaction(function () use ($rows, $knownUnitIds, $sourceIds) {
            $data = [
                'name'                 => $this->name,
                'department_id'        => $this->department_id,
                'factory_id'           => $this->factory_id,
                // The scalar column stays the default warehouse for anything
                // resolved without an item type.
                'source_warehouse_id'  => $sourceIds[0],
                'source_warehouse_ids' => $sourceIds,
                'item_type_warehouses' => $this->itemTypeWarehousesForSave($sourceIds),
                'fg_warehouse_id'      => $this->fg_warehouse_id,
            ];

            if ($this->editing) {
                $packaging = Packaging::findOrFail($this->packagingId);
                $packaging->update($data);
            } else {
                $packaging = Packaging::create($data);
            }

            // Replace everything this page can see. A row for a unit the catalog
            // doesn't list (its item was deactivated in the parent) is left alone
            // while its item type stays ticked, so it isn't lost silently.
            $packaging->capacities()
                ->where(fn ($q) => $q->whereIn('item_unit_id', $knownUnitIds)
                    ->orWhereNotIn('item_type_id', $this->item_type_ids ?: [0]))
                ->delete();

            $packaging->capacities()->createMany($rows);
            $packaging->eventTypes()->sync($this->selectedEventTypes);
        });

        return redirect()->route('packagings')
            ->with('success', $this->editing ? 'Packaging updated.' : 'Packaging created.');
    }

    public function render()
    {
        return view('livewire.packagings.packaging-create', [
            'groups' => $this->selectedGroups(),
        ]);
    }
}
