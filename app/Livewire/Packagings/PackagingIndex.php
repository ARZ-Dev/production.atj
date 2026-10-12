<?php

namespace App\Livewire\Packagings;

use App\Models\Packaging;
use App\Services\ApiService;
use Livewire\Attributes\On;
use Livewire\Component;

class PackagingIndex extends Component
{
    public $packagings = [];

    // id => name lookups for the remote master data shown in the table
    public array $departmentNames = [];
    public array $factoryNames    = [];
    public array $itemTypeNames   = [];

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.packaging-list');

        $this->departmentNames = collect($api->get('/v1/departments', ['module' => 'production', 'filter' => 'production'])['data'] ?? [])
            ->mapWithKeys(fn ($d) => [(int) $d['id'] => $d['name']])
            ->all();

        $this->factoryNames = collect($api->get('/v1/warehouses')['data'] ?? [])
            ->mapWithKeys(fn ($w) => [(int) $w['id'] => $w['name']])
            ->all();

        $this->itemTypeNames = collect($api->get('/v1/item-types')['data'] ?? [])
            ->mapWithKeys(fn ($t) => [(int) $t['id'] => trim((string) $t['name'])])
            ->all();

        $this->packagings = Packaging::with('capacities:id,packaging_id,item_type_id', 'eventTypes')
            ->orderBy('name')
            ->get();
    }

    #[On('delete')]
    public function delete(int $id)
    {
        authorizeRequest('production.packaging-delete');
        Packaging::findOrFail($id)->delete();

        return redirect()->route('packagings')->with('success', 'Packaging deleted.');
    }

    public function render()
    {
        return view('livewire.packagings.packaging-index');
    }
}
