<?php

namespace App\Livewire\Warehouses\Warehouses;

use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class WarehouseIndex extends Component
{

    use AuthorizesRequests;

    public $warehouses;

    public function mount()
    {
        $this->authorize('warehouse-list');

        if(authUser()->hasRole('Super Admin'))
        {
            $this->warehouses = Warehouse::with('warehouseType','company')->get();
        }else{
            $this->warehouses = Warehouse::with('warehouseType','company')
                ->where('company_id',authUser()->company_id)
                ->get();
        }
    }

    #[On('delete')]
    public function delete($id)
    {
        $this->authorize('warehouse-delete');
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return to_route('warehouses')->with('success', 'Warehouse deleted successfully.');
    }

    public function render()
    {
        return view('livewire.warehouses.warehouses.warehouse-index');
    }
}
