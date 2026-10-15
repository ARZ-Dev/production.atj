<?php

namespace App\Livewire\Plans;

use App\Models\Event;
use App\Models\MonthPlan;
use App\Models\Plan;
use App\Services\ApiService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
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
    public ?string $selectedFactoryName = null;
    public bool $editing = false;

    // Whether a month plan exists for the selected factory + viewed month.
    // Individual daily plans can only be created once this is true.
    public bool $monthPlanReady = false;

    public array $departments = [];
    public array $factories   = [];

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(): void
    {
        authorizeRequest('production.plan-list');

        $this->currentYear  = now()->year;
        $this->currentMonth = now()->month;

        $departments = $this->api->get('/v1/departments', ['module' => 'production'])['data'] ?? [];

        $this->departments = collect($departments)
            ->filter(fn($dept) => !empty($dept['related_to_production']))
            ->values()
            ->toArray();
    }

    public function onDepartmentChange(?int $deptId): void
    {
        $this->department_id       = $deptId;
        $this->factory_id          = null;
        $this->selectedFactoryName = null;
        $this->monthPlanReady      = false;

        $this->factories = $this->fetchFactoryWarehouses($deptId);
        $this->dispatch('planFactoriesReady', factories: $this->factories);
    }

    public function onFactoryChange(?int $factoryId, ?string $factoryName = null): void
    {
        $this->factory_id          = $factoryId;
        $this->selectedFactoryName = $factoryName;
        $this->monthPlanReady      = false;

        if (!$factoryId) {
            return;
        }

        $this->monthPlanReady = $this->currentMonthPlan()->exists();
    }

    private function currentMonthPlan()
    {
        return MonthPlan::where('factory_id', $this->factory_id)
            ->where('year', $this->currentYear)
            ->where('month', $this->currentMonth);
    }

    public function createMonthPlan(): void
    {
        authorizeRequest('production.plan-create');

        if (!$this->factory_id) {
            $this->addError('factory_id', 'Select a factory first.');

            return;
        }

        MonthPlan::firstOrCreate(
            [
                'factory_id' => $this->factory_id,
                'year'       => $this->currentYear,
                'month'      => $this->currentMonth,
            ],
            ['factory_name' => $this->selectedFactoryName]
        );

        $this->monthPlanReady = true;

        $this->dispatch(
            'monthPlanCreated',
            factory: $this->selectedFactoryName ?? 'this factory',
            period: $this->monthLabel(),
        );
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
            ->filter(fn($wh) => !empty($wh['type']['is_factory'])
                && (int) ($wh['department_id'] ?? $wh['department']['id'] ?? null) === $deptId)
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
        $this->plan_id             = null;
        $this->date                = '';
        $this->department_id       = null;
        $this->factory_id          = null;
        $this->selectedFactoryName = null;
        $this->monthPlanReady      = false;
        $this->factories           = [];
        $this->editing             = false;
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
        $plan = Plan::with('monthPlan')->findOrFail($id);
        $this->plan_id       = $plan->id;
        $this->date          = Carbon::parse($plan->date)->format('Y-m-d');
        $this->factory_id    = $plan->factory_id;
        $this->editing       = true;
        // The plan already belongs to a month plan, so daily editing is unlocked.
        $this->monthPlanReady      = true;
        $this->selectedFactoryName = $plan->monthPlan?->display_name;

        $allFactories = $this->fetchAllFactories();
        $matched      = collect($allFactories)->firstWhere('id', $plan->factory_id);
        $this->department_id = $matched['department_id'] ?? ($matched['department']['id'] ?? null);
        $this->factories     = $this->fetchFactoryWarehouses($this->department_id);

        $this->dispatch('openModal', factories: $this->factories);
    }

    protected function rules(): array
    {
        return [
            'date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $d = Carbon::parse($value);
                    if ($d->year !== $this->currentYear || $d->month !== $this->currentMonth) {
                        $fail('The date must be within ' . $this->monthLabel() . '.');
                    }
                },
            ],
            'factory_id' => [
                'required',
                'integer',
                Rule::unique('plans', 'factory_id')
                    ->where('date', $this->date)
                    ->whereNull('deleted_at')
                    ->ignore($this->plan_id),
            ],
        ];
    }

    protected $messages = [
        'factory_id.unique'   => 'A plan already exists for this factory on this date.',
        'factory_id.required' => 'Please select a factory.',
    ];

    public function submit(): void
    {
        authorizeRequest($this->editing ? 'production.plan-edit' : 'production.plan-create');

        $this->validate();

        $monthPlan = $this->currentMonthPlan()->first();

        if (!$monthPlan) {
            $this->monthPlanReady = false;
            $this->addError('factory_id', 'Create a monthly plan for ' . $this->monthLabel() . ' before adding plans.');

            return;
        }

        $data = [
            'date'          => $this->date,
            'factory_id'    => $this->factory_id,
            'month_plan_id' => $monthPlan->id,
        ];

        if ($this->editing) {
            Plan::findOrFail($this->plan_id)->update($data);
        } else {
            Plan::create($data);
        }

        $this->dispatch('closeModal');
        $wasEditing = $this->editing;
        $this->resetForm();
        session()->flash('success', $wasEditing ? 'Plan updated.' : 'Plan created.');
    }

    #[On('delete')]
    public function delete(int $id): void
    {
        authorizeRequest('production.plan-delete');

        $plan = Plan::findOrFail($id);

        if ($started = $this->startedEventCount([$plan->id])) {
            $this->refuseDeletion($started, 'this plan');

            return;
        }

        $plan->delete();
        session()->flash('success', 'Plan deleted.');
    }

    #[On('deleteMonthPlan')]
    public function deleteMonthPlan(int $id): void
    {
        authorizeRequest('production.plan-delete');

        $monthPlan = MonthPlan::findOrFail($id);

        if ($started = $this->startedEventCount($monthPlan->plans()->pluck('id')->all())) {
            $this->refuseDeletion($started, 'this monthly plan');

            return;
        }

        $monthPlan->plans()->delete();
        $monthPlan->delete();

        session()->flash('success', 'Monthly plan and its plans were deleted.');
    }

    /**
     * Started events on the given plans. Deleting their plan would leave
     * their stock held in process (or their movement record orphaned) — the
     * same reason the board won't delete or unplace a started event.
     */
    private function startedEventCount(array $planIds): int
    {
        return $planIds
            ? Event::whereIn('plan_id', $planIds)->whereNotNull('status')->count()
            : 0;
    }

    private function refuseDeletion(int $started, string $what): void
    {
        $this->dispatch('swal:error', [
            'title' => 'Plan has started events',
            'text'  => "{$started} event" . ($started === 1 ? ' has' : 's have') . " already started on {$what}, so it can't be deleted — their stock movements are tied to it.",
        ]);
    }

    private function monthLabel(): string
    {
        return Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->format('F Y');
    }

    public function render()
    {
        $plansByDate = Plan::withCount('events')
            ->with('monthPlan')
            ->whereYear('date', $this->currentYear)
            ->whereMonth('date', $this->currentMonth)
            ->orderBy('date')
            ->get()
            ->groupBy(fn($p) => Carbon::parse($p->date)->format('Y-m-d'));

        $monthPlans = MonthPlan::withCount('plans')
            ->where('year', $this->currentYear)
            ->where('month', $this->currentMonth)
            ->orderBy('factory_name')
            ->get();

        $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);

        return view('livewire.plans.plan-index', compact('plansByDate', 'monthPlans', 'startOfMonth'));
    }
}
