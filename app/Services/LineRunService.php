<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventQuantity;
use App\Models\Line;
use App\Models\Preparation;
use App\Models\Recipe;
use App\Models\RecipeInput;
use App\Models\RecipeStage;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * A line run: one event for a whole continuous line (cutting, cooking,
 * packaging, packing … running at once). Its recipe has stages, each a
 * station type, run on the line's stations (machines) of that type.
 *
 *  - A stage's rate is the sum of its stations' rates; the line runs at its
 *    slowest stage's (lineRate()), which sets the run's duration and its
 *    hourly target.
 *  - Nothing is reserved at Start: dough is mixed while the line runs. Stock
 *    moves once, at End, from what the supervisor counts per station
 *    (settle()); everything a run takes or makes passes through the line's
 *    own warehouses (warehouseFor()).
 *  - planMixes() schedules the preparation events that make its dough.
 *
 * Counts: every stage counts in its own output unit ("kg", "packets"), except
 * the last, which counts in the recipe's own unit (the boxes that go into
 * stock). A stage's output_quantity is also the unit its station's rate is in.
 */
class LineRunService
{
    /** Percent a material may stray from the recipe at End before the run is flagged. */
    public const DEFAULT_TOLERANCE = 1.0;

    /** Below this share of the hour's target, an hourly count asks for a reason. */
    public const PACE_REASON_BELOW = 90.0;

    /** A mix is planned to be ready this long before the line needs it. */
    public const MIX_READY_BEFORE = 10;

    /** A mix's duration when the mixer has no capacity for that dough. */
    public const MIX_DEFAULT_MINUTES = 20;

    /** A counter may disagree with what went on by less than this many units. */
    protected const COUNT_SLACK = 0.5;

    protected array $itemCache = [];
    protected array $warehouseTypesCache = [];

    public function __construct(protected ApiService $api)
    {
    }

    // ─── What a run is ───────────────────────────────────────────────────────

    public function isStaged(?Recipe $recipe): bool
    {
        if (!$recipe) {
            return false;
        }

        return $recipe->relationLoaded('stages')
            ? $recipe->stages->isNotEmpty()
            : $recipe->stages()->exists();
    }

    /** A recipe event whose recipe has stages runs as a line run. */
    public function isRun(?Event $event): bool
    {
        return $event
            && $event->eventType?->has_recipe
            && $event->recipe_id
            && $this->isStaged($event->recipe);
    }

    /** The line a run is placed on, if it is placed on one. */
    public function lineOf(Event $event): ?Line
    {
        return $event->placeable_type === Line::class && $event->placeable_id
            ? Line::with('stations.type')->find($event->placeable_id)
            : null;
    }

    /** Batches the event runs; anything missing or not positive is one. */
    public function batchCount(Event $event): float
    {
        $count = (float) str_replace(',', '', (string) $event->batch_count);

        return $count > 0 ? $count : 1.0;
    }

    public function lastStage(Recipe $recipe): ?RecipeStage
    {
        return $recipe->stages->sortBy('sequence')->last();
    }

    /**
     * How much of a stage's count makes one batch: its output_quantity, but
     * the recipe's own quantity for the last stage, whose count is the
     * finished product.
     */
    public function countPerBatch(RecipeStage $stage, Recipe $recipe): float
    {
        $last = $this->lastStage($recipe);

        $perBatch = $last && $last->id === $stage->id
            ? (float) $recipe->quantity_per_batch
            : (float) $stage->output_quantity;

        return $perBatch > 0 ? $perBatch : 1.0;
    }

    // ─── Rate and duration ───────────────────────────────────────────────────

