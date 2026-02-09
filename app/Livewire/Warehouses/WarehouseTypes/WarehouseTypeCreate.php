<?php

namespace App\Livewire\Warehouses\WarehouseTypes;

use App\Models\Company;
use App\Models\WarehouseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class WarehouseTypeCreate extends Component
{

    use AuthorizesRequests;

    public $warehouseType; 
    public $companies = [];
    public $company_id;
    public $id;
    public $name;

    public bool $editing = false;

    public function mount($id = 0)
    {
        $this->authorize('warehouseType-create');
        if(auth()->user()->hasRole('Super Admin')){
            $this->companies = Company::all();
        }else{
            $this->company_id = auth()->user()->company_id;
        }

        if ($id) {
            $this->editing = true;
            $this->warehouseType = WarehouseType::findOrFail($id);
            $this->company_id = $this->warehouseType->company_id;
            $this->name = $this->warehouseType->name;
            $this->id = $this->warehouseType->id;
        }
    }


    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|unique:warehouse_types,name,' . $this->id,
        ];
    }

    public function submit()
    {
        $this->authorize('warehouseType-create');

        $this->validate();

        if ($this->editing) {
            $this->warehouseType->update([
                'name' => $this->name,
                'company_id' => $this->company_id,
            ]);

            return to_route('warehouse-types')->with('success', 'Warehouse Type updated successfully.');
        } else {
            WarehouseType::create([
                'name' => $this->name,
                'company_id' => $this->company_id,
            ]);

            return to_route('warehouse-types')->with('success', 'Warehouse Type created successfully.');
        }
    }
    public function render()
    {
        return view('livewire.warehouses.warehouse-types.warehouse-type-create');
    }
}
