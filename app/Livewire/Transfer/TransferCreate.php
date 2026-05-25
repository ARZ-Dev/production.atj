<?php

namespace App\Livewire\Transfer;

use App\Models\Transfer;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TransferCreate extends Component
{
    use AuthorizesRequests;

    public bool $editing       = false;
    public $id;
    public $viewStatus;
    public $confirmStatus      = 0;

    public $warehouse_from_id;
    public $warehouse_to_id;
    public $warehouses         = [];

    public $allItems           = [];   // raw material items from API
    public $rawMaterials       = [];   // row data
    public $rowUnits           = [];   // units per row keyed by index

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api, $id = null, $viewStatus = null)
    {
        $routeName = request()->route()->getName();

        if ($routeName === 'item-transfers.approve-load') {
            $this->confirmStatus = 1;
            authorizeRequest('production.itemTransfer-approve');
        } elseif ($routeName === 'item-transfers.approve-receive') {
            $this->confirmStatus = 2;
            authorizeRequest('production.itemTransfer-approve');
        } else {
            $this->confirmStatus = 0;
            authorizeRequest('production.itemTransfer-create');
        }

        $this->viewStatus = $viewStatus;

        $this->warehouses = $api->get('/v1/warehouses', [
            'related_to_production' => true,
        ])['data'] ?? [];

        $this->allItems = $api->get('/v1/items', [
            'item_type' => 'Raw Material',
            'is_active'  => true,
        ])['data'] ?? [];

        if ($id) {
            $this->id      = $id;
            $this->editing = true;

            $transfer = Transfer::with('reportItems')->findOrFail($id);

            if ($this->confirmStatus == 1 && $transfer->status !== 'pending') {
                session()->flash('error', 'This transfer has already been processed!');
                return redirect()->route('item-transfers');
            }

            if ($this->confirmStatus == 2 && $transfer->status !== 'loaded') {
                session()->flash('error', 'Transfer must be in loaded status to approve receive!');
                return redirect()->route('item-transfers');
            }

            $this->warehouse_from_id = $transfer->warehouse_from_id;
            $this->warehouse_to_id   = $transfer->warehouse_to_id;

            $this->rawMaterials = $transfer->reportItems->map(fn($reportItem) => [
                'id'                => $reportItem->id,
                'item_id'           => $reportItem->item_id,
                'item_unit_id'      => $reportItem->item_unit_id,
                'quantity'          => $reportItem->quantity,
                'received_quantity' => $this->confirmStatus == 2
                    ? ($reportItem->received_quantity ?? $reportItem->quantity)
                    : null,
            ])->toArray();

            // Pre-load units for each existing row
            foreach ($this->rawMaterials as $index => $row) {
                $this->rowUnits[$index] = $row['item_id']
                    ? $this->fetchUnitsForItem($row['item_id'])
                    : [];
            }
        } else {
            $this->addRow();
        }
    }

    protected function fetchUnitsForItem(int $itemId): array
    {
        $response = $this->api->get("/v1/items/{$itemId}");
        return $response['data']['units'] ?? [];
    }

    public function updatedRawMaterials($value, $key): void
    {
        if (str_ends_with($key, '.item_id')) {
            $index = (int) explode('.', $key)[0];

            if ($value) {
                $units                  = $this->fetchUnitsForItem((int) $value);
                $this->rowUnits[$index] = $units;

                // Auto-select basic unit, fallback to first
                $basicUnit = collect($units)->firstWhere('basic', true);
                $this->rawMaterials[$index]['item_unit_id'] = $basicUnit['id'] ?? ($units[0]['id'] ?? null);
            } else {
                $this->rowUnits[$index]                     = [];
                $this->rawMaterials[$index]['item_unit_id'] = null;
            }
        }
    }

    public function addRow(): void
    {
        $this->rawMaterials[] = [
            'id'                => null,
            'item_id'           => null,
            'item_unit_id'      => null,
            'quantity'          => '',
            'received_quantity' => '',
        ];
        $this->rowUnits[] = [];
    }

    public function removeItem(int $index): void
    {
        if (count($this->rawMaterials) <= 1) {
            $this->dispatch('swal:error', [
                'title' => 'Warning',
                'text'  => 'At least one item is required!',
            ]);
            return;
        }

        unset($this->rawMaterials[$index], $this->rowUnits[$index]);
        $this->rawMaterials = array_values($this->rawMaterials);
        $this->rowUnits     = array_values($this->rowUnits);
    }

    protected function rules(): array
    {
        if ($this->confirmStatus == 2) {
            return [
                'rawMaterials.*.received_quantity' => 'required|numeric|min:0',
            ];
        }

        return [
            'warehouse_from_id'           => 'required|integer',
            'warehouse_to_id'             => 'required|integer|different:warehouse_from_id',
            'rawMaterials'                => 'required|array|min:1',
            'rawMaterials.*.item_id'      => 'required|integer',
            'rawMaterials.*.item_unit_id' => 'required|integer',
            'rawMaterials.*.quantity'     => 'required|numeric|min:0.000001',
        ];
    }

    protected function messages(): array
    {
        return [
            'warehouse_to_id.different'                  => 'Warehouse To must be different from Warehouse From.',
            'rawMaterials.required'                      => 'Please add at least one item.',
            'rawMaterials.min'                           => 'Please add at least one item.',
            'rawMaterials.*.item_id.required'            => 'Item is required.',
            'rawMaterials.*.item_unit_id.required'       => 'Unit is required.',
            'rawMaterials.*.quantity.required'           => 'Loaded quantity is required.',
            'rawMaterials.*.quantity.numeric'            => 'Loaded quantity must be a number.',
            'rawMaterials.*.quantity.min'                => 'Loaded quantity must be greater than 0.',
            'rawMaterials.*.received_quantity.required'  => 'Received quantity is required.',
            'rawMaterials.*.received_quantity.numeric'   => 'Received quantity must be a number.',
            'rawMaterials.*.received_quantity.min'       => 'Received quantity must be 0 or greater.',
        ];
    }

    public function submit(): mixed
    {
        $this->validate();

        DB::beginTransaction();
        try {
            if ($this->editing) {
                $transfer = Transfer::findOrFail($this->id);

                if ($transfer->status !== 'pending') {
                    DB::rollBack();
                    return to_route('item-transfers')->with('error', 'Cannot edit a transfer that has already been processed!');
                }

                $transfer->update([
                    'warehouse_from_id' => $this->warehouse_from_id,
                    'warehouse_to_id'   => $this->warehouse_to_id,
                ]);
            } else {
                $transfer = Transfer::create([
                    'warehouse_from_id' => $this->warehouse_from_id,
                    'warehouse_to_id'   => $this->warehouse_to_id,
                    'status'            => 'pending',
                ]);
            }

            $syncedIds = [];

            foreach ($this->rawMaterials as $index => $row) {
                $item = collect($this->allItems)->firstWhere('id', (int) $row['item_id']);
                abort_if(!$item, 422, 'Invalid item selected.');

                $unit = collect($this->rowUnits[$index] ?? [])->firstWhere('id', (int) $row['item_unit_id']);
                abort_if(!$unit, 422, 'Invalid unit selected.');

                $data = [
                    'warehouse_id'  => $this->warehouse_from_id,
                    'item_id'       => $item['id'],
                    'item_unit_id'  => $unit['id'],
                    'quantity'      => (float) $row['quantity'],
                ];

                if (!empty($row['id'])) {
                    $input = $transfer->reportItems()->findOrFail($row['id']);
                    $input->update($data);
                } else {
                    $input = $transfer->reportItems()->create($data);
                }

                $syncedIds[] = $input->id;
            }

            if ($this->editing) {
                $transfer->reportItems()->whereNotIn('id', $syncedIds)->delete();
            }

            DB::commit();

            return to_route('item-transfers')->with(
                'success',
                $this->editing ? 'Transfer updated successfully!' : 'Transfer created successfully!'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }
    }

    public function confirmLoad(): mixed
    {
        $this->validate();

        $transfer = Transfer::with('reportItems')->findOrFail($this->id);

        DB::beginTransaction();
        try {
            foreach ($this->rawMaterials as $index => $row) {
                $item = collect($this->allItems)->firstWhere('id', (int) $row['item_id']);
                abort_if(!$item, 422, 'Invalid item selected.');

                $unit = collect($this->rowUnits[$index] ?? [])->firstWhere('id', (int) $row['item_unit_id']);
                abort_if(!$unit, 422, 'Invalid unit selected.');

                $transfer->reportItems()->findOrFail($row['id'])->update([
                    'warehouse_id'  => $this->warehouse_from_id,
                    'item_id'       => $item['id'],
                    'item_unit_id'  => $unit['id'],
                    'quantity'      => (float) $row['quantity'],
                ]);

                // Ensure inventory rows exist on both sides
                WarehouseInventory::firstOrCreate([
                    'warehouse_id' => $this->warehouse_from_id,
                    'item_id'      => $item['id'],
                    'item_unit_id' => $unit['id'],
                ], ['quantity' => 0]);

                WarehouseInventory::firstOrCreate([
                    'warehouse_id' => $this->warehouse_to_id,
                    'item_id'      => $item['id'],
                    'item_unit_id' => $unit['id'],
                ], ['quantity' => 0]);
            }

            $transfer->update(['status' => 'loaded']);

            DB::commit();

            return to_route('item-transfers')->with('success', 'Transfer load approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }
    }

    public function confirmReceive(): mixed
    {
        $this->validate();

        $transfer = Transfer::with('reportItems')->findOrFail($this->id);

        DB::beginTransaction();
        try {
            foreach ($this->rawMaterials as $row) {
                $receivedQty = (float) $row['received_quantity'];

                // Add to destination warehouse
                $inventoryTo = WarehouseInventory::where('warehouse_id', $this->warehouse_to_id)
                    ->where('item_id', $row['item_id'])
                    ->where('item_unit_id', $row['item_unit_id'])
                    ->first();

                if ($inventoryTo) {
                    $inventoryTo->increment('quantity', $receivedQty);
                }

                // Deduct from source warehouse
                $inventoryFrom = WarehouseInventory::where('warehouse_id', $this->warehouse_from_id)
                    ->where('item_id', $row['item_id'])
                    ->where('item_unit_id', $row['item_unit_id'])
                    ->first();

                if ($inventoryFrom) {
                    $inventoryFrom->decrement('quantity', $receivedQty);
                }

                // Save received_quantity on the report item
                $transfer->reportItems()
                    ->where('id', $row['id'])
                    ->update(['received_quantity' => $receivedQty]);
            }

            $transfer->update(['status' => 'approved']);

            DB::commit();

            return to_route('item-transfers')->with('success', 'Transfer receive approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        if ($this->viewStatus == 1) {
            return view('livewire.transfer.transfer-view');
        }

        return view('livewire.transfer.transfer-create', [
            'items'      => $this->allItems,
            'warehouses' => $this->warehouses,
            'rowUnits'   => $this->rowUnits,
        ]);
    }
}