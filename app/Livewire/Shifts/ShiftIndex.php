<?php

namespace App\Livewire\Shifts;

use App\Models\Shift;
use Livewire\Attributes\On;
use Livewire\Component;

class ShiftIndex extends Component
{
    public $shifts;
    public $name;
    public $from_time;
    public $to_time;
    public $shift_id;
    public $editing = false;

    public function mount()
    {
        authorizeRequest('production.shift-list');
        $this->loadShifts();
    }

    public function loadShifts()
    {
        $this->shifts = Shift::orderBy('from_time')->get();
    }

    public function resetForm()
    {
        $this->shift_id  = null;
        $this->name      = '';
        $this->from_time = '';
        $this->to_time   = '';
        $this->editing   = false;
        $this->resetValidation();
    }

    public function create()
    {
        authorizeRequest('production.shift-create');
        $this->resetForm();
        $this->dispatch('openModal');
    }

    public function edit($id)
    {
        authorizeRequest('production.shift-edit');
        $this->resetForm();

        $shift = Shift::findOrFail($id);
        $this->shift_id  = $shift->id;
        $this->name      = $shift->name;
        $this->from_time = \Carbon\Carbon::parse($shift->from_time)->format('H:i');
        $this->to_time   = \Carbon\Carbon::parse($shift->to_time)->format('H:i');
        $this->editing   = true;

        $this->dispatch('openModal');
    }

    protected function rules()
    {
        return [
            'name'      => 'required|string|max:255',
            'from_time' => 'required|date_format:H:i',
            'to_time'   => 'required|date_format:H:i|after:from_time',
        ];
    }

    public function submit()
    {
        $this->validate();

        $data = [
            'name'      => $this->name,
            'from_time' => $this->from_time,
            'to_time'   => $this->to_time,
        ];

        if ($this->editing) {
            authorizeRequest('production.shift-edit');
            Shift::findOrFail($this->shift_id)->update($data);
        } else {
            authorizeRequest('production.shift-create');
            Shift::create($data);
        }

        return redirect()->route('shifts')
            ->with('success', $this->editing ? 'Shift updated successfully.' : 'Shift created successfully.');
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.shift-delete');
        Shift::findOrFail($id)->delete();

        return redirect()->route('shifts')->with('success', 'Shift deleted successfully.');
    }

    public function render()
    {
        return view('livewire.shifts.shift-index');
    }
}
