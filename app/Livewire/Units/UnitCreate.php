<?php

namespace App\Livewire\Units;

use App\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class UnitCreate extends Component
{
    use AuthorizesRequests;

    public $id;
    public $name;
    public $symbol;
    public $type;
    public $base_unit_id;
    public $conversion_factor_to_base = 1;
    public $is_base = false;
    public $is_active = true;

    public function mount($id = null)
    {
        $this->type = 'weight'; // default type for listing base units
        if ($id) {
            $unit = Unit::findOrFail($id);
            $this->id = $unit->id;
            $this->name = $unit->name;
            $this->symbol = $unit->symbol;
            $this->type = $unit->type;
            $this->base_unit_id = $unit->base_unit_id;
            $this->conversion_factor_to_base = $unit->conversion_factor_to_base;
            $this->is_base = $unit->is_base;
            $this->is_active = $unit->is_active;
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
            'type' => 'required|in:weight,volume,count',
            'base_unit_id' => 'nullable|exists:units,id',
            'conversion_factor_to_base' => 'required|numeric|min:0',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($this->id) {
            $unit = Unit::findOrFail($this->id);
        } else {
            $unit = new Unit();
        }

        $unit->name = $this->name;
        $unit->symbol = $this->symbol;
        $unit->type = $this->type;
        $unit->base_unit_id = $this->base_unit_id;
        $unit->conversion_factor_to_base = $this->conversion_factor_to_base;
        $unit->is_base = $this->is_base;
        $unit->is_active = $this->is_active;
        $unit->save();

        return redirect()->route('units')->with('success', 'Unit saved successfully.');
    }
    public function render()
    {
        return view('livewire.units.unit-create', [
            'units' => Unit::where('is_base', true)
                ->when($this->id, fn($q) => $q->where('id', '!=', $this->id))
                ->get(),
        ]);
    }
}
