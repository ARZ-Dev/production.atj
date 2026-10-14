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

        if ($id) {
            $packaging = Packaging::with('eventTypes')->findOrFail($id);

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
        }
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

    protected function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'department_id' => 'required|integer',
            'factory_id'    => ['required', 'integer', Rule::in(array_column($this->factories, 'id'))],

            'source_warehouse_ids'   => 'required|array|min:1',
            'source_warehouse_ids.*' => ['integer', Rule::in(array_column($this->departmentWarehouses, 'id'))],
            'fg_warehouse_id'        => ['required', 'integer', Rule::in(array_column($this->departmentInternalWarehouses, 'id'))],
            'selectedEventTypes'     => 'required|array|min:1',
            'selectedEventTypes.*'   => 'exists:event_types,id',
        ];
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
        return [
            'department_id'        => 'department',
            'factory_id'           => 'factory',
            'source_warehouse_ids' => 'consume-from warehouses',
            'fg_warehouse_id'      => 'finished goods warehouse',
            'selectedEventTypes'   => 'allowed event types',
        ];
    }

    public function submit()
    {
        authorizeRequest($this->editing ? 'production.packaging-edit' : 'production.packaging-create');

        $this->validate();

        $sourceIds = array_values(array_unique(array_map('intval', $this->source_warehouse_ids)));

        DB::transaction(function () use ($sourceIds) {
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

            $packaging->eventTypes()->sync($this->selectedEventTypes);
        });

        return redirect()->route('packagings')
            ->with('success', $this->editing ? 'Packaging updated.' : 'Packaging created.');
    }

    public function render()
    {
        return view('livewire.packagings.packaging-create');
    }
}
