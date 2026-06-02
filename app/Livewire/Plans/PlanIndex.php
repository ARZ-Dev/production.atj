<?php

namespace App\Livewire\Plans;

use App\Models\Plan;
use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class PlanIndex extends Component
{
    public int $currentYear;
    public int $currentMonth;
    public $shifts = [];

    // Modal form fields
    public $plan_id;
    public $shift_id;
    public $date;
    public bool $editing = false;

    public function mount()
    {
        $this->currentYear  = now()->year;
        $this->currentMonth = now()->month;
        $this->loadShifts();
    }

    public function loadShifts(): void
    {
        $user = authUser();
        if ($user && $user->hasPermission('production.shift-viewAll')) {
            $this->shifts = Shift::orderBy('from_time')->get();
        } else {
            $userId = $user?->id;
            $this->shifts = Shift::whereHas('users', fn($q) => $q->where('user_id', $userId))
                ->orderBy('from_time')->get();
        }
    }

    public function prevMonth(): void
    {
        $d = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentYear  = $d->year;
        $this->currentMonth = $d->month;
    }

    public function nextMonth(): void
    {
        $d = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentYear  = $d->year;
        $this->currentMonth = $d->month;
    }

    public function goToToday(): void
    {
        $this->currentYear  = now()->year;
        $this->currentMonth = now()->month;
    }

    public function resetForm(): void
    {
        $this->plan_id  = null;
        $this->shift_id = '';
        $this->date     = '';
        $this->editing  = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->dispatch('openModal');
    }

    public function createForDate(string $date): void
    {
        $this->resetForm();
        $this->date = $date;
        $this->dispatch('openModal');
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $plan = Plan::findOrFail($id);
        $this->plan_id  = $plan->id;
        $this->shift_id = $plan->shift_id;
        $this->date     = Carbon::parse($plan->date)->format('Y-m-d');
        $this->editing  = true;
        $this->dispatch('openModal');
    }

    protected function rules(): array
    {
        return [
            'shift_id' => 'required|exists:shifts,id',
            'date'     => 'required|date',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $duplicate = Plan::where('shift_id', $this->shift_id)
            ->whereDate('date', $this->date)
            ->when($this->editing, fn($q) => $q->where('id', '!=', $this->plan_id))
            ->exists();

        if ($duplicate) {
            $this->addError('shift_id', 'This shift already has a plan for the selected date.');
            return;
        }

        $data = ['shift_id' => $this->shift_id, 'date' => $this->date];

        if ($this->editing) {
            Plan::findOrFail($this->plan_id)->update($data);
        } else {
            Plan::create($data);
        }

        $this->dispatch('closeModal');
        $this->resetForm();
        session()->flash('success', $this->editing ? 'Plan updated.' : 'Plan created.');
    }

    #[On('delete')]
    public function delete(int $id): void
    {
        Plan::findOrFail($id)->delete();
        session()->flash('success', 'Plan deleted.');
    }

    public function render()
    {
        $plansByDate = Plan::with('shift')
            ->withCount('events')
            ->leftJoin('shifts', 'shifts.id', '=', 'plans.shift_id')
            ->select('plans.*')
            ->whereYear('plans.date', $this->currentYear)
            ->whereMonth('plans.date', $this->currentMonth)
            ->orderBy('plans.date')
            ->orderBy('shifts.from_time')
            ->get()
            ->groupBy(fn($p) => Carbon::parse($p->date)->format('Y-m-d'));

        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);

        return view('livewire.plans.plan-index', compact('plansByDate', 'startOfMonth'));
    }
}
