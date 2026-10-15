<?php

namespace App\Livewire\Waste;

use App\Models\Waste;
use App\Services\ApiService;
use App\Livewire\Concerns\PreventsDuplicateSubmission;
use App\Services\InventoryService;
use DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Utils\Constants;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class WasteCreate extends Component
{
    use AuthorizesRequests, PreventsDuplicateSubmission, WithFileUploads;

    #[Locked]
    public bool $editing  = false;
    #[Locked]
    public $id;
    #[Locked]
    public $viewStatus;
    public $waste;
    public $notes;
    public $warehouse_id;

    public $warehouses = [];
    public $allItems   = [];   // items for the selected warehouse from API
    public $wasteItems = [];   // row data
    public $rowUnits   = [];   // units per row keyed by index

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

        authorizeRequest(match (true) {
            $viewStatus == Constants::VIEW_STATUS => 'production.itemWaste-view',
            (bool) $id                            => 'production.itemWaste-edit',
            default                               => 'production.itemWaste-create',
        });

        $this->warehouses = $this->api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];

        if ($id) {
            $this->id      = $id;
            $this->editing = true;

            $waste              = Waste::with('reportItems')->findOrFail($id);

            // Once approved, its stock has moved — re-saving would reserve it all
            // over again. The index hides Edit; this covers the URL.
            abort_if(
                $viewStatus != Constants::VIEW_STATUS && $waste->status !== 'pending',
                403,
                'Only a pending Waste can be edited.'
            );
            $this->waste        = $waste;
            $this->warehouse_id = $waste->warehouse_id;
            $this->notes        = $waste->notes;

            $this->getWarehouseItems($this->warehouse_id, dispatch: false);

            $this->wasteItems = $waste->reportItems->map(fn($input) => [
                'id'           => $input->id,
                'item_id'      => $input->item_id,
                'item_unit_id' => $input->item_unit_id,
                'quantity'     => format_quantity($input->quantity),
            ])->toArray();

            // Pre-load units for each existing row
            foreach ($this->wasteItems as $index => $row) {
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

        // When the item has exactly one unit, select it automatically so the
        // user doesn't have to open a dropdown with a single choice.
        $autoUnitId = count($units) === 1 ? ($units[0]['id'] ?? null) : null;
        if ($autoUnitId !== null) {
            $this->wasteItems[$index]['item_unit_id'] = $autoUnitId;
        }

        if ($dispatch) {
            $this->dispatch('setItemUnits', $index, $units, $autoUnitId);
        }
    }

    public function addRow(): void
    {
        $this->wasteItems[] = [
            'id'           => null,
            'item_id'      => null,
            'item_unit_id' => null,
            'quantity'     => '',
        ];
        $this->rowUnits[] = [];
    }

    public function removeItem(int $index): void
    {
        if (count($this->wasteItems) <= 1) {
            $this->dispatch('swal:error', [
                'title' => 'Warning',
                'text'  => 'At least one item is required!',
            ]);
            return;
        }

        unset($this->wasteItems[$index], $this->rowUnits[$index]);
        $this->wasteItems = array_values($this->wasteItems);
        $this->rowUnits   = array_values($this->rowUnits);
    }

    /** Strip Cleave.js thousands separators (e.g. "1,000") from quantities before use. */
    protected function sanitizeQuantities(): void
    {
        foreach ($this->wasteItems as $index => $row) {
            $this->wasteItems[$index]['quantity'] = str_replace(',', '', (string) ($row['quantity'] ?? ''));
        }
    }

    public function submit()
    {
        // The view screen is gated by the -view permission only; it never saves.
        abort_if($this->viewStatus == Constants::VIEW_STATUS, 403);

        $this->sanitizeQuantities();

        $this->validate([
            'warehouse_id'              => 'required|integer',
            'wasteItems'                => 'required|array|min:1',
            'wasteItems.*.item_id'      => 'required|integer',
            'wasteItems.*.item_unit_id' => 'required|integer',
            'wasteItems.*.quantity'     => 'required|numeric|min:0.000001',
        ], [
            'wasteItems.required'                => 'Please add at least one item.',
            'wasteItems.min'                     => 'Please add at least one item.',
            'wasteItems.*.item_id.required'      => 'Item is required.',
            'wasteItems.*.item_unit_id.required' => 'Unit is required.',
            'wasteItems.*.quantity.required'     => 'Quantity is required.',
            'wasteItems.*.quantity.numeric'      => 'Quantity must be a number.',
            'wasteItems.*.quantity.min'          => 'Quantity must be greater than 0.',
        ]);

        DB::beginTransaction();
        try {
            if ($this->editing) {
                $waste = Waste::lockForUpdate()->findOrFail($this->id);

                if ($waste->status !== 'pending') {
                    throw new \RuntimeException('This Waste has already been approved and can no longer be edited.');
                }

                $waste->update([
                    'warehouse_id' => $this->warehouse_id,
                    'notes'        => $this->notes,
                ]);
            } else {
                // One form, one document: a repeated Submit (a click queued during
                // the first request, or one made while the list was loading) finds
                // the document the first one created.
                if ($this->alreadySubmitted(Waste::class)) {
                    DB::rollBack();

                    return to_route('item-wastes')->with('success', 'Waste created successfully!');
                }

                $waste = Waste::create([
                    'warehouse_id'   => $this->warehouse_id,
                    'notes'          => $this->notes,
                    'submission_key' => $this->submissionKey,
                ]);
            }

            $ops = [];

            // When editing, unwind the previous reservation before re-applying below
            if ($this->editing) {
                foreach ($waste->reportItems()->get() as $old) {
                    $ops[] = InventoryService::releaseOut(
                        $old->warehouse_id, $old->item_id, $old->item_unit_id, (float) $old->quantity
                    );
                }
            }

            $syncedIds = [];

            foreach ($this->wasteItems as $index => $row) {
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
                    $input = $waste->reportItems()->findOrFail($row['id']);
                    $input->update($data);
                } else {
                    $input = $waste->reportItems()->create($data);
                }

                // Reserve the outgoing quantity as pending until the Waste is approved
                $ops[] = InventoryService::reserveOut(
                    $this->warehouse_id, $item['id'], $unit['id'], (float) $row['quantity']
                );

                $syncedIds[] = $input->id;
            }

            if ($this->editing) {
                $waste->reportItems()->whereNotIn('id', $syncedIds)->delete();
            }

            // Push the reservation changes to the parent inventory (throws on failure)
            $this->inventory->applyOrFail($ops);

            DB::commit();

            return to_route('item-wastes')->with(
                'success',
                $this->editing ? 'Waste updated successfully!' : 'Waste created successfully!'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->isDuplicateSubmission($e)) {
                return to_route('item-wastes')->with('success', 'Waste created successfully!');
            }

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

        foreach ($this->wasteItems as $index => $row) {
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
            return view('livewire.waste.waste-view', [
                'warehouseName' => $this->warehouseName($this->warehouse_id),
                'rows'          => $this->resolvedRows(),
            ]);
        }

        return view('livewire.waste.waste-create', [
            'items'      => $this->allItems,
            'warehouses' => $this->warehouses,
            'rowUnits'   => $this->rowUnits,
        ]);
    }
}
