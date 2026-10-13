<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Pairs of events executed in parallel on the same product: a lead event
 * (production on a line) and its companion (packaging on a packaging lane),
 * created together from the lead event type's "executed with another event
 * type" setting and linked through events.linked_event_id.
 *
 * The pre-finished good (PRE FG) the lead produces passes straight to the
 * companion without touching a warehouse — so it is never checked, reserved
 * or stocked on either side.
 */
class LinkedEventService
{
    /** Per-request cache of item units fetched from the API (item id => units). */
    protected array $unitCache = [];

    public function __construct(
        protected ApiService $api,
        protected PlanCarryOverService $carryOverService,
    ) {
    }

    /**
     * The item types handed straight from the lead to the companion: what the
     * lead's recipe type produces that the companion's recipe type consumes
     * (e.g. PFG). Read from the recipe types' own configuration, matched by id —
     * never by name, since several parent type names carry trailing spaces.
     *
     * The lead recipe's own item type always counts as produced, and a
     * companion recipe type with no item types configured falls back to the
     * item types its recipe actually lists as inputs.
     *
     * @return int[]
     */
    public function handoffItemTypeIds(?Recipe $leadRecipe, ?Recipe $companionRecipe): array
    {
        if (!$leadRecipe || !$companionRecipe) {
            return [];
        }

        $produced = collect($leadRecipe->recipeType?->output_item_type_ids ?? [])
            ->push($leadRecipe->item_type_id)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique();

        $consumed = collect($companionRecipe->recipeType?->item_type_ids ?: $companionRecipe->inputs->pluck('item_type_id'))
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique();

        return $produced->intersect($consumed)->values()->all();
    }

    /**
     * Recipes the companion can run for an item: only those that consume what
     * the lead produces, once the lead's item is known.
     */
    public function companionRecipes(int $companionItemId, ?int $leadItemId): Collection
    {
        return Recipe::where('item_id', $companionItemId)
            ->where('status', true)
            ->when($leadItemId, fn($q) => $q->whereHas('inputs', fn($inputs) => $inputs->where('item_id', $leadItemId)))
            ->orderBy('name')
            ->get();
    }

    /**
     * How many companion batches the lead's output fills: lead batches × lead
     * quantity per batch, divided by what one companion batch consumes of the
     * lead's item — both converted to basic units, since the two recipes can
     * state the item in different units. Null when it can't be worked out.
     */
    public function companionBatchCount(?Recipe $leadRecipe, $leadBatchCount, ?Recipe $companionRecipe, ?int $leadItemId = null): ?string
    {
        if (!$leadRecipe || !$companionRecipe || (float) $leadRecipe->quantity_per_batch <= 0) {
            return null;
        }

        $leadItemId = $leadItemId ?: (int) $leadRecipe->item_id;
        $input      = $companionRecipe->inputs->firstWhere('item_id', $leadItemId);

        if (!$input || (float) $input->quantity <= 0) {
            return null;
        }

        $batches = (float) str_replace(',', '', (string) $leadBatchCount);
        $batches = $batches > 0 ? $batches : 1.0;

        $produced = (float) $leadRecipe->quantity_per_batch * $batches * $this->unitFormula($leadItemId, $leadRecipe->item_unit_id);
        $perBatch = (float) $input->quantity * $this->unitFormula($leadItemId, $input->item_unit_id);

        return $this->trimNumber(round($produced / $perBatch, 4));
    }

    /**
     * Keep a pair on one time window. The companion runs in parallel, so it
     * starts with the lead and both last as long as the longer of the two —
     * each event's own duration comes from its own lane's capacity. When the
     * companion isn't placed the lead simply runs its own duration again.
     *
     * Every touched event's carry-over link is refreshed. Returns the plans
     * newly created for days the window now runs into.
     */
    public function syncPair(Event $lead): Collection
    {
        $lead->loadMissing('eventType', 'plan');

        $companion = $lead->companionEvent()->with('eventType')->first();

        if (!$companion || !$lead->placeable_id || !$lead->from_time) {
            return collect();
        }

        $from    = Carbon::parse($lead->from_time);
        $minutes = $companion->placeable_id
            ? max($this->durationOf($lead), $this->durationOf($companion))
            : $this->durationOf($lead);

        $to = $minutes > 0 ? $from->copy()->addMinutes($minutes)->format('Y-m-d H:i:s') : null;

        $created = collect();

        foreach (array_filter([$lead, $companion->placeable_id ? $companion : null]) as $event) {
            $event->from_time = $from->format('Y-m-d H:i:s');
            $event->to_time   = $to;

            ['to_plan_id' => $toPlanId, 'created' => $plans] =
                $this->carryOverService->carryOverLink($lead->plan, $event->from_time, $event->to_time);

            $event->to_plan_id = $toPlanId;
            $event->save();

            $created = $created->merge($plans);
        }

        return $created->unique('id')->values();
    }

    /** An event's own planned length in minutes (recipe: computed; otherwise the type's). */
    public function durationOf(Event $event): int
    {
        $minutes = $event->eventType?->has_recipe ? $event->calculated_duration : $event->planned_duration;

        return (int) $minutes;
    }

    /** Basic units in one of the given unit (the basic unit's own formula is 1). */
    protected function unitFormula(int $itemId, $itemUnitId): float
    {
        if (!array_key_exists($itemId, $this->unitCache)) {
            $this->unitCache[$itemId] = $this->api->get("/v1/items/{$itemId}")['data']['units'] ?? [];
        }

        $unit = collect($this->unitCache[$itemId])->firstWhere('id', (int) $itemUnitId);

        return (float) ($unit['formula'] ?? 1) ?: 1.0;
    }

    protected function trimNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.') ?: '0';
    }
}