    /**
     * Each stage with the line's stations of its type and how many batches an
     * hour they take together, and the slowest stage — the line's rate. A
     * stage's stations share the work, so their rates add up; one without a
     * rate adds nothing, and a stage whose stations have none (or that has no
     * station on the line) doesn't limit it. $downStationId takes that
     * station out (a station stop): its stage runs on the others, or stops.
     *
     * @return array{stages: array, batches_per_hour: ?float, bottleneck: ?string, missing: array}
     */
    public function lineRate(Recipe $recipe, Line $line, ?int $downStationId = null): array
    {
        $stations = $line->relationLoaded('stations') ? $line->stations : $line->stations()->with('type')->get();
        $rows     = [];
        $missing  = [];
        $slowest  = null;

        foreach ($recipe->stages->sortBy('sequence') as $stage) {
            $own = $this->stationsFor($stage, $stations);

            if ($own->isEmpty()) {
                $missing[] = $stage->name;
            }

            $rated  = $own->filter(fn(Station $station) => $station->hourlyRate() !== null);
            $up     = $rated->reject(fn(Station $station) => $downStationId && $station->id === $downStationId);
            $hourly = $rated->isNotEmpty() ? (float) $up->sum(fn(Station $station) => $station->hourlyRate()) : null;
            $perBph = $hourly !== null && $stage->output_quantity > 0 ? $hourly / (float) $stage->output_quantity : null;

            $rows[] = [
                'stage_id'         => $stage->id,
                'stage'            => $stage->name,
                'station_ids'      => $own->pluck('id')->all(),
                'station'          => $own->pluck('name')->implode(', ') ?: null,
                'machines'         => $own->count() ?: null,
                'rate_label'       => $this->stageRateLabel($rated, $own->count()),
                'batches_per_hour' => $perBph,
            ];

            if ($perBph !== null && ($slowest === null || $perBph < $slowest['batches_per_hour'])) {
                $slowest = end($rows);
            }
        }

        return [
            'stages'           => $rows,
            'batches_per_hour' => $slowest['batches_per_hour'] ?? null,
            'bottleneck'       => $slowest['stage'] ?? null,
            'missing'          => $missing,
        ];
    }

    /**
     * The line's stations a stage runs on: those of its station type (by the
     * type's name for a stage saved without one).
     */
    public function stationsFor(RecipeStage $stage, Collection $stations): Collection
    {
        if ($stage->station_type_id) {
            return $stations->where('station_type_id', $stage->station_type_id)->values();
        }

        $name = $this->normalize($stage->name);

        return $stations->filter(fn(Station $station) => $this->normalize($station->type?->name) === $name)->values();
    }

    /** "1,200 kg/h", "2 × 90 packets/min", or the sum when their rates differ. */
    protected function stageRateLabel(Collection $rated, int $count): ?string
    {
        if ($rated->isEmpty()) {
            return null;
        }

        $labels = $rated->map(fn(Station $station) => $station->rateLabel())->unique();
        $label  = $labels->count() === 1
            ? ($rated->count() > 1 ? $rated->count() . ' × ' : '') . $labels->first()
            : $this->formatNumber($rated->sum(fn(Station $station) => $station->hourlyRate())) . ' ' . $rated->first()->rateUnit() . '/h';

        return $label . ($rated->count() < $count ? ' (' . $rated->count() . ' of ' . $count . ' rated)' : '');
    }

    /** Minutes $batches take on $line, or null when no station has a rate. */
    public function durationMinutes(Recipe $recipe, Line $line, float $batches): ?int
    {
        $rate = $this->lineRate($recipe, $line)['batches_per_hour'];

        return $rate ? (int) round($batches / $rate * 60) : null;
    }

    /**
     * Before a run is placed: its duration on the first line that takes its
     * event type and has rates for it.
     */
    public function estimateMinutes(Recipe $recipe, ?int $eventTypeId, float $batches): ?int
    {
        $lines = Line::with('stations.type')
            ->when($eventTypeId, fn($q) => $q->whereHas('eventTypes', fn($t) => $t->where('event_types.id', $eventTypeId)))
            ->orderBy('name')
            ->get();

        foreach ($lines as $line) {
            if ($minutes = $this->durationMinutes($recipe, $line, $batches)) {
                return $minutes;
            }
        }

        return null;
    }

