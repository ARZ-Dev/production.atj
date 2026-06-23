<?php

namespace App\Livewire\Plans;

use App\Models\Plan;
use App\Services\ApiService;
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
    public ?int $department_id = null;
    public ?int $factory_id = null;
    public bool $editing = false;

    public array $departments = [];
    public array $factories   = [];

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(): void
    {
        $this->currentYear  = now()->year;
        $this->currentMonth = now()->month;

        $this->departments = $this->api->get('/v1/departments', ['module' => 'production'])['data'] ?? [];
    }

    public function onDepartmentChange(?int $deptId): void
    {
        $this->department_id = $deptId;
        $this->factory_id    = null;

        $factories = $this->fetchFactoryWarehouses($deptId);
        $this->dispatch('planFactoriesReady', factories: $factories);
    }

    private function fetchFactoryWarehouses(?int $deptId): array
    {
        if (!$deptId) {
            return [];
        }

        $all = $this->api->get('/v1/warehouses', [
            'related_to_production' => true,
            'department_id'         => $deptId,
        ])['data'] ?? [];

        return collect($all)
            ->filter(fn($wh) => !empty($wh['type']['is_factory']))
            ->values()
            ->toArray();
    }

    private function fetchAllFactories(): array
    {
        $all = $this->api->get('/v1/warehouses', ['related_to_production' => true])['data'] ?? [];

        return collect($all)
            ->filter(fn($wh) => !empty($wh['type']['is_factory']))
            ->values()
            ->toArray();
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
        $this->plan_id      = null;
        $this->date         = '';
        $this->department_id = null;
        $this->factory_id    = null;
        $this->factories      = [];
        $this->editing      = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->dispatch('openModal', factories: []);
    }

    public function createForDate(string $date): void
    {
        $this->resetForm();
        $this->date = $date;
        $this->dispatch('openModal', factories: []);
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $plan = Plan::findOrFail($id);
        $this->plan_id   = $plan->id;
        $this->date      = Carbon::parse($plan->date)->format('Y-m-d');
        $this->factory_id = $plan->factory_id;
        $this->editing   = true;

        $allFactories = $this->fetchAllFactories();
        $matched = collect($allFactories)->firstWhere('id', $plan->factory_id);
        $this->department_id = $matched['department_id'] ?? ($matched['department']['id'] ?? null);

        $this->dispatch('openModal', factories: $this->fetchFactoryWarehouses($this->department_id));
    }

    protected function rules(): array
    {
        return [
            'date'       => 'required|date',
            'factory_id' => 'required|integer',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $data = ['date' => $this->date, 'factory_id' => $this->factory_id];

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
