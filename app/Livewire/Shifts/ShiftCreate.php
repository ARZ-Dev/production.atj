<?php

namespace App\Livewire\Shifts;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ShiftCreate extends Component
{
    use AuthorizesRequests;

    public $companies = [];
    public $company_id;
    public $name;
    public $from_time;
    public $to_time;
    public $id;
    public $editing = false;

    public function mount($id = 0)
    {
        $this->authorize('shift-create');
        $this->id = $id;
        if (auth()->user()->hasRole('Super Admin')) {
            $this->companies = Company::all();
        } else {
            $this->companies = Company::where('id', auth()->user()->company_id)->get();
            $this->company_id = auth()->user()->company_id;
        }

        if ($id) {
            $shift = Shift::findOrFail($id);
            $this->company_id = $shift->company_id;
            $this->name = $shift->name;
            $this->from_time = \Carbon\Carbon::parse($shift->from_time)->format('H:i');
            $this->to_time = \Carbon\Carbon::parse($shift->to_time)->format('H:i');
            $this->editing = true;
        }
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
            $shift = Shift::findOrFail($this->id);
            $this->authorize('shift-edit');
            $shift->update([
                'company_id' => $this->company_id,
                'name' => $this->name,
                'from_time' => $this->from_time,
                'to_time' => $this->to_time,
            ]);

            return to_route('shifts')->with('success', 'Shift updated successfully.');
        } else {
            $this->authorize('shift-create');
            Shift::create([
                'company_id' => $this->company_id,
                'name' => $this->name,
                'from_time' => $this->from_time,
                'to_time' => $this->to_time,
            ]);

            return to_route('shifts')->with('success', 'Shift created successfully.');
        }
    }

    public function render()
    {
        return view('livewire.shifts.shift-create');
    }
}
