<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Locked;

/**
 * Capacity pages (preparation, line, packaging) only offer the item types the
 * placeable can actually produce: those held by the warehouse it produces into
 * — its `fg_warehouse_id`, labelled "Semi Finished Goods Warehouse" on a
 * preparation and "Finished Goods Warehouse" on a line or packaging.
 *
 * Item types that already have capacities stay on offer even when the
 * warehouse no longer holds them, flagged, so saving never drops a capacity
 * the page didn't show.
 *
 * Expects the component to have $api (ApiService) and $itemTypes
 * ([['id', 'name', 'item_count'], …]).
 */
trait OffersOutputWarehouseItemTypes
{
    #[Locked]
    public ?int $outputWarehouseId = null;
    #[Locked]
    public ?string $outputWarehouseName = null;
    #[Locked]
    public string $outputWarehouseLabel = 'finished goods warehouse';
    #[Locked]
    public array $outputItemTypeIds = [];

    /**
     * Read the output warehouse's item types from the parent. False when it
     * couldn't be read — the page then can't tell which item types to show,
     * and refuses to save rather than guess.
     */
    protected function loadOutputWarehouse($placeable, string $label): bool
    {
        $this->outputWarehouseLabel = $label;
        $this->outputWarehouseId    = $placeable->destinationWarehouseId();
        $this->outputWarehouseName  = null;
        $this->outputItemTypeIds    = [];

        if (!$this->outputWarehouseId) {
            return true;
        }

        $response = $this->api->get("/v1/warehouses/{$this->outputWarehouseId}");

        if (empty($response['success']) || empty($response['data'])) {
            return false;
        }

        $this->outputWarehouseName = trim((string) ($response['data']['name'] ?? '')) ?: "Warehouse #{$this->outputWarehouseId}";

        // Stored as a JSON list of ids, as strings ('["4","5"]').
        $ids = $response['data']['items_type_id'] ?? [];
        $ids = is_string($ids) ? (json_decode($ids, true) ?: []) : (array) $ids;

        $this->outputItemTypeIds = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return true;
    }

    /**
     * Narrow $this->itemTypes to what the page offers: the output warehouse's
     * item types, plus any the placeable already has capacities under (each
     * marked `in_warehouse` either way).
     */
    protected function offerOutputWarehouseItemTypes(array $storedTypeIds): void
    {
        $stored = array_map('intval', $storedTypeIds);

        $this->itemTypes = collect($this->itemTypes)
            ->map(fn(array $type) => $type + ['in_warehouse' => in_array($type['id'], $this->outputItemTypeIds, true)])
            ->filter(fn(array $type) => $type['in_warehouse'] || in_array($type['id'], $stored, true))
            ->values()
            ->all();
    }
}
