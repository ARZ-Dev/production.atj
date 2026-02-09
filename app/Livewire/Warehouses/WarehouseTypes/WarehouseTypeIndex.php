<?php

namespace App\Livewire\Warehouses\WarehouseTypes;

use App\Models\WarehouseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class WarehouseTypeIndex extends Component
{

    use AuthorizesRequests;

    public $warehouseTypes;

    public function mount()
    {
        $this->authorize('warehouseType-list');

        if(auth()->user()->hasRole('Super Admin')){
            $this->warehouseTypes = WarehouseType::with('company')->get();
        }else{
            $this->warehouseTypes = WarehouseType::with('company')
                ->where('company_id', auth()->user()->company_id)
                ->get();
        }

    }

    #[On('delete')]
    public function delete($id)
    {
        $this->authorize('warehouseType-delete');

        $warehouseType = WarehouseType::findOrFail($id);
        if ($warehouseType->warehouses()->count() > 0) {
            return to_route('warehouse-types')->with('error', 'Cannot delete Warehouse Type with associated Warehouses.');
        }
        $warehouseType->delete();


        return to_route('warehouse-types')->with('success', 'Warehouse Type deleted successfully.');
    }
    public function render()
    {
        return view('livewire.warehouses.warehouse-types.warehouse-type-index');
    }
}
