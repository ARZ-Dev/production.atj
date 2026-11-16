<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Preparation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PreparationSeeder extends Seeder
{
    /** Preparations of earlier drawings, soft-deleted by every run; ids not reused. */
    public const RETIRED = [12];   // Mixer ZM 500 9 — Naya Vertical's own, 2026-11-04 to 2026-11-16

    /**
     * Run the database seeds.
     *
     * Department, warehouse and item type ids are the parent's (erp_auth), as
     * a fresh auth-service seed assigns them — this app can't read the
     * parent's database on the server, so they're written out; keep them in
     * step with auth-service's seeders. Event types and production lines are
     * this app's own rows — runs after FactoryEventTypeSeeder and ProductionLineSeeder.
     *
     * Massa Tomate's come from "Equipment and capacity Massa tomate.xlsx"
     * (sheet FAB MASSA TOMAT): its Mixing row. Capacities are CapacitySeeder's.
     *
     * Bolacha's are the mixers of the factory's line drawings of 2026-11-16,
     * each under the production line it feeds — exactly the equipment sheet's
     * eight Laser ZM 500s and two ZM 1100s: two on Lines 01, 02 and 06, one on
     * Lines 03 and 04, the ZM 1100s on Line 05 — and the wafer line's dough and
     * cream mixers (Franz Haas; their tanks are stations only). Each is named
     * after its mixer station (StationSeeder). They mix from RM BOLACHA into
     * SFG Bolacha, and have no capacities (the sheet gives kg a batch, not a
     * rate), so a planned mix takes 20 minutes. A run's mixes go on its
     * production line's preparations in id order, each material its own where
     * the line has one spare — so the wafer's dough mixer (13) comes before
     * its cream mixer (14), as the batter's stage comes before the cream's.
     *
     * Changed from the 2026-11-04 drawings: Lines 03 and 04 have one mixer
     * each (ZM 500 6 moved to Line 04, ZM 500 7 and 8 to Line 06), Naya
     * Vertical's own ZM 500 9 (12) is gone — RETIRED, soft-deleted — and the
     * wafer preparations are its mixers rather than its tanks (same ids).
     * Those replaced "Bolacha P1" (id 2), which served every line.
     *
     * Refuses, before writing, while an event that hasn't ended sits on a
     * retired preparation or on one this would move off the event's
     * production line — it would drop off the board.
     */
    public function run(): void
    {
        $bolacha = [
            'department_id' => 5,                // Bolacha - Production
            'source_warehouse_ids' => [5],       // RM BOLACHA
            'fg_warehouse_id' => 7,              // SFG Bolacha
            'item_type_warehouses' => [],
            'event_type_ids' => [4],             // Preparation Bolacha
        ];

        $preparations = [
            [
                'id' => 1,
                'name' => 'Mixing',              // Allan (China), 1 line, 3.5 t/h
                'department_id' => 14,           // MT Production Department
                'source_warehouse_ids' => [2],   // P.S.A MASSA TOMATE
                'fg_warehouse_id' => 2,          // P.S.A MASSA TOMATE
                'item_type_warehouses' => [],    // item type id => one of source_warehouse_ids
                'event_type_ids' => [1, 2],      // Cleaning, Preparation Massa Tomate
                'production_line_ids' => [1],    // Cans Tomato Paste Line
            ],
            ['id' => 2, 'name' => 'Mixer ZM 500 1', 'production_line_ids' => [2]] + $bolacha,     // Line 01
            ['id' => 3, 'name' => 'Mixer ZM 500 2', 'production_line_ids' => [2]] + $bolacha,
            ['id' => 4, 'name' => 'Mixer ZM 500 3', 'production_line_ids' => [3]] + $bolacha,     // Line 02
            ['id' => 5, 'name' => 'Mixer ZM 500 4', 'production_line_ids' => [3]] + $bolacha,
            ['id' => 6, 'name' => 'Mixer ZM 500 5', 'production_line_ids' => [4]] + $bolacha,     // Line 03
            ['id' => 7, 'name' => 'Mixer ZM 500 6', 'production_line_ids' => [5]] + $bolacha,     // Line 04
            ['id' => 8, 'name' => 'Mixer ZM 500 7', 'production_line_ids' => [7]] + $bolacha,     // Line 06 (vertical)
            ['id' => 9, 'name' => 'Mixer ZM 500 8', 'production_line_ids' => [7]] + $bolacha,
            ['id' => 10, 'name' => 'Mixer ZM 1100 1', 'production_line_ids' => [6]] + $bolacha,   // Line 05
            ['id' => 11, 'name' => 'Mixer ZM 1100 2', 'production_line_ids' => [6]] + $bolacha,
            ['id' => 13, 'name' => 'Wafer Dough Mixer', 'production_line_ids' => [8]] + $bolacha, // Wafer — "Wafer Dough Tank" until 2026-11-16
            ['id' => 14, 'name' => 'Wafer Cream Mixer', 'production_line_ids' => [8]] + $bolacha, // "Wafer Cream Tank" until 2026-11-16
        ];

        $problems = $this->eventsInTheWay($preparations);

        if ($problems) {
            throw new RuntimeException('PreparationSeeder: ' . implode('; ', $problems)
                . '. End, unplace or delete them first.');
        }

        DB::transaction(function () use ($preparations) {
            foreach ($preparations as $row) {
                $preparation = Preparation::withTrashed()->updateOrCreate(['id' => $row['id']], [
                    'name' => $row['name'],
                    'department_id' => $row['department_id'],
                    // The scalar column is the default source: always the list's first entry.
                    'rm_warehouse_id' => $row['source_warehouse_ids'][0] ?? null,
                    'rm_warehouse_ids' => $row['source_warehouse_ids'],
                    'fg_warehouse_id' => $row['fg_warehouse_id'],
                    'item_type_warehouses' => $row['item_type_warehouses'],
                    'deleted_at' => null,
                ]);
                $preparation->eventTypes()->sync($row['event_type_ids']);
                $preparation->productionLines()->sync($row['production_line_ids']);
            }

            // Soft-deleted, pivots kept for history (nothing to do on a fresh database).
            Preparation::whereIn('id', self::RETIRED)->delete();
        });
    }

    /**
     * Events that haven't ended on a retired preparation, or on one whose new
     * production lines leave out the event's.
     *
     * @param  array<int, array{id: int, name: string, production_line_ids: int[]}>  $preparations
     * @return string[]
     */
    private function eventsInTheWay(array $preparations): array
    {
        $productionLines = collect($preparations)->mapWithKeys(fn($row) => [$row['id'] => $row['production_line_ids']]);

        return Event::notEnded()
            ->where('placeable_type', Preparation::class)
            ->whereIn('placeable_id', $productionLines->keys()->merge(self::RETIRED)->all())
            ->get(['id', 'placeable_id', 'production_line_id'])
            ->filter(fn(Event $event) => in_array((int) $event->placeable_id, self::RETIRED, true)
                || !in_array((int) $event->production_line_id, $productionLines[(int) $event->placeable_id], true))
            ->groupBy('placeable_id')
            ->map(fn($events, $placeableId) => (Preparation::withTrashed()->find($placeableId)?->name ?? "preparation #{$placeableId}")
                . ' holds event' . ($events->count() === 1 ? ' #' : 's #') . $events->pluck('id')->implode(', #'))
            ->values()
            ->all();
    }
}
