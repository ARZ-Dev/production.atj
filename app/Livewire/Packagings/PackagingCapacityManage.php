<?php

namespace App\Livewire\Packagings;

use App\Models\Packaging;
use App\Services\ApiService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PackagingCapacityManage extends Component
{
    #[Locked]
    public int    $packagingId;
    #[Locked]
    public string $packagingName = '';
    #[Locked]
    public int    $storedCount = 0;   // capacities on file when the page opened

    #[Locked]
    public array $item_type_ids = [];   // set only through setItemTypes()

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

    public function mount(int $id): void
    {
        authorizeRequest('production.packaging-edit');

        $packaging = Packaging::with('capacities')->findOrFail($id);

        $this->packagingId   = $packaging->id;
        $this->packagingName = $packaging->name;
        $this->loadCatalog();

        // Group by the item's *current* type, falling back to the stored one,
        // so an item the parent has since moved shows up under its new type.
        $unitTypes = $this->unitTypeIndex();
        $typeIds   = [];

        foreach ($packaging->capacities as $capacity) {
            $this->capacities[$capacity->item_unit_id] = format_quantity($capacity->capacity);
            $typeIds[] = $unitTypes[$capacity->item_unit_id] ?? $capacity->item_type_id;
        }

        $this->storedCount   = $packaging->capacities->count();
        $this->item_type_ids = $this->knownTypeIds($typeIds);
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

    /** Strip Cleave.js thousands separators (e.g. "1,000") before validation and use. */
    protected function sanitizeCapacities(): void
    {
        foreach ($this->capacities as $unitId => $value) {
            $this->capacities[$unitId] = trim(str_replace(',', '', (string) ($value ?? '')));
        }
    }

    protected function rules(): array
    {
        // Built per row rather than with a wildcard: only the units on screen count,
        // and the stored values of deselected item types must not block a save.
        $rules = [];
        foreach (array_keys($this->visibleUnits()) as $unitId) {
            $rules["capacities.{$unitId}"] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attributes = [];
        foreach ($this->visibleUnits() as $unitId => $meta) {
            $attributes["capacities.{$unitId}"] = "capacity for {$meta['label']}";
        }

        return $attributes;
    }

    public function save()
    {
        authorizeRequest('production.packaging-edit');

        if (!$this->catalogLoaded) {
            // Saving against an empty catalog would read as "no item types selected"
            // and wipe every capacity this packaging has.
            $this->addError('item_type_ids', 'Items could not be loaded from the auth service, so capacities can\'t be saved right now. Reload the page and try again.');
            return null;
        }

        $this->sanitizeCapacities();

        // With every item type unticked there is nothing to validate, and
        // Livewire throws on an empty rule set rather than passing.
        if ($this->rules()) {
            $this->validate();
        }

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

        DB::transaction(function () use ($rows, $knownUnitIds) {
            $packaging = Packaging::findOrFail($this->packagingId);

            // Replace everything this page can see. A row for a unit the catalog
            // doesn't list (its item was deactivated in the parent) is left alone
            // while its item type stays ticked, so it isn't lost silently.
            $packaging->capacities()
                ->where(fn ($q) => $q->whereIn('item_unit_id', $knownUnitIds)
                    ->orWhereNotIn('item_type_id', $this->item_type_ids ?: [0]))
                ->delete();

            $packaging->capacities()->createMany($rows);
        });

        // A full reload, so the grid comes back exactly as stored.
        return redirect()->route('packagings.capacity', $this->packagingId)
            ->with('success', 'Capacity saved.');
    }

    public function render()
    {
        return view('livewire.packagings.packaging-capacity-manage', [
            'groups' => $this->selectedGroups(),
        ]);
    }
}
