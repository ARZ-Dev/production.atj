<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Packaging;
use App\Models\Plan;
use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Events executed in parallel on the same product: a lead event (production
 * on a line) and its companions (packaging on packaging lanes — one per lane
 * the run is packed on), created together from the lead event type's
 * "executed with another event type" setting and linked through
 * events.linked_event_id.
 *
 * The pre-finished good (PRE FG) the lead produces passes straight to the
 * companions without touching a warehouse — so it is never checked, reserved
 * or stocked on either side. How much of it each companion takes is set by
 * its batch count, suggested from each packaging's capacity (distribution()).
 */
class LinkedEventService
{
    /** Per-request cache of items fetched from the API (item id => data). */
    protected array $itemCache = [];

    public function __construct(
        protected ApiService $api,
        protected PlanCarryOverService $carryOverService,
        protected RecipeDurationService $durationService,
    ) {
    }

    /**
     * The item types handed straight from the lead to a companion: what the
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
     * Recipes a companion can run for an item: only those that consume what
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

    /** The packagings of a factory an event type is allowed on. */
    public function packagingsFor(?int $eventTypeId, ?int $factoryId): Collection
    {
        if (!$eventTypeId || !$factoryId) {
            return collect();
        }

        return Packaging::where('factory_id', $factoryId)
            ->whereHas('eventTypes', fn($q) => $q->where('event_types.id', $eventTypeId))
            ->orderBy('name')
            ->get();
    }

    /**
     * How the lead's output is shared out between its companions.
     *
     * Each companion turns the lead's item into its own product at the rate
     * its packaging is set up for. Split in proportion to those rates, every
     * companion takes the same time to get through its share — so they all
     * finish together, which is what a group running on one window wants.
     * When a rate can't be worked out for every companion (no packaging
     * picked, or none set for the item on it) the output is split evenly.
     *
     * The suggestion (`suggested_batches`) is what the popup fills each
     * companion's batch count with; what was actually entered is measured
     * separately (`assigned`), so an edited batch count shows as quantity left
     * unassigned or assigned twice rather than being hidden.
     *
     * Quantities of the lead's item are stated in the lead recipe's unit.
     *
     * @param array $companions key => ['recipe_id', 'item_id', 'packaging_id', 'batch_count']
     */
    public function distribution(?Recipe $leadRecipe, $leadBatchCount, ?int $leadItemId, array $companions): ?array
    {
        if (!$leadRecipe || (float) $leadRecipe->quantity_per_batch <= 0) {
            return null;
        }

        $leadItemId = $leadItemId ?: (int) $leadRecipe->item_id;
        $leadItem   = $this->item($leadItemId);
        $leadFactor = $this->unitFormula($leadItemId, $leadRecipe->item_unit_id);

        $perBatch   = (float) $leadRecipe->quantity_per_batch;
        $batches    = $this->batchCount($leadBatchCount) ?? 1.0;
        $total      = $perBatch * $batches;
        $totalBasic = $total * $leadFactor;

        $rows = [];
        $number = 0;

        foreach ($companions as $key => $companion) {
            $rows[$key] = $this->companionShareRow($companion, $leadItemId, $leadFactor) + ['number' => ++$number];
        }

        // Only companions whose recipe consumes the lead's item take a share.
        $sharing = array_filter($rows, fn($row) => !in_array($row['issue'], ['recipe', 'consumes'], true));
        $mode    = null;

        if ($sharing) {
            $mode = collect($sharing)->every(fn($row) => $row['throughput'] > 0) ? 'capacity' : 'even';
            $sum  = array_sum(array_column($sharing, 'throughput'));

            foreach ($sharing as $key => $row) {
                $share = $mode === 'capacity' ? $row['throughput'] / $sum : 1 / count($sharing);

                $rows[$key]['share']             = $share;
                $rows[$key]['suggested_qty']     = $total * $share;
                $rows[$key]['suggested_batches'] = $this->trimNumber(round($totalBasic * $share / $row['in_basic_per_batch'], 4));
            }
        }

        $assignedBasic = 0.0;

        foreach ($rows as $key => $row) {
            if ($row['batches'] !== null && $row['in_basic_per_batch'] > 0) {
                $assignedBasic += $row['batches'] * $row['in_basic_per_batch'];

                $rows[$key]['assigned']       = $row['batches'] * $row['in_basic_per_batch'] / $leadFactor;
                $rows[$key]['assigned_share'] = $totalBasic > 0 ? $row['batches'] * $row['in_basic_per_batch'] / $totalBasic : null;
            }
        }

        $assigned = $assignedBasic / $leadFactor;
        $balance  = round($total - $assigned, 4);

        return [
            'item_name'      => $leadItem['name'] ?? "Item #{$leadItemId}",
            'unit'           => $this->unitLabel($leadItemId, $leadRecipe->item_unit_id),
            'per_batch'      => $perBatch,
            'batches'        => $batches,
            'total'          => $total,
            'mode'           => $mode,
            'companions'     => $rows,
            'assigned'       => $assigned,
            // Rounding the suggested batch counts to 4 decimals can leave a
            // crumb either way — not worth flagging.
            'balance'        => abs($balance) <= max(1e-6, $total * 1e-4) ? 0.0 : $balance,
            'finish_minutes' => $mode === 'capacity' && $totalBasic > 0
                ? (int) round($totalBasic / array_sum(array_column($sharing, 'throughput')) * 60)
                : null,
        ];
    }

