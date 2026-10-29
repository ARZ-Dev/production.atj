<?php

namespace App\Livewire\Lines;

use App\Models\Line;
use App\Models\Station;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The machines of a line, picked from the stations of its department, in the
 * order the product passes them. A recipe stage runs on the line's stations
 * of its station type; the slowest stage sets the line's rate.
 */
class LineStations extends Component
{
    #[Locked]
    public int $lineId;

    #[Locked]
    public string $lineName = '';

    #[Locked]
    public ?int $departmentId = null;

    /** What's on the line as saved: offered even if it has moved department since. */
    #[Locked]
    public array $savedIds = [];

    /**
     * Keyed by a stable key ("s<id>" for saved rows, random for new ones) so
     * removing a row never shifts another row's wire:model paths.
     *
     * @var array<string, array{station_id: mixed}>
     */
    public array $rows = [];

    public function mount(int $id): void
    {
        authorizeRequest('production.line-edit');

        $line = Line::with('stations')->findOrFail($id);

        $this->lineId       = $line->id;
        $this->lineName     = $line->name;
        $this->departmentId = $line->department_id ? (int) $line->department_id : null;
        $this->savedIds     = $line->stations->pluck('id')->all();

        foreach ($line->stations as $station) {
            $this->rows['s' . $station->id] = ['station_id' => (string) $station->id];
        }

        if (!$this->rows) {
            $this->addRow();
        }
    }

    public function addRow(): void
    {
        $this->rows['n' . bin2hex(random_bytes(4))] = ['station_id' => ''];
    }

    public function removeRow(string $key): void
    {
        unset($this->rows[$key]);
    }

    /** Move a station one place up (-1) or down (+1) in the line's order. */
    public function moveRow(string $key, int $direction): void
    {
        $keys  = array_keys($this->rows);
        $index = array_search($key, $keys, true);
        $swap  = $index === false ? null : $index + $direction;

        if ($swap === null || $swap < 0 || $swap >= count($keys)) {
            return;
        }

        [$keys[$index], $keys[$swap]] = [$keys[$swap], $keys[$index]];

        $this->rows = array_combine($keys, array_map(fn($k) => $this->rows[$k], $keys));
    }

    public function save()
    {
        authorizeRequest('production.line-edit');

        // An empty row is just an unused line of the form.
        $this->rows = array_filter($this->rows, fn($row) => (string) ($row['station_id'] ?? '') !== '');

        $known = $this->offered()->pluck('id')->map(fn($id) => (string) $id)->all();
        $rules = [];

        foreach (array_keys($this->rows) as $key) {
            $rules["rows.{$key}.station_id"] = ['required', 'in:' . implode(',', $known)];
        }

        if ($rules) {
            $this->validate($rules, ['rows.*.station_id.in' => 'Pick a station of this line\'s department.']);
        }

        $ids = collect($this->rows)->pluck('station_id')->map(fn($id) => (int) $id);

        if ($ids->duplicates()->isNotEmpty()) {
            $this->addError('rows', Station::find($ids->duplicates()->first())?->name . ' is on the line twice.');

            return;
        }

        DB::transaction(function () use ($ids) {
            Line::findOrFail($this->lineId)->stations()->sync(
                $ids->values()->mapWithKeys(fn($id, $i) => [$id => ['sequence' => $i + 1]])->all()
            );
        });

        return redirect()->route('lines.stations', $this->lineId)->with('success', 'Stations saved.');
    }

    /**
     * The stations this line can take: its department's (all of them when the
     * line has none), and whatever is on it already.
     */
    protected function offered()
    {
        return Station::with(['type', 'lines' => fn($q) => $q->orderBy('name')])
            ->when($this->departmentId, fn($q) => $q->where(fn($q) => $q
                ->where('department_id', $this->departmentId)
                ->orWhereIn('id', $this->savedIds ?: [0])))
            ->get();
    }

    public function render()
    {
        $stations = $this->offered();
        $byType   = $stations->groupBy(fn(Station $station) => $station->station_type_id)
            ->sortBy(fn($group) => sprintf('%05d|%s', $group->first()->type?->sequence ?? 0, $group->first()->type?->name))
            ->map(fn($group) => $group->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values());

        // What each stage type adds up to on this line, as the form stands.
        $chosen  = collect($this->rows)->map(fn($row) => $stations->firstWhere('id', (int) ($row['station_id'] ?? 0)))->filter();
        $summary = $chosen->groupBy('station_type_id')->map(function ($group) {
            $rated = $group->filter(fn(Station $station) => $station->hourlyRate() !== null);

            return [
                'type'     => $group->first()->type?->name,
                'count'    => $group->count(),
                'rated'    => $rated->count(),
                'per_hour' => $rated->sum(fn(Station $station) => $station->hourlyRate()),
            ];
        })->values();

        return view('livewire.lines.line-stations', [
            'stations' => $stations->keyBy('id'),
            'byType'   => $byType,
            'summary'  => $summary,
        ]);
    }
}
