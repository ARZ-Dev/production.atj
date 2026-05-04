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
    public $units = [];
    public $viewStatus;

    public $notes;

    public $documents = [];
    public function mount(ApiService $api,$id = null, $viewStatus = null)
    {
        authorizeRequest('production.stockIn-create');
        $this->viewStatus = $viewStatus;

      
            $this->warehouses = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
            $this->availableRawMaterials = RawMaterial::all();
            $this->units = Unit::all();

        $this->rawMaterials = [
            [
                'raw_material_id' => '',
                'unit_id' => '',
                'quantity' => '',
            ]
        ];

        if ($id) {
            $this->id = $id;
            $this->editing = true;

            $this->stockIn = RawMaterialStockIn::with('reportRawMaterials')->findOrFail($id);
            $this->warehouses = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
            $this->warehouse_id = $this->stockIn->warehouse_id;
            $this->notes = $this->stockIn->notes;

            $this->availableRawMaterials = RawMaterial::all();
            $this->rawMaterials = [];
            $this->rawMaterials = $this->stockIn->reportRawMaterials->map(function ($item) {
                return [
                    'id' => $item->id,
                    'raw_material_id' => $item->raw_material_id,
                    'unit_id' => $item->unit_id,
                    'quantity' => $item->quantity,
                    'units' => $item->item?->itemUnits,
                ];
            })->toArray();

            // if (count($this->stockIn->documents)) {
            //     $this->documents = [];
            //     foreach ($this->stockIn->documents as $document) {
            //         $this->documents[] = asset('storage/' . $document->document);
            //     }
            // }
        }
    }


   
    public function addRow()
    {
        $this->rawMaterials[] = [
            'raw_material_id' => '',
            'unit_id' => '',
            'quantity' => '',
        ];
    }

    public function removeItem($index)
    {
        // ✅ Prevent deleting the last row
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
                // Update existing stock in
                $this->stockIn->update([
                    'warehouse_id' => $this->warehouse_id,
                    'notes' => $this->notes,
                ]);

            } else {
                // Create new stock in
                $this->stockIn = RawMaterialStockIn::create([
                    'warehouse_id' => $this->warehouse_id,
                    'notes' => $this->notes,
                ]);
            }

            foreach ($this->rawMaterials as $item) {
                $inventory = RawMaterialWarehouseInventory::where('warehouse_id', $this->warehouse_id)
                    ->where('raw_material_id', $item['raw_material_id'])
                    ->where('unit_id', $item['unit_id'])
                    ->first();

                if (!$inventory) {
                    RawMaterialWarehouseInventory::create([
                        'warehouse_id' => $this->warehouse_id,
                        'raw_material_id' => $item['raw_material_id'],
                        'unit_id' => $item['unit_id'],
                        'quantity' => 0,
                    ]);
                }
            }

            $existingItemIds = [];
            // Update or create report items
            foreach ($this->rawMaterials as $item) {
                // Update existing item
                $reportItem = $this->stockIn->reportRawMaterials()
                    ->updateOrCreate([
                        'id' => $item['id'] ?? null,
                    ], [
                        'warehouse_id' => $this->warehouse_id,
                        'raw_material_id' => $item['raw_material_id'],
                        'unit_id' => $item['unit_id'],
                        'quantity' => $item['quantity'],
                    ]);
                $existingItemIds[] = $reportItem->id;
            }
            // Delete removed items
            $this->stockIn->reportRawMaterials()->whereNotIn('id', $existingItemIds)->delete();

            // Handle documents
            // if ($this->documents) {
            //     foreach ($this->documents as $document) {
            //         $documentPath = $document->store('stockIn', ['disk' => 'public']);
            //         $this->stockIn->documents()->create([
            //             'document' => $documentPath,
            //         ]);
            //     }
            // }

            DB::commit();
            return to_route('raw-material-stock-ins')->with('success', $this->editing ? 'Stock In updated successfully!' : 'Stock In created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('swal:error', [
                'title' => 'Error',
                'text' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    // #[On('deleteDocument')]
    // public function deleteDocument($filename = null)
    // {
    //     if (!$filename)
    //         return;

    //     if ($this->stockIn) {
    //         $filename = preg_replace('/.*\/stockIn\//', 'stockIn/', $filename);
    //         $this->stockIn->documents()->where('document', $filename)->delete();
    //     }
    // }
    public function render()
    {
        if ($this->viewStatus == 1) {
            return view('livewire.raw-material-stock-in.raw-material-stock-in-view');
        }
        return view('livewire.raw-material-stock-in.raw-material-stock-in-create');
    }
}