<?php

namespace App\Livewire\MachineTypes;

use App\Models\MachineType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class MachineTypeIndex extends Component
{
    use AuthorizesRequests;

    public $machineTypes;

    public function mount()
    {
        $this->authorize('machineType-list');

        if (authUser()->hasRole('Super Admin')) {
            $this->machineTypes = MachineType::with('company')->get();
        } else {
            $this->machineTypes = MachineType::with('company')
                ->where('company_id', authUser()->company_id)
                ->get();
        }

    }

    #[On('delete')]
    public function delete($id)
    {
        $this->authorize('machineType-delete');

        $machineType = MachineType::findOrFail($id);
        if ($machineType->machines()->count() > 0) {
            return to_route('machine-types')->with('error', 'Cannot delete Machine Type with associated Machines.');
        }
        $machineType->delete();


        return to_route('machine-types')->with('success', 'Machine Type deleted successfully.');
    }
    public function render()
    {
        return view('livewire.machine-types.machine-type-index');
    }
}
