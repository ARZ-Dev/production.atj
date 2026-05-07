<?php

namespace App\Livewire\RawMaterialStockIn;

use App\Models\RawMaterial;
use App\Models\RawMaterialStockIn;
use App\Models\Unit;
use App\Models\RawMaterialWarehouseInventory;
use App\Services\ApiService;
use DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class RawMaterialStockInCreate extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public bool $editing = false;
    public $stockIn;
    public $id;
    public $warehouses = [];
    public $warehouse_id;

    public $rawMaterials = [];
    public $availableRawMaterials = [];
    public $viewStatus;
    public $notes;
    public $documents = [];

    public function mount(ApiService $api, $id = null, $viewStatus = null)
    {
        authorizeRequest('production.stockIn-create');
        $this->viewStatus = $viewStatus;

        $this->warehouses = collect(
            $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? []
        )->filter(function ($warehouse) {
            return isset($warehouse['department']['related_to_production'])
                && $warehouse['department']['related_to_production'] == 1;
        })->values()->toArray();

        $this->availableRawMaterials = RawMaterial::all();

        $this->rawMaterials = [
            [
                'raw_material_id' => '',
                'unit_id' => '',
                'quantity' => '',
                'units' => [],
            ]
        ];

        if ($id) {
            $this->id = $id;
            $this->editing = true;

            $this->stockIn = RawMaterialStockIn::with('reportRawMaterials')->findOrFail($id);
            $this->warehouses = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
            $this->warehouse_id = $this->stockIn->warehouse_id;
            $this->notes = $this->stockIn->notes;

            $this->rawMaterials = $this->stockIn->reportRawMaterials->map(function ($item) {
                $units = [];

                if ($item->rawMaterial && $item->rawMaterial->purchase_unit_id) {
                    $units = Unit::where('id', $item->rawMaterial->purchase_unit_id)
                        ->get()
                        ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                        ->toArray();
                }

                return [
                    'id' => $item->id,
                    'raw_material_id' => $item->raw_material_id,
                    'unit_id' => $item->unit_id,
                    'quantity' => $item->quantity,
                    'units' => $units,
                ];
            })->toArray();
        }
    }

    public function addRow()
    {
        $this->rawMaterials[] = [
            'raw_material_id' => '',
            'unit_id' => '',
            'quantity' => '',
            'units' => [],
        ];
    }

    public function removeItem($index)
    {
        if (count($this->rawMaterials) <= 1) {
            $this->dispatch('swal:error', [
                'title' => 'Warning',
                'text' => 'At least one item is required!'
            ]);
            return;
        }

        unset($this->rawMaterials[$index]);
        $this->rawMaterials = array_values($this->rawMaterials);
    }

    #[On('getUnits')]
    public function getUnits($rawMaterialId, $index)
    {
        $units = [];
        $rawMaterial = RawMaterial::find($rawMaterialId);

        if ($rawMaterial && $rawMaterial->purchase_unit_id) {
            $units = Unit::where('id', $rawMaterial->purchase_unit_id)
                ->get()
                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                ->toArray();
        }

        // Clear unit selection when raw material changes
        $this->rawMaterials[$index]['units'] = $units;
        $this->rawMaterials[$index]['unit_id'] = count($units) === 1 ? $units[0]['id'] : '';

        $this->dispatch('setUnits', [
            'units' => $units,
            'index' => $index,
            'selectedUnitId' => $this->rawMaterials[$index]['unit_id'],
        ]);
    }

    public function submit()
    {
        $this->validate([
            'warehouse_id' => 'required',
            'rawMaterials' => 'required|array|min:1',
            'rawMaterials.*.raw_material_id' => 'required|exists:raw_materials,id',
            'rawMaterials.*.unit_id' => 'required|exists:units,id',
            'rawMaterials.*.quantity' => 'required|numeric|min:0.01',
        ], [
            'rawMaterials.required' => 'Please add at least one item.',
            'rawMaterials.min' => 'Please add at least one item.',
            'rawMaterials.*.raw_material_id.required' => 'Raw material is required.',
            'rawMaterials.*.unit_id.required' => 'Unit is required.',
            'rawMaterials.*.quantity.required' => 'Quantity is required.',
            'rawMaterials.*.quantity.numeric' => 'Quantity must be a number.',
            'rawMaterials.*.quantity.min' => 'Quantity must be greater than 0.',
        ]);

        DB::beginTransaction();
        try {
            if ($this->editing) {
                $this->stockIn->update([
                    'warehouse_id' => $this->warehouse_id,
                    'notes' => $this->notes,
                ]);
            } else {
                $this->stockIn = RawMaterialStockIn::create([
                    'warehouse_id' => $this->warehouse_id,
                    'notes' => $this->notes,
                ]);
            }

            foreach ($this->rawMaterials as $item) {
                $exists = RawMaterialWarehouseInventory::where('warehouse_id', $this->warehouse_id)
                    ->where('raw_material_id', $item['raw_material_id'])
                    ->where('unit_id', $item['unit_id'])
                    ->exists();

                if (!$exists) {
                    RawMaterialWarehouseInventory::create([
                        'warehouse_id' => $this->warehouse_id,
                        'raw_material_id' => $item['raw_material_id'],
                        'unit_id' => $item['unit_id'],
                        'quantity' => 0,
                    ]);
                }
            }

            $existingItemIds = [];
            foreach ($this->rawMaterials as $item) {
                $reportItem = $this->stockIn->reportRawMaterials()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [
                        'warehouse_id' => $this->warehouse_id,
                        'raw_material_id' => $item['raw_material_id'],
                        'unit_id' => $item['unit_id'],
                        'quantity' => $item['quantity'],
                    ]
                );
                $existingItemIds[] = $reportItem->id;
            }

            $this->stockIn->reportRawMaterials()->whereNotIn('id', $existingItemIds)->delete();

            DB::commit();

            return to_route('raw-material-stock-ins')->with(
                'success',
                $this->editing ? 'Stock In updated successfully!' : 'Stock In created successfully!'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        if ($this->viewStatus == 1) {
            return view('livewire.raw-material-stock-in.raw-material-stock-in-view');
        }
        return view('livewire.raw-material-stock-in.raw-material-stock-in-create');
    }
}