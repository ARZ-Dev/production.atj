<?php

namespace Database\Seeders;

use App\Models\Capacity;
use App\Models\Preparation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Item type, item and unit ids are the parent's (erp_auth), as a fresh
 * auth-service seed assigns them — this app can't read the parent's database
 * on the server, so they're written out; keep them in step with
 * auth-service's item seeders. Each list is code => [item id, basic unit id,
 * item type id (the item's own), basic units per unit of the machine's rate].
 */
class CapacitySeeder extends Seeder
{
    /** What Mixing makes — every SFG Massa Tomate item, rated in KG, its basic unit. */
    private const SFG_MASSA_TOMATE = [
        '402000003' => [119, 147, 12, 1], // Tomato Paste Bulk Brix 22-24
        'MTTP'      => [120, 148, 12, 1], // Mama Tia Tomato Paste
        'SFGMT-01'  => [122, 150, 12, 1], // Mamatia
        'SFGMT-02'  => [123, 151, 12, 1], // Dourado
        'SFGMT-03'  => [124, 152, 12, 1], // Yokabel
        'SFGMT-04'  => [125, 153, 12, 1], // Super Chef
        'SFGMT-05'  => [126, 154, 12, 1], // Adal
        'SFGMT-06'  => [127, 155, 12, 1], // Cereal
        'SFGMT-07'  => [128, 156, 12, 1], // Apple
        'SFGMT-08'  => [129, 157, 12, 1], // Kup Dourado
        'SFGMT-09'  => [130, 158, 12, 1], // Sauce of Peeled Tomato
    ];

    /**
     * Run the database seeds. Runs after PreparationSeeder.
     *
     * Only preparations have capacities: a line's speed comes from its
     * stations (StationSeeder) and packagings are gone (2026-10-29). A
     * preparation keeps one capacity per item, in its basic unit (the unit is
     * the one it was entered in), per hour. Every preparation listed ends up
     * with exactly these — matched on those keys and updated in place,
     * anything else on it removed, as saving its capacity page does;
     * preparations not listed are left alone.
     */
    public function run(): void
    {
        // "Equipment and capacity Massa tomate.xlsx" (sheet FAB MASSA TOMAT).
        $capacities = $this->rates(Preparation::class, 1, self::SFG_MASSA_TOMATE, 3500); // Mixing — 3.5 t/h

        DB::transaction(function () use ($capacities) {
            foreach (collect($capacities)->groupBy(fn($row) => "{$row['model']}#{$row['id']}") as $rows) {
                Capacity::where('capacityable_type', $rows[0]['model'])
                    ->where('capacityable_id', $rows[0]['id'])
                    ->whereNotIn('item_id', $rows->pluck('item_id'))
                    ->delete();

                foreach ($rows as $row) {
                    Capacity::updateOrCreate(
                        ['capacityable_type' => $row['model'], 'capacityable_id' => $row['id'], 'item_id' => $row['item_id']],
                        ['item_type_id' => $row['item_type_id'], 'item_unit_id' => $row['item_unit_id'], 'capacity' => $row['capacity']]
                    );
                }
            }
        });
    }

    /**
     * A machine's rate per hour on each of its items, converted to the
     * item's basic unit.
     */
    private function rates(string $model, int $id, array $items, float $perHour): array
    {
        return array_map(fn(array $item) => [
            'model' => $model,
            'id' => $id,
            'item_id' => $item[0],
            'item_unit_id' => $item[1],
            'item_type_id' => $item[2],
            'capacity' => round($perHour * $item[3], 2),
        ], array_values($items));
    }
}
