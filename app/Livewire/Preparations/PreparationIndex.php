<?php

namespace App\Livewire\Preparations;

use App\Models\EventType;
use App\Models\Preparation;
use App\Services\ApiService;
use Livewire\Attributes\On;
use Livewire\Component;

class PreparationIndex extends Component
{
    public $preparations = [];
    public $departments  = [];
    public $warehouses   = [];
    public $eventTypes   = [];

    // Form fields
    public ?int    $preparation_id  = null;
    public string  $name            = '';
    public ?int    $department_id   = null;
    public ?int    $rm_warehouse_id = null;
    public ?int    $fg_warehouse_id = null;
    public array   $selectedEventTypes = [];
    public bool    $editing         = false;

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.preparation-list');

        $this->departments = $api->get('/v1/departments', ['module' => 'production'])['data'] ?? [];

        $allWarehouses    = $api->get('/v1/warehouses', ['related_to_production' => true])['data'] ?? [];
        $this->warehouses = collect($allWarehouses)
            ->filter(fn($wh) => !empty($wh['type']['is_internal']))
            ->values()
            ->toArray();

        $this->eventTypes = EventType::orderBy('name')->get();

        $this->loadPreparations();
    }

    public function loadPreparations(): void
    {
        $this->preparations = Preparation::with('eventTypes')->orderBy('name')->get();
    }

    public function resetForm(): void
    {
        $this->preparation_id  = null;
        $this->name            = '';
        $this->department_id   = null;
        $this->rm_warehouse_id = null;
        $this->fg_warehouse_id = null;
        $this->selectedEventTypes = [];
        $this->editing         = false;
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
        $this->preparation_id  = $prep->id;
        $this->name            = $prep->name;
        $this->department_id   = $prep->department_id;
        $this->rm_warehouse_id = $prep->rm_warehouse_id;
        $this->fg_warehouse_id = $prep->fg_warehouse_id;
        $this->selectedEventTypes = $prep->eventTypes->pluck('id')->toArray();
        $this->editing         = true;

        $this->dispatch('openModal', warehouses: $this->fetchInternalWarehouses($this->department_id));
    }

    public function onDepartmentChange(?int $deptId): void
    {
        $this->department_id   = $deptId;
        $this->rm_warehouse_id = null;
        $this->fg_warehouse_id = null;

        $warehouses = $this->fetchInternalWarehouses($deptId);
        $this->dispatch('prepWarehousesReady', warehouses: $warehouses);
    }

    private function fetchInternalWarehouses(?int $deptId): array
    {
        if (!$deptId) return [];
        $all = $this->api->get('/v1/warehouses', [
            'related_to_production' => true,
            'department_id'         => $deptId,
        ])['data'] ?? [];
        return collect($all)
            ->filter(fn($wh) => !empty($wh['type']['is_internal']))
            ->values()
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'department_id'   => 'required|integer',
            'rm_warehouse_id' => 'required|integer',
            'fg_warehouse_id' => 'required|integer',
            'selectedEventTypes'   => 'required|array|min:1',
            'selectedEventTypes.*' => 'exists:event_types,id',
        ];
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'name'            => $this->name,
            'department_id'   => $this->department_id,
            'rm_warehouse_id' => $this->rm_warehouse_id,
            'fg_warehouse_id' => $this->fg_warehouse_id,
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
