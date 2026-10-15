<?php

namespace App\Livewire\Capacities;

use App\Livewire\Concerns\OffersOutputWarehouseItemTypes;
use App\Services\ApiService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Capacity of a preparation or line — the same page shape as the packaging
 * capacity page, but with a different model underneath: one figure per *item*,
 * stored in basic units (that's what RecipeDurationService divides by), entered
 * in whichever unit is convenient. The per-unit inputs are linked in the browser;
 * only the basic value and the unit it was entered in reach the server.
 *
 * Only the item types of the warehouse it produces into are offered — see
 * OffersOutputWarehouseItemTypes.
 */
class CapacityManage extends Component
{
    use OffersOutputWarehouseItemTypes;

    #[Locked]
    public string $modelType;   // 'preparation' | 'line'
    #[Locked]
    public int    $modelId;
    #[Locked]
    public string $modelName = '';
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

    // Both keyed by item_id — a stable domain id, never a position. wire:model paths
    // embed this key, and Livewire captures the path when the directive initialises,
    // so a positional key would go stale as soon as an item type is deselected.
    // Values of deselected item types are kept here so re-ticking one restores them;
    // only the items currently shown are validated and saved.
    public array $capacities    = [];   // item_id => capacity per hour, in the item's basic unit
    public array $capacityUnits = [];   // item_id => item_unit_id it was entered in

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(string $modelType, int $id): void
    {
        $this->modelType = $modelType;
        $this->modelId   = $id;

        // A capacity is part of the preparation / line's setup — the same gate
        // as editing it (packaging's capacity page uses packaging-edit).
        authorizeRequest(match ($modelType) {
            'preparation' => 'production.preparation-edit',
            'line'        => 'production.line-edit',
            default       => abort(404),
        });

        $model           = $this->resolveModel();
        $this->modelName = $model->name;
        $this->loadCatalog();

        // A preparation makes semi-finished goods, a line finished ones; both
        // keep the warehouse they produce into in fg_warehouse_id.
        $this->catalogLoaded = $this->loadOutputWarehouse($model, $modelType === 'preparation'
                ? 'semi-finished goods warehouse'
                : 'finished goods warehouse')
            && $this->catalogLoaded;

        // Group by the item's *current* type, falling back to the stored one,
        // so an item the parent has since moved shows up under its new type.
        $itemTypes = $this->itemTypeIndex();
        $typeIds   = [];
        $stored    = $model->capacities()->get();

        foreach ($stored as $capacity) {
            $this->capacities[$capacity->item_id]    = $this->plainNumber($capacity->capacity);
            $this->capacityUnits[$capacity->item_id] = $capacity->item_unit_id ? (int) $capacity->item_unit_id : null;
            $typeIds[] = $itemTypes[$capacity->item_id] ?? $capacity->item_type_id;
        }

        $this->offerOutputWarehouseItemTypes($typeIds);

        $this->storedCount   = $stored->count();
        $this->item_type_ids = $this->knownTypeIds($typeIds);
        $this->fillBlankCapacities();
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

    /** "1250.00" → "1250": the hidden basic value is read back by JS, so no separators. */
    protected function plainNumber($value): string
    {
        $value = (string) $value;

        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
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
                    // qty of basic units in 1 of this unit; the basic unit's own is 1
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

    /** item_id => item_type_id for every item in the catalog. */
    protected function itemTypeIndex(): array
    {
        $index = [];
        foreach ($this->catalog as $typeId => $items) {
            foreach ($items as $item) {
                $index[$item['id']] = (int) $typeId;
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
                    'name'         => $type['name'],
                    'items'        => $this->catalog[$type['id']] ?? [],
                    'in_warehouse' => $type['in_warehouse'] ?? true,
                ];
            }
        }

        return $groups;
    }

    /**
     * item_id => metadata for every item currently on screen that has units —
     * an item without units has no input, so there's nothing to set for it.
     */
    protected function visibleItems(): array
    {
        $items = [];
        foreach ($this->selectedGroups() as $typeId => $group) {
            foreach ($group['items'] as $item) {
                if (empty($item['units'])) {
                    continue;
                }

                $basic = collect($item['units'])->firstWhere('basic', true) ?? $item['units'][0];

                $items[$item['id']] = [
                    'item_type_id'  => (int) $typeId,
                    'label'         => $item['name'],
                    'unit_ids'      => array_column($item['units'], 'id'),
                    'basic_unit_id' => $basic['id'],
                ];
            }
        }

        return $items;
    }

    protected function fillBlankCapacities(): void
    {
        foreach ($this->visibleItems() as $itemId => $meta) {
            if (!array_key_exists($itemId, $this->capacities)) {
                $this->capacities[$itemId] = '';
            }
            if (empty($this->capacityUnits[$itemId])) {
                $this->capacityUnits[$itemId] = $meta['basic_unit_id'];
            }
        }
    }

    public function setItemTypes(array $ids): void
    {
        $this->item_type_ids = $this->knownTypeIds($ids);
        $this->fillBlankCapacities();
        $this->resetValidation('item_type_ids');
    }

    /** Strip any thousands separators before validation and use. */
    protected function sanitizeCapacities(): void
    {
        foreach ($this->capacities as $itemId => $value) {
            $this->capacities[$itemId] = trim(str_replace(',', '', (string) ($value ?? '')));
        }
    }

    protected function rules(): array
    {
        // Built per row rather than with a wildcard: only the items on screen count,
        // and the stored values of deselected item types must not block a save.
        $rules = [];
        foreach (array_keys($this->visibleItems()) as $itemId) {
            $rules["capacities.{$itemId}"] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attributes = [];
        foreach ($this->visibleItems() as $itemId => $meta) {
            $attributes["capacities.{$itemId}"] = "capacity for {$meta['label']}";
        }

        return $attributes;
    }

    public function save()
    {
        if (!$this->catalogLoaded) {
            // Saving against an empty catalog would read as "no item types selected"
            // and wipe every capacity this model has.
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
        foreach ($this->visibleItems() as $itemId => $meta) {
            $value = $this->capacities[$itemId] ?? '';

            // Blank and zero both mean "not produced here" — a zero capacity
            // gives no duration either.
            if ($value === '' || (float) $value <= 0) {
                continue;
            }

            // The unit is a record of how it was entered; the figure is always basic.
            $unitId = (int) ($this->capacityUnits[$itemId] ?? 0);

            $rows[] = [
                'item_type_id' => $meta['item_type_id'],
                'item_id'      => $itemId,
                'item_unit_id' => in_array($unitId, $meta['unit_ids'], true) ? $unitId : $meta['basic_unit_id'],
                'capacity'     => $value,
            ];
        }

        // Items the page can show an input for. One the catalog doesn't list
        // (deactivated in the parent) or lists without units has none.
        $knownItemIds = [];
        foreach ($this->catalog as $items) {
            foreach ($items as $item) {
                if (!empty($item['units'])) {
                    $knownItemIds[] = $item['id'];
                }
            }
        }

        DB::transaction(function () use ($rows, $knownItemIds) {
            $model = $this->resolveModel();

            // Replace everything this page can see. A row for an item it can't
            // show is left alone while its item type stays ticked, so it isn't
            // lost silently.
            $model->capacities()
                ->where(fn ($q) => $q->whereIn('item_id', $knownItemIds ?: [0])
                    ->orWhereNotIn('item_type_id', $this->item_type_ids ?: [0]))
                ->delete();

            $model->capacities()->createMany($rows);
        });

        // A full reload, so every unit input comes back from what was stored —
        // the visible per-unit fields are driven by JS, not by wire:model.
        $route = $this->modelType === 'preparation' ? 'preparations.capacity' : 'lines.capacity';

        return redirect()->route($route, $this->modelId)
            ->with('success', 'Capacity saved successfully.');
    }

    public function render()
    {
        return view('livewire.capacities.capacity-manage', [
            'groups' => $this->selectedGroups(),
        ]);
    }
}