    // ─── Materials ───────────────────────────────────────────────────────────

    /**
     * What a run of $batches uses, input by input, with its stage.
     *
     * @return array<int, array> input id => row
     */
    public function materials(Recipe $recipe, float $batches): array
    {
        $stages = $recipe->stages->keyBy('id');
        $rows   = [];

        foreach ($recipe->inputs as $input) {
            [$itemName, $unitName] = $this->itemAndUnitNames($input->item_id, $input->item_unit_id);

            $rows[$input->id] = [
                'input_id'     => $input->id,
                'stage_id'     => $input->recipe_stage_id,
                'stage'        => $stages->get($input->recipe_stage_id)?->name,
                'item_type_id' => $input->item_type_id ? (int) $input->item_type_id : null,
                'item_id'      => (int) $input->item_id,
                'item_unit_id' => (int) $input->item_unit_id,
                'item_name'    => $itemName,
                'unit_name'    => $unitName,
                'per_batch'    => (float) $input->quantity,
                'total'        => round((float) $input->quantity * $batches, 4),
            ];
        }

        return $rows;
    }

    // ─── Warehouses ──────────────────────────────────────────────────────────

    /**
     * Where a run takes (or puts back) an item type: the line's routing when
     * it names one, else the first of the line's source warehouses that holds
     * that item type, else its default source warehouse.
     */
    public function warehouseFor(Line $line, ?int $itemTypeId): ?int
    {
        $sources = $line->sourceWarehouseIds();
        $routed  = $itemTypeId ? ($line->itemTypeWarehouseMap()[$itemTypeId] ?? null) : null;

        if ($routed && in_array($routed, $sources, true)) {
            return $routed;
        }

        if ($itemTypeId) {
            foreach ($sources as $warehouseId) {
                if (in_array($itemTypeId, $this->warehouseItemTypes($warehouseId), true)) {
                    return $warehouseId;
                }
            }
        }

        return $line->defaultSourceWarehouseId();
    }

    /** The item types a warehouse holds, from the parent. */
    protected function warehouseItemTypes(int $warehouseId): array
    {
        if (!array_key_exists($warehouseId, $this->warehouseTypesCache)) {
            $raw = $this->api->get("/v1/warehouses/{$warehouseId}")['data']['items_type_id'] ?? [];

            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }

            $this->warehouseTypesCache[$warehouseId] = array_values(array_filter(array_map('intval', (array) $raw)));
        }

