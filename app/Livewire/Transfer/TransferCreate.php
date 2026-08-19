<?php

namespace App\Livewire\Transfer;

use App\Models\Transfer;
use App\Services\ApiService;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class TransferCreate extends Component
{
    use AuthorizesRequests;

    public bool $editing       = false;
    public $id;
    public $viewStatus;
    public $confirmStatus      = 0;
    public $transfer;

    public $warehouse_from_id;
    public $warehouse_to_id;
    public $warehouses         = [];

    /** Internal transfer: both ends are internal warehouses. */
    public bool $is_internal   = false;
    public $department_id;
    public $departments        = [];

    /** Warehouses offered by each picker, narrowed by department + transfer kind. */
    public $warehousesFrom     = [];
    public $warehousesTo       = [];

    public $allItems           = [];   // items common to both warehouses' item types
    public $transferItems      = [];   // row data
    public $rowUnits           = [];   // units per row keyed by index

    protected ApiService $api;
    protected InventoryService $inventory;

    public function boot(ApiService $api, InventoryService $inventory): void
    {
        $this->api       = $api;
        $this->inventory = $inventory;
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
        } elseif ($viewStatus == 1) {
            $this->confirmStatus = 0;
            authorizeRequest('production.itemTransfer-view');
        } else {
            $this->confirmStatus = 0;
            authorizeRequest('production.itemTransfer-create');
        }

        $this->viewStatus = $viewStatus;

        $this->warehouses = $this->api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];

        $this->departments = $this->api->get('/v1/departments', [
            'module' => 'production',
            'filter' => 'production',
        ])['data'] ?? [];

        if ($id) {
            $this->id      = $id;
            $this->editing = true;

            $transfer = Transfer::with('reportItems')->findOrFail($id);
            $this->transfer = $transfer;

            if ($this->confirmStatus == 1 && $transfer->status !== 'pending') {
                session()->flash('error', 'This transfer has already been processed!');
                return redirect()->route('item-transfers');
            }

            if ($this->confirmStatus == 2 && $transfer->status !== 'loaded') {
                session()->flash('error', 'Transfer must be in loaded status to approve receive!');
                return redirect()->route('item-transfers');
            }

            $this->is_internal       = (bool) $transfer->is_internal;
            $this->department_id     = $transfer->department_id;
            $this->warehouse_from_id = $transfer->warehouse_from_id;
            $this->warehouse_to_id   = $transfer->warehouse_to_id;

            // Populate the pickers without pruning: an older transfer may point at a
            // warehouse the current filters no longer offer, and it must still render.
            $this->loadWarehouseOptions();

            $this->getTransferItems(dispatch: false);

            $this->transferItems = $transfer->reportItems->map(fn($reportItem) => [
                'id'                => $reportItem->id,
                'item_id'           => $reportItem->item_id,
                'item_unit_id'      => $reportItem->item_unit_id,
                'quantity'          => format_quantity($reportItem->quantity),
                'received_quantity' => $this->confirmStatus == 2
                    ? format_quantity($reportItem->received_quantity ?? $reportItem->quantity)
                    : null,
            ])->toArray();

            // Pre-load units for each existing row
            foreach ($this->transferItems as $index => $row) {
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

    /**
     * Fetch the warehouses one end of the transfer may use.
     *
     * Internal transfer  → both ends must be internal.
     * External transfer  → the source must be internal and allowed to send,
     *                      the destination external and allowed to receive.
     *
     * The filters are optional query strings on the shared warehouses endpoint,
     * so callers elsewhere that omit them are unaffected.
     */
    protected function fetchWarehouses(bool $forSource): array
    {
        if (!$this->department_id) {
            return [];
        }

        $query = [
            'module'        => 'production',
            'department_id' => $this->department_id,
        ];

        if ($this->is_internal) {
            $query['is_internal'] = 1;
        } elseif ($forSource) {
            $query['is_internal']       = 1;
            $query['can_send_transfer'] = 1;
        } else {
            $query['is_internal']          = 0;
            $query['can_receive_transfer'] = 1;
        }

        return $this->api->get('/v1/warehouses', $query)['data'] ?? [];
    }

    /** Refill both pickers for the current department + transfer kind. */
    protected function loadWarehouseOptions(): void
    {
        $this->warehousesFrom = $this->fetchWarehouses(forSource: true);

        // An internal transfer offers the same set on both ends — no second call needed.
        $this->warehousesTo = $this->is_internal
            ? $this->warehousesFrom
            : $this->fetchWarehouses(forSource: false);
    }

    public function updatedIsInternal(): void
    {
        $this->refreshWarehouseOptions();
    }

    public function updatedDepartmentId(): void
    {
        $this->refreshWarehouseOptions();
    }

    /**
     * Reload both pickers, drop any selection the new filters no longer offer,
     * and push the fresh option lists to the (wire:ignore'd) selectpickers.
     */
    protected function refreshWarehouseOptions(): void
    {
        $this->loadWarehouseOptions();

        if (!collect($this->warehousesFrom)->firstWhere('id', (int) $this->warehouse_from_id)) {
            $this->warehouse_from_id = null;
        }

        if (!collect($this->warehousesTo)->firstWhere('id', (int) $this->warehouse_to_id)) {
            $this->warehouse_to_id = null;
        }

        // The item list depends on both warehouses, so it has to follow.
        $this->getTransferItems(dispatch: false);

        $this->dispatch(
            'setTransferWarehouses',
            $this->warehousesFrom,
            $this->warehousesTo,
            $this->warehouse_from_id,
            $this->warehouse_to_id
        );
        $this->dispatch('setWarehouseItems', $this->allItems);
    }

    /**
     * Fetch the items that may be transferred between the two selected
     * warehouses. Items are limited to the item types held in common by both
     * the source and destination warehouses.
     */
    #[On('getTransferItems')]
    public function getTransferItems($warehouseFromId = null, $warehouseToId = null, $dispatch = true): void
    {
        $fromId = $warehouseFromId ?: $this->warehouse_from_id;
        $toId   = $warehouseToId ?: $this->warehouse_to_id;

        // Items can only be resolved once both warehouses are chosen
        if (!$fromId || !$toId) {
            $this->allItems = [];

            if ($dispatch) {
                $this->dispatch('setWarehouseItems', $this->allItems);
            }
            return;
        }

        // Item type IDs configured for the destination warehouse
        $toTypeIds = collect($this->api->get("/v1/warehouses/{$toId}/item-types")['data'] ?? [])
            ->pluck('id')
            ->all();

        // Items available in the source warehouse (already scoped to its own item
        // types), narrowed to the item types the destination warehouse also holds.
        $this->allItems = collect($this->api->get("/v1/warehouses/{$fromId}/items")['data'] ?? [])
            ->filter(fn($item) => in_array($item['item_type_id'] ?? null, $toTypeIds))
            ->values()
            ->all();

        if ($dispatch) {
            $this->dispatch('setWarehouseItems', $this->allItems);
        }
    }

    #[On('getItemUnits')]
    public function getItemUnits($itemId, $index, $dispatch = true): void
    {
        $units = $this->fetchUnitsForItem((int) $itemId);
        $this->rowUnits[$index] = $units;

        // When the item has exactly one unit, select it automatically so the
        // user doesn't have to open a dropdown with a single choice.
        $autoUnitId = count($units) === 1 ? ($units[0]['id'] ?? null) : null;
        if ($autoUnitId !== null) {
            $this->transferItems[$index]['item_unit_id'] = $autoUnitId;
        }

        if ($dispatch) {
            $this->dispatch('setItemUnits', $index, $units, $autoUnitId);
        }
    }

    public function addRow(): void
    {
        $this->transferItems[] = [
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
        if (count($this->transferItems) <= 1) {
            $this->dispatch('swal:error', [
                'title' => 'Warning',
                'text'  => 'At least one item is required!',
            ]);
            return;
        }

        unset($this->transferItems[$index], $this->rowUnits[$index]);
        $this->transferItems = array_values($this->transferItems);
        $this->rowUnits      = array_values($this->rowUnits);
    }

    protected function rules(): array
    {
        if ($this->confirmStatus == 2) {
            return [
                'transferItems.*.received_quantity' => 'required|numeric|min:0',
            ];
        }

        $rules = [
            'warehouse_from_id'            => 'required|integer',
            'warehouse_to_id'             => 'required|integer|different:warehouse_from_id',
            'transferItems'                => 'required|array|min:1',
            'transferItems.*.item_id'      => 'required|integer',
            'transferItems.*.item_unit_id' => 'required|integer',
            'transferItems.*.quantity'     => 'required|numeric|min:0.000001',
        ];

        // Only create/edit picks the department; the approve screens re-validate
        // transfers that may predate these columns, so don't require them there.
        if (!$this->confirmStatus) {
            $rules['department_id'] = 'required|integer';
            $rules['is_internal']   = 'boolean';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'department_id.required'                     => 'Department is required.',
            'warehouse_to_id.different'                  => 'Warehouse To must be different from Warehouse From.',
            'transferItems.required'                     => 'Please add at least one item.',
            'transferItems.min'                          => 'Please add at least one item.',
            'transferItems.*.item_id.required'           => 'Item is required.',
            'transferItems.*.item_unit_id.required'      => 'Unit is required.',
            'transferItems.*.quantity.required'          => 'Loaded quantity is required.',
            'transferItems.*.quantity.numeric'           => 'Loaded quantity must be a number.',
            'transferItems.*.quantity.min'               => 'Loaded quantity must be greater than 0.',
            'transferItems.*.received_quantity.required' => 'Received quantity is required.',
            'transferItems.*.received_quantity.numeric'  => 'Received quantity must be a number.',
            'transferItems.*.received_quantity.min'      => 'Received quantity must be 0 or greater.',
        ];
    }

    /** Strip Cleave.js thousands separators (e.g. "1,000") from quantities before use. */
    protected function sanitizeQuantities(): void
    {
        foreach ($this->transferItems as $index => $row) {
            $this->transferItems[$index]['quantity'] = str_replace(',', '', (string) ($row['quantity'] ?? ''));

            if (isset($this->transferItems[$index]['received_quantity'])) {
                $this->transferItems[$index]['received_quantity'] =
                    str_replace(',', '', (string) $this->transferItems[$index]['received_quantity']);
            }
        }
    }

    public function submit(): mixed
    {
        $this->sanitizeQuantities();

        $this->validate();

        DB::beginTransaction();
        try {
            $ops = [];

            if ($this->editing) {
                $transfer = Transfer::findOrFail($this->id);

                if ($transfer->status !== 'pending') {
                    DB::rollBack();
                    return to_route('item-transfers')->with('error', 'Cannot edit a transfer that has already been processed!');
                }

                // Unwind the previous reservation using the transfer's current (old) warehouses
                foreach ($transfer->reportItems as $old) {
                    $ops[] = InventoryService::releaseOut(
                        $transfer->warehouse_from_id, $old->item_id, $old->item_unit_id, (float) $old->quantity
                    );
                    $ops[] = InventoryService::releaseIn(
                        $transfer->warehouse_to_id, $old->item_id, $old->item_unit_id, (float) $old->quantity
                    );
                }

                $transfer->update([
                    'is_internal'       => $this->is_internal,
                    'department_id'     => $this->department_id,
                    'warehouse_from_id' => $this->warehouse_from_id,
                    'warehouse_to_id'   => $this->warehouse_to_id,
                ]);
            } else {
                $transfer = Transfer::create([
                    'is_internal'       => $this->is_internal,
                    'department_id'     => $this->department_id,
                    'warehouse_from_id' => $this->warehouse_from_id,
                    'warehouse_to_id'   => $this->warehouse_to_id,
                    'status'            => 'pending',
                ]);
            }

            $syncedIds = [];

            foreach ($this->transferItems as $index => $row) {
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

                // Reserve out of the source and into the destination until confirmed
                $ops[] = InventoryService::reserveOut(
                    $this->warehouse_from_id, $item['id'], $unit['id'], (float) $row['quantity']
                );
                $ops[] = InventoryService::reserveIn(
                    $this->warehouse_to_id, $item['id'], $unit['id'], (float) $row['quantity']
                );

                $syncedIds[] = $input->id;
            }

            if ($this->editing) {
                $transfer->reportItems()->whereNotIn('id', $syncedIds)->delete();
            }

            // Push the reservation changes to the parent inventory (throws on failure)
            $this->inventory->applyOrFail($ops);

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

            // Stay on the page so the error shows; the ": mixed" return type
            // requires an explicit return on this path.
            return null;
        }
    }

    public function confirmLoad(): mixed
    {
        $this->sanitizeQuantities();

        $this->validate();

        $transfer = Transfer::with('reportItems')->findOrFail($this->id);

        DB::beginTransaction();
        try {
            $ops = [];

            foreach ($this->transferItems as $index => $row) {
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

                // Release the source reservation into an actual deduction. The parent
                // validates availability and rejects the batch if the source is short.
                $ops[] = InventoryService::confirmOut(
                    $this->warehouse_from_id, $item['id'], $unit['id'], (float) $row['quantity'], (float) $row['quantity']
                );
            }

            $this->inventory->applyOrFail($ops);

            $transfer->update(['status' => 'loaded']);

            DB::commit();

            return to_route('item-transfers')->with('success', 'Transfer load approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);

            // Stay on the page so the error shows; the ": mixed" return type
            // requires an explicit return on this path.
            return null;
        }
    }

    public function confirmReceive(): mixed
    {
        $this->sanitizeQuantities();

        $this->validate();

        $transfer = Transfer::with('reportItems')->findOrFail($this->id);

        DB::beginTransaction();
        try {
            $ops = [];

            foreach ($this->transferItems as $row) {
                $receivedQty = (float) $row['received_quantity'];
                $loadedQty   = (float) $row['quantity'];

                // Move the destination reservation into actual stock.
                // The source was already deducted at load, so only the destination changes here;
                // the received quantity may differ from the loaded (reserved) quantity.
                $ops[] = InventoryService::confirmIn(
                    $this->warehouse_to_id, (int) $row['item_id'], (int) $row['item_unit_id'], $loadedQty, $receivedQty
                );

                // Save received_quantity on the report item
                $transfer->reportItems()
                    ->where('id', $row['id'])
                    ->update(['received_quantity' => $receivedQty]);
            }

            $this->inventory->applyOrFail($ops);

            $transfer->update(['status' => 'approved']);

            DB::commit();

            return to_route('item-transfers')->with('success', 'Transfer receive approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);

            // Stay on the page so the error shows; the ": mixed" return type
            // requires an explicit return on this path.
            return null;
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
        // Received quantities live on the persisted report items (not the form rows)
        $received = $this->transfer
            ? $this->transfer->reportItems->pluck('received_quantity', 'id')
            : collect();

        $rows = [];

        foreach ($this->transferItems as $index => $row) {
            $item = collect($this->allItems)->firstWhere('id', (int) $row['item_id']);
            $unit = collect($this->rowUnits[$index] ?? [])->firstWhere('id', (int) $row['item_unit_id']);

            $rows[] = [
                'item'     => $item ? trim(($item['name'] ?? '') . ' (' . ($item['code'] ?? '') . ')') : '—',
                'unit'     => $unit ? trim(($unit['name'] ?? '') . ' (' . ($unit['symbol'] ?? '') . ')') : '—',
                'quantity' => $row['quantity'],
                'received' => $received[$row['id']] ?? null,
            ];
        }

        return $rows;
    }

    protected function departmentName($departmentId): string
    {
        return collect($this->departments)->firstWhere('id', (int) $departmentId)['name'] ?? '—';
    }

    public function render()
    {
        if ($this->viewStatus == 1) {
            return view('livewire.transfer.transfer-view', [
                'warehouseFromName' => $this->warehouseName($this->warehouse_from_id),
                'warehouseToName'   => $this->warehouseName($this->warehouse_to_id),
                'departmentName'    => $this->departmentName($this->department_id),
                'rows'              => $this->resolvedRows(),
            ]);
        }

        // The approve screens are read-only and may show a transfer whose warehouses
        // the current filters no longer offer, so fall back to the full list there.
        $fallback = $this->confirmStatus ? $this->warehouses : [];

        return view('livewire.transfer.transfer-create', [
            'items'          => $this->allItems,
            'warehouses'     => $this->warehouses,
            'warehousesFrom' => $this->warehousesFrom ?: $fallback,
            'warehousesTo'   => $this->warehousesTo ?: $fallback,
            'rowUnits'       => $this->rowUnits,
        ]);
    }
}