    /**
     * One companion's side of distribution(): what a batch of its recipe takes
     * of the lead's item and makes of its own, and — when its packaging has a
     * rate for that — how much of the lead's item it gets through per hour.
     *
     * `issue` names the first thing missing: 'recipe' (none picked),
     * 'consumes' (the recipe doesn't use the lead's item), 'packaging' (none
     * picked) or 'capacity' (none set on it for this item). Only the first two
     * keep the companion out of the split.
     */
    protected function companionShareRow(array $companion, int $leadItemId, float $leadFactor): array
    {
        $row = [
            'issue'              => null,
            'packaging_name'     => null,
            'rate'               => null,
            'in_basic_per_batch' => 0.0,
            'per_batch_in'       => null,   // of the lead's item, in the lead recipe's unit
            'in_qty'             => null,   // the same, as this recipe states it
            'in_unit'            => null,
            'per_batch_out'      => null,
            'out_unit'           => null,
            'out_item'           => null,
            'throughput'         => 0.0,    // basic units of the lead's item per hour
            'batches'            => $this->batchCount($companion['batch_count'] ?? null),
            'share'              => null,
            'suggested_qty'      => null,
            'suggested_batches'  => null,
            'assigned'           => null,
            'assigned_share'     => null,
            'makes'              => null,
            'minutes'            => null,
        ];

        $packaging = !empty($companion['packaging_id']) ? Packaging::find($companion['packaging_id']) : null;
        $row['packaging_name'] = $packaging?->name;

        $recipe = !empty($companion['recipe_id']) ? Recipe::with('inputs')->find($companion['recipe_id']) : null;

        if (!$recipe) {
            return ['issue' => 'recipe'] + $row;
        }

        $outItemId = (int) ($companion['item_id'] ?: $recipe->item_id);

        $row['per_batch_out'] = (float) $recipe->quantity_per_batch;
        $row['out_unit']      = $this->unitLabel($outItemId, $recipe->item_unit_id);
        $row['out_item']      = $this->item($outItemId)['name'] ?? "Item #{$outItemId}";

        if ($row['batches'] !== null) {
            $row['makes'] = $row['batches'] * $row['per_batch_out'];
        }

        $input = $recipe->inputs->firstWhere('item_id', $leadItemId);

        if (!$input || (float) $input->quantity <= 0) {
            return ['issue' => 'consumes'] + $row;
        }

        $row['in_basic_per_batch'] = (float) $input->quantity * $this->unitFormula($leadItemId, $input->item_unit_id);
        $row['per_batch_in']       = $row['in_basic_per_batch'] / $leadFactor;
        $row['in_qty']             = (float) $input->quantity;
        $row['in_unit']            = $this->unitLabel($leadItemId, $input->item_unit_id);

        if (!$packaging) {
            return ['issue' => 'packaging'] + $row;
        }

        $rate = $this->durationService->packagingRate($packaging->id, $outItemId, $recipe);

        if (!$rate || (float) $recipe->quantity_per_batch <= 0) {
            return ['issue' => 'capacity'] + $row;
        }

        $batchesPerHour = $rate['basic_per_hour'] / ((float) $recipe->quantity_per_batch * $rate['recipe_formula']);

        $row['rate']       = ['capacity' => $rate['capacity'], 'unit' => $this->unitLabel($outItemId, $rate['item_unit_id'])];
        $row['throughput'] = $batchesPerHour * $row['in_basic_per_batch'];

        if ($row['batches'] !== null) {
            $row['minutes'] = (int) round($row['batches'] / $batchesPerHour * 60);
        }

        return $row;
    }

