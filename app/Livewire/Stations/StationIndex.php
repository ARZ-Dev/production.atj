<?php

namespace App\Livewire\Stations;

use App\Models\Station;
use App\Models\StationType;
use App\Services\ApiService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The machines — Oven 1, Flow Wrap 3 … — each of one station type, with its
 * own rate, in a department and its factory (the same pair a production line
 * carries). A line picks its machines, from its own department, on its
 * Stations page. Gated like lines.
 */
class StationIndex extends Component
{
    public $stations;
    public $types;

    // Production departments, and id => name lookups for the table.
    public array $departments     = [];
    public array $departmentNames = [];
    public array $factoryNames    = [];

    // The factories (warehouses of type factory) of the selected department.
    public array $factories = [];

    #[Locked]
    public ?int $stationId = null;

    #[Locked]
    public bool $editing = false;

    public $station_type_id = '';
    public $department_id   = '';
    public $factory_id      = '';
    public string $name = '';
    public ?string $manufacturer = null;
    public $rate = null;
    public string $rate_period = 'hour';
    public ?string $rate_unit = null;
    public ?string $notes = null;

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(): void
    {
        authorizeRequest('production.line-list');

        $this->departments = collect($this->api->get('/v1/departments', ['module' => 'production', 'filter' => 'production'])['data'] ?? [])
            ->map(fn($d) => ['id' => (int) $d['id'], 'name' => $d['name']])
            ->values()
            ->all();
        $this->departmentNames = array_column($this->departments, 'name', 'id');
        $this->factoryNames    = collect($this->api->get('/v1/warehouses')['data'] ?? [])
            ->mapWithKeys(fn($w) => [(int) $w['id'] => $w['name']])
            ->all();

        $this->stations = Station::with(['type', 'lines' => fn($q) => $q->orderBy('name')])->get()
            ->sortBy(fn(Station $station) => sprintf('%05d|%s', $station->type?->sequence ?? 0, $station->type?->name) . '|' . $station->name, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $this->types = StationType::orderBy('sequence')->orderBy('name')->get(['id', 'name', 'rate_unit']);
    }

    public function resetForm(): void
    {
        $this->stationId       = null;
        $this->editing         = false;
        $this->station_type_id = '';
        $this->department_id   = '';
        $this->factory_id      = '';
        $this->factories       = [];
        $this->name            = '';
        $this->manufacturer    = null;
        $this->rate            = null;
        $this->rate_period     = 'hour';
        $this->rate_unit       = null;
        $this->notes           = null;
        $this->resetValidation();
    }

    public function create(): void
    {
        authorizeRequest('production.line-create');
        $this->resetForm();
        $this->dispatch('openModal');
    }

    public function edit($id): void
    {
        authorizeRequest('production.line-edit');
        $this->resetForm();

        $station = Station::findOrFail($id);

        $this->stationId       = $station->id;
        $this->editing         = true;
        $this->station_type_id = (string) $station->station_type_id;
        $this->department_id   = $station->department_id ? (string) $station->department_id : '';
        $this->loadFactories();
        $this->factory_id      = $station->factory_id ? (string) $station->factory_id : '';
        $this->name            = $station->name;
        $this->manufacturer    = $station->manufacturer;
        $this->rate            = $station->rate !== null ? rtrim(rtrim(number_format($station->rate, 4, '.', ''), '0'), '.') : null;
        $this->rate_period     = $station->rate_period ?: 'hour';
        $this->rate_unit       = $station->rate_unit;
        $this->notes           = $station->notes;

        $this->dispatch('openModal');
    }

    /** A new department: its factories, the only one picked for you. */
    public function updatedDepartmentId(): void
    {
        $this->factory_id = '';
        $this->loadFactories();

        if (count($this->factories) === 1) {
            $this->factory_id = (string) $this->factories[0]['id'];
        }
    }

    protected function loadFactories(): void
    {
        $deptId = (int) $this->department_id;

        $this->factories = $deptId
            ? collect($this->api->get('/v1/warehouses', ['department_id' => $deptId])['data'] ?? [])
                ->filter(fn($wh) => (int) ($wh['department_id'] ?? $wh['department']['id'] ?? 0) === $deptId && !empty($wh['type']['is_factory']))
                ->map(fn($wh) => ['id' => (int) $wh['id'], 'name' => $wh['name']])
                ->values()
                ->all()
            : [];
    }

    protected function rules(): array
    {
        return [
            'station_type_id' => ['required', Rule::exists('station_types', 'id')->whereNull('deleted_at')],
            'department_id'   => ['required', Rule::in(array_column($this->departments, 'id'))],
            'factory_id'      => ['required', Rule::in(array_column($this->factories, 'id'))],
            'name'            => ['required', 'string', 'max:255', Rule::unique('stations', 'name')->whereNull('deleted_at')->ignore($this->stationId)],
            'manufacturer'    => 'nullable|string|max:255',
            'rate'            => 'nullable|numeric|min:0',
            'rate_period'     => 'required|in:hour,minute',
            'rate_unit'       => 'nullable|string|max:50',
            'notes'           => 'nullable|string|max:2000',
        ];
    }

    protected function messages(): array
    {
        return [
            'station_type_id.required' => 'What does this machine do?',
            'department_id.required'   => 'Which department is it in?',
            'department_id.in'         => 'Select a production department.',
            'factory_id.required'      => 'Which factory is it in?',
            'factory_id.in'            => 'Select a factory of the chosen department.',
            'name.unique'              => 'There is already a station with this name.',
            'rate.numeric'             => 'The rate must be a number.',
        ];
    }

    public function submit()
    {
        authorizeRequest($this->editing ? 'production.line-edit' : 'production.line-create');

        $this->name = trim($this->name);
        $this->rate = $this->rate === '' || $this->rate === null ? null : str_replace(',', '', (string) $this->rate);
        $this->validate();

        // A station stays in the department of the lines it's on.
        if ($this->editing) {
            $elsewhere = Station::findOrFail($this->stationId)->lines()
                ->where('lines.department_id', '!=', (int) $this->department_id)
                ->pluck('lines.name');

            if ($elsewhere->isNotEmpty()) {
                $this->addError('department_id', 'It is on ' . $elsewhere->implode(', ') . ', of another department. Take it off first.');

                return;
            }
        }

        $data = [
            'station_type_id' => (int) $this->station_type_id,
            'department_id'   => (int) $this->department_id,
            'factory_id'      => (int) $this->factory_id,
            'name'            => $this->name,
            'manufacturer'    => $this->manufacturer ? trim($this->manufacturer) : null,
            'rate'            => $this->rate !== null && (float) $this->rate > 0 ? (float) $this->rate : null,
            'rate_period'     => $this->rate_period,
            'rate_unit'       => $this->rate_unit ? trim($this->rate_unit) : null,
            'notes'           => $this->notes ? trim($this->notes) : null,
        ];

        $this->editing
            ? Station::findOrFail($this->stationId)->update($data)
            : Station::create($data);

        return redirect()->route('stations')
            ->with('success', $this->editing ? 'Station updated.' : 'Station created.');
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.line-delete');

        $station = Station::with('lines')->findOrFail($id);

        if ($station->lines->isNotEmpty()) {
            return redirect()->route('stations')->with('error', "{$station->name} can't be deleted while it's on "
                . $station->lines->pluck('name')->implode(', ') . '. Take it off the line first.');
        }

        $station->delete();

        return redirect()->route('stations')->with('success', 'Station deleted.');
    }

    public function render()
    {
        return view('livewire.stations.station-index', [
            'typeUnit' => $this->types->firstWhere('id', (int) $this->station_type_id)?->rate_unit,
        ]);
    }
}
