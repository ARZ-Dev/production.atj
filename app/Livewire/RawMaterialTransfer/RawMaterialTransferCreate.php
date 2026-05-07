<?php

namespace App\Livewire\RawMaterialTransfer;

use App\Models\RawMaterial;
use App\Models\RawMaterialTransfer;
use App\Models\Unit;
use App\Models\RawMaterialWarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class RawMaterialTransferCreate extends Component
{
    use AuthorizesRequests;

    public bool $editing = false;
    public $transfer;
    public $id;
    public $warehouses = [];
    public $warehouse_from_id;
    public $warehouse_to_id;

    public $rawMaterials = [];
    public $availableRawMaterials = [];
    public $viewStatus;
    public $confirmStatus = 0;

    public function mount(ApiService $api, $id = null, $viewStatus = null)
    {
        $routeName = request()->route()->getName();

        if ($routeName === 'raw-material-transfers.approve-load') {
            $this->confirmStatus = 1;
            authorizeRequest('production.rawMaterialTransfer-approve');
        } elseif ($routeName === 'raw-material-transfers.approve-receive') {
            $this->confirmStatus = 2;
            authorizeRequest('production.rawMaterialTransfer-approve');
        } else {
            $this->confirmStatus = 0;
            authorizeRequest('production.rawMaterialTransfer-create');
        }

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
                'raw_material_id'   => '',
                'unit_id'           => '',
                'quantity'          => '',
                'received_quantity' => '',
                'units'             => [],
            ]
        ];

        if ($id) {
            $this->id      = $id;
            $this->editing = true;

            $this->transfer = RawMaterialTransfer::with('reportRawMaterials')->findOrFail($id);

            if ($this->confirmStatus == 1 && $this->transfer->status !== 'pending') {
                session()->flash('error', 'This transfer has already been processed!');
                return redirect()->route('raw-material-transfers');
            }

            if ($this->confirmStatus == 2 && $this->transfer->status !== 'loaded') {
                session()->flash('error', 'Transfer must be in loaded status to approve receive!');
                return redirect()->route('raw-material-transfers');
            }

            $this->warehouses        = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
            $this->warehouse_from_id = $this->transfer->warehouse_from_id;
            $this->warehouse_to_id   = $this->transfer->warehouse_to_id;

            $this->rawMaterials = $this->transfer->reportRawMaterials->map(function ($item) {
                $units = [];

                if ($item->rawMaterial && $item->rawMaterial->purchase_unit_id) {
                    $units = Unit::where('id', $item->rawMaterial->purchase_unit_id)
                        ->get()
                        ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                        ->toArray();
                }

                return [
                    'id'                => $item->id,
                    'raw_material_id'   => $item->raw_material_id,
                    'unit_id'           => $item->unit_id,
                    'quantity'          => $item->quantity,
                    'received_quantity' => $this->confirmStatus == 2
                        ? ($item->received_quantity ?? $item->quantity)
                        : null,
                    'units'             => $units,
                ];
            })->toArray();

        } else {
            $this->transfer = new RawMaterialTransfer();
        }
    }

    public function addRow()
    {
        $this->rawMaterials[] = [
            'raw_material_id'   => '',
            'unit_id'           => '',
            'quantity'          => '',
            'received_quantity' => '',
            'units'             => [],
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

    public function rules()
    {
        if ($this->confirmStatus == 2) {
            return [
                'rawMaterials.*.received_quantity' => 'required|numeric|min:0',
            ];
        }

        return [
            'warehouse_from_id'              => 'required',
            'warehouse_to_id'                => 'required|different:warehouse_from_id',
            'rawMaterials'                   => 'required|array|min:1',
            'rawMaterials.*.raw_material_id' => 'required|exists:raw_materials,id',
            'rawMaterials.*.unit_id'         => 'required|exists:units,id',
            'rawMaterials.*.quantity'        => 'required|numeric|min:0.01',
        ];
    }

    protected function messages()
    {
        return [
            'warehouse_to_id.different'               => 'Warehouse To must be different from Warehouse From.',
            'rawMaterials.required'                   => 'Please add at least one item.',
            'rawMaterials.min'                        => 'Please add at least one item.',
            'rawMaterials.*.raw_material_id.required' => 'Raw material is required.',
            'rawMaterials.*.unit_id.required'         => 'Unit is required.',
            'rawMaterials.*.quantity.required'        => 'Loaded quantity is required.',
            'rawMaterials.*.quantity.numeric'         => 'Loaded quantity must be a number.',
            'rawMaterials.*.quantity.min'             => 'Loaded quantity must be greater than 0.',
            'rawMaterials.*.received_quantity.required' => 'Received quantity is required.',
            'rawMaterials.*.received_quantity.numeric'  => 'Received quantity must be a number.',
            'rawMaterials.*.received_quantity.min'      => 'Received quantity must be 0 or greater.',
        ];
    }

    #[On('getUnits')]
    public function getUnits($rawMaterialId, $index)
    {
        $units       = [];
        $rawMaterial = RawMaterial::find($rawMaterialId);

        if ($rawMaterial && $rawMaterial->purchase_unit_id) {
            $units = Unit::where('id', $rawMaterial->purchase_unit_id)
                ->get()
                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                ->toArray();
        }

        $this->rawMaterials[$index]['units']   = $units;
        $this->rawMaterials[$index]['unit_id'] = count($units) === 1 ? $units[0]['id'] : '';

        $this->dispatch('setUnits', [
            'units'          => $units,
            'index'          => $index,
            'selectedUnitId' => $this->rawMaterials[$index]['unit_id'],
        ]);
    }

    public function submit()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            if ($this->editing) {
                if ($this->transfer->status !== 'pending') {
                    DB::rollBack();
                    return to_route('raw-material-transfers')->with('error', 'Cannot edit transfer that has already been processed!');
                }

                $this->transfer->update([
                    'warehouse_from_id' => $this->warehouse_from_id,
                    'warehouse_to_id'   => $this->warehouse_to_id,
                ]);
            } else {
                $this->transfer = RawMaterialTransfer::create([
                    'warehouse_from_id' => $this->warehouse_from_id,
                    'warehouse_to_id'   => $this->warehouse_to_id,
                    'status'            => 'pending',
                ]);
            }

            $existingItemIds = [];
            foreach ($this->rawMaterials as $item) {
                $reportItem = $this->transfer->reportRawMaterials()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [
                        'warehouse_id'    => $this->warehouse_from_id,
                        'raw_material_id' => $item['raw_material_id'],
                        'unit_id'         => $item['unit_id'],
                        'quantity'        => $item['quantity'],
                    ]
                );
                $existingItemIds[] = $reportItem->id;
            }

            if ($this->editing) {
                $this->transfer->reportRawMaterials()->whereNotIn('id', $existingItemIds)->delete();
            }

            DB::commit();

            return to_route('raw-material-transfers')->with(
                'success',
                $this->editing ? 'Transfer updated successfully!' : 'Transfer created successfully!'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function confirmLoad()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $existingItemIds = [];
            foreach ($this->rawMaterials as $item) {
                $reportItem = $this->transfer->reportRawMaterials()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [
                        'warehouse_id'    => $this->warehouse_from_id,
                        'raw_material_id' => $item['raw_material_id'],
                        'unit_id'         => $item['unit_id'],
                        'quantity'        => $item['quantity'],
                    ]
                );
                $existingItemIds[] = $reportItem->id;

                RawMaterialWarehouseInventory::firstOrCreate([
                    'warehouse_id'    => $this->warehouse_to_id,
                    'raw_material_id' => $item['raw_material_id'],
                    'unit_id'         => $item['unit_id'],
                ], ['quantity' => 0]);

                RawMaterialWarehouseInventory::firstOrCreate([
                    'warehouse_id'    => $this->warehouse_from_id,
                    'raw_material_id' => $item['raw_material_id'],
                    'unit_id'         => $item['unit_id'],
                ], ['quantity' => 0]);
            }

            $this->transfer->update(['status' => 'loaded']);

            DB::commit();

            return to_route('raw-material-transfers')->with('success', 'Transfer load approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function confirmReceive()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            foreach ($this->rawMaterials as $item) {
                $inventoryTo = RawMaterialWarehouseInventory::where('warehouse_id', $this->warehouse_to_id)
                    ->where('raw_material_id', $item['raw_material_id'])
                    ->where('unit_id', $item['unit_id'])
                    ->first();

                if ($inventoryTo) {
                    $inventoryTo->increment('quantity', $item['received_quantity']);
                }

                $inventoryFrom = RawMaterialWarehouseInventory::where('warehouse_id', $this->warehouse_from_id)
                    ->where('raw_material_id', $item['raw_material_id'])
                    ->where('unit_id', $item['unit_id'])
                    ->first();

                if ($inventoryFrom) {
                    $inventoryFrom->decrement('quantity', $item['received_quantity']);
                }

                $this->transfer->reportRawMaterials()
                    ->where('id', $item['id'])
                    ->update(['received_quantity' => $item['received_quantity']]);
            }

            $this->transfer->update(['status' => 'approved']);

            DB::commit();

            return to_route('raw-material-transfers')->with('success', 'Transfer receive approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        if ($this->viewStatus == 1) {
            return view('livewire.raw-material-transfer.raw-material-transfer-view');
        }
        return view('livewire.raw-material-transfer.raw-material-transfer-create');
    }
}   