    /**
     * Keep a group on one time window. The companions run in parallel, so
     * they start with the lead and all last as long as the longest of them —
     * each event's own duration comes from its own lane's capacity. With no
     * companion placed the lead simply runs its own duration again.
     *
     * A companion that isn't placed but was planned on a packaging in the
     * add-event popup is placed there first, at the lead's time — so dropping
     * the lead puts its whole group on the board.
     *
     * Every touched event's carry-over link is refreshed. Returns the plans
     * newly created for days the window now runs into.
     */
    public function syncGroup(Event $lead): Collection
    {
        $lead->loadMissing('eventType', 'plan');

        $companions = $lead->companionEvents()->with('eventType')->get();

        if ($companions->isEmpty() || !$lead->placeable_id || !$lead->from_time) {
            return collect();
        }

        foreach ($companions as $companion) {
            if (!$companion->placeable_id && !$companion->status && $companion->planned_packaging_id) {
                $this->placeOnPlannedPackaging($companion, $lead->plan);
            }
        }

        $placed = $companions->filter(fn(Event $companion) => $companion->placeable_id);

        $from    = Carbon::parse($lead->from_time);
        $minutes = $placed->map(fn(Event $companion) => $this->durationOf($companion))
            ->push($this->durationOf($lead))
            ->max();

        $to = $minutes > 0 ? $from->copy()->addMinutes($minutes)->format('Y-m-d H:i:s') : null;

        $created = collect();

        foreach ($placed->prepend($lead) as $event) {
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

    /**
     * Put a companion on the packaging it was planned on, if that packaging is
     * still on the plan's factory and still takes the companion's event type —
     * otherwise it stays in the tray, to be dragged like any other event.
     * Not saved here; syncGroup() saves it with its times.
     */
    protected function placeOnPlannedPackaging(Event $companion, ?Plan $plan): void
    {
        $packaging = $this->packagingsFor($companion->event_type_id, $plan?->factory_id)
            ->firstWhere('id', (int) $companion->planned_packaging_id);

        if (!$packaging) {
            return;
        }

        $companion->placeable_type      = Packaging::class;
        $companion->placeable_id        = $packaging->id;
        $companion->production_line_id  = null;
        $companion->calculated_duration = $companion->eventType?->has_recipe && $companion->recipe_id && $companion->item_id
            ? $this->durationService->compute($companion, Packaging::class, $packaging->id)
            : null;
    }

    /** An event's own planned length in minutes (recipe: computed; otherwise the type's). */
    public function durationOf(Event $event): int
    {
        $minutes = $event->eventType?->has_recipe ? $event->calculated_duration : $event->planned_duration;

        return (int) $minutes;
    }

    /** A batch count as entered (free text, may carry separators); null unless positive. */
    protected function batchCount($value): ?float
    {
        $count = (float) str_replace(',', '', (string) $value);

        return $count > 0 ? $count : null;
    }

    protected function item(int $itemId): array
    {
        if (!array_key_exists($itemId, $this->itemCache)) {
            $this->itemCache[$itemId] = $this->api->get("/v1/items/{$itemId}")['data'] ?? [];
        }

        return $this->itemCache[$itemId];
    }

    /** Basic units in one of the given unit (the basic unit's own formula is 1). */
    protected function unitFormula(int $itemId, $itemUnitId): float
    {
        $unit = collect($this->item($itemId)['units'] ?? [])->firstWhere('id', (int) $itemUnitId);

        return (float) ($unit['formula'] ?? 1) ?: 1.0;
    }

    /** A unit's short label: its symbol, else its name. */
    protected function unitLabel(int $itemId, $itemUnitId): ?string
    {
        $unit = collect($this->item($itemId)['units'] ?? [])->firstWhere('id', (int) $itemUnitId);

        if (!$unit) {
            return null;
        }

        return trim((string) ($unit['symbol'] ?? '')) ?: (trim((string) ($unit['name'] ?? '')) ?: null);
    }

    protected function trimNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.') ?: '0';
    }
}
