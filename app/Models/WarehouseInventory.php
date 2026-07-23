<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * Get (or create) the inventory row for a warehouse/item/unit combination.
     */
    public static function ensureRow($warehouseId, $itemId, $unitId): self
    {
        return static::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'item_id'      => $itemId,
                'item_unit_id' => $unitId,
            ],
            [
                'quantity'             => 0,
                'quantity_pending_in'  => 0,
                'quantity_pending_out' => 0,
            ]
        );
    }

    /**
     * Actual (confirmed) quantity on hand for a warehouse/item/unit.
     */
    public static function availableQuantity($warehouseId, $itemId, $unitId): float
    {
        return (float) (static::where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('item_unit_id', $unitId)
            ->value('quantity') ?? 0);
    }

    // ─── Reserve on create/edit ──────────────────────────────────────────────

    /** Reserve an incoming quantity (Stock In / Transfer destination). */
    public static function addPendingIn($warehouseId, $itemId, $unitId, $qty): void
    {
        static::ensureRow($warehouseId, $itemId, $unitId)->increment('quantity_pending_in', (float) $qty);
    }

    /** Reserve an outgoing quantity (Stock Out / Waste / Transfer source). */
    public static function addPendingOut($warehouseId, $itemId, $unitId, $qty): void
    {
        static::ensureRow($warehouseId, $itemId, $unitId)->increment('quantity_pending_out', (float) $qty);
    }

    /** Release a previously reserved incoming quantity (edit / delete). */
    public static function releasePendingIn($warehouseId, $itemId, $unitId, $qty): void
    {
        static::ensureRow($warehouseId, $itemId, $unitId)->decrement('quantity_pending_in', (float) $qty);
    }

    /** Release a previously reserved outgoing quantity (edit / delete). */
    public static function releasePendingOut($warehouseId, $itemId, $unitId, $qty): void
    {
        static::ensureRow($warehouseId, $itemId, $unitId)->decrement('quantity_pending_out', (float) $qty);
    }

    // ─── Confirm (pending becomes actual) ────────────────────────────────────

    /**
     * Confirm an incoming movement: release the reservation and add to stock.
     * $actualQty defaults to $pendingQty (used by Transfer receive where the
     * received quantity may differ from the reserved/loaded quantity).
     */
    public static function confirmIn($warehouseId, $itemId, $unitId, $pendingQty, $actualQty = null): void
    {
        $row = static::ensureRow($warehouseId, $itemId, $unitId);
        $row->decrement('quantity_pending_in', (float) $pendingQty);
        $row->increment('quantity', (float) ($actualQty ?? $pendingQty));
    }

    /**
     * Confirm an outgoing movement: release the reservation and deduct stock.
     */
    public static function confirmOut($warehouseId, $itemId, $unitId, $qty): void
    {
        $row = static::ensureRow($warehouseId, $itemId, $unitId);
        $row->decrement('quantity_pending_out', (float) $qty);
        $row->decrement('quantity', (float) $qty);
    }
}
