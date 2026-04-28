<?php

namespace App\Livewire\Units;

use App\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class UnitIndex extends Component
{

    use AuthorizesRequests;

    public $units;

    public function mount()
    {
        $this->authorize('unit-list');

        $this->units = Unit::with('baseUnit')->get();
    }

    #[On('delete')]
    public function delete($id)
    {
        $this->authorize('unit-delete');

        $unit = Unit::findOrFail($id);
        $unit->delete();

        return redirect()->route('units')->with('success', 'Unit deleted successfully.');
    }
    public function render()
    {
        return view('livewire.units.unit-index');
    }
}
