<?php

namespace Database\Seeders;

use App\Models\Capacity;
use App\Models\Line;
use App\Models\Packaging;
use App\Models\PackagingCapacity;
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
     * MassaTomateProductionRecipeSeeder's products by size, in "SKU's
     * TP.xlsx" order, rated in cans. The basic unit is the can or sachet
     * itself, except Super Chef 403000021's, which is KG — 70 g a can.
     */
    private const CANS_70G = [
        '403000001' => [1, 1, 4, 1],         // MASSA TOMATE MAMA TIA LATA 70 GR (50X70), 70g
        '403000005' => [5, 9, 4, 1],         // MASSA TOMATE MAMA TIA PACOTE 70 GR (sachet, 67 g), 70g
        '403000018' => [18, 35, 4, 1],       // MASSA TOMATE MAMA TIA LATA 70 GR (25X70), 70g
        '403000002' => [2, 3, 4, 1],         // MASSA TOMATE DOURADO LATA 70 GR, 70g
        '403000006' => [6, 11, 4, 1],        // MASSA TOMATE DOURADO PACOTE 70 GR (sachet, 67 g), 70g
        '403000021' => [121, 149, 4, 0.07],  // MASSA TOMATE SUPER CHEF LATA 50x70 GR, KG
        '403000101' => [258, 288, 4, 1],     // MASSA TOMATE ADAL 50X70 GR, 70g
    ];

    private const CANS_400G = [
        '403000003' => [3, 5, 4, 1],         // MASSA TOMATE MAMA TIA LATA ABRI FACIL 400 GR, 400g
        '403000007' => [7, 13, 4, 1],        // MASSA TOMATE MAMA TIA PACOTE 400 GR (sachet), 400g
        '403000004' => [4, 7, 4, 1],         // MASSA TOMATE DOURADO LATA ABRI FACIL 400 GR, 400g
        '403000008' => [8, 15, 4, 1],        // MASSA TOMATE DOURADO PACOTE 400 GR (sachet), 400g
        '403000019' => [256, 284, 4, 1],     // MASSA TOMATE YOKABEL LATA ABRI FACIL 24X400 GR, 400g
        '403000022' => [257, 286, 4, 1],     // MASSA TOMATE SUPER CHEF LATA ABRI FACIL 24X400 GR, 400g
        '403000102' => [259, 290, 4, 1],     // MASSA TOMATE ADAL LATA ABRI FACIL 24X400 GR, 400g
        '403900001' => [263, 300, 5, 1],     // Tomato Peelado Mamatia 24X400 GR, 400g
        '403900002' => [21, 41, 5, 1],       // TOMATE PELADO MAMATIA 400 GR (12X400), 400g
        '403900003' => [22, 43, 5, 1],       // TOMATE PELADO MAMATIA 400 GR (6X400), 400g
        '403900004' => [264, 302, 5, 1],     // Tomato Peelado Yokabel 24X400 GR, 400g
    ];

    /**
     * Run the database seeds. Runs after PreparationSeeder, LineSeeder and
     * PackagingSeeder.
     *
     * A preparation / line keeps one capacity per item, in its basic unit
     * (the unit is the one it was entered in); a packaging keeps one per item
     * unit, and gets its basic unit's here. Capacities are per hour. Every
     * placeable listed ends up with exactly these — matched on those keys and
     * updated in place, anything else on it removed, as saving its capacity
     * page does; placeables not listed are left alone.
     */
    public function run(): void
    {
        // "Equipment and capacity Massa tomate.xlsx" (sheet FAB MASSA TOMAT).
        // The can lines' rates moved to their stations on 2026-10-28
        // (StationSeeder): a line run takes its speed from its slowest
        // station. The packaging machines are the lines' Packing stations, and
        // PackagingSeeder no longer runs — so only the mixer keeps capacities.
        $capacities = array_merge(
            $this->rates(Preparation::class, 1, self::SFG_MASSA_TOMATE, 3500), // Mixing — 3.5 t/h
            // $this->rates(Line::class, 1, self::CANS_70G, 360 * 60),         // Filling & Seaming 70g — 360 cans/min
            // $this->rates(Line::class, 3, self::CANS_400G, 130 * 60),        // Filling & Seaming 400g — 130 cans/min
        );

        $packagingCapacities = [
            // ...$this->rates(Packaging::class, 3, self::CANS_70G, 600 * 60), // Packaging Machine 70g — 600 cans/min
            // ...$this->rates(Packaging::class, 4, self::CANS_400G, 200 * 60), // Packaging Machine 400g — 200 cans/min
        ];

        DB::transaction(function () use ($capacities, $packagingCapacities) {
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

            foreach (collect($packagingCapacities)->groupBy('id') as $packagingId => $rows) {
                PackagingCapacity::where('packaging_id', $packagingId)
                    ->whereNotIn('item_unit_id', $rows->pluck('item_unit_id'))
                    ->delete();

                foreach ($rows as $row) {
                    PackagingCapacity::updateOrCreate(
                        ['packaging_id' => $row['id'], 'item_unit_id' => $row['item_unit_id']],
                        ['item_type_id' => $row['item_type_id'], 'item_id' => $row['item_id'], 'capacity' => $row['capacity']]
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
