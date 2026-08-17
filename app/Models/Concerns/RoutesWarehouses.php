<?php

namespace App\Models\Concerns;

/**
 * Shared warehouse routing for the placeables events run on (Preparation,
 * Line).
 *
 * A placeable holds a list of source warehouses (raw material for a
 * preparation, semi-finished goods for a line) and one destination warehouse
 * (finished goods). Consumed items come from the first of the source list,
 * unless the item type has been routed to a specific warehouse in
 * `item_type_warehouses`:
 *
 *     { "<itemTypeId>": <warehouseId> }
 *
 * The map is keyed by item type only — the popup groups the rows by event type
 * for readability, but an item type always comes from the same place.
 */
trait RoutesWarehouses
{
    /**
     * The scalar column holding the default source warehouse:
     * `rm_warehouse_id` for preparations, `sfg_warehouse_id` for lines.
     */
    abstract public function sourceWarehouseColumn(): string;

    /** The column holding the source warehouse list. */
    public function sourceWarehouseListColumn(): string
    {
        return $this->sourceWarehouseColumn() . 's';
    }

    /** Every warehouse items may be consumed from. */
    public function sourceWarehouseIds(): array
    {
        $listColumn   = $this->sourceWarehouseListColumn();
        $scalarColumn = $this->sourceWarehouseColumn();

        $ids = collect((array) ($this->{$listColumn} ?? []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Rows written before the list existed only have the scalar column.
        if (!$ids && $this->{$scalarColumn}) {
            $ids = [(int) $this->{$scalarColumn}];
        }

        return $ids;
    }

    /** The default (first) source warehouse. */
    public function defaultSourceWarehouseId(): ?int
    {
        return $this->sourceWarehouseIds()[0] ?? null;
    }

    /**
     * Where items of this type are consumed from — the item type's routed
     * warehouse, or the default source warehouse.
     *
     * A routed warehouse is only honoured while it is still one of the
     * placeable's selected warehouses; dropping a warehouse from the list
     * falls the item type back to the default rather than pointing at a store
     * that is no longer configured.
     */
    public function sourceWarehouseFor(?int $itemTypeId): ?int
    {
        $routed = $itemTypeId ? ($this->itemTypeWarehouseMap()[$itemTypeId] ?? null) : null;

        return $routed && in_array($routed, $this->sourceWarehouseIds(), true)
            ? $routed
            : $this->defaultSourceWarehouseId();
    }

    /** Where produced items and side products are delivered into. */
    public function destinationWarehouseId(): ?int
    {
        return $this->fg_warehouse_id ? (int) $this->fg_warehouse_id : null;
    }

    /** The routing map, normalised to `[itemTypeId => warehouseId]`. */
    public function itemTypeWarehouseMap(): array
    {
        $map = [];

        foreach ((array) ($this->item_type_warehouses ?? []) as $itemTypeId => $warehouseId) {
            if ((int) $warehouseId) {
                $map[(int) $itemTypeId] = (int) $warehouseId;
            }
        }

        return $map;
    }
}
