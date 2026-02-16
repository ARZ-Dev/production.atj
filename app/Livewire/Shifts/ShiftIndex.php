<?php

namespace App\Livewire\Shifts;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class ShiftIndex extends Component
{
    use AuthorizesRequests;

    public $shifts;
    public $companies = [];
    public $company_id;
    public $name;
    public $from_time;
    public $to_time;
    public $shift_id;
    public $editing = false;

    public function mount()
    {
        $this->authorize('shift-list');
        $this->loadShifts();

        if (auth()->user()->hasRole('Super Admin')) {
            $this->companies = Company::all();
        } else {
            $this->companies = Company::where('id', auth()->user()->company_id)->get();
            $this->company_id = auth()->user()->company_id;
        }
    }

    public function loadShifts()
    {
        if (auth()->user()->hasRole('Super Admin')) {
            $this->shifts = Shift::all();
        } else {
            $this->shifts = Shift::where('company_id', auth()->user()->company_id)->get();
        }
    }

    public function resetForm()
    {
        $this->shift_id = null;
        $this->name = '';
        $this->from_time = '';
        $this->to_time = '';
        $this->editing = false;
        if (!auth()->user()->hasRole('Super Admin')) {
            $this->company_id = auth()->user()->company_id;
        } else {
            $this->company_id = '';
        }
        $this->resetValidation();
    }

    public function create()
    {
        $this->authorize('shift-create');
        $this->resetForm();
        $this->dispatch('openModal');
    }

    public function edit($id)
    {
        $this->authorize('shift-edit');
        $this->resetForm();

        $shift = Shift::findOrFail($id);
        $this->shift_id = $shift->id;
        $this->company_id = $shift->company_id;
        $this->name = $shift->name;
        $this->from_time = \Carbon\Carbon::parse($shift->from_time)->format('H:i');
        $this->to_time = \Carbon\Carbon::parse($shift->to_time)->format('H:i');
        $this->editing = true;

        $this->dispatch('openModal');
    }

    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'from_time' => 'required|date_format:H:i',
            'to_time' => 'required|date_format:H:i|after:from_time',
        ];
    }

    public function submit()
    {
        $this->validate();

        if ($this->editing) {
            $shift = Shift::findOrFail($this->shift_id);
            $this->authorize('shift-edit');
            $shift->update([
                'company_id' => $this->company_id,
                'name' => $this->name,
                'from_time' => $this->from_time,
                'to_time' => $this->to_time,
            ]);

       
        } else {
            $this->authorize('shift-create');
            Shift::create([
                'company_id' => $this->company_id,
                'name' => $this->name,
                'from_time' => $this->from_time,
                'to_time' => $this->to_time,
            ]);

        }


        $this->loadShifts();
        $this->resetForm();
        $this->dispatch('closeModal');

        return to_route('shifts')->with('success', $this->editing ? 'Shift updated successfully.' : 'Shift created successfully.');
    }

    #[On('delete')]
    public function delete($id)
    {
        $shift = Shift::findOrFail($id);
        $this->authorize('shift-delete');
        $shift->delete();

        $this->loadShifts();
        return to_route('shifts')->with('success', 'Shift deleted successfully.');
    }

    public function render()
    {
        return view('livewire.shifts.shift-index');
    }
}