<?php

namespace App\Livewire\Shifts;

use App\Models\Shift;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class ShiftIndex extends Component
{
    public $shifts;
    public $users = [];
    public $name;
    public $from_time;
    public $to_time;
    public $shift_id;
    public $selectedUsers = [];
    public $editing = false;

    public function mount()
    {
        authorizeRequest('production.shift-list');
        $this->users = User::orderBy('first_name')->get();
        $this->loadShifts();
    }

    public function loadShifts()
    {
        $this->shifts = Shift::with('users')->orderBy('from_time')->get();
    }

    public function resetForm()
    {
        $this->shift_id      = null;
        $this->name          = '';
        $this->from_time     = '';
        $this->to_time       = '';
        $this->selectedUsers = [];
        $this->editing       = false;
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

        $shift = Shift::with('users')->findOrFail($id);
        $this->shift_id      = $shift->id;
        $this->name          = $shift->name;
        $this->from_time     = \Carbon\Carbon::parse($shift->from_time)->format('H:i');
        $this->to_time       = \Carbon\Carbon::parse($shift->to_time)->format('H:i');
        $this->selectedUsers = $shift->users->pluck('id')->toArray();
        $this->editing       = true;

        $this->dispatch('openModal');
    }

    protected function rules()
    {
        return [
            'name'          => 'required|string|max:255',
            'from_time'     => 'required|date_format:H:i',
            'to_time'       => 'required|date_format:H:i|after:from_time',
            'selectedUsers' => 'nullable|array',
            'selectedUsers.*' => 'exists:users,id',
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
            $shift = Shift::findOrFail($this->shift_id);
            $shift->update($data);
        } else {
            authorizeRequest('production.shift-create');
            $shift = Shift::create($data);
        }

        $shift->users()->sync($this->selectedUsers ?? []);

        $this->loadShifts();
        $this->resetForm();
        $this->dispatch('closeModal');

        session()->flash('success', $this->editing ? 'Shift updated successfully.' : 'Shift created successfully.');
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.shift-delete');
        Shift::findOrFail($id)->delete();
        $this->loadShifts();
        session()->flash('success', 'Shift deleted successfully.');
    }

    public function render()
    {
        return view('livewire.shifts.shift-index');
    }
}
