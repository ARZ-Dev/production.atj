<?php

namespace App\Livewire\Concerns;

use App\Models\EventType;

/**
 * Builds the "warehouse per item type" table shown under the event-type picker
 * in the preparation / line popups.
 *
 * The table is grouped by event type, listing that event type's own item types
 * (`event_types.item_type_ids`). Each gets a warehouse: items of that type are
 * consumed from it instead of the placeable's default source warehouse.
 *
 * The saved map itself is keyed by item type only — the grouping is for
 * readability, an item type always resolves to the same warehouse. An item type
 * appearing under two event types shows twice, bound to the same value.
 */
trait BuildsItemTypeRouting
{
    /**
     * [['event_type_id', 'event_type_name', 'color', 'rows' => [
     *     ['item_type_id', 'item_type_name'],
     * ]]]
     */
    public array $itemTypeRoutingGroups = [];

    /** [itemTypeId => warehouseId] */
    public array $itemTypeWarehouses = [];

    /** Item type id => name, from the parent API (loaded once per request). */
    protected ?array $itemTypeNameMap = null;

    /**
     * Rebuild the table for the currently selected event types, keeping any
     * warehouse already chosen for an item type.
     */
    public function buildItemTypeRouting(array $eventTypeIds): void
    {
        $eventTypeIds = array_values(array_filter(array_map('intval', $eventTypeIds)));

        if (!$eventTypeIds) {
            $this->itemTypeRoutingGroups = [];

            return;
        }

        $groups = [];

        foreach (EventType::whereIn('id', $eventTypeIds)->orderBy('name')->get() as $type) {
            $rows = [];

            foreach (array_unique(array_map('intval', (array) ($type->item_type_ids ?? []))) as $itemTypeId) {
                $rows[] = [
                    'item_type_id'   => $itemTypeId,
                    'item_type_name' => $this->itemTypeName($itemTypeId),
                ];

                $this->itemTypeWarehouses[$itemTypeId] ??= null;
            }

            usort($rows, fn($a, $b) => strcmp($a['item_type_name'], $b['item_type_name']));

            $groups[] = [
                'event_type_id'   => $type->id,
                'event_type_name' => $type->name,
                'color'           => $type->color ?? '#818cf8',
                'rows'            => $rows,
            ];
        }

        $this->itemTypeRoutingGroups = $groups;
    }

    /**
     * Seed the map from a saved placeable, then build the table for its event
     * types.
     */
    public function loadItemTypeRouting(?array $saved, array $eventTypeIds): void
    {
        $this->itemTypeWarehouses = [];

        foreach ((array) $saved as $itemTypeId => $warehouseId) {
            $this->itemTypeWarehouses[(int) $itemTypeId] = (int) $warehouseId ?: null;
        }

        $this->buildItemTypeRouting($eventTypeIds);
    }

    /**
     * The map to persist: only item types currently in the table, and only
     * warehouses still selected on the placeable. Unrouted item types are left
     * out entirely so they fall back to the default warehouse.
     */
    public function itemTypeWarehousesForSave(array $sourceWarehouseIds): array
    {
        $sourceWarehouseIds = array_map('intval', $sourceWarehouseIds);

        $map = [];

        foreach ($this->itemTypeRoutingGroups as $group) {
            foreach ($group['rows'] as $row) {
                $itemTypeId  = (int) $row['item_type_id'];
                $warehouseId = (int) ($this->itemTypeWarehouses[$itemTypeId] ?? 0);

                if ($warehouseId && in_array($warehouseId, $sourceWarehouseIds, true)) {
                    $map[$itemTypeId] = $warehouseId;
                }
            }
        }

        return $map;
    }

    protected function itemTypeName(int $itemTypeId): string
    {
        if ($this->itemTypeNameMap === null) {
            $this->itemTypeNameMap = collect($this->api->get('/v1/item-types')['data'] ?? [])
                ->pluck('name', 'id')
                ->all();
        }

        return $this->itemTypeNameMap[$itemTypeId] ?? "Item Type #{$itemTypeId}";
    }
}
