<?php

namespace App\Livewire\ProductionLines;

use App\Models\Line;
use App\Models\Preparation;
use App\Models\ProductionLine;
use App\Services\ApiService;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductionLineIndex extends Component
{
    public $productionLines = [];
    public $departments     = [];
    public $factories       = [];   // warehouses of type "factory"
    public $preparations    = [];
    public $lines           = [];

    // Form fields
    public ?int    $production_line_id  = null;
    public string  $name               = '';
    public ?int    $department_id      = null;
    public ?int    $factory_id         = null;
    public array   $selectedPreparations = [];
    public array   $selectedLines       = [];
    public bool    $editing             = false;

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.production-line-list');

        $this->departments  = $api->get('/v1/departments', ['module' => 'production'])['data'] ?? [];

        $allWarehouses   = $api->get('/v1/warehouses', ['related_to_production' => true])['data'] ?? [];
        $this->factories = collect($allWarehouses)
            ->filter(fn($wh) => !empty($wh['type']['is_factory']))
            ->values()
            ->toArray();

        $this->preparations = Preparation::orderBy('name')->get();
        $this->lines        = Line::orderBy('name')->get();

        $this->loadProductionLines();
    }

    public function loadProductionLines(): void
    {
        $this->productionLines = ProductionLine::with('preparations', 'lines')
            ->orderBy('name')
            ->get();
    }

    public function resetForm(): void
    {
        $this->production_line_id   = null;
        $this->name                 = '';
        $this->department_id        = null;
        $this->factory_id           = null;
        $this->selectedPreparations = [];
        $this->selectedLines        = [];
        $this->editing              = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        authorizeRequest('production.production-line-create');
        $this->resetForm();
        $this->dispatch('openModal', factories: []);
    }

    public function edit(int $id): void
    {
        authorizeRequest('production.production-line-edit');
        $this->resetForm();

        $pl = ProductionLine::with('preparations', 'lines')->findOrFail($id);
        $this->production_line_id   = $pl->id;
        $this->name                 = $pl->name;
        $this->department_id        = $pl->department_id;
        $this->factory_id           = $pl->factory_id;
        $this->selectedPreparations = $pl->preparations->pluck('id')->toArray();
        $this->selectedLines        = $pl->lines->pluck('id')->toArray();
        $this->editing              = true;

        $this->dispatch('openModal', factories: $this->fetchFactoryWarehouses($this->department_id));
    }

    public function onDepartmentChange(?int $deptId): void
    {
        $this->department_id = $deptId;
        $this->factory_id    = null;

        $factories = $this->fetchFactoryWarehouses($deptId);
        $this->dispatch('plFactoriesReady', factories: $factories);
    }

    private function fetchFactoryWarehouses(?int $deptId): array
    {
        if (!$deptId) return [];
        $all = $this->api->get('/v1/warehouses', [
            'related_to_production' => true,
            'department_id'         => $deptId,
        ])['data'] ?? [];
        return collect($all)
            ->filter(fn($wh) => !empty($wh['type']['is_factory']))
            ->values()
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'department_id'        => 'required|integer',
            'factory_id'           => 'required|integer',
            'selectedPreparations' => 'nullable|array',
            'selectedPreparations.*' => 'exists:preparations,id',
            'selectedLines'        => 'nullable|array',
            'selectedLines.*'      => 'exists:lines,id',
        ];
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'name'          => $this->name,
            'department_id' => $this->department_id,
            'factory_id'    => $this->factory_id,
        ];

        if ($this->editing) {
            authorizeRequest('production.production-line-edit');
            $pl = ProductionLine::findOrFail($this->production_line_id);
            $pl->update($data);
        } else {
            authorizeRequest('production.production-line-create');
            $pl = ProductionLine::create($data);
        }

        $pl->preparations()->sync($this->selectedPreparations ?? []);
        $pl->lines()->sync($this->selectedLines ?? []);

        return redirect()->route('production-lines')
            ->with('success', $this->editing ? 'Production Line updated.' : 'Production Line created.');
    }

    #[On('delete')]
    public function delete(int $id)
    {
        authorizeRequest('production.production-line-delete');
        ProductionLine::findOrFail($id)->delete();

        return redirect()->route('production-lines')->with('success', 'Production Line deleted.');
    }

    public function render()
    {
        return view('livewire.production-lines.production-line-index');
    }
}
