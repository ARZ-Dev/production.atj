<?php

namespace App\Services;

use App\Models\Capacity;
use App\Models\Event;
use App\Models\Recipe;

class RecipeDurationService
{
    protected ApiService $api;

    public function __construct(ApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Duration (in minutes) to produce $event's batch_count on the given
     * line/preparation, based on the recipe's quantity_per_batch and the
     * placeable's capacity per hour for that item.
     */
    public function compute(Event $event, string $placeableClass, int $placeableId): ?string
    {
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
        return $this->computeUsing($event, Capacity::where('item_id', $event->item_id));
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
}
