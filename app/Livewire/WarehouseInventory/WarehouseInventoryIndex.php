<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\ReportItem;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class WarehouseInventoryIndex extends Component
{
    use AuthorizesRequests;

    public $warehouses      = [];
    public $warehouse_id;
    public $inventoryItems  = [];
    public $selectedWarehouse;
    public $selectedInventory;
    public $warehouseUnits  = [];
    public $warehouseMap    = [];

    // API lookup maps  [ id => name ],  [ item_id => [ unit_id => name ] ]
    public $itemMap         = [];
    public $unitMap         = [];

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.rawMaterialWarehouseInventory-list');

        $this->warehouses = $api->get('/v1/warehouses', [
            'related_to_production' => true,
        ])['data'] ?? [];

        $this->warehouseMap = collect($this->warehouses)->pluck('name', 'id')->toArray();

        // Fetch active items with their units from the API so any item held in
        // inventory (whatever its type) can be resolved to a name/unit.
        $allItems = $api->get('/v1/items', [
            'is_active' => true,
        ])['data'] ?? [];

        foreach ($allItems as $item) {
            $this->itemMap[$item['id']] = $item['name'];

            foreach ($item['units'] ?? [] as $unit) {
                $this->unitMap[$item['id']][$unit['id']] = $unit['name'];
            }
        }
    }

    // Resolve item name from API map
    protected function itemName(int $itemId): string
    {
        return $this->itemMap[$itemId] ?? 'N/A';
    }

    // Resolve unit name from API map
    protected function unitName(int $itemId, int $unitId): string
    {
        return $this->unitMap[$itemId][$unitId] ?? 'N/A';
    }

    public function getData(): void
    {
        if (!$this->warehouse_id) {
            $this->warehouseUnits = [];
            return;
        }

        // Read the live inventory rows for the selected warehouse
        $this->warehouseUnits = WarehouseInventory::where('warehouse_id', $this->warehouse_id)
            ->orderBy('item_id')
            ->get()
            ->map(function ($row) {
                return (object) [
                    'warehouse_id'         => $row->warehouse_id,
                    'item_id'              => $row->item_id,
                    'item_unit_id'         => $row->item_unit_id,
                    'item'                 => $this->itemName($row->item_id),
                    'unit'                 => $this->unitName($row->item_id, $row->item_unit_id),
                    'quantity'             => $row->quantity,
                    'quantity_pending_in'  => $row->quantity_pending_in,
                    'quantity_pending_out' => $row->quantity_pending_out,
                ];
            })
            ->values();
    }

    #[On('viewUnitActivity')]
    public function viewUnitActivity($warehouseId, $itemId, $itemUnitId): void
    {
        $this->selectedWarehouse = (object) [
            'id'   => $warehouseId,
            'name' => $this->warehouseMap[$warehouseId] ?? 'Unknown',
        ];

        // Live on-hand / pending figures for this item-unit in this warehouse
        $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('item_unit_id', $itemUnitId)
            ->first();

        $this->selectedInventory = (object) [
            'item'                 => $this->itemName($itemId),
            'unit'                 => $this->unitName($itemId, $itemUnitId),
            'quantity'             => $inventory->quantity ?? 0,
            'quantity_pending_in'  => $inventory->quantity_pending_in ?? 0,
            'quantity_pending_out' => $inventory->quantity_pending_out ?? 0,
        ];

        $items = ReportItem::where('item_id', $itemId)
            ->where('item_unit_id', $itemUnitId)
            ->where(function ($query) use ($warehouseId) {
                $query->whereHas('stockIn', function ($q) use ($warehouseId) {
                        $q->where('status', 'approved')
                          ->where('warehouse_id', $warehouseId);
                    })
                    ->orWhereHas('stockOut', function ($q) use ($warehouseId) {
                        $q->where('status', 'approved')
                          ->where('warehouse_id', $warehouseId);
                    })
                    ->orWhereHas('waste', function ($q) use ($warehouseId) {
                        $q->where('status', 'approved')
                          ->where('warehouse_id', $warehouseId);
                    })
                    ->orWhereHas('transfer', function ($q) use ($warehouseId) {
                        $q->where('status', 'approved')
                          ->where(function ($qq) use ($warehouseId) {
                              $qq->where('warehouse_from_id', $warehouseId)
                                 ->orWhere('warehouse_to_id', $warehouseId);
                          });
                    });
            })
            ->with('transfer')   // only local relation needed for warehouse_from/to check
            ->orderBy('created_at', 'asc')
            ->get();

        $runningStock = 0;

        foreach ($items as $item) {
            // Attach resolved names from API maps
            $item->item_name = $this->itemName($item->item_id);
            $item->unit_name = $this->unitName($item->item_id, $item->item_unit_id);

            if ($item->stock_in_id) {
                $runningStock += $item->quantity ?? 0;
            } elseif ($item->stock_out_id) {
                $runningStock -= $item->quantity ?? 0;
            } elseif ($item->waste_id) {
                $runningStock -= $item->quantity ?? 0;
            } elseif ($item->transfer_id) {
                if ($item->transfer->warehouse_from_id == $warehouseId) {
                    $runningStock -= $item->received_quantity ?? 0;
                } elseif ($item->transfer->warehouse_to_id == $warehouseId) {
                    $runningStock += $item->received_quantity ?? 0;
                }
            }

            $item->stock_total = $runningStock;
        }

        $this->inventoryItems = $items;

        $this->dispatch('openDetailsModal');
        $this->getData();
    }

    public function render()
    {
        return view('livewire.warehouse-inventory.warehouse-inventory-index');
    }
}
