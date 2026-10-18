<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Looks up the parent's (erp_auth) ids by name or code, so seeded rows never
 * depend on the ids one environment happened to assign — after a fresh parent
 * seed an id can belong to a different row altogether.
 *
 * A lookup that finds nothing, or more than one row, is recorded instead of
 * thrown: resolve every row first, then call failIfParentMissing() to report
 * all of them at once before anything is written.
 */
trait ResolvesParentRecords
{
    private array $parentMissing = [];

    private array $parentNames = [];

    protected function itemTypeId(string $name): ?int
    {
        return $this->parentIdByName('item_types', 'item type', $name);
    }

    protected function departmentId(string $name): ?int
    {
        return $this->parentIdByName('departments', 'department', $name);
    }

    protected function warehouseId(string $name): ?int
    {
        return $this->parentIdByName('warehouses', 'warehouse', $name);
    }

    /**
     * A placeable's routing, [item type name => warehouse name], as
     * [item type id => warehouse id] — what item_type_warehouses stores.
     */
    protected function warehouseRouting(array $byName): array
    {
        $routing = [];
        foreach ($byName as $itemType => $warehouse) {
            $routing[$this->itemTypeId($itemType)] = $this->warehouseId($warehouse);
        }

        return $routing;
    }

    /**
     * The parent's active item with this code, as ['id' => …, 'item_type_id' => …].
     */
    protected function parentItem(string $code): ?array
    {
        $item = DB::connection('auth_service')->table('items')
            ->whereNull('deleted_at')
            ->where('code', $code)
            ->first(['id', 'item_type_id']);

        if (!$item) {
            $this->parentMissing[] = "item {$code}";

            return null;
        }

        return ['id' => (int) $item->id, 'item_type_id' => (int) $item->item_type_id];
    }

    /**
     * Id of the unit with this name on the item with this code.
     */
    protected function parentUnitId(string $itemCode, string $unitName): ?int
    {
        $ids = DB::connection('auth_service')->table('item_units as u')
            ->join('items as i', 'i.id', '=', 'u.item_id')
            ->whereNull('u.deleted_at')
            ->whereNull('i.deleted_at')
            ->where('i.code', $itemCode)
            ->where('u.name', $unitName)
            ->pluck('u.id');

        if ($ids->count() !== 1) {
            $this->parentMissing[] = "unit \"{$unitName}\" of item {$itemCode}" . ($ids->count() ? " (matches {$ids->count()} units)" : '');

            return null;
        }

        return (int) $ids->first();
    }

    /**
     * Stop the seeder, naming everything that couldn't be resolved.
     */
    protected function failIfParentMissing(): void
    {
        if ($this->parentMissing) {
            throw new RuntimeException(class_basename(static::class) . ' — not found in the parent (erp_auth): '
                . implode('; ', array_unique($this->parentMissing)) . '. This seeder wrote nothing.');
        }
    }

    private function parentIdByName(string $table, string $label, string $name): ?int
    {
        $this->parentNames[$table] ??= DB::connection('auth_service')->table($table)
            ->whereNull('deleted_at')
            ->get(['id', 'name'])
            ->groupBy(fn ($row) => $this->normaliseName($row->name))
            ->map(fn ($rows) => $rows->pluck('id')->all())
            ->all();

        $ids = $this->parentNames[$table][$this->normaliseName($name)] ?? [];

        if (count($ids) !== 1) {
            $this->parentMissing[] = "{$label} \"{$name}\"" . ($ids ? ' (matches ' . count($ids) . ' rows)' : '');

            return null;
        }

        return (int) $ids[0];
    }

    /**
     * Parent names carry stray spaces ("Raw Material ", "Massa Tomate Factory
     * Warehouse" with two), so compare trimmed, single-spaced and case-blind.
     */
    private function normaliseName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }
}
