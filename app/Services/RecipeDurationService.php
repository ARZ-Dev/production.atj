<?php

namespace App\Services;

use App\Models\Capacity;
use App\Models\Event;
use App\Models\Line;
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
     * line/preparation. On a line it's a line run's: as long as the slowest
     * station needs (a line has no capacity of its own, so a recipe without
     * stages gets none). On a preparation it's the recipe's quantity_per_batch
     * against the preparation's capacity per hour for that item.
     */
    public function compute(Event $event, string $placeableClass, int $placeableId): ?string
    {
        if ($placeableClass === Line::class) {
            $recipe = $this->stagedRecipe($event);
            $line   = $recipe ? Line::with('stations.type')->find($placeableId) : null;

            return $line ? $this->minutes($this->lineRuns->durationMinutes($recipe, $line, $this->batches($event))) : null;
        }

        return $this->computeUsing($event, Capacity::where('capacityable_type', $placeableClass)
            ->where('capacityable_id', $placeableId)
            ->where('item_id', $event->item_id));
    }

    /**
     * Same calculation, but before the event has been placed on a specific
     * line/preparation — used to preview duration as soon as recipe, item
     * and batch are picked: a line run's on the first line that takes it,
     * else the first preparation capacity for the item. Recalculated against
     * the actual lane once the event is dropped on the calendar.
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
}
