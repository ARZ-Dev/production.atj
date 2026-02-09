<?php

namespace App\Livewire\Factories;

use App\Models\Company;
use App\Models\Factory;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class FactoryCreate extends Component
{
    use AuthorizesRequests;

    public $factory;
    public $id;
    public $name;
    public $address;
    public $warehouseTypes = [];
    public $warehouses = [];
    public $company_id;
    public $companies = [];
    public $editing = false;

    public function mount($id = null)
    {
        $this->id = $id;
        $this->editing = !is_null($id);

        if ($this->editing) {
            $this->authorize('factory-edit');
        } else {
            $this->authorize('factory-create');
        }

        if (auth()->user()->hasRole('Super Admin')) {
            $this->companies = Company::all();
        }else{
            $this->company_id = auth()->user()->company_id;
            $this->warehouseTypes = WarehouseType::where('company_id', $this->company_id)->get();
        }

        if ($this->editing) {
            $this->factory = Factory::with(['warehouses.warehouseType'])->findOrFail($id);

            $this->name = $this->factory->name;
            $this->address = $this->factory->address;
            $this->company_id = $this->factory->company_id;

            $this->warehouseTypes = WarehouseType::where('company_id', $this->company_id)->get();

            $this->warehouses = [];
            foreach ($this->factory->warehouses as $warehouse) {
                $this->warehouses[] = [
                    'id' => $warehouse->id,
                    'warehouse_type_id' => $warehouse->warehouse_type_id,
                    'warehouse_name' => $warehouse->name,
                ];
            }

            if (empty($this->warehouses)) {
                $this->warehouses = [
                    [
                        'id' => null,
                        'warehouse_type_id' => '',
                        'warehouse_name' => '',
                    ]
                ];
            }
        } else {
            $this->warehouses = [
                [
                    'id' => null,
                    'warehouse_type_id' => '',
                    'warehouse_name' => '',
                ]
            ];

        }
    }

    #[On('getWarehouseTypes')]
    public function getWarehouseTypes($companyId)
    {
        $this->company_id = $companyId;
        $this->warehouseTypes = WarehouseType::where('company_id', $companyId)->get();

        $this->dispatch('setWarehouseTypes', $this->warehouseTypes);
    }

    #[On('addWarehouseRow')]
    public function addWarehouseRow()
    {
        $this->warehouses[] = [
            'id' => null,
            'warehouse_type_id' => '',
            'warehouse_name' => '',
        ];
    }

    #[On('removeWarehouseRow')]
    public function removeWarehouseRow($index)
    {
        if (isset($this->warehouses[$index]['id']) && $this->warehouses[$index]['id']) {
            Warehouse::find($this->warehouses[$index]['id'])?->delete();
        }

        unset($this->warehouses[$index]);
        $this->warehouses = array_values($this->warehouses);

        if (empty($this->warehouses)) {
            $this->warehouses = [
                [
                    'id' => null,
                    'warehouse_type_id' => '',
                    'warehouse_name' => '',
                ]
            ];
        }
    }

    public function submit()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'address' => 'nullable|string',
            'warehouses' => 'required|array|min:1',
            'warehouses.*.warehouse_type_id' => 'required|exists:warehouse_types,id|distinct',
            'warehouses.*.warehouse_name' => 'required|string|max:255',
        ];

        $messages = [
            'name.required' => 'Factory name is required.',
            'company_id.required' => 'Company is required.',
            'company_id.exists' => 'Selected company does not exist.',
            'warehouses.required' => 'At least one warehouse is required.',
            'warehouses.min' => 'At least one warehouse is required.',
            'warehouses.*.warehouse_type_id.required' => 'Warehouse type is required.',
            'warehouses.*.warehouse_type_id.exists' => 'Selected warehouse type does not exist.',
            'warehouses.*.warehouse_name.required' => 'Warehouse name is required.',
        ];

        $this->validate($rules, $messages);

        try {
            if ($this->editing) {
                $this->factory->update([
                    'company_id' => $this->company_id,
                    'name' => $this->name,
                    'address' => $this->address,
                ]);

                $factory = $this->factory;

                $existingWarehouseIds = collect($this->warehouses)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                Warehouse::where('factory_id', $factory->id)
                    ->whereNotIn('id', $existingWarehouseIds)
                    ->delete();

                foreach ($this->warehouses as $warehouseData) {
                    if (isset($warehouseData['id']) && $warehouseData['id']) {
                        Warehouse::where('id', $warehouseData['id'])->update([
                            'name' => $warehouseData['warehouse_name'],
                            'warehouse_type_id' => $warehouseData['warehouse_type_id'],
                            'company_id' => $this->company_id,
                        ]);
                    } else {
                        Warehouse::create([
                            'name' => $warehouseData['warehouse_name'],
                            'warehouse_type_id' => $warehouseData['warehouse_type_id'],
                            'factory_id' => $factory->id,
                            'company_id' => $this->company_id,
                        ]);
                    }
                }

                return redirect()->route('factories')->with('success', 'Factory updated successfully.');
            } else {
                $factory = Factory::create([
                    'company_id' => $this->company_id,
                    'name' => $this->name,
                    'address' => $this->address,
                ]);

                foreach ($this->warehouses as $warehouseData) {
                    Warehouse::create([
                        'name' => $warehouseData['warehouse_name'],
                        'warehouse_type_id' => $warehouseData['warehouse_type_id'],
                        'factory_id' => $factory->id,
                        'company_id' => $this->company_id,
                    ]);
                }

                return redirect()->route('factories')->with('success', 'Factory created successfully with ' . count($this->warehouses) . ' warehouse(s).');
            }


        } catch (\Exception $e) {
            $action = $this->editing ? 'updating' : 'creating';
            session()->flash('error', 'An error occurred while ' . $action . ' the factory: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.factories.factory-create');
    }
}