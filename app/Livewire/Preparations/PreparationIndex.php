<?php

namespace App\Livewire\Preparations;

use App\Livewire\Concerns\BuildsItemTypeRouting;
use App\Models\EventType;
use App\Models\Preparation;
use App\Services\ApiService;
use Livewire\Attributes\On;
use Livewire\Component;

class PreparationIndex extends Component
{
    use BuildsItemTypeRouting;

    public $preparations = [];
    public $departments  = [];
    public $warehouses   = [];
    public $eventTypes   = [];

    // Form fields
    public ?int    $preparation_id   = null;
    public string  $name             = '';
    public ?int    $department_id    = null;
    public array   $rm_warehouse_ids = [];
    public ?int    $fg_warehouse_id  = null;
    public array   $selectedEventTypes = [];
    public bool    $editing          = false;

    // All warehouses of the selected department: the source picker and the
    // routing table offer these, internal or not.
    public array   $departmentWarehouses = [];
    // The internal subset — the finished-goods picker is still limited to these.
    public array   $departmentInternalWarehouses = [];

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.preparation-list');

        $this->departments = $api->get('/v1/departments', ['module' => 'production', 'filter' => 'production'])['data'] ?? [];

        // Every production warehouse — used to resolve names in the index table.
        $allWarehouses    = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
        $this->warehouses = $allWarehouses;

        $this->eventTypes = EventType::orderBy('name')->get();

        $this->loadPreparations();
    }

    public function loadPreparations(): void
    {
        $this->preparations = Preparation::with('eventTypes')->orderBy('name')->get();
    }

    public function resetForm(): void
    {
        $this->preparation_id   = null;
        $this->name             = '';
        $this->department_id    = null;
        $this->rm_warehouse_ids = [];
        $this->fg_warehouse_id  = null;
        $this->selectedEventTypes = [];
        $this->departmentWarehouses = [];
        $this->departmentInternalWarehouses = [];
        $this->itemTypeRoutingGroups = [];
        $this->itemTypeWarehouses = [];
        $this->editing          = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        authorizeRequest('production.preparation-create');
        $this->resetForm();
        $this->dispatch('openModal', warehouses: []);
    }

    public function edit(int $id): void
    {
        authorizeRequest('production.preparation-edit');
        $this->resetForm();

        $prep = Preparation::with('eventTypes')->findOrFail($id);
        $this->preparation_id   = $prep->id;
        $this->name             = $prep->name;
        $this->department_id    = $prep->department_id;
        $this->rm_warehouse_ids = $prep->sourceWarehouseIds();
        $this->fg_warehouse_id  = $prep->fg_warehouse_id;
        $this->selectedEventTypes = $prep->eventTypes->pluck('id')->toArray();
        $this->editing          = true;

        $this->loadDepartmentWarehouses($this->department_id);
        $this->loadItemTypeRouting($prep->item_type_warehouses, $this->selectedEventTypes);

        $this->dispatch(
            'openModal',
            warehouses: $this->departmentWarehouses,
            internalWarehouses: $this->departmentInternalWarehouses,
        );
    }

    public function onDepartmentChange(?int $deptId): void
    {
        $this->department_id    = $deptId;
        $this->rm_warehouse_ids = [];
        $this->fg_warehouse_id  = null;

        $this->loadDepartmentWarehouses($deptId);
        $this->dispatch(
            'prepWarehousesReady',
            warehouses: $this->departmentWarehouses,
            internalWarehouses: $this->departmentInternalWarehouses,
        );
    }

    /**
     * Rebuild the item-type routing table whenever the event types change.
     */
    public function onEventTypesChange(array $eventTypeIds): void
    {
        $this->selectedEventTypes = array_values(array_filter(array_map('intval', $eventTypeIds)));
        $this->buildItemTypeRouting($this->selectedEventTypes);
    }

    /**
     * Load the selected department's warehouses in one call and split them:
     * the source picker (and the routing table under it) offers all of them,
     * while the finished-goods picker stays limited to the internal ones.
     */
    private function loadDepartmentWarehouses(?int $deptId): void
    {
        $all = $deptId
            ? ($this->api->get('/v1/warehouses', ['department_id' => $deptId])['data'] ?? [])
            : [];

        $this->departmentWarehouses         = $all;
        $this->departmentInternalWarehouses = collect($all)
            ->filter(fn($wh) => !empty($wh['type']['is_internal']))
            ->values()
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'department_id'        => 'required|integer',
            'rm_warehouse_ids'     => 'required|array|min:1',
            'rm_warehouse_ids.*'   => 'integer',
            'fg_warehouse_id'      => 'required|integer',
            'selectedEventTypes'   => 'required|array|min:1',
            'selectedEventTypes.*' => 'exists:event_types,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'rm_warehouse_ids' => 'raw material warehouses',
        ];
    }

    public function submit()
    {
        $this->validate();

        $rmIds = array_values(array_unique(array_map('intval', $this->rm_warehouse_ids)));

        $data = [
            'name'                 => $this->name,
            'department_id'        => $this->department_id,
            // The scalar column stays the default warehouse for anything
            // resolved without an item type.
            'rm_warehouse_id'      => $rmIds[0],
            'rm_warehouse_ids'     => $rmIds,
            'fg_warehouse_id'      => $this->fg_warehouse_id,
            'item_type_warehouses' => $this->itemTypeWarehousesForSave($rmIds),
        ];

        if ($this->editing) {
            authorizeRequest('production.preparation-edit');
            $prep = Preparation::findOrFail($this->preparation_id);
            $prep->update($data);
            $message = 'Preparation updated successfully.';
        } else {
            authorizeRequest('production.preparation-create');
            $prep = Preparation::create($data);
            $message = 'Preparation created successfully.';
        }

        $prep->eventTypes()->sync($this->selectedEventTypes);

        return redirect()->route('preparations')->with('success', $message);
    }

    #[On('delete')]
    public function delete(int $id)
    {
        authorizeRequest('production.preparation-delete');
        Preparation::findOrFail($id)->delete();

        return redirect()->route('preparations')->with('success', 'Preparation deleted successfully.');
    }

    public function render()
    {
        return view('livewire.preparations.preparation-index');
    }
}
