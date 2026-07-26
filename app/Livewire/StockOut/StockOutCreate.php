<?php

namespace App\Livewire\StockOut;

use App\Models\StockOut;
use App\Services\ApiService;
use App\Services\InventoryService;
use DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class StockOutCreate extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public bool $editing  = false;
    public $id;
    public $viewStatus;
    public $stockOut;
    public $notes;
    public $warehouse_id;

    public $warehouses    = [];
    public $allItems      = [];   // items for the selected warehouse from API
    public $stockOutItems = [];   // row data
    public $rowUnits      = [];   // units per row keyed by index

    protected ApiService $api;
    protected InventoryService $inventory;

    public function boot(ApiService $api, InventoryService $inventory): void
    {
        $this->api       = $api;
        $this->inventory = $inventory;
    }

    public function mount(ApiService $api, $id = null, $viewStatus = null): void
    {
        $this->viewStatus = $viewStatus;

        authorizeRequest($viewStatus == 1 ? 'production.itemStockOut-view' : 'production.stockOut-create');

        $this->warehouses = $api->get('/v1/warehouses', [
            'related_to_production' => true,
        ])['data'] ?? [];

        if ($id) {
            $this->id      = $id;
            $this->editing = true;

            $stockOut           = StockOut::with('reportItems')->findOrFail($id);
            $this->stockOut     = $stockOut;
            $this->warehouse_id = $stockOut->warehouse_id;
            $this->notes        = $stockOut->notes;

            $this->getWarehouseItems($this->warehouse_id, dispatch: false);

            $this->stockOutItems = $stockOut->reportItems->map(fn($input) => [
                'id'           => $input->id,
                'item_id'      => $input->item_id,
                'item_unit_id' => $input->item_unit_id,
                'quantity'     => $input->quantity,
            ])->toArray();

            // Pre-load units for each existing row
            foreach ($this->stockOutItems as $index => $row) {
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

    #[On('getWarehouseItems')]
    public function getWarehouseItems($warehouseId, $dispatch = true): void
    {
        $this->allItems = $this->api->get("/v1/warehouses/{$warehouseId}/items")['data'] ?? [];

        if ($dispatch) {
            $this->dispatch('setWarehouseItems',  $this->allItems);
        }
    }

    #[On('getItemUnits')]
    public function getItemUnits($itemId, $index, $dispatch = true): void
    {
        $units = $this->fetchUnitsForItem((int) $itemId);
        $this->rowUnits[$index] = $units;

        if ($dispatch) {
            $this->dispatch('setItemUnits', $index, $units);
        }
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
                $this->stockOutItems[$index]['item_unit_id'] = $basicUnit['id'] ?? ($units[0]['id'] ?? null);
            } else {
                $this->rowUnits[$index]                      = [];
                $this->stockOutItems[$index]['item_unit_id'] = null;
            }
        }
    }

    public function addRow(): void
    {
        $this->stockOutItems[] = [
            'id'           => null,
            'item_id'      => null,
            'item_unit_id' => null,
            'quantity'     => '',
        ];
        $this->rowUnits[] = [];
    }

    public function removeItem(int $index): void
    {
        if (count($this->stockOutItems) <= 1) {
            $this->dispatch('swal:error', [
                'title' => 'Warning',
                'text'  => 'At least one item is required!',
            ]);
            return;
        }

        unset($this->stockOutItems[$index], $this->rowUnits[$index]);
        $this->stockOutItems = array_values($this->stockOutItems);
        $this->rowUnits      = array_values($this->rowUnits);
    }

    public function submit()
    {
        $this->validate([
            'warehouse_id'                 => 'required|integer',
            'stockOutItems'                => 'required|array|min:1',
            'stockOutItems.*.item_id'      => 'required|integer',
            'stockOutItems.*.item_unit_id' => 'required|integer',
            'stockOutItems.*.quantity'     => 'required|numeric|min:0.000001',
        ], [
            'stockOutItems.required'                => 'Please add at least one item.',
            'stockOutItems.min'                     => 'Please add at least one item.',
            'stockOutItems.*.item_id.required'      => 'Item is required.',
            'stockOutItems.*.item_unit_id.required' => 'Unit is required.',
            'stockOutItems.*.quantity.required'     => 'Quantity is required.',
            'stockOutItems.*.quantity.numeric'      => 'Quantity must be a number.',
            'stockOutItems.*.quantity.min'          => 'Quantity must be greater than 0.',
        ]);

        DB::beginTransaction();
        try {
            if ($this->editing) {
                $stockOut = StockOut::findOrFail($this->id);
                $stockOut->update([
                    'warehouse_id' => $this->warehouse_id,
                    'notes'        => $this->notes,
                ]);
            } else {
                $stockOut = StockOut::create([
                    'warehouse_id' => $this->warehouse_id,
                    'notes'        => $this->notes,
                ]);
            }

            $ops = [];

            // When editing, unwind the previous reservation before re-applying below
            if ($this->editing) {
                foreach ($stockOut->reportItems()->get() as $old) {
                    $ops[] = InventoryService::releaseOut(
                        $old->warehouse_id, $old->item_id, $old->item_unit_id, (float) $old->quantity
                    );
                }
            }

            $syncedIds = [];

            foreach ($this->stockOutItems as $index => $row) {
                $item = collect($this->allItems)->firstWhere('id', (int) $row['item_id']);
                abort_if(!$item, 422, 'Invalid item selected.');

                $unit = collect($this->rowUnits[$index] ?? [])->firstWhere('id', (int) $row['item_unit_id']);
                abort_if(!$unit, 422, 'Invalid unit selected.');

                $data = [
                    'warehouse_id'  => $this->warehouse_id,
                    'item_id'       => $item['id'],
                    'item_unit_id'  => $unit['id'],
                    'quantity'      => (float) $row['quantity'],
                ];

                if (!empty($row['id'])) {
                    $input = $stockOut->reportItems()->findOrFail($row['id']);
                    $input->update($data);
                } else {
                    $input = $stockOut->reportItems()->create($data);
                }

                // Reserve the outgoing quantity as pending until the Stock Out is approved
                $ops[] = InventoryService::reserveOut(
                    $this->warehouse_id, $item['id'], $unit['id'], (float) $row['quantity']
                );

                $syncedIds[] = $input->id;
            }

            if ($this->editing) {
                $stockOut->reportItems()->whereNotIn('id', $syncedIds)->delete();
            }

            // Push the reservation changes to the parent inventory (throws on failure)
            $this->inventory->applyOrFail($ops);

            DB::commit();

            return to_route('item-stock-outs')->with(
                'success',
                $this->editing ? 'Stock Out updated successfully!' : 'Stock Out created successfully!'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }
    }

    protected function warehouseName($warehouseId): string
    {
        return collect($this->warehouses)->firstWhere('id', (int) $warehouseId)['name'] ?? '—';
    }

    /**
     * Resolve each row's item/unit into display names using the data already
     * loaded from the API (the parent DB holds items, units and warehouses).
     */
    protected function resolvedRows(): array
    {
        $rows = [];

        foreach ($this->stockOutItems as $index => $row) {
            $item = collect($this->allItems)->firstWhere('id', (int) $row['item_id']);
            $unit = collect($this->rowUnits[$index] ?? [])->firstWhere('id', (int) $row['item_unit_id']);

            $rows[] = [
                'item'     => $item ? trim(($item['name'] ?? '') . ' (' . ($item['code'] ?? '') . ')') : '—',
                'unit'     => $unit ? trim(($unit['name'] ?? '') . ' (' . ($unit['symbol'] ?? '') . ')') : '—',
                'quantity' => $row['quantity'],
            ];
        }

        return $rows;
    }

    public function render()
    {
        if ($this->viewStatus == 1) {
            return view('livewire.stock-out.stock-out-view', [
                'warehouseName' => $this->warehouseName($this->warehouse_id),
                'rows'          => $this->resolvedRows(),
            ]);
        }

        return view('livewire.stock-out.stock-out-create', [
            'items'      => $this->allItems,
            'warehouses' => $this->warehouses,
            'rowUnits'   => $this->rowUnits,
        ]);
    }
}
