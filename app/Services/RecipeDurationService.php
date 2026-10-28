<?php

namespace App\Services;

use App\Models\Capacity;
use App\Models\Event;
use App\Models\Line;
use App\Models\Packaging;
use App\Models\PackagingCapacity;
use App\Models\Recipe;

class RecipeDurationService
{
    protected ApiService $api;
    protected LineRunService $lineRuns;

    public function __construct(ApiService $api, LineRunService $lineRuns)
    {
        $this->api      = $api;
        $this->lineRuns = $lineRuns;
    }

    /**
     * Duration (in minutes) to produce $event's batch_count on the given
     * line/preparation, based on the recipe's quantity_per_batch and the
     * placeable's capacity per hour for that item.
     */
    public function compute(Event $event, string $placeableClass, int $placeableId): ?string
    {
        // A line run takes as long as its slowest station needs.
        if ($placeableClass === Line::class && ($recipe = $this->stagedRecipe($event))) {
            $line = Line::with('stations.type')->find($placeableId);

            return $line ? $this->minutes($this->lineRuns->durationMinutes($recipe, $line, $this->batches($event))) : null;
        }

        if ($placeableClass === Packaging::class) {
            return $this->computeForPackaging($event, $placeableId);
        }

        return $this->computeUsing($event, Capacity::where('capacityable_type', $placeableClass)
            ->where('capacityable_id', $placeableId)
            ->where('item_id', $event->item_id));
    }

    /**
     * Same calculation, but before the event has been placed on a specific
     * line/preparation — used to preview duration as soon as recipe, item
     * and batch are picked. Takes the first capacity defined for that item
     * on any line/preparation as an estimate; this gets recalculated against
     * the actual line once the event is dropped on the calendar.
     */
    public function computeEstimate(Event $event): ?string
    {
        if ($recipe = $this->stagedRecipe($event)) {
            return $this->minutes($this->lineRuns->estimateMinutes($recipe, $event->event_type_id ? (int) $event->event_type_id : null, $this->batches($event)));
        }

        return $this->computeUsing($event, Capacity::where('item_id', $event->item_id));
    }

    /** The event's recipe when it has stages (a line run), else null. */
    private function stagedRecipe(Event $event): ?Recipe
    {
        $recipe = $event->recipe_id ? Recipe::with('stages')->find($event->recipe_id) : null;

        return $recipe && $recipe->stages->isNotEmpty() ? $recipe : null;
    }

    private function batches(Event $event): float
    {
        $count = (float) str_replace(',', '', (string) $event->batch_count);

        return $count > 0 ? $count : 1.0;
    }

    private function minutes(?int $minutes): ?string
    {
        return $minutes !== null ? (string) $minutes : null;
    }

    private function computeUsing(Event $event, $capacityQuery): ?string
    {
        $recipe = Recipe::find($event->recipe_id);

        if (!$recipe || !$recipe->quantity_per_batch) {
            return null;
        }

        $capacity = $capacityQuery->first();

        if (!$capacity || (float) $capacity->capacity <= 0) {
            return null;
        }

        $batchCount  = (float) ($event->batch_count ?: 1);
        $requiredQty = (float) $recipe->quantity_per_batch * $batchCount;

        $units      = $this->api->get("/v1/items/{$event->item_id}")['data']['units'] ?? [];
        $recipeUnit = collect($units)->firstWhere('id', $recipe->item_unit_id);

        // formula = qty of basic units needed to make 1 of that unit
        // (the basic unit's own formula is 1) — unit -> basic: multiply.
        $formula  = (float) ($recipeUnit['formula'] ?? 1) ?: 1;
        $basicQty = $requiredQty * $formula;

        $durationHours = $basicQty / (float) $capacity->capacity;

        return (string) round($durationHours * 60);
    }

    /**
     * A packaging stores a capacity per *item unit* rather than one per item
     * in basic units, and each unit's rate is set independently. Prefer the
     * rate for the recipe's own unit (exact); otherwise the basic unit's, then
     * any configured unit, converted through the unit formulas.
     */
    private function computeForPackaging(Event $event, int $packagingId): ?string
    {
        $recipe = Recipe::find($event->recipe_id);

        if (!$recipe || !$recipe->quantity_per_batch) {
            return null;
        }

        $rate = $this->packagingRate($packagingId, (int) $event->item_id, $recipe);

        if (!$rate) {
            return null;
        }

        $batchCount  = (float) ($event->batch_count ?: 1);
        $requiredQty = (float) $recipe->quantity_per_batch * $batchCount;

        // Both sides in basic units. When the rate is for the recipe's own
        // unit the formulas cancel out and this is simply quantity ÷ rate.
        $basicQty = $requiredQty * $rate['recipe_formula'];

        return (string) round($basicQty / $rate['basic_per_hour'] * 60);
    }

    /**
     * How fast a packaging handles an item, for a recipe making it: the rate
     * set for the recipe's own unit (exact), else the basic unit's, else any
     * configured unit — each unit's rate is set independently on the packaging.
     *
     * Returns the chosen rate as entered (`capacity` per hour in `item_unit_id`)
     * and converted to basic units per hour, plus the recipe unit's formula so
     * a caller can bring the recipe's quantities to basic units too. Null when
     * the packaging has no rate for the item.
     *
     * @return array{capacity: float, item_unit_id: int, basic_per_hour: float, recipe_formula: float}|null
     */
    public function packagingRate(int $packagingId, int $itemId, Recipe $recipe): ?array
    {
        $rates = PackagingCapacity::where('packaging_id', $packagingId)
            ->where('item_id', $itemId)
            ->where('capacity', '>', 0)
            ->get();

        if ($rates->isEmpty()) {
            return null;
        }

        $units       = collect($this->api->get("/v1/items/{$itemId}")['data']['units'] ?? []);
        $formulaOf   = fn($unitId) => (float) ($units->firstWhere('id', (int) $unitId)['formula'] ?? 1) ?: 1;
        $basicUnitId = $units->firstWhere('basic', true)['id'] ?? null;

        $rate = $rates->firstWhere('item_unit_id', (int) $recipe->item_unit_id)
            ?? ($basicUnitId ? $rates->firstWhere('item_unit_id', (int) $basicUnitId) : null)
            ?? $rates->first();

        return [
            'capacity'       => (float) $rate->capacity,
            'item_unit_id'   => (int) $rate->item_unit_id,
            'basic_per_hour' => (float) $rate->capacity * $formulaOf($rate->item_unit_id),
            'recipe_formula' => $formulaOf($recipe->item_unit_id),
        ];
    }
}
