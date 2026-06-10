<?php

namespace App\Livewire\Plans;

use App\Models\Plan;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class PlanIndex extends Component
{
    public int $currentYear;
    public int $currentMonth;

    // Modal form fields
    public $plan_id;
    public $date;
    public bool $editing = false;

    public function mount()
    {
        $this->currentYear  = now()->year;
        $this->currentMonth = now()->month;
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
        $this->plan_id = null;
        $this->date    = '';
        $this->editing = false;
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
        $this->plan_id = $plan->id;
        $this->date    = Carbon::parse($plan->date)->format('Y-m-d');
        $this->editing = true;
        $this->dispatch('openModal');
    }

    protected function rules(): array
    {
        return [
            'date' => 'required|date',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $data = ['date' => $this->date];

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
        $plansByDate = Plan::withCount('events')
            ->whereYear('date', $this->currentYear)
            ->whereMonth('date', $this->currentMonth)
            ->orderBy('date')
            ->get()
            ->groupBy(fn($p) => Carbon::parse($p->date)->format('Y-m-d'));

        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);

        return view('livewire.plans.plan-index', compact('plansByDate', 'startOfMonth'));
    }
}
