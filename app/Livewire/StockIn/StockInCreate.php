<?php

namespace App\Livewire\StockIn;

use App\Models\StockIn;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class StockInCreate extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public bool $editing  = false;
    public $id;
    public $viewStatus;
    public $stockIn;
    public $notes;
    public $warehouse_id;

    public $warehouses   = [];
    public $allItems     = [];   // raw material items from API
    public $stockInItems = [];   // row data
    public $rowUnits     = [];   // units per row keyed by index

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api, $id = null, $viewStatus = null): void
    {
        $this->viewStatus = $viewStatus;

        authorizeRequest($viewStatus == 1 ? 'production.itemStockIn-view' : 'production.stockIn-create');

        $this->warehouses = $api->get('/v1/warehouses', [
            'related_to_production' => true,
        ])['data'] ?? [];

        if ($id) {
            $this->id      = $id;
            $this->editing = true;

            $stockIn            = StockIn::with('reportItems')->findOrFail($id);
            $this->stockIn = $stockIn;
            $this->warehouse_id = $stockIn->warehouse_id;
            $this->notes        = $stockIn->notes;

            $this->getWarehouseItems($this->warehouse_id, dispatch: false);

            $this->stockInItems = $stockIn->reportItems->map(fn($input) => [
                'id'           => $input->id,
                'item_id'      => $input->item_id,
                'item_unit_id' => $input->item_unit_id,
                'quantity'     => $input->quantity,
            ])->toArray();

            // Pre-load units for each existing row
            foreach ($this->stockInItems as $index => $row) {
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
                $this->stockInItems[$index]['item_unit_id'] = $basicUnit['id'] ?? ($units[0]['id'] ?? null);
            } else {
                $this->rowUnits[$index]                     = [];
                $this->stockInItems[$index]['item_unit_id'] = null;
            }
        }
    }

    public function addRow(): void
    {
        $this->stockInItems[] = [
            'id'           => null,
            'item_id'      => null,
            'item_unit_id' => null,
            'quantity'     => '',
        ];
        $this->rowUnits[] = [];
    }

    public function removeItem(int $index): void
    {
        if (count($this->stockInItems) <= 1) {
            $this->dispatch('swal:error', [
                'title' => 'Warning',
                'text'  => 'At least one item is required!',
            ]);
            return;
        }

        unset($this->stockInItems[$index], $this->rowUnits[$index]);
        $this->stockInItems = array_values($this->stockInItems);
        $this->rowUnits     = array_values($this->rowUnits);
    }

    public function submit()
    {
        $this->validate([
            'warehouse_id'                => 'required|integer',
            'stockInItems'                => 'required|array|min:1',
            'stockInItems.*.item_id'      => 'required|integer',
            'stockInItems.*.item_unit_id' => 'required|integer',
            'stockInItems.*.quantity'     => 'required|numeric|min:0.000001',
        ], [
            'stockInItems.required'                => 'Please add at least one item.',
            'stockInItems.min'                     => 'Please add at least one item.',
            'stockInItems.*.item_id.required'      => 'Item is required.',
            'stockInItems.*.item_unit_id.required' => 'Unit is required.',
            'stockInItems.*.quantity.required'     => 'Quantity is required.',
            'stockInItems.*.quantity.numeric'      => 'Quantity must be a number.',
            'stockInItems.*.quantity.min'          => 'Quantity must be greater than 0.',
        ]);

        DB::beginTransaction();
        try {
            if ($this->editing) {
                $stockIn = StockIn::findOrFail($this->id);
                $stockIn->update([
                    'warehouse_id' => $this->warehouse_id,
                    'notes'        => $this->notes,
                ]);
            } else {
                $stockIn = StockIn::create([
                    'warehouse_id' => $this->warehouse_id,
                    'notes'        => $this->notes,
                ]);
            }

            // When editing, unwind the previous pending reservation before re-applying below
            $oldItems = $this->editing ? $stockIn->reportItems()->get() : collect();
            foreach ($oldItems as $old) {
                WarehouseInventory::releasePendingIn(
                    $old->warehouse_id, $old->item_id, $old->item_unit_id, (float) $old->quantity
                );
            }

            $syncedIds = [];

            foreach ($this->stockInItems as $index => $row) {
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
                    $input = $stockIn->reportItems()->findOrFail($row['id']);
                    $input->update($data);
                } else {
                    $input = $stockIn->reportItems()->create($data);
                }

                // Reserve the incoming quantity as pending until the Stock In is approved
                WarehouseInventory::addPendingIn(
                    $this->warehouse_id, $item['id'], $unit['id'], (float) $row['quantity']
                );

                $syncedIds[] = $input->id;
            }

            if ($this->editing) {
                $stockIn->reportItems()->whereNotIn('id', $syncedIds)->delete();
            }

            DB::commit();

            return to_route('item-stock-ins')->with(
                'success',
                $this->editing ? 'Stock In updated successfully!' : 'Stock In created successfully!'
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

        foreach ($this->stockInItems as $index => $row) {
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
            return view('livewire.stock-in.stock-in-view', [
                'warehouseName' => $this->warehouseName($this->warehouse_id),
                'rows'          => $this->resolvedRows(),
            ]);
        }

        return view('livewire.stock-in.stock-in-create', [
            'items'      => $this->allItems,
            'warehouses' => $this->warehouses,
            'rowUnits'   => $this->rowUnits,
        ]);
    }
}
