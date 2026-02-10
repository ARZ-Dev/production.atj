<?php

namespace App\Livewire\Factories;

use App\Models\Factory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class FactoryIndex extends Component
{
    use AuthorizesRequests;

    public $factories;

    public function mount()
    {
        $this->authorize('factory-list');

        if (authUser()->hasRole('Super Admin')) {
            $this->factories = Factory::all();
        } else {
            $this->factories = Factory::where('company_id', authUser()->company_id)->get();
        }
    }

    #[On('delete')]
    public function delete($id)
    {
        $this->authorize('factory-delete');

        $factory = Factory::findOrFail($id);
        // if ($factory->warehouses()->count() > 0) {
        //     $this->dispatch('swal:error', [
        //         'title' => 'Error',
        //         'text' => 'Cannot delete factory that has associated warehouses!'
        //     ]);
        //     return;
        // } else {
        //     $factory->delete();
        // }

        $factory->warehouses()->delete();
        $factory->delete();
        return redirect()->route('factories')->with('success', 'Factory deleted successfully.');
    }
    public function render()
    {
        return view('livewire.factories.factory-index');
    }
}
