<?php

namespace App\Livewire\Warehouses\WarehouseTypes;

use App\Models\WarehouseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class WarehouseTypeCreate extends Component
{

    use AuthorizesRequests;

    public $warehouseType;
    public $id;
    public $name;

    public bool $editing = false;

    public function mount($id = 0)
    {
        authorizeRequest('production.warehouseType-create');

        if ($id) {
            $this->editing = true;
            $this->warehouseType = WarehouseType::findOrFail($id);
            $this->name = $this->warehouseType->name;
            $this->id = $this->warehouseType->id;
        }
    }


    public function rules()
    {
        return [
            'name' => 'required|string|unique:warehouse_types,name,' . $this->id,
        ];
    }

    public function submit()
    {
        authorizeRequest('production.warehouseType-create');

        $this->validate();

        if ($this->editing) {
            $this->warehouseType->update([
                'name' => $this->name,
            ]);

            return to_route('warehouse-types')->with('success', 'Warehouse Type updated successfully.');
        } else {
            WarehouseType::create([
                'name' => $this->name,
            ]);

            return to_route('warehouse-types')->with('success', 'Warehouse Type created successfully.');
        }
    }
    public function render()
    {
        return view('livewire.warehouses.warehouse-types.warehouse-type-create');
    }
}
