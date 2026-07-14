<?php

namespace App\Livewire\Lines;

use App\Models\EventType;
use App\Models\Line;
use App\Services\ApiService;
use Livewire\Attributes\On;
use Livewire\Component;

class LineIndex extends Component
{
    public $lines       = [];
    public $departments = [];
    public $warehouses  = [];
    public $eventTypes  = [];

    // Form fields
    public ?int   $line_id         = null;
    public string $name            = '';
    public ?int   $department_id   = null;
    public ?int   $sfg_warehouse_id = null;
    public ?int   $fg_warehouse_id  = null;
    public array  $selectedEventTypes = [];
    public bool   $editing          = false;

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.line-list');

        $this->departments = $api->get('/v1/departments', ['module' => 'production', 'filter' => 'production'])['data'] ?? [];

        $allWarehouses    = $api->get('/v1/warehouses', ['related_to_production' => true])['data'] ?? [];
        $this->warehouses = collect($allWarehouses)
            ->filter(fn($wh) => !empty($wh['type']['is_internal']))
            ->values()
            ->toArray();

        $this->eventTypes = EventType::orderBy('name')->get();

        $this->loadLines();
    }

    public function loadLines(): void
    {
        $this->lines = Line::with('eventTypes')->orderBy('name')->get();
    }

    public function resetForm(): void
    {
        $this->line_id          = null;
        $this->name             = '';
        $this->department_id    = null;
        $this->sfg_warehouse_id = null;
        $this->fg_warehouse_id  = null;
        $this->selectedEventTypes = [];
        $this->editing          = false;
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
        $this->line_id          = $line->id;
        $this->name             = $line->name;
        $this->department_id    = $line->department_id;
        $this->sfg_warehouse_id = $line->sfg_warehouse_id;
        $this->fg_warehouse_id  = $line->fg_warehouse_id;
        $this->selectedEventTypes = $line->eventTypes->pluck('id')->toArray();
        $this->editing          = true;

        $this->dispatch('openModal', warehouses: $this->fetchInternalWarehouses($this->department_id));
    }

    public function onDepartmentChange(?int $deptId): void
    {
        $this->department_id    = $deptId;
        $this->sfg_warehouse_id = null;
        $this->fg_warehouse_id  = null;

        $warehouses = $this->fetchInternalWarehouses($deptId);
        $this->dispatch('lineWarehousesReady', warehouses: $warehouses);
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
            'name'             => 'required|string|max:255',
            'department_id'    => 'required|integer',
            'sfg_warehouse_id' => 'required|integer',
            'fg_warehouse_id'  => 'required|integer',
            'selectedEventTypes'   => 'required|array|min:1',
            'selectedEventTypes.*' => 'exists:event_types,id',
        ];
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'name'             => $this->name,
            'department_id'    => $this->department_id,
            'sfg_warehouse_id' => $this->sfg_warehouse_id,
            'fg_warehouse_id'  => $this->fg_warehouse_id,
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
