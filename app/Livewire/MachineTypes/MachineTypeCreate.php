<?php

namespace App\Livewire\MachineTypes;

use App\Models\Company;
use App\Models\MachineType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class MachineTypeCreate extends Component
{
    use AuthorizesRequests;

    public $machineType;
    public $companies = [];
    public $company_id;
    public $id;
    public $name;

    public bool $editing = false;

    public function mount($id = 0)
    {
        $this->authorize('machineType-create');
        if (authUser()->hasRole('Super Admin')) {
            $this->companies = Company::all();
        } else {
            $this->company_id = authUser()->company_id;
        }

        if ($id) {
            $this->editing = true;
            $this->machineType = MachineType::findOrFail($id);
            $this->company_id = $this->machineType->company_id;
            $this->name = $this->machineType->name;
            $this->id = $this->machineType->id;
        }
    }


    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|unique:machine_types,name,' . $this->id,
        ];
    }

    public function submit()
    {
        $this->authorize('machineType-create');

        $this->validate();

        if ($this->editing) {
            $this->machineType->update([
                'name' => $this->name,
                'company_id' => $this->company_id,
            ]);

            return to_route('machine-types')->with('success', 'Machine Type updated successfully.');
        } else {
            MachineType::create([
                'name' => $this->name,
                'company_id' => $this->company_id,
            ]);

            return to_route('machine-types')->with('success', 'Machine Type created successfully.');
        }
    }
    public function render()
    {
        return view('livewire.machine-types.machine-type-create');
    }
}
