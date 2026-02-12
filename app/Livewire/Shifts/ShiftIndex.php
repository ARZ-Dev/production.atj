<?php

namespace App\Livewire\Shifts;

use App\Models\Shift;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class ShiftIndex extends Component
{
    use AuthorizesRequests;

    public $shifts;

    public function mount()
    {
        $this->authorize('shift-list');

        if (auth()->user()->hasRole('Super Admin')) {
            $this->shifts = Shift::all();
        } else {
            $this->shifts = Shift::where('company_id', auth()->user()->company_id)->get();
        }
    }

    #[On('delete')]
    public function delete($id)
    {
        $shift = Shift::findOrFail($id);
        $this->authorize('shift-delete');

        $shift->delete();

        return to_route('shifts')->with('success', 'Shift deleted successfully.');
    }
    public function render()
    {
        return view('livewire.shifts.shift-index');
    }
}
