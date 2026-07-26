<?php

namespace App\Services;

/**
 * Talks to the parent project's warehouse-inventory API. The inventory itself
 * lives in the parent DB; this project only reads it and pushes operations.
 */
class InventoryService
{
    public function __construct(private ApiService $api)
    {
    }

    // ─── Reads ───────────────────────────────────────────────

    /** All live inventory rows for a warehouse. */
    public function forWarehouse($warehouseId): array
    {
        return $this->api->get('/v1/warehouse-inventories', [
            'warehouse_id' => $warehouseId,
        ])['data'] ?? [];
    }

    /** A single inventory row (quantity + pendings); zeroed structure when none. */
    public function find($warehouseId, $itemId, $itemUnitId): array
    {
        return $this->api->get('/v1/warehouse-inventories/find', [
            'warehouse_id' => $warehouseId,
            'item_id'      => $itemId,
            'item_unit_id' => $itemUnitId,
        ])['data'] ?? [];
    }

    // ─── Writes ──────────────────────────────────────────────

    /** Apply a batch of operations atomically. Returns the raw API response. */
    public function apply(array $operations): array
    {
        return $this->api->post('/v1/warehouse-inventories/apply', [
            'operations' => array_values($operations),
        ]);
    }

    /**
     * Apply a batch and throw on failure (e.g. insufficient stock) so the caller's
     * surrounding try/catch can roll back its local transaction and surface the message.
     */
    public function applyOrFail(array $operations): void
    {
        if (empty($operations)) {
            return;
        }

        $response = $this->apply($operations);

        if (!($response['success'] ?? false)) {
            throw new \RuntimeException($response['message'] ?? 'Inventory update failed.');
        }
    }

    // ─── Operation builders ──────────────────────────────────

    public static function reserveIn($warehouseId, $itemId, $itemUnitId, $quantity): array
    {
        return self::line('reserve_in', $warehouseId, $itemId, $itemUnitId, ['quantity' => (float) $quantity]);
    }

    public static function reserveOut($warehouseId, $itemId, $itemUnitId, $quantity): array
    {
        return self::line('reserve_out', $warehouseId, $itemId, $itemUnitId, ['quantity' => (float) $quantity]);
    }

    public static function releaseIn($warehouseId, $itemId, $itemUnitId, $quantity): array
    {
        return self::line('release_in', $warehouseId, $itemId, $itemUnitId, ['quantity' => (float) $quantity]);
    }

    public static function releaseOut($warehouseId, $itemId, $itemUnitId, $quantity): array
    {
        return self::line('release_out', $warehouseId, $itemId, $itemUnitId, ['quantity' => (float) $quantity]);
    }

    public static function confirmIn($warehouseId, $itemId, $itemUnitId, $pendingQuantity, $actualQuantity): array
    {
        return self::line('confirm_in', $warehouseId, $itemId, $itemUnitId, [
            'pending_quantity' => (float) $pendingQuantity,
            'actual_quantity'  => (float) $actualQuantity,
        ]);
    }

    public static function confirmOut($warehouseId, $itemId, $itemUnitId, $pendingQuantity, $actualQuantity): array
    {
        return self::line('confirm_out', $warehouseId, $itemId, $itemUnitId, [
            'pending_quantity' => (float) $pendingQuantity,
            'actual_quantity'  => (float) $actualQuantity,
        ]);
    }

    protected static function line(string $op, $warehouseId, $itemId, $itemUnitId, array $extra): array
    {
        return array_merge([
            'op'           => $op,
            'warehouse_id' => (int) $warehouseId,
            'item_id'      => (int) $itemId,
            'item_unit_id' => (int) $itemUnitId,
        ], $extra);
    }
}
