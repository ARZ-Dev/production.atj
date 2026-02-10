<?php

namespace App\Livewire\Warehouses\Warehouses;

use App\Models\Company;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class WarehouseCreate extends Component
{

    use AuthorizesRequests;

    public $warehouse;
    public $id;
    public $name;
    public $warehouseTypes = [];
    public $warehouse_type_id;
    public $companies = [];
    public $company_id;
    public $address;
    public bool $editing = false;


    public function mount($id = 0)
    {
        $this->authorize('warehouse-create');

        if(authUser()->hasRole('Super Admin'))
        {
            $this->companies = Company::all();

        }
        else{
            $this->company_id = authUser()->company_id;
            $this->warehouseTypes = WarehouseType::where('company_id', $this->company_id)->get();
        }

        if ($id) {
            $this->editing = true;
            $this->warehouse = Warehouse::findOrFail($id);
            $this->fill($this->warehouse->toArray());
            $this->warehouseTypes = WarehouseType::where('company_id', $this->warehouse->company_id)->get();

        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|unique:warehouses,name,' . $this->id,
            'warehouse_type_id' => 'required|exists:warehouse_types,id',
            'company_id' => 'required|exists:companies,id',
            'address' => 'nullable|string',
        ];
    }

    #[On('getWarehouseTypes')]
    public function getWarehouseTypes($companyId)
    {
        $this->warehouseTypes = WarehouseType::where('company_id', $companyId)->get();
        $this->dispatch('setWarehouseTypes', $this->warehouseTypes);
    }

    public function submit()
    {
        $this->authorize('warehouse-create');

        $this->validate();

        if ($this->editing) {
            $this->warehouse->update([
                'name' => $this->name,
                'warehouse_type_id' => $this->warehouse_type_id,
                'company_id' => $this->company_id ? $this->company_id : authUser()->company_id,
                'address' => $this->address,
            ]);

            return to_route('warehouses')->with('success', 'Warehouse updated successfully.');
        } else {
            Warehouse::create([
                'name' => $this->name,
                'warehouse_type_id' => $this->warehouse_type_id,
                'company_id' => $this->company_id ? $this->company_id : authUser()->company_id,
                'address' => $this->address,
            ]);

            return to_route('warehouses')->with('success', 'Warehouse created successfully.');
        }
    }
    public function render()
    {
        return view('livewire.warehouses.warehouses.warehouse-create');
    }
}
