<?php

namespace App\Livewire\Preparations;

use App\Models\Preparation;
use App\Services\ApiService;
use Livewire\Attributes\On;
use Livewire\Component;

class PreparationIndex extends Component
{
    public $preparations = [];
    public $departments  = [];
    public $warehouses   = [];

    // Form fields
    public ?int    $preparation_id  = null;
    public string  $name            = '';
    public ?int    $department_id   = null;
    public ?int    $rm_warehouse_id = null;
    public ?int    $fg_warehouse_id = null;
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

        $this->loadPreparations();
    }

    public function loadPreparations(): void
    {
        $this->preparations = Preparation::orderBy('name')->get();
    }

    public function resetForm(): void
    {
        $this->preparation_id  = null;
        $this->name            = '';
        $this->department_id   = null;
        $this->rm_warehouse_id = null;
        $this->fg_warehouse_id = null;
        $this->editing         = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        authorizeRequest('production.preparation-create');
        $this->resetForm();
        $this->dispatch('openModal');
    }

    public function edit(int $id): void
    {
        authorizeRequest('production.preparation-edit');
        $this->resetForm();

        $prep = Preparation::findOrFail($id);
        $this->preparation_id  = $prep->id;
        $this->name            = $prep->name;
        $this->department_id   = $prep->department_id;
        $this->rm_warehouse_id = $prep->rm_warehouse_id;
        $this->fg_warehouse_id = $prep->fg_warehouse_id;
        $this->editing         = true;

        $this->dispatch('openModal');
    }

    protected function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'department_id'   => 'required|integer',
            'rm_warehouse_id' => 'required|integer',
            'fg_warehouse_id' => 'required|integer',
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
            Preparation::findOrFail($this->preparation_id)->update($data);
            $message = 'Preparation updated successfully.';
        } else {
            authorizeRequest('production.preparation-create');
            Preparation::create($data);
            $message = 'Preparation created successfully.';
        }

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
