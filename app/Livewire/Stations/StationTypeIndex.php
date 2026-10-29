<?php

namespace App\Livewire\Stations;

use App\Models\RecipeStage;
use App\Models\StationType;
use App\Services\ApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * What a station does — Cutting, Cooking, Packaging … Recipe stages pick a
 * type, and run on the line's machines of that type. Gated like lines: the
 * types are part of a line's setup.
 */
class StationTypeIndex extends Component
{
    public $types;

    #[Locked]
    public ?int $typeId = null;

    #[Locked]
    public bool $editing = false;

    public string $name = '';
    public $sequence = '';
    public ?string $description = null;

    /** id => name of the production departments, for "Used in". */
    public array $departmentNames = [];

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.line-list');

        $this->departmentNames = collect($api->get('/v1/departments', ['module' => 'production', 'filter' => 'production'])['data'] ?? [])
            ->mapWithKeys(fn($d) => [(int) $d['id'] => $d['name']])
            ->all();

        // One list for every factory; each type's stations say which departments use it.
        $this->types = StationType::withCount(['stations', 'recipeStages'])
            ->with('stations:id,station_type_id,department_id')
            ->orderBy('sequence')->orderBy('name')
            ->get();
    }

    public function resetForm(): void
    {
        $this->typeId      = null;
        $this->editing     = false;
        $this->name        = '';
        $this->sequence    = '';
        $this->description = null;
        $this->resetValidation();
    }

    public function create(): void
    {
        authorizeRequest('production.line-create');
        $this->resetForm();
        $this->sequence = (string) ((int) StationType::max('sequence') + 1);
        $this->dispatch('openModal');
    }

    public function edit($id): void
    {
        authorizeRequest('production.line-edit');
        $this->resetForm();

        $type = StationType::findOrFail($id);

        $this->typeId      = $type->id;
        $this->editing     = true;
        $this->name        = $type->name;
        $this->sequence    = (string) $type->sequence;
        $this->description = $type->description;

        $this->dispatch('openModal');
    }

    protected function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100', Rule::unique('station_types', 'name')->whereNull('deleted_at')->ignore($this->typeId)],
            'sequence'    => 'required|integer|min:0|max:999',
            'description' => 'nullable|string|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.unique' => 'There is already a station type with this name.',
        ];
    }

    public function submit()
    {
        authorizeRequest($this->editing ? 'production.line-edit' : 'production.line-create');

        $this->name = trim($this->name);
        $this->validate();

        $data = [
            'name'        => $this->name,
            'sequence'    => (int) $this->sequence,
            'description' => $this->description ? trim($this->description) : null,
        ];

        DB::transaction(function () use ($data) {
            if (!$this->editing) {
                StationType::create($data);

                return;
            }

            StationType::findOrFail($this->typeId)->update($data);

            // A stage shows its type's name.
            RecipeStage::where('station_type_id', $this->typeId)->update(['name' => $data['name']]);
        });

        return redirect()->route('station-types')
            ->with('success', $this->editing ? 'Station type updated.' : 'Station type created.');
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.line-delete');

        $type = StationType::withCount(['stations', 'recipeStages'])->findOrFail($id);

        if ($type->stations_count || $type->recipe_stages_count) {
            return redirect()->route('station-types')->with('error', "{$type->name} can't be deleted: "
                . collect([
                    $type->stations_count ? $type->stations_count . ' ' . str('station')->plural($type->stations_count) : null,
                    $type->recipe_stages_count ? $type->recipe_stages_count . ' recipe ' . str('stage')->plural($type->recipe_stages_count) : null,
                ])->filter()->implode(' and ') . ' use it.');
        }

        $type->delete();

        return redirect()->route('station-types')->with('success', 'Station type deleted.');
    }

    public function render()
    {
        return view('livewire.stations.station-type-index');
    }
}
