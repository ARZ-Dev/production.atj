<?php

namespace App\Livewire\Lines;

use App\Models\Line;
use App\Models\LineStation;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The machines of a line, in the order the product passes them. A recipe
 * with stages runs on the line by matching each stage to the station of the
 * same name; the slowest station sets the line's rate.
 */
class LineStations extends Component
{
    #[Locked]
    public int $lineId;

    #[Locked]
    public string $lineName = '';

    /**
     * Keyed by a stable key ("s<id>" for saved stations, random for new ones)
     * so removing a row never shifts another row's wire:model paths.
     *
     * @var array<string, array{id: ?int, name: string, machine: ?string, machine_count: mixed, rate: mixed, rate_period: string, rate_unit: ?string}>
     */
    public array $stations = [];

    public function mount(int $id): void
    {
        authorizeRequest('production.line-edit');

        $line = Line::with('stations')->findOrFail($id);

        $this->lineId   = $line->id;
        $this->lineName = $line->name;

        foreach ($line->stations as $station) {
            $this->stations['s' . $station->id] = [
                'id'            => $station->id,
                'name'          => $station->name,
                'machine'       => $station->machine,
                'machine_count' => $station->machine_count,
                'rate'          => $station->rate !== null ? $this->plain($station->rate) : null,
                'rate_period'   => $station->rate_period ?: 'hour',
                'rate_unit'     => $station->rate_unit,
            ];
        }

        if (!$this->stations) {
            $this->addStation();
        }
    }

    public function addStation(): void
    {
        $this->stations['n' . bin2hex(random_bytes(4))] = [
            'id'            => null,
            'name'          => '',
            'machine'       => null,
            'machine_count' => 1,
            'rate'          => null,
            'rate_period'   => 'hour',
            'rate_unit'     => null,
        ];
    }

    public function removeStation(string $key): void
    {
        unset($this->stations[$key]);
    }

    /** Move a station one place up (-1) or down (+1) in the line's order. */
    public function moveStation(string $key, int $direction): void
    {
        $keys  = array_keys($this->stations);
        $index = array_search($key, $keys, true);
        $swap  = $index === false ? null : $index + $direction;

        if ($swap === null || $swap < 0 || $swap >= count($keys)) {
            return;
        }

        [$keys[$index], $keys[$swap]] = [$keys[$swap], $keys[$index]];

        $this->stations = array_combine($keys, array_map(fn($k) => $this->stations[$k], $keys));
    }

    protected function rules(): array
    {
        $rules = [];

        foreach (array_keys($this->stations) as $key) {
            $rules["stations.{$key}.name"]          = 'required|string|max:255';
            $rules["stations.{$key}.machine"]       = 'nullable|string|max:255';
            $rules["stations.{$key}.machine_count"] = 'required|integer|min:1|max:999';
            $rules["stations.{$key}.rate"]          = 'nullable|numeric|min:0';
            $rules["stations.{$key}.rate_period"]   = 'required|in:hour,minute';
            $rules["stations.{$key}.rate_unit"]     = 'nullable|string|max:50';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'stations.*.name.required'          => 'Name the station — recipe stages are matched to it by this name.',
            'stations.*.machine_count.required' => 'How many machines?',
            'stations.*.machine_count.min'      => 'At least one machine.',
            'stations.*.rate.numeric'           => 'The rate must be a number.',
        ];
    }

    public function save()
    {
        authorizeRequest('production.line-edit');

        foreach ($this->stations as $key => $station) {
            $this->stations[$key]['rate'] = $station['rate'] === '' || $station['rate'] === null
                ? null
                : str_replace(',', '', (string) $station['rate']);
        }

        if ($this->stations) {
            $this->validate();
        }

        $names = collect($this->stations)->map(fn($s) => mb_strtolower(trim($s['name'])));

        if ($names->duplicates()->isNotEmpty()) {
            $this->addError('stations', 'Two stations have the same name: "' . $names->duplicates()->first() . '". Recipe stages are matched by name, so each must be unique.');

            return;
        }

        DB::transaction(function () {
            $kept     = [];
            $sequence = 0;

            foreach ($this->stations as $station) {
                $data = [
                    'line_id'       => $this->lineId,
                    'sequence'      => ++$sequence,
                    'name'          => trim($station['name']),
                    'machine'       => $station['machine'] ? trim($station['machine']) : null,
                    'machine_count' => (int) $station['machine_count'],
                    'rate'          => $station['rate'] !== null ? (float) $station['rate'] : null,
                    'rate_period'   => $station['rate_period'],
                    'rate_unit'     => $station['rate_unit'] ? trim($station['rate_unit']) : null,
                ];

                $model = !empty($station['id'])
                    ? LineStation::where('line_id', $this->lineId)->find($station['id'])
                    : null;

                $model ? $model->update($data) : $model = LineStation::create($data);

                $kept[] = $model->id;
            }

            LineStation::where('line_id', $this->lineId)->whereNotIn('id', $kept)->delete();
        });

        return redirect()->route('lines.stations', $this->lineId)->with('success', 'Stations saved.');
    }

    protected function plain(float $number): string
    {
        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }

    public function render()
    {
        return view('livewire.lines.line-stations');
    }
}
