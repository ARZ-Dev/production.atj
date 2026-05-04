<?php

namespace App\Livewire\RawMaterials;

use App\Models\RawMaterial;
use App\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class RawMaterialCreate extends Component
{
    use AuthorizesRequests;

    public $id;
    public $code;
    public $name;
    public $base_unit_id;
    public $purchase_unit_id;
    public $type = 'solid';
    public $density;
    public $reorder_point;
    public $is_active = true;
    public $units = [];
    public $purchaseUnits = [];

    public function mount($id = null)
    {
        authorizeRequest($id ? 'production.raw-material-edit' : 'production.raw-material-create');
        
        $this->units = Unit::where('is_base', true)
            ->where('is_active', true)
            ->get();
        if ($id) {
            $rawMaterial = RawMaterial::findOrFail($id);

            $this->id = $rawMaterial->id;
            $this->code = $rawMaterial->code;
            $this->name = $rawMaterial->name;
            $this->base_unit_id = $rawMaterial->base_unit_id;

            // Load purchase units including base unit
            $this->purchaseUnits = Unit::where('is_active', true)
                ->where(function ($query) use ($rawMaterial) {
                    $query->where('base_unit_id', $rawMaterial->base_unit_id)
                        ->orWhere('id', $rawMaterial->base_unit_id);
                })
                ->get();

            $this->purchase_unit_id = $rawMaterial->purchase_unit_id;
            $this->type = $rawMaterial->type;
            $this->density = $rawMaterial->density;
            $this->reorder_point = $rawMaterial->reorder_point;
            $this->is_active = $rawMaterial->is_active;
        }
    }

    #[On('getPurchaseUnits')]
    public function getPurchaseUnits($baseUnitId)
    {
        $this->purchaseUnits = Unit::where(function ($query) use ($baseUnitId) {
            $query->where('base_unit_id', $baseUnitId)
                ->orWhere('id', $baseUnitId); // include the base unit itself
        })->where('is_active', true)->get();
        $this->dispatch('setPurchaseUnits', $this->purchaseUnits);
    }

    public function save()
    {
        $this->validate([
            'code' => 'required|string|max:255|unique:raw_materials,code' . ($this->id ? ",{$this->id}" : ''),
            'name' => 'required|string|max:255',
            'base_unit_id' => 'required|exists:units,id',
            'purchase_unit_id' => 'nullable|exists:units,id',
            'type' => 'required|in:solid,liquid,countable,other',
            'density' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $rawMaterial = $this->id ? RawMaterial::findOrFail($this->id) : new RawMaterial();

        $rawMaterial->code = $this->code;
        $rawMaterial->name = $this->name;
        $rawMaterial->base_unit_id = $this->base_unit_id;
        $rawMaterial->purchase_unit_id = $this->purchase_unit_id;
        $rawMaterial->type = $this->type;
        $rawMaterial->density = $this->density;
        $rawMaterial->reorder_point = $this->reorder_point;
        $rawMaterial->is_active = $this->is_active;
        $rawMaterial->save();

        return redirect()->route('raw-materials')->with('success', 'Raw material saved successfully.');
    }

    public function render()
    {
        return view('livewire.raw-materials.raw-material-create');
    }
}