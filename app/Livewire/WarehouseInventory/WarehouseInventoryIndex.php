<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\EventQuantity;
use App\Models\ReportItem;
use App\Services\ApiService;
use App\Services\InventoryService;
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

    // Active bucket filter for the activity popup: all|on_hand|pending_in|pending_out|in_process
    public string $activityFilter = 'all';

    public $warehouseUnits  = [];
    public $warehouseMap    = [];

    // API lookup maps  [ id => name ],  [ item_id => [ unit_id => name ] ]
    public $itemMap         = [];
    public $unitMap         = [];

    protected ApiService $api;
    protected InventoryService $inventory;

    public function boot(ApiService $api, InventoryService $inventory): void
    {
        $this->api       = $api;
        $this->inventory = $inventory;
    }

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.rawMaterialWarehouseInventory-list');

        $this->warehouses = $this->api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];

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

        // Read the live inventory rows for the selected warehouse from the parent API
        $this->warehouseUnits = collect($this->inventory->forWarehouse($this->warehouse_id))
            ->map(function ($row) {
                return (object) [
                    'warehouse_id'         => $row['warehouse_id'],
                    'item_id'              => $row['item_id'],
                    'item_unit_id'         => $row['item_unit_id'],
                    'item'                 => $this->itemName($row['item_id']),
                    'unit'                 => $this->unitName($row['item_id'], $row['item_unit_id']),
                    'quantity'             => $row['quantity'],
                    'quantity_pending_in'  => $row['quantity_pending_in'],
                    'quantity_pending_out' => $row['quantity_pending_out'],
                    'quantity_in_process'  => $row['quantity_in_process'] ?? 0,
                ];
            })
            ->values();
    }

    #[On('viewUnitActivity')]
    public function viewUnitActivity($warehouseId, $itemId, $itemUnitId): void
    {
        // Every re-open starts unfiltered (all buckets shown)
        $this->activityFilter = 'all';

        $this->selectedWarehouse = (object) [
            'id'   => $warehouseId,
            'name' => $this->warehouseMap[$warehouseId] ?? 'Unknown',
        ];

        // Live on-hand / pending figures for this item-unit in this warehouse
        $inventory = $this->inventory->find($warehouseId, $itemId, $itemUnitId);

        $this->selectedInventory = (object) [
            'item'                 => $this->itemName($itemId),
            'unit'                 => $this->unitName($itemId, $itemUnitId),
            'quantity'             => $inventory['quantity'] ?? 0,
            'quantity_pending_in'  => $inventory['quantity_pending_in'] ?? 0,
            'quantity_pending_out' => $inventory['quantity_pending_out'] ?? 0,
            'quantity_in_process'  => $inventory['quantity_in_process'] ?? 0,
        ];

        // Every stock movement that touches this item-unit in this warehouse,
        // regardless of status — pending and completed alike. Stock documents
        // and production-event movements are merged into one timeline.
        $reportItems = ReportItem::where('item_id', $itemId)
            ->where('item_unit_id', $itemUnitId)
            ->where(function ($query) use ($warehouseId) {
                $query->whereHas('stockIn', fn ($q) => $q->where('warehouse_id', $warehouseId))
                    ->orWhereHas('stockOut', fn ($q) => $q->where('warehouse_id', $warehouseId))
                    ->orWhereHas('waste', fn ($q) => $q->where('warehouse_id', $warehouseId))
                    ->orWhereHas('transfer', function ($q) use ($warehouseId) {
                        $q->where('warehouse_from_id', $warehouseId)
                          ->orWhere('warehouse_to_id', $warehouseId);
                    });
            })
            ->with(['stockIn', 'stockOut', 'waste', 'transfer'])
            ->get();

        // Event / emergency-event movements for this item-unit in this warehouse.
        $eventQuantities = EventQuantity::with('event')
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('item_unit_id', $itemUnitId)
            ->get();

        // Build a single, chronologically-sorted movement list. Each entry
        // carries the display meta and the date it took effect (the confirm
        // time for events; the record time for stock documents).
        $movements = [];

        foreach ($reportItems as $item) {
            $meta = $this->movementMeta($item, (int) $warehouseId);

            if ($meta !== null) {
                $movements[] = [
                    'date'         => $item->created_at,
                    'meta'         => $meta,
                    'item_id'      => $item->item_id,
                    'item_unit_id' => $item->item_unit_id,
                ];
            }
        }

        foreach ($eventQuantities as $eq) {
            $meta = $this->eventMovementMeta($eq);

            if ($meta !== null) {
                $movements[] = [
                    'date'         => $eq->confirmed_at ?? $eq->created_at,
                    'meta'         => $meta,
                    'item_id'      => $eq->item_id,
                    'item_unit_id' => $eq->item_unit_id,
                ];
            }
        }

        usort($movements, fn ($a, $b) => (optional($a['date'])->timestamp ?? 0) <=> (optional($b['date'])->timestamp ?? 0));

        // Flatten to plain, serialisable rows so the filter round-trips keep the
        // computed fields (bucket, view link, running total) intact across requests.
        $running = 0;
        $rows    = [];

        foreach ($movements as $movement) {
            $meta = $movement['meta'];

            $running += $meta['delta'];
            $onHand   = $meta['bucket'] === 'on_hand';

            $rows[] = [
                'reference_id'    => $meta['reference_id'],
                'type_label'      => $meta['type_label'],
                'badge_class'     => $meta['badge_class'],
                'view_url'        => $meta['view_url'],
                'view_permission' => $meta['view_permission'],
                'status'          => $meta['status'],
                'bucket'          => $meta['bucket'],
                'item_name'       => $this->itemName($movement['item_id']),
                'unit_name'       => $this->unitName($movement['item_id'], $movement['item_unit_id']),
                'pending_in'      => $meta['bucket'] === 'pending_in'  ? $meta['qty'] : null,
                'pending_out'     => $meta['bucket'] === 'pending_out' ? $meta['qty'] : null,
                'on_hand'         => $onHand                           ? $meta['qty'] : null,
                'in_process'      => $meta['bucket'] === 'in_process'  ? $meta['qty'] : null,
                'stock_total'     => $onHand ? $running : null,
                'date'            => optional($movement['date'])->format('Y-m-d'),
            ];
        }

        $this->inventoryItems = $rows;

        $this->dispatch('openDetailsModal');
        $this->getData();
    }

    /**
     * Classify an event / emergency-event quantity as an inventory movement:
     * consumed items are "in process" while the event runs and become an
     * on-hand deduction once confirmed out; produced items and side products
     * are added straight into stock. Returns null for zero quantities.
     */
    protected function eventMovementMeta(EventQuantity $eq): ?array
    {
        $qty = (float) $eq->actual_quantity;

        if ($qty <= 0) {
            return null;
        }

        $confirmed = $eq->confirmed_at !== null;
        $planId    = $eq->event?->plan_id;
        $viewUrl   = $planId ? route('plans.view', ['id' => $planId]) : '#';

        if (in_array($eq->source, ['input', 'emergency'], true)) {
            $isEmergency = $eq->source === 'emergency';

            return [
                'type_label'      => $isEmergency ? 'Emergency Use' : 'Event Use',
                'badge_class'     => $isEmergency ? 'bg-danger' : 'bg-primary',
                'reference_id'    => $eq->event_id,
                'view_url'        => $viewUrl,
                'view_permission' => 'production.event-create',
                'status'          => $confirmed ? 'consumed' : 'in process',
                'qty'             => $qty,
                'bucket'          => $confirmed ? 'on_hand' : 'in_process',
                'delta'           => $confirmed ? -$qty : 0,
            ];
        }

        // Produced item or side product — added into stock when the event ends.
        return [
            'type_label'      => $eq->source === 'side_product' ? 'Event Side Product' : 'Event Output',
            'badge_class'     => 'bg-primary',
            'reference_id'    => $eq->event_id,
            'view_url'        => $viewUrl,
            'view_permission' => 'production.event-create',
            'status'          => $confirmed ? 'produced' : 'in process',
            'qty'             => $qty,
            'bucket'          => $confirmed ? 'on_hand' : 'in_process',
            'delta'           => $confirmed ? $qty : 0,
        ];
    }

    /** Toggle the activity bucket filter; clicking the active bucket clears it. */
    public function setActivityFilter(string $bucket): void
    {
        $this->activityFilter = $this->activityFilter === $bucket ? 'all' : $bucket;
    }

    /**
     * Classify a report-item movement for this warehouse: the action it represents,
     * the parent record it links to, its status, and the inventory bucket (with the
     * on-hand delta) it belongs to. Returns null when the movement does not apply.
     */
    protected function movementMeta(ReportItem $item, int $warehouseId): ?array
    {
        if ($item->stock_in_id) {
            $status = $item->stockIn->status ?? 'pending';
            $qty    = (float) ($item->quantity ?? 0);
            $onHand = $status === 'approved';

            return [
                'type_label'      => 'Stock In',
                'badge_class'     => 'bg-success',
                'reference_id'    => $item->stock_in_id,
                'view_url'        => route('item-stock-ins.view', [$item->stock_in_id, 1]),
                'view_permission' => 'production.itemStockIn-view',
                'status'          => $status,
                'qty'             => $qty,
                'bucket'          => $onHand ? 'on_hand' : 'pending_in',
                'delta'           => $onHand ? $qty : 0,
            ];
        }

        if ($item->stock_out_id) {
            $status = $item->stockOut->status ?? 'pending';
            $qty    = (float) ($item->quantity ?? 0);
            $onHand = $status === 'approved';

            return [
                'type_label'      => 'Stock Out',
                'badge_class'     => 'bg-danger',
                'reference_id'    => $item->stock_out_id,
                'view_url'        => route('item-stock-outs.view', [$item->stock_out_id, 1]),
                'view_permission' => 'production.itemStockOut-view',
                'status'          => $status,
                'qty'             => $qty,
                'bucket'          => $onHand ? 'on_hand' : 'pending_out',
                'delta'           => $onHand ? -$qty : 0,
            ];
        }

        if ($item->waste_id) {
            $status = $item->waste->status ?? 'pending';
            $qty    = (float) ($item->quantity ?? 0);
            $onHand = $status === 'approved';

            return [
                'type_label'      => 'Waste',
                'badge_class'     => 'bg-warning text-dark',
                'reference_id'    => $item->waste_id,
                'view_url'        => route('item-wastes.view', [$item->waste_id, 1]),
                'view_permission' => 'production.itemWaste-view',
                'status'          => $status,
                'qty'             => $qty,
                'bucket'          => $onHand ? 'on_hand' : 'pending_out',
                'delta'           => $onHand ? -$qty : 0,
            ];
        }

        if ($item->transfer_id) {
            $transfer = $item->transfer;
            $status   = $transfer->status ?? 'pending';
            $isSource = $transfer && $transfer->warehouse_from_id == $warehouseId;

            if ($isSource) {
                // The source is deducted once the load is approved (loaded/approved).
                $qty    = (float) ($item->quantity ?? 0);
                $onHand = in_array($status, ['loaded', 'approved'], true);
                $bucket = $onHand ? 'on_hand' : 'pending_out';
                $delta  = $onHand ? -$qty : 0;
            } else {
                // The destination receives into stock only once fully approved.
                $onHand = $status === 'approved';
                $qty    = $onHand ? (float) ($item->received_quantity ?? 0) : (float) ($item->quantity ?? 0);
                $bucket = $onHand ? 'on_hand' : 'pending_in';
                $delta  = $onHand ? $qty : 0;
            }

            return [
                'type_label'      => 'Transfer',
                'badge_class'     => 'bg-info text-dark',
                'reference_id'    => $item->transfer_id,
                'view_url'        => route('item-transfers.view', [$item->transfer_id, 1]),
                'view_permission' => 'production.itemTransfer-view',
                'status'          => $status,
                'qty'             => $qty,
                'bucket'          => $bucket,
                'delta'           => $delta,
            ];
        }

        return null;
    }

    public function render()
    {
        // Apply the active bucket filter to the pre-computed activity rows.
        $activityRows = collect($this->inventoryItems);

        if ($this->activityFilter !== 'all') {
            $activityRows = $activityRows->where('bucket', $this->activityFilter)->values();
        }

        return view('livewire.warehouse-inventory.warehouse-inventory-index', [
            'activityRows' => $activityRows,
        ]);
    }
}
