<?php

namespace App\Livewire\RawMaterialWaste;

use App\Models\RawMaterial;
use App\Models\Unit;
use App\Models\RawMaterialWarehouseInventory;
use App\Models\RawMaterialWaste;
use App\Services\ApiService;
use DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class RawMaterialWasteCreate extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public bool $editing = false;
    public $waste;
    public $id;
    public $warehouses = [];
    public $warehouse_id;

    public $rawMaterials = [];
    public $availableRawMaterials = [];
    public $units = [];
    public $viewStatus;
    public $notes;
    public $documents = [];

    public function mount(ApiService $api, $id = null, $viewStatus = null)
    {
        authorizeRequest('production.rawMaterialWaste-create');
        $this->viewStatus = $viewStatus;

        $this->warehouses = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
        $this->availableRawMaterials = RawMaterial::all();
        $this->units = Unit::all();

        $this->rawMaterials = [
            [
                'raw_material_id' => '',
                'unit_id'         => '',
                'quantity'        => '',
            ]
        ];

        if ($id) {
            $this->id = $id;
            $this->editing = true;

            $this->waste = RawMaterialWaste::with('reportRawMaterials')->findOrFail($id);
            $this->warehouses = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
            $this->warehouse_id = $this->waste->warehouse_id;
            $this->notes = $this->waste->notes;

            $this->availableRawMaterials = RawMaterial::all();
            $this->rawMaterials = $this->waste->reportRawMaterials->map(function ($item) {
                return [
                    'id'              => $item->id,
                    'raw_material_id' => $item->raw_material_id,
                    'unit_id'         => $item->unit_id,
                    'quantity'        => $item->quantity,
                ];
            })->toArray();
        }
    }

    public function addRow()
    {
        $this->rawMaterials[] = [
            'raw_material_id' => '',
            'unit_id'         => '',
            'quantity'        => '',
        ];
    }

    public function removeItem($index)
    {
        if (count($this->rawMaterials) <= 1) {
            $this->dispatch('swal:error', [
                'title' => 'Warning',
                'text'  => 'At least one item is required!'
            ]);
            return;
        }

        unset($this->rawMaterials[$index]);
        $this->rawMaterials = array_values($this->rawMaterials);
    }

    public function submit()
    {
        $this->validate([
            'warehouse_id'                   => 'required',
            'rawMaterials'                   => 'required|array|min:1',
            'rawMaterials.*.raw_material_id' => 'required|exists:raw_materials,id',
            'rawMaterials.*.unit_id'         => 'required|exists:units,id',
            'rawMaterials.*.quantity'        => 'required|numeric|min:0.01',
        ], [
            'rawMaterials.required'                   => 'Please add at least one item.',
            'rawMaterials.min'                        => 'Please add at least one item.',
            'rawMaterials.*.raw_material_id.required' => 'Raw material is required.',
            'rawMaterials.*.unit_id.required'         => 'Unit is required.',
            'rawMaterials.*.quantity.required'        => 'Quantity is required.',
            'rawMaterials.*.quantity.numeric'         => 'Quantity must be a number.',
            'rawMaterials.*.quantity.min'             => 'Quantity must be greater than 0.',
        ]);

        DB::beginTransaction();
        try {
            if ($this->editing) {
                $this->waste->update([
                    'warehouse_id' => $this->warehouse_id,
                    'notes'        => $this->notes,
                ]);
            } else {
                $this->waste = RawMaterialWaste::create([
                    'warehouse_id' => $this->warehouse_id,
                    'notes'        => $this->notes,
                ]);
            }

            $existingItemIds = [];
            foreach ($this->rawMaterials as $item) {
                $reportItem = $this->waste->reportRawMaterials()
                    ->updateOrCreate([
                        'id' => $item['id'] ?? null,
                    ], [
                        'warehouse_id'    => $this->warehouse_id,
                        'raw_material_id' => $item['raw_material_id'],
                        'unit_id'         => $item['unit_id'],
                        'quantity'        => $item['quantity'],
                    ]);
                $existingItemIds[] = $reportItem->id;
            }

            $this->waste->reportRawMaterials()->whereNotIn('id', $existingItemIds)->delete();

            DB::commit();
            return to_route('raw-material-wastes')->with('success', $this->editing ? 'Waste updated successfully!' : 'Waste created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        if ($this->viewStatus == 1) {
            return view('livewire.raw-material-waste.raw-material-waste-view');
        }
        return view('livewire.raw-material-waste.raw-material-waste-create');
    }
}