        return $this->warehouseTypesCache[$warehouseId];
    }

    // ─── End ─────────────────────────────────────────────────────────────────

    /**
     * Work out a run's End from the supervisor's figures.
     *
     * $stages, in line order: ['key', 'name', 'count_per_batch', 'count_unit',
     * 'made', 'rejects', 'leftover', 'is_last']; $inputs: ['key', 'stage_key',
     * 'per_batch', 'fed', 'left', 'used', 'item_name', 'unit_name']. Figures
     * may be blank strings.
     *
     * Batches are traced back from the last station's count: each stage
     * processed what the next one took, plus what it left over, plus what it
     * rejected — or, where a counter was entered, what that counter says, as
     * long as it agrees. Each input's expected use is then its stage's batches
     * × the recipe figure; what was used is the figure entered, else fed −
     * left, else the expected figure itself.
     *
     * @return array{errors: array, flagged: bool, good_batches: float, stages: array, inputs: array}
     */
    public function settle(array $stages, array $inputs, ?float $tolerance = null): array
    {
        $tolerance ??= self::DEFAULT_TOLERANCE;
        $stages    = array_values($stages);
        $errors    = [];
        $out       = [];
        $batches   = [];
        $n         = count($stages);

        if ($n === 0) {
            return ['errors' => ['This recipe has no stages.'], 'flagged' => false, 'good_batches' => 0.0, 'stages' => [], 'inputs' => []];
        }

        $last = $stages[$n - 1];
        $made = $this->number($last['made'] ?? null);

        if ($made === null) {
            $errors[] = "Enter how many {$last['count_unit']} {$last['name']} made.";
            $made = 0.0;
        }

        $lastRejects = $this->number($last['rejects'] ?? null) ?? 0.0;

        if ($lastRejects > $made + 1e-9) {
            $errors[] = "{$last['name']}: more rejected than made.";
        }

        $batches[$n - 1] = $made / $last['count_per_batch'];
        $goodBatches     = max(0.0, $made - $lastRejects) / $last['count_per_batch'];

        $out[$last['key']] = [
            'batches'      => $batches[$n - 1],
            'made'         => $made,
            'made_derived' => false,
            'rejects'      => $lastRejects,
            'leftover'     => 0.0,
            'good'         => max(0.0, $made - $lastRejects),
        ];

        for ($i = $n - 2; $i >= 0; $i--) {
            $stage    = $stages[$i];
            $perBatch = $stage['count_per_batch'];
            $rejects  = $this->number($stage['rejects'] ?? null) ?? 0.0;
            $leftover = $this->number($stage['leftover'] ?? null) ?? 0.0;
            $counted  = $this->number($stage['made'] ?? null);

            // What the next stage took, in this stage's unit, plus what's left.
            $passedOn = $batches[$i + 1] * $perBatch + $leftover;
            $derived  = $passedOn + $rejects;

            if ($counted !== null) {
                $good = $counted - $rejects;

                if (abs($good - $passedOn) > self::COUNT_SLACK) {
                    $next     = $stages[$i + 1];
                    $errors[] = sprintf(
                        '%s: %s made − %s rejected = %s %s, but %s took %s%s.',
                        $stage['name'],
                        $this->formatNumber($counted),
                        $this->formatNumber($rejects),
                        $this->formatNumber($good),
                        $stage['count_unit'],
                        $next['name'],
                        $this->formatNumber($batches[$i + 1] * $perBatch),
                        $leftover > 0 ? ' and ' . $this->formatNumber($leftover) . ' are left over (' . $this->formatNumber($passedOn) . ')' : ''
                    );
                }

                $batches[$i] = $counted / $perBatch;
            } else {
                $batches[$i] = $derived / $perBatch;
            }

            $out[$stage['key']] = [
                'batches'      => $batches[$i],
                'made'         => $counted ?? round($derived, 4),
                'made_derived' => $counted === null,
                'rejects'      => $rejects,
                'leftover'     => $leftover,
                'good'         => ($counted ?? $derived) - $rejects,
            ];
        }

        $stageIndex = [];
        foreach ($stages as $i => $stage) {
            $stageIndex[$stage['key']] = $i;
        }

        $flagged   = false;
        $inputsOut = [];

        foreach ($inputs as $input) {
            $index    = $stageIndex[$input['stage_key'] ?? ''] ?? 0;
            $expected = round(($batches[$index] ?? 0) * (float) $input['per_batch'], 4);
            $fed      = $this->number($input['fed'] ?? null);
            $left     = $this->number($input['left'] ?? null) ?? 0.0;
            $typed    = $this->number($input['used'] ?? null);

            $used = $typed ?? ($fed !== null ? $fed - $left : $expected);

            if ($used < -1e-9) {
                $errors[] = "{$input['item_name']}: more is left on the line than was fed.";
                $used = 0.0;
            }

            $variance = $expected > 0 ? round(($used - $expected) / $expected * 100, 2) : null;
            $over     = $variance !== null && abs($variance) > $tolerance + 1e-9;
            $flagged  = $flagged || $over;

            $inputsOut[$input['key']] = [
                'expected'       => $expected,
                'used'           => round($used, 4),
                'left'           => $fed !== null ? $left : 0.0,
                'source'         => $typed !== null ? 'entered' : ($fed !== null ? 'fed' : 'recipe'),
                'variance'       => $variance,
                'over_tolerance' => $over,
            ];
        }

        return [
            'errors'       => array_values(array_unique($errors)),
            'flagged'      => $flagged,
            'good_batches' => $goodBatches,
            'stages'       => $out,
            'inputs'       => $inputsOut,
        ];
    }

    // ─── Hourly counts ───────────────────────────────────────────────────────

    /**
     * What an hourly count at $at with the counter at $counter means: boxes
     * since the previous count (or the start), against the line's rate over
     * that time.
     *
     * @return array{from: Carbon, minutes: int, quantity: float, target: ?float, pace: ?float, previous: float}
     */
    public function countPreview(Event $run, Carbon $at, float $counter): array
    {
        $previous = $run->counts()->where('counted_at', '<=', $at)->get()->last();
        $start    = $run->statusLogs()->where('action', 'start')->orderBy('id')->first();
        $from     = $previous
            ? Carbon::parse($previous->counted_at)
            : Carbon::parse($start?->happened_at ?? $start?->created_at ?? $at);

        $minutes  = max(0, (int) round(($at->getTimestamp() - $from->getTimestamp()) / 60));
        $quantity = $counter - (float) ($previous?->counter ?? 0);
        $perHour  = $this->countsPerHour($run);
        $target   = $perHour !== null ? round($perHour * $minutes / 60, 2) : null;

        return [
            'from'     => $from,
            'minutes'  => $minutes,
            'quantity' => $quantity,
            'target'   => $target,
            'pace'     => $target ? round($quantity / $target * 100, 1) : null,
            'previous' => (float) ($previous?->counter ?? 0),
        ];
    }

    /** The run's rate in its final count unit (boxes an hour), or null. */
    public function countsPerHour(Event $run): ?float
    {
        $line = $this->lineOf($run);

        if (!$line || !$run->recipe) {
            return null;
        }

        $rate = $this->lineRate($run->recipe, $line)['batches_per_hour'];

        return $rate ? $rate * (float) ($run->recipe->quantity_per_batch ?: 1) : null;
    }

    // ─── After the run ───────────────────────────────────────────────────────

    /**
     * How the run went against what the line could have made in that time,
     * and where the difference went: line stops (charged to a station), a
     * station down while the line ran on, rejects, and the rest.
     * Null when the line has no rates or the run hasn't ended.
     */
    public function summary(Event $run): ?array
    {
        $run->loadMissing('recipe.stages', 'statusLogs', 'pauseActivities');

        $line   = $this->lineOf($run);
        $recipe = $run->recipe;

        if (!$line || !$recipe) {
            return null;
        }

        $rate     = $this->lineRate($recipe, $line);
        $perHour  = $rate['batches_per_hour'];
        $logs     = $run->statusLogs->sortBy('id')->values();
        $startLog = $logs->firstWhere('action', 'start');
        $endLog   = $logs->where('action', 'terminate')->last();

        if (!$perHour || !$startLog || !$endLog) {
            return null;
        }

        $at      = fn($log) => Carbon::parse($log->happened_at ?? $log->created_at);
        $window  = max(1, ($at($endLog)->getTimestamp() - $at($startLog)->getTimestamp()) / 60);
        $losses  = [];
        $stopped = 0.0;

        // Line stops: pause → its resume (or the end).
        foreach ($logs as $i => $log) {
            if ($log->action !== 'pause') {
                continue;
            }

            $resume  = $logs->slice($i + 1)->first(fn($l) => in_array($l->action, ['resume', 'terminate'], true));
            $minutes = max(0, (($resume ? $at($resume) : $at($endLog))->getTimestamp() - $at($log)->getTimestamp()) / 60);
            $stopped += $minutes;

            $losses[] = [
                'kind'    => 'Line stopped ' . $this->formatNumber(round($minutes)) . ' min' . ($log->reason ? ', ' . $log->reason : ''),
                'station' => $log->station_name ?: '—',
                'batches' => $minutes / 60 * $perHour,
            ];
        }

        // Station stops: the line ran on at the rate the stage's other stations allow.
        foreach ($run->pauseActivities->whereNotNull('station_id') as $activity) {
            $from    = Carbon::parse($activity->happened_at ?? $activity->created_at);
            $to      = $activity->ended_at ? Carbon::parse($activity->ended_at) : $at($endLog);
            $minutes = max(0, ($to->getTimestamp() - $from->getTimestamp()) / 60);
            $reduced = $this->lineRate($recipe, $line, (int) $activity->station_id)['batches_per_hour'] ?? 0.0;

            $losses[] = [
                'kind'    => ($activity->event_type_name ?: 'Station stop') . ' ' . $this->formatNumber(round($minutes)) . ' min',
                'station' => $activity->station_name ?: '—',
                'batches' => $minutes / 60 * max(0.0, $perHour - $reduced),
            ];
        }

        $quantities  = EventQuantity::where('event_id', $run->id)->get();
        $perBatchOut = (float) ($recipe->quantity_per_batch ?: 1);
        $good        = (float) $quantities->where('source', 'output')->sum('actual_quantity') / $perBatchOut;
        $stages      = $recipe->stages->keyBy('id');
        $rejected    = 0.0;
        $rejectBy    = [];

        foreach ($quantities->where('source', 'reject') as $reject) {
            $stage = $stages->get($reject->recipe_stage_id);

            if (!$stage) {
                continue;
            }

            $batches  = (float) $reject->actual_quantity / $this->countPerBatch($stage, $recipe);
            $rejected += $batches;
            $rejectBy[] = $stage->name;
        }

        if ($rejected > 0) {
            $losses[] = ['kind' => 'Rejects', 'station' => implode(', ', array_unique($rejectBy)), 'batches' => $rejected];
        }

        $possible = $window / 60 * $perHour;
        $other    = $possible - $good - array_sum(array_column($losses, 'batches'));

        $losses[] = ['kind' => 'Start-up, slow running and short stops not logged', 'station' => '—', 'batches' => max(0.0, $other)];

        $running      = max(1e-9, $window - $stopped);
        $availability = $running / $window;
        $performance  = ($good + $rejected) / ($running / 60 * $perHour);
        $quality      = ($good + $rejected) > 0 ? $good / ($good + $rejected) : 0.0;

        return [
            'unit'         => $perBatchOut,
            'possible'     => $possible * $perBatchOut,
            'good'         => $good * $perBatchOut,
            'planned'      => $this->batchCount($run) * $perBatchOut,
            'bottleneck'   => $rate['bottleneck'],
            'per_hour'     => $perHour * $perBatchOut,
            'losses'       => array_map(fn($loss) => $loss + ['quantity' => $loss['batches'] * $perBatchOut], $losses),
            'availability' => round($availability * 100, 1),
            'performance'  => round($performance * 100, 1),
            'quality'      => round($quality * 100, 1),
            'oee'          => round($good / $possible * 100, 1),
        ];
    }

    // ─── Plan mixes ──────────────────────────────────────────────────────────

    /**
     * Schedule the preparation events that make a placed run's dough: for each
     * input another recipe makes, enough mixes (one batch of that recipe each,
     * the last one part of a batch) on a preparation of the run's production
     * line, each ready shortly before the line needs it at its rate. Earlier
     * planned mixes for the run are replaced; started ones stop it.
     *
     * @return array{created: Collection, notes: array}
     */
    public function planMixes(Event $run, RecipeDurationService $durations, PlanCarryOverService $carryOver): array
    {
        $run->loadMissing('recipe.stages', 'recipe.inputs', 'plan', 'eventType');

        $line   = $this->lineOf($run);
        $recipe = $run->recipe;

        if (!$this->isRun($run)) {
            throw new RuntimeException('Only a line run (a recipe with stages) gets mixes planned.');
        }

        if (!$line || !$run->from_time || !$run->production_line_id) {
            throw new RuntimeException('Place the run on its line first — the mixes are timed from its start.');
        }

        if ($run->status === 'terminated') {
            throw new RuntimeException('This run has ended.');
        }

        if ($run->feedingEvents()->whereNotNull('status')->exists()) {
            throw new RuntimeException('Some mixes for this run have started. Add any more by hand.');
        }

        $perHour = $this->lineRate($recipe, $line)['batches_per_hour'];

        if (!$perHour) {
            throw new RuntimeException("The stations of {$line->name} have no rates, so the mixes can't be timed. Set the rates on the Stations page.");
        }

        $preparations = Preparation::with('eventTypes')
            ->whereHas('productionLines', fn($q) => $q->where('production_lines.id', $run->production_line_id))
            ->orderBy('name')
            ->get();

        $batches   = $this->batchCount($run);
        $allowance = 1 + max(0.0, (float) $recipe->reject_allowance) / 100;
        $runStart  = Carbon::parse($run->from_time);
        $created   = collect();
        $notes     = [];
        $plans     = [];

        foreach ($recipe->inputs as $input) {
            $mixRecipe = Recipe::where('item_id', $input->item_id)
                ->where('status', true)
                ->whereDoesntHave('stages')
                ->orderBy('name')
                ->first();

            if (!$mixRecipe || !(float) $mixRecipe->quantity_per_batch) {
                continue;
            }

            [$itemName] = $this->itemAndUnitNames($input->item_id, $input->item_unit_id);

            [$preparation, $eventType] = $this->mixerFor($preparations, (int) $mixRecipe->item_type_id);

            if (!$preparation) {
                $notes[] = "No preparation on this production line makes {$itemName}.";
                continue;
            }

            // Everything in the run input's own unit.
            $perMix = (float) $mixRecipe->quantity_per_batch
                * $this->unitFormula((int) $input->item_id, $mixRecipe->item_unit_id)
                / $this->unitFormula((int) $input->item_id, $input->item_unit_id);

            $needed   = $batches * (float) $input->quantity * $allowance;
            $perHourU = $perHour * (float) $input->quantity;
            $mixes    = (int) ceil($needed / $perMix - 1e-9);
            $interval = $perMix / $perHourU * 60;

            $plans[] = compact('input', 'mixRecipe', 'preparation', 'eventType', 'perMix', 'needed', 'mixes', 'interval', 'itemName');
        }

        if (!$plans) {
            throw new RuntimeException($notes ? implode(' ', $notes) : 'Nothing in this recipe is made by a preparation recipe.');
        }

        // Re-planning replaces what was planned before.
        $run->feedingEvents()->whereNull('status')->delete();

        foreach ($plans as $plan) {
            ['mixRecipe' => $mixRecipe, 'preparation' => $preparation, 'eventType' => $eventType] = $plan;

            for ($k = 0; $k < $plan['mixes']; $k++) {
                $share = min(1.0, $plan['needed'] / $plan['perMix'] - $k);
                $count = $k === $plan['mixes'] - 1 ? round($share, 4) : 1.0;

                $mix = Event::make([
                    'event_type_id' => $eventType->id,
                    'item_id'       => $mixRecipe->item_id,
                    'recipe_id'     => $mixRecipe->id,
                    'batch_count'   => $this->formatNumber($count),
                ]);

                $minutes = (int) ($durations->compute($mix, Preparation::class, $preparation->id) ?: self::MIX_DEFAULT_MINUTES);
                $neededAt = $runStart->copy()->addSeconds((int) round($k * $plan['interval'] * 60));
                $to       = $neededAt->copy()->subMinutes(self::MIX_READY_BEFORE);
                $from     = $to->copy()->subMinutes($minutes);

                ['to_plan_id' => $toPlanId] = $carryOver->carryOverLink($run->plan, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s'));

                $created->push(Event::create([
                    'plan_id'             => $run->plan_id,
                    'event_type_id'       => $eventType->id,
                    'name'                => $eventType->name,
                    'description'         => sprintf('Mix %d of %d for %s run #%d', $k + 1, $plan['mixes'], $recipe->name, $run->id),
                    'item_type_id'        => $mixRecipe->item_type_id,
                    'item_id'             => $mixRecipe->item_id,
                    'recipe_type_id'      => $mixRecipe->recipe_type_id,
                    'recipe_id'           => $mixRecipe->id,
                    'batch_count'         => $this->formatNumber($count),
                    'calculated_duration' => $minutes,
                    'production_line_id'  => $run->production_line_id,
                    'placeable_type'      => Preparation::class,
                    'placeable_id'        => $preparation->id,
                    'from_time'           => $from->format('Y-m-d H:i:s'),
                    'to_time'             => $to->format('Y-m-d H:i:s'),
                    'to_plan_id'          => $toPlanId,
                    'feeds_event_id'      => $run->id,
                ]));
            }

            $notes[] = sprintf(
                '%s: %s %s needed = %d mix%s of %s on %s, one every %d min.',
                $plan['itemName'],
                $this->formatNumber(round($plan['needed'], 2)),
                $this->unitName((int) $plan['input']->item_id, $plan['input']->item_unit_id),
                $plan['mixes'],
                $plan['mixes'] === 1 ? '' : 'es',
                $this->formatNumber(round($plan['perMix'], 2)),
                $preparation->name,
                (int) round($plan['interval'])
            );
        }

        return ['created' => $created, 'notes' => $notes];
    }

    /** A preparation of the line, and its event type, that make this item type. */
    protected function mixerFor(Collection $preparations, int $itemTypeId): array
    {
        foreach ($preparations as $preparation) {
            $eventType = $preparation->eventTypes->first(fn($type) => $type->has_recipe
                && in_array($itemTypeId, array_map('intval', $type->item_type_ids ?? []), true));

            if ($eventType) {
                return [$preparation, $eventType];
            }
        }

        return [null, null];
    }

    /**
     * Dough a run has been fed so far: what its ended mixes produced, per item.
     *
     * @return array<int, float> item id => quantity
     */
    public function fedSoFar(Event $run): array
    {
        $mixIds = $run->feedingEvents()->where('status', 'terminated')->pluck('id');

        if ($mixIds->isEmpty()) {
            return [];
        }

        return EventQuantity::whereIn('event_id', $mixIds)
            ->where('source', 'output')
            ->get()
            ->groupBy('item_id')
            ->map(fn($rows) => (float) $rows->sum('actual_quantity'))
            ->all();
    }

    // ─── Items and units (parent API) ────────────────────────────────────────

    public function item(int $itemId): array
    {
        return $this->itemCache[$itemId] ??= ($this->api->get("/v1/items/{$itemId}")['data'] ?? []);
    }

    /** @return array{0: string, 1: ?string} [item name, unit name] */
    public function itemAndUnitNames(?int $itemId, ?int $unitId): array
    {
        if (!$itemId) {
            return ['—', null];
        }

        $item = $this->item($itemId);

        return [
            $item['name'] ?? "Item #{$itemId}",
            $unitId ? $this->unitName($itemId, $unitId) : null,
        ];
    }

    public function unitName(int $itemId, $unitId): ?string
    {
        $unit = collect($this->item($itemId)['units'] ?? [])->firstWhere('id', (int) $unitId);

        return $unit['name'] ?? ($unitId ? "Unit #{$unitId}" : null);
    }

    /** Basic units in one of $unitId (the basic unit's own formula is 1). */
    public function unitFormula(int $itemId, $unitId): float
    {
        $unit = collect($this->item($itemId)['units'] ?? [])->firstWhere('id', (int) $unitId);

        return (float) ($unit['formula'] ?? 1) ?: 1.0;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function number($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(',', '', (string) $value);

        return is_numeric($value) ? (float) $value : null;
    }

    public function formatNumber($number): string
    {
        $formatted = rtrim(rtrim(number_format((float) $number, 4, '.', ','), '0'), '.');

        return $formatted === '' || $formatted === '-0' ? '0' : $formatted;
    }

    protected function normalize(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }
}
