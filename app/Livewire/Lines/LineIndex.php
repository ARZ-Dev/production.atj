<?php

namespace App\Livewire\Lines;

use App\Livewire\Concerns\BuildsItemTypeRouting;
use App\Models\EventType;
use App\Models\Line;
use App\Services\ApiService;
use Livewire\Attributes\On;
use Livewire\Component;

class LineIndex extends Component
{
    use BuildsItemTypeRouting;

    public $lines       = [];
    public $departments = [];
    public $warehouses  = [];
    public $eventTypes  = [];

    // Form fields
    public ?int   $line_id           = null;
    public string $name              = '';
    public ?int   $department_id     = null;
    public array  $sfg_warehouse_ids = [];
    public ?int   $fg_warehouse_id   = null;
    public array  $selectedEventTypes = [];
    public bool   $editing           = false;

    // All warehouses of the selected department: the source picker and the
    // routing table offer these, internal or not.
    public array  $departmentWarehouses = [];
    // The internal subset — the finished-goods picker is still limited to these.
    public array  $departmentInternalWarehouses = [];

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.line-list');

        $this->departments = $api->get('/v1/departments', ['module' => 'production', 'filter' => 'production'])['data'] ?? [];

        // Every production warehouse — used to resolve names in the index table.
        $allWarehouses    = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
        $this->warehouses = $allWarehouses;

        $this->eventTypes = EventType::orderBy('name')->get();

        $this->loadLines();
    }

    public function loadLines(): void
    {
        $this->lines = Line::with('eventTypes')->orderBy('name')->get();
    }

    public function resetForm(): void
    {
        $this->line_id           = null;
        $this->name              = '';
        $this->department_id     = null;
        $this->sfg_warehouse_ids = [];
        $this->fg_warehouse_id   = null;
        $this->selectedEventTypes = [];
        $this->departmentWarehouses = [];
        $this->departmentInternalWarehouses = [];
        $this->itemTypeRoutingGroups = [];
        $this->itemTypeWarehouses = [];
        $this->editing           = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        authorizeRequest('production.line-create');
        $this->resetForm();
        $this->dispatch('openModal', warehouses: []);
    }

    public function edit(int $id): void
    {
        authorizeRequest('production.line-edit');
        $this->resetForm();

        $line = Line::with('eventTypes')->findOrFail($id);
        $this->line_id           = $line->id;
        $this->name              = $line->name;
        $this->department_id     = $line->department_id;
        $this->sfg_warehouse_ids = $line->sourceWarehouseIds();
        $this->fg_warehouse_id   = $line->fg_warehouse_id;
        $this->selectedEventTypes = $line->eventTypes->pluck('id')->toArray();
        $this->editing           = true;

        $this->loadDepartmentWarehouses($this->department_id);
        $this->loadItemTypeRouting($line->item_type_warehouses, $this->selectedEventTypes);

        $this->dispatch(
            'openModal',
            warehouses: $this->departmentWarehouses,
            internalWarehouses: $this->departmentInternalWarehouses,
        );
    }

    public function onDepartmentChange(?int $deptId): void
    {
        $this->department_id     = $deptId;
        $this->sfg_warehouse_ids = [];
        $this->fg_warehouse_id   = null;

        $this->loadDepartmentWarehouses($deptId);
        $this->dispatch(
            'lineWarehousesReady',
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
            'sfg_warehouse_ids'    => 'required|array|min:1',
            'sfg_warehouse_ids.*'  => 'integer',
            'fg_warehouse_id'      => 'required|integer',
            'selectedEventTypes'   => 'required|array|min:1',
            'selectedEventTypes.*' => 'exists:event_types,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'sfg_warehouse_ids' => 'raw material warehouses',
        ];
    }

    public function submit()
    {
        $this->validate();

        $sfgIds = array_values(array_unique(array_map('intval', $this->sfg_warehouse_ids)));

        $data = [
            'name'                 => $this->name,
            'department_id'        => $this->department_id,
            // The scalar column stays the default warehouse for anything
            // resolved without an item type.
            'sfg_warehouse_id'     => $sfgIds[0],
            'sfg_warehouse_ids'    => $sfgIds,
            'fg_warehouse_id'      => $this->fg_warehouse_id,
            'item_type_warehouses' => $this->itemTypeWarehousesForSave($sfgIds),
        ];

        if ($this->editing) {
            authorizeRequest('production.line-edit');
            $line = Line::findOrFail($this->line_id);
            $line->update($data);
            $message = 'Line updated successfully.';
        } else {
            authorizeRequest('production.line-create');
            $line = Line::create($data);
            $message = 'Line created successfully.';
        }

        $line->eventTypes()->sync($this->selectedEventTypes);

        return redirect()->route('lines')->with('success', $message);
    }

    #[On('delete')]
    public function delete(int $id)
    {
        authorizeRequest('production.line-delete');
        Line::findOrFail($id)->delete();

        return redirect()->route('lines')->with('success', 'Line deleted successfully.');
    }

    public function render()
    {
        return view('livewire.lines.line-index');
    }
}
