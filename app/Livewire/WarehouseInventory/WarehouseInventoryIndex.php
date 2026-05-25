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

        // Fetch all raw-material items with their units from the API
        $allItems = $api->get('/v1/items', [
            'item_type' => 'Raw Material',
            'is_active'  => true,
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

    public function totalQuantity($warehouseId, $itemId, $itemUnitId): float|int
    {
        return WarehouseInventory::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('item_unit_id', $itemUnitId)
            ->value('quantity') ?? 0;
    }

    public function getData(): void
    {
        if (!$this->warehouse_id) {
            return;
        }

        $warehouseId = $this->warehouse_id;

        $this->warehouseUnits = ReportItem::where(function ($query) use ($warehouseId) {
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
            ->get()
            ->groupBy(fn($row) => $row->item_id . '_' . $row->item_unit_id)
            ->map(function ($group) use ($warehouseId) {
                $first = $group->first();

                return (object) [
                    'warehouse_id'   => $warehouseId,
                    'item_id'        => $first->item_id,
                    'item_unit_id'   => $first->item_unit_id,
                    'item'           => $this->itemName($first->item_id),
                    'unit'           => $this->unitName($first->item_id, $first->item_unit_id),
                    'total_quantity' => $group->sum('quantity'),
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