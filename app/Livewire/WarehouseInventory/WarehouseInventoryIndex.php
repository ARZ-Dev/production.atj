<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\ReportRawMaterial;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class WarehouseInventoryIndex extends Component
{
    use AuthorizesRequests;

    public $warehouses = [];
    public $warehouse_id;
    public $inventoryItems = [];
    public $selectedWarehouse;
    public $warehouseUnits = [];
    public $warehouseMap = [];

    public function mount(ApiService $api)
    {
        authorizeRequest('production.warehouseInventory-list');

        $this->warehouses = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
        $this->warehouseMap = collect($this->warehouses)->pluck('name', 'id')->toArray();
    }

    public function totalQuantity($warehouseId, $rawMaterialId, $unitId)
    {
        return WarehouseInventory::where('warehouse_id', $warehouseId)
            ->where('raw_material_id', $rawMaterialId)
            ->where('unit_id', $unitId)
            ->value('quantity') ?? 0;
    }

    public function getData()
    {
        if ($this->warehouse_id) {
            $warehouseId = $this->warehouse_id;

            $this->warehouseUnits = ReportRawMaterial::where(function ($query) use ($warehouseId) {
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
                });
            })
                ->with(['rawMaterial', 'unit'])
                ->get()
                ->groupBy(function ($row) {
                    return $row->raw_material_id . '_' . $row->unit_id;
                })
                ->map(function ($group) use ($warehouseId) {
                    $first = $group->first();

                    return (object) [
                        'warehouse_id'    => $warehouseId,
                        'raw_material_id' => $first->raw_material_id,
                        'unit_id'         => $first->unit_id,
                        'rawMaterial'     => $first->rawMaterial->name,
                        'unit'            => $first->unit->name,
                        'total_quantity'  => $group->sum('quantity'),
                    ];
                })
                ->values();
        }
    }

    #[On('viewUnitActivity')]
    public function viewUnitActivity($warehouseId, $rawMaterialId, $unitId)
    {
        // Use warehouseMap from API instead of DB query
        $this->selectedWarehouse = (object) [
            'id'   => $warehouseId,
            'name' => $this->warehouseMap[$warehouseId] ?? 'Unknown',
        ];

        $items = ReportRawMaterial::where('raw_material_id', $rawMaterialId)
            ->where('unit_id', $unitId)
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
                });
            })
            ->orderBy('created_at', 'asc')
            ->with(['rawMaterial', 'unit'])
            ->get();

        $runningStock = 0;

        foreach ($items as $item) {
            if ($item->stock_in_id) {
                $runningStock += $item->quantity ?? 0;
            } elseif ($item->stock_out_id) {
                $runningStock -= $item->quantity ?? 0;
            } elseif ($item->waste_id) {
                $runningStock -= $item->quantity ?? 0;
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