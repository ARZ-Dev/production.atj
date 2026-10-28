<?php

namespace App\Livewire\Plans\Concerns;

use App\Models\Event;
use App\Models\EventCount;
use App\Models\EventQuantity;
use App\Models\Line;
use App\Models\LineStation;
use App\Models\Recipe;
use App\Models\RecipeStage;
use App\Models\ReportItem;
use App\Models\StockIn;
use App\Models\Waste;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The plan board's side of a line run (see LineRunService): one event for a
 * whole continuous line.
 *
 *  - Start takes nothing — dough is mixed while the line runs.
 *  - While it runs: hourly counts against the line's rate, Pause for a stop
 *    of the whole line (charged to a station), and station stops (one machine
 *    down while the rest run on), which are emergency events with a station.
 *  - End: what each station counted, its rejects and leftovers, and what was
 *    used of each material; checked, then stock moves in one batch.
 *
 * Expects the host component's $lineRuns, $inventory, $durationService,
 * $carryOverService, $plan, $eventAction, $actionTime, $actionNotes and its
 * helpers (applyStatusChange, parsedActionTime, closeActionModal …).
 */
trait RunsLineRuns
{
    // ─── Modal state ─────────────────────────────────────────────────────────

    /**
     * The End form, keyed by stable keys so no wire:model path shifts:
     * ['stages' => ['s<id>' => …], 'inputs' => ['i<id>' => …]].
     */
    public array $runEnd = [];

    /** The last check of the End figures (LineRunService::settle() + labels). */
    public ?array $runEndCheck = null;

    /** The hourly count form: ['counter', 'reason'] and its preview. */
    public array $runCount = [];

    /** The station a line stop (Pause) is charged to. */
    public ?int $actionStationId = null;

    protected function resetRunForm(): void
    {
        $this->runEnd          = [];
        $this->runEndCheck     = null;
        $this->runCount        = [];
        $this->actionStationId = null;
    }

    // ─── Stations ────────────────────────────────────────────────────────────

    /** The stations of the line a run is on, for the station pickers. */
    protected function runStations(Event $event): array
    {
        $line = $this->lineRuns->lineOf($event);

        return $line
            ? $line->stations->map(fn(LineStation $station) => [
                'id'       => $station->id,
                'name'     => $station->name,
                'machine'  => $station->machine,
                'machines' => max(1, (int) $station->machine_count),
            ])->all()
            : [];
    }

    /** One of the run's stations by id, or null when it isn't one of them. */
    protected function runStation(Event $event, $stationId): ?array
    {
        return collect($this->runStations($event))->firstWhere('id', (int) $stationId);
    }

    // ─── Start ───────────────────────────────────────────────────────────────

    /**
     * A run starts with nothing taken from stock; the modal shows the line's
     * stations and rate, and what the first stage has to start with.
     */
    protected function prepareRunStartModal(Event $event): bool
    {
        $line = $this->lineRuns->lineOf($event);

        if (!$line) {
            $this->dispatch('swal:error', [
                'title' => 'Not on a line',
                'text'  => 'A line run is started on a line — place it on one first.',
            ]);

            return false;
        }

        $recipe = $event->recipe->loadMissing('stages', 'inputs');
        $rate   = $this->lineRuns->lineRate($recipe, $line);
        $unit   = $this->runUnit($recipe);

        $this->eventAction = array_merge($this->baseActionPayload($event, 'start'), [
            'run'          => true,
            'has_recipe'   => true,
            'line_name'    => $line->name,
            'stages'       => $rate['stages'],
            'bottleneck'   => $rate['bottleneck'],
            'missing'      => $rate['missing'],
            'per_hour'     => $rate['batches_per_hour'] ? $rate['batches_per_hour'] * (float) $recipe->quantity_per_batch : null,
            'unit'         => $unit,
            'planned'      => $this->lineRuns->batchCount($event) * (float) $recipe->quantity_per_batch,
            'first_inputs' => $this->firstStageStock($event, $recipe, $line),
        ]);

        return true;
    }

    /** What the first stage uses, with what its warehouse holds now. */
    protected function firstStageStock(Event $event, Recipe $recipe, Line $line): array
    {
        $first = $recipe->stages->sortBy('sequence')->first();

        if (!$first) {
            return [];
        }

        return $recipe->inputs
            ->where('recipe_stage_id', $first->id)
            ->map(function ($input) use ($line, $first) {
                [$itemName, $unitName] = $this->lineRuns->itemAndUnitNames((int) $input->item_id, (int) $input->item_unit_id);
                $warehouseId           = $this->lineRuns->warehouseFor($line, $input->item_type_id ? (int) $input->item_type_id : null);

                return [
                    'stage'          => $first->name,
                    'item_id'        => (int) $input->item_id,
                    'item_name'      => $itemName,
                    'unit_name'      => $unitName,
                    'warehouse_name' => $warehouseId ? $this->warehouseName($warehouseId) : null,
                    'available'      => $warehouseId ? $this->inventory->available($warehouseId, (int) $input->item_id, (int) $input->item_unit_id) : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function submitRunStart(Event $event): void
    {
        $event->loadMissing('recipe.stages', 'recipe.inputs');
        $line = $this->lineRuns->lineOf($event);

        if (!$line) {
            $this->dispatch('swal:error', ['title' => 'Not on a line', 'text' => 'A line run is started on a line — place it on one first.']);
            $this->closeActionModal();

            return;
        }

        // The line can't start without what its first station takes — read
        // live, not from when the window opened.
        foreach ($this->firstStageStock($event, $event->recipe, $line) as $input) {
            if (($input['available'] ?? 0) <= 0) {
                $this->dispatch('swal:error', [
                    'title' => 'Cannot start the run',
                    'text'  => "There is no {$input['item_name']} in " . ($input['warehouse_name'] ?: 'the line\'s warehouse')
                        . " yet — the {$input['stage']} station has nothing to start with.",
                ]);

                return;
            }
        }

        DB::transaction(fn() => $this->applyStatusChange($event, 'start', [
            'notes'       => $this->actionNotes,
            'happened_at' => $this->parsedActionTime(),
        ]));

        $this->closeActionModal();
    }

    // ─── Hourly counts ───────────────────────────────────────────────────────

    public function openHourlyCount(int $eventId): void
    {
        authorizeRequest('production.event-create');

        $event = Event::with('eventType', 'recipe.stages')->where('plan_id', $this->plan->id)->findOrFail($eventId);

        if (!$this->lineRuns->isRun($event) || !in_array($event->status, ['in_progress', 'paused'], true)) {
            $this->dispatch('swal:error', [
                'title' => 'Action not allowed',
                'text'  => 'Hourly counts are taken on a line run while it runs.',
            ]);

            return;
        }

        $this->resetActionForm();

        // On the hour, the usual time of a count.
        $this->actionTime = now()->startOfHour()->format('Y-m-d\TH:i');
        $this->runCount   = ['counter' => '', 'reason' => ''];

        $this->eventAction = array_merge($this->baseActionPayload($event, 'count'), [
            'run'    => true,
            'unit'   => $this->runUnit($event->recipe),
            'counts' => $this->countRows($event),
        ]);

        $this->refreshCountPreview();

        $this->dispatch('openEventActionModal');
    }

    /** The count's figures for the time and counter entered so far. */
    protected function refreshCountPreview(): void
    {
        if (($this->eventAction['action'] ?? null) !== 'count') {
            return;
        }

        $event   = Event::with('recipe')->find($this->eventAction['event_id']);
        $counter = $this->lineRuns->number($this->runCount['counter'] ?? null);

        if (!$event || strtotime((string) $this->actionTime) === false) {
            $this->eventAction['preview'] = null;

            return;
        }

        $preview = $this->lineRuns->countPreview($event, Carbon::parse($this->actionTime), $counter ?? 0.0);

        $this->eventAction['preview'] = [
            'from'     => $preview['from']->format('H:i'),
            'minutes'  => $preview['minutes'],
            'previous' => $preview['previous'],
            'quantity' => $counter !== null ? $preview['quantity'] : null,
            'target'   => $preview['target'],
            'pace'     => $counter !== null ? $preview['pace'] : null,
            'stops'    => $this->stopsBetween($event, $preview['from'], Carbon::parse($this->actionTime)),
        ];
    }

    /** Line stops and station stops logged in a period — offered as the reason. */
    protected function stopsBetween(Event $event, Carbon $from, Carbon $to): array
    {
        $stops = [];

        foreach ($event->statusLogs()->where('action', 'pause')->get() as $log) {
            $at = Carbon::parse($log->happened_at ?? $log->created_at);

            if ($at->between($from, $to)) {
                $resume  = $event->statusLogs()->where('id', '>', $log->id)->whereIn('action', ['resume', 'terminate'])->orderBy('id')->first();
                $minutes = $resume ? (int) round(abs(Carbon::parse($resume->happened_at ?? $resume->created_at)->diffInMinutes($at))) : null;
                $stops[] = 'Line stopped' . ($minutes !== null ? " {$minutes} min" : '') . ($log->station_name ? ", {$log->station_name}" : '') . ($log->reason ? " — {$log->reason}" : '');
            }
        }

        foreach ($event->pauseActivities()->whereNotNull('line_station_id')->get() as $activity) {
            $at = Carbon::parse($activity->happened_at ?? $activity->created_at);

            if ($at->between($from, $to)) {
                $minutes = $activity->ended_at ? (int) round(abs(Carbon::parse($activity->ended_at)->diffInMinutes($at))) : null;
                $stops[] = ($activity->station_name ?: 'Station') . ($activity->machine_number ? " machine {$activity->machine_number}" : '')
                    . ' down' . ($minutes !== null ? " {$minutes} min" : '') . ($activity->event_type_name ? " ({$activity->event_type_name})" : '');
            }
        }

        return $stops;
    }

    protected function submitHourlyCount(Event $event): void
    {
        $this->runCount['counter'] = str_replace(',', '', (string) ($this->runCount['counter'] ?? ''));

        $this->validate(
            ['runCount.counter' => 'required|numeric|min:0', 'runCount.reason' => 'nullable|string|max:1000'],
            ['runCount.counter.required' => 'Enter the counter reading.', 'runCount.counter.numeric' => 'The counter must be a number.']
        );

        if (!$this->lineRuns->isRun($event) || !in_array($event->status, ['in_progress', 'paused'], true)) {
            $this->closeActionModal();

            return;
        }

        $at      = $this->parsedActionTime();
        $counter = (float) $this->runCount['counter'];
        $preview = $this->lineRuns->countPreview($event, $at, $counter);

        if ($preview['quantity'] < 0) {
            $this->addError('runCount.counter', 'Lower than the previous count (' . $this->lineRuns->formatNumber($preview['previous']) . ') — the counter only goes up during a run.');

            return;
        }

        if ($preview['pace'] !== null && $preview['pace'] < \App\Services\LineRunService::PACE_REASON_BELOW && blank($this->runCount['reason'] ?? null)) {
            $this->addError('runCount.reason', 'Below ' . (int) \App\Services\LineRunService::PACE_REASON_BELOW . ' % of the target — say why.');

            return;
        }

        EventCount::create([
            'event_id'        => $event->id,
            'counted_at'      => $at,
            'counter'         => $counter,
            'quantity'        => $preview['quantity'],
            'target'          => $preview['target'],
            'reason'          => $this->runCount['reason'] ?: null,
            'created_by'      => authUser()?->id,
            'created_by_name' => authUser()?->name,
        ]);

        $this->closeActionModal();
    }

    /** A run's hourly counts for display. */
    protected function countRows(Event $event): array
    {
        return EventCount::where('event_id', $event->id)
            ->orderBy('counted_at')
            ->orderBy('id')
            ->get()
            ->map(fn(EventCount $count) => [
                'at'       => $count->counted_at->format('H:i'),
                'counter'  => $count->counter,
                'quantity' => $count->quantity,
                'target'   => $count->target,
                'pace'     => $count->target ? round($count->quantity / $count->target * 100, 1) : null,
                'reason'   => $count->reason,
                'by'       => $count->created_by_name,
            ])
            ->all();
    }

    // ─── End ─────────────────────────────────────────────────────────────────

    /**
     * The End form: per station what it made (counted or worked back from the
     * next), its rejects and where they go, its leftovers; per material what
     * was used — fed − left for dough brought by the run's mixes, else as
     * entered, else the recipe figure.
     */
    protected function prepareRunEndModal(Event $event): bool
    {
        $event->loadMissing('recipe.stages', 'recipe.inputs');

        $line   = $this->lineRuns->lineOf($event);
        $recipe = $event->recipe;

        if (!$line) {
            $this->dispatch('swal:error', ['title' => 'Not on a line', 'text' => 'This run is not on a line.']);

            return false;
        }

        $stages    = $recipe->stages->sortBy('sequence')->values();
        $last      = $stages->last();
        $lastCount = EventCount::where('event_id', $event->id)->orderByDesc('counted_at')->orderByDesc('id')->first();
        $fed       = $this->lineRuns->fedSoFar($event);
        $unit      = $this->runUnit($recipe);

        $this->runEnd = ['stages' => [], 'inputs' => []];

        foreach ($stages as $stage) {
            $isLast = $stage->id === $last->id;

            $this->runEnd['stages']['s' . $stage->id] = [
                'id'              => $stage->id,
                'name'            => $stage->name,
                'is_last'         => $isLast,
                'count_per_batch' => $this->lineRuns->countPerBatch($stage, $recipe),
                'count_unit'      => $isLast ? $unit : ($stage->output_unit ?: 'units'),
                // The last station's counter is the hourly counts' one.
                'made'            => $isLast && $lastCount ? $this->plainNumber($lastCount->counter) : '',
                'rejects'         => '',
                'reject_reason'   => '',
                'reject_action'   => $stage->canRework() ? ($stage->reject_action ?: 'waste') : 'waste',
                'can_rework'      => $stage->canRework(),
                'rework_label'    => $stage->canRework() ? $this->lineRuns->itemAndUnitNames((int) $stage->rework_item_id, (int) $stage->rework_item_unit_id)[0] : null,
                'has_leftover'    => !$isLast && $stage->output_item_unit_id,
                'leftover'        => '',
            ];
        }

        foreach ($recipe->inputs as $input) {
            [$itemName, $unitName] = $this->lineRuns->itemAndUnitNames((int) $input->item_id, (int) $input->item_unit_id);
            $warehouseId           = $this->lineRuns->warehouseFor($line, $input->item_type_id ? (int) $input->item_type_id : null);
            $fedQty                = $fed[(int) $input->item_id] ?? null;

            $this->runEnd['inputs']['i' . $input->id] = [
                'id'             => $input->id,
                'stage_key'      => 's' . ($input->recipe_stage_id ?: $stages->first()->id),
                'stage_name'     => $stages->firstWhere('id', $input->recipe_stage_id)?->name ?? $stages->first()->name,
                'item_type_id'   => $input->item_type_id ? (int) $input->item_type_id : null,
                'item_id'        => (int) $input->item_id,
                'item_unit_id'   => (int) $input->item_unit_id,
                'item_name'      => $itemName,
                'unit_name'      => $unitName,
                'per_batch'      => (float) $input->quantity,
                'from_mixes'     => $fedQty !== null,
                'fed'            => $fedQty !== null ? $this->plainNumber($fedQty) : '',
                'left'           => '',
                'left_action'    => 'keep',
                'used'           => '',
                'warehouse_id'   => $warehouseId,
                'warehouse_name' => $warehouseId ? $this->warehouseName($warehouseId) : null,
            ];
        }

        $this->eventAction = array_merge($this->baseActionPayload($event, 'terminate'), [
            'run'        => true,
            'has_recipe' => true,
            'line_name'  => $line->name,
            'unit'       => $unit,
            'planned'    => $this->lineRuns->batchCount($event) * (float) $recipe->quantity_per_batch,
            'tolerance'  => (float) ($recipe->balance_tolerance ?? \App\Services\LineRunService::DEFAULT_TOLERANCE),
            'counts'     => $this->countRows($event),
            'mixes'      => $event->feedingEvents()->where('status', 'terminated')->count(),
        ]);

        return true;
    }

    /** "Check figures": work the End out without saving anything. */
    public function checkRunEnd(): void
    {
        if (($this->eventAction['action'] ?? null) !== 'terminate' || empty($this->eventAction['run'])) {
            return;
        }

        $this->runEndCheck = $this->settleRunEnd();
    }

    /** LineRunService::settle() on the form's figures, plus what the modal labels need. */
    protected function settleRunEnd(): array
    {
        $stages = [];

        foreach ($this->runEnd['stages'] ?? [] as $key => $stage) {
            $stages[] = [
                'key'             => $key,
                'name'            => $stage['name'],
                'count_per_batch' => (float) $stage['count_per_batch'],
                'count_unit'      => $stage['count_unit'],
                'made'            => $stage['made'],
                'rejects'         => $stage['rejects'],
                'leftover'        => !empty($stage['has_leftover']) ? $stage['leftover'] : null,
                'is_last'         => !empty($stage['is_last']),
            ];
        }

        $inputs = [];

        foreach ($this->runEnd['inputs'] ?? [] as $key => $input) {
            $inputs[] = [
                'key'       => $key,
                'stage_key' => $input['stage_key'],
                'per_batch' => (float) $input['per_batch'],
                'fed'       => $input['fed'],
                'left'      => $input['left'],
                'used'      => $input['used'],
                'item_name' => $input['item_name'],
                'unit_name' => $input['unit_name'],
            ];
        }

        $result = $this->lineRuns->settle($stages, $inputs, $this->eventAction['tolerance'] ?? null);

        $perBatch          = (float) (Event::with('recipe')->find($this->eventAction['event_id'])?->recipe?->quantity_per_batch ?: 1);
        $result['output']  = $result['good_batches'] * $perBatch;
        $result['planned'] = (float) ($this->eventAction['planned'] ?? 0);

        return $result;
    }

    protected function runEndRules(): array
    {
        $rules = [];

        foreach ($this->runEnd['stages'] ?? [] as $key => $stage) {
            $rules["runEnd.stages.{$key}.made"]          = (!empty($stage['is_last']) ? 'required' : 'nullable') . '|numeric|min:0';
            $rules["runEnd.stages.{$key}.rejects"]       = 'nullable|numeric|min:0';
            $rules["runEnd.stages.{$key}.reject_reason"] = 'nullable|string|max:255';
            $rules["runEnd.stages.{$key}.reject_action"] = !empty($stage['can_rework']) ? 'required|in:waste,rework' : 'required|in:waste';
            $rules["runEnd.stages.{$key}.leftover"]      = 'nullable|numeric|min:0';
        }

        foreach (array_keys($this->runEnd['inputs'] ?? []) as $key) {
            $rules["runEnd.inputs.{$key}.fed"]         = 'nullable|numeric|min:0';
            $rules["runEnd.inputs.{$key}.left"]        = 'nullable|numeric|min:0';
            $rules["runEnd.inputs.{$key}.left_action"] = 'required|in:keep,waste';
            $rules["runEnd.inputs.{$key}.used"]        = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    /** Strip Cleave/typed thousands separators before validating. */
    protected function sanitizeRunEnd(): void
    {
        foreach ($this->runEnd['stages'] ?? [] as $key => $stage) {
            foreach (['made', 'rejects', 'leftover'] as $field) {
                $this->runEnd['stages'][$key][$field] = str_replace(',', '', (string) ($stage[$field] ?? ''));
            }
        }

        foreach ($this->runEnd['inputs'] ?? [] as $key => $input) {
            foreach (['fed', 'left', 'used'] as $field) {
                $this->runEnd['inputs'][$key][$field] = str_replace(',', '', (string) ($input[$field] ?? ''));
            }
        }
    }

    protected function submitRunEnd(Event $event): void
    {
        $event->loadMissing('recipe.stages', 'recipe.inputs', 'plan', 'eventType');

        $this->sanitizeRunEnd();

        $this->validate($this->runEndRules(), [
            'runEnd.stages.*.made.required' => 'Enter what the last station made.',
            'runEnd.stages.*.made.numeric'  => 'Must be a number.',
            'runEnd.stages.*.rejects.numeric' => 'Must be a number.',
            'runEnd.inputs.*.fed.numeric'   => 'Must be a number.',
            'runEnd.inputs.*.left.numeric'  => 'Must be a number.',
            'runEnd.inputs.*.used.numeric'  => 'Must be a number.',
        ]);

        $line = $this->lineRuns->lineOf($event);

        // The form's rows are client state: they must still be this recipe's.
        $stageIds = $event->recipe->stages->pluck('id')->map(fn($id) => 's' . $id)->sort()->values()->all();
        $inputIds = $event->recipe->inputs->pluck('id')->map(fn($id) => 'i' . $id)->sort()->values()->all();

        if (!$line
            || collect(array_keys($this->runEnd['stages'] ?? []))->sort()->values()->all() !== $stageIds
            || collect(array_keys($this->runEnd['inputs'] ?? []))->sort()->values()->all() !== $inputIds) {
            $this->dispatch('swal:error', ['title' => 'The run has changed', 'text' => 'Its recipe or line changed since this window opened. Open it again.']);
            $this->closeActionModal();

            return;
        }

        $result = $this->runEndCheck = $this->settleRunEnd();

        if ($result['errors']) {
            $this->addError('runEnd', 'The figures don\'t add up — see the checks below.');

            return;
        }

        $endedAt = $this->parsedActionTime();
        $recipe  = $event->recipe;
        $stages  = $recipe->stages->keyBy('id');
        $batches = $this->lineRuns->batchCount($event);
        $fgId    = $line->destinationWarehouseId();

        DB::beginTransaction();
        try {
            $log = $this->applyStatusChange($event, 'terminate', [
                'notes'       => $this->actionNotes,
                'happened_at' => $endedAt,
            ]);

            $ops       = [];
            $documents = [];

            // ── Materials: used out of the line's warehouses; dough left on the
            //    line stays there, or is written off.
            foreach ($this->runEnd['inputs'] as $key => $input) {
                $figures     = $result['inputs'][$key];
                $warehouseId = $this->lineRuns->warehouseFor($line, $input['item_type_id']);
                $stageId     = (int) substr($input['stage_key'], 1);

                $this->storeRunQuantity($event, $log, 'input', [
                    'recipe_input_id'  => $input['id'],
                    'recipe_stage_id'  => $stageId,
                    'stage_name'       => $input['stage_name'],
                    'item_type_id'     => $input['item_type_id'],
                    'item_id'          => $input['item_id'],
                    'item_unit_id'     => $input['item_unit_id'],
                    'item_name'        => $input['item_name'],
                    'unit_name'        => $input['unit_name'],
                    'planned_quantity' => $figures['expected'],
                    'actual_quantity'  => $figures['used'],
                    'percentage'       => $figures['variance'],
                    'warehouse_id'     => $warehouseId,
                    'confirmed_at'     => $endedAt,
                ]);

                if ($warehouseId && $figures['used'] > 0) {
                    $ops[] = InventoryService::reserveProcess($warehouseId, $input['item_id'], $input['item_unit_id'], $figures['used']);
                    $ops[] = InventoryService::confirmProcessOut($warehouseId, $input['item_id'], $input['item_unit_id'], $figures['used'], $figures['used']);
                }

                if ($figures['left'] > 0) {
                    $document = null;

                    if ($input['left_action'] === 'waste' && $warehouseId) {
                        $document = $documents["waste:{$warehouseId}"] ??= Waste::create([
                            'warehouse_id' => $warehouseId,
                            'status'       => 'approved',
                            'notes'        => $this->runDocumentNote($event, 'waste', $endedAt),
                        ]);

                        ReportItem::create([
                            'waste_id'     => $document->id,
                            'warehouse_id' => $warehouseId,
                            'item_id'      => $input['item_id'],
                            'item_unit_id' => $input['item_unit_id'],
                            'quantity'     => $figures['left'],
                        ]);

                        $ops[] = InventoryService::reserveOut($warehouseId, $input['item_id'], $input['item_unit_id'], $figures['left']);
                        $ops[] = InventoryService::confirmOut($warehouseId, $input['item_id'], $input['item_unit_id'], $figures['left'], $figures['left']);
                    }

                    // The movement (if any) is the document's: recorded against no warehouse.
                    $this->storeRunQuantity($event, $log, 'input_leftover', [
                        'recipe_stage_id' => $stageId,
                        'stage_name'      => $input['stage_name'],
                        'item_type_id'    => $input['item_type_id'],
                        'item_id'         => $input['item_id'],
                        'item_unit_id'    => $input['item_unit_id'],
                        'item_name'       => $input['item_name'],
                        'unit_name'       => $input['unit_name'],
                        'actual_quantity' => $figures['left'],
                        'disposition'     => $input['left_action'] === 'waste' && $document ? 'waste' : 'keep',
                        'remaining_document_type' => $document ? $document::class : null,
                        'remaining_document_id'   => $document?->id,
                    ]);
                }
            }

            // ── Stations: what each made, its rejects and leftovers.
            foreach ($this->runEnd['stages'] as $key => $form) {
                $stage   = $stages->get($form['id']);
                $figures = $result['stages'][$key];

                $this->storeRunQuantity($event, $log, 'stage_output', [
                    'recipe_stage_id'  => $stage->id,
                    'stage_name'       => $stage->name,
                    'item_name'        => $stage->name,
                    'unit_name'        => $form['count_unit'],
                    'planned_quantity' => round($batches * (float) $form['count_per_batch'], 4),
                    'actual_quantity'  => $figures['made'],
                    // A figure worked back from the next station, not counted.
                    'reason'           => $figures['made_derived'] ? 'worked out' : 'counted',
                ]);

                if ($figures['rejects'] > 0) {
                    $rework   = $form['reject_action'] === 'rework' && $stage->canRework();
                    $document = null;

                    if ($rework) {
                        $reworkQty   = round($figures['rejects'] * (float) $stage->rework_factor, 4);
                        $warehouseId = $this->lineRuns->warehouseFor($line, $stage->rework_item_type_id ? (int) $stage->rework_item_type_id : null);

                        if ($warehouseId && $reworkQty > 0) {
                            $document = $documents["rework:{$warehouseId}"] ??= StockIn::create([
                                'warehouse_id' => $warehouseId,
                                'status'       => 'approved',
                                'notes'        => $this->runDocumentNote($event, 'rework', $endedAt),
                            ]);

                            ReportItem::create([
                                'stock_in_id'  => $document->id,
                                'warehouse_id' => $warehouseId,
                                'item_id'      => $stage->rework_item_id,
                                'item_unit_id' => $stage->rework_item_unit_id,
                                'quantity'     => $reworkQty,
                            ]);

                            $ops[] = InventoryService::reserveIn($warehouseId, $stage->rework_item_id, $stage->rework_item_unit_id, $reworkQty);
                            $ops[] = InventoryService::confirmIn($warehouseId, $stage->rework_item_id, $stage->rework_item_unit_id, $reworkQty, $reworkQty);
                        }
                    }

                    // Rejects were never stock — their dough is already in what
                    // was used — so a write-off moves nothing; a rework is its
                    // Stock In's movement.
                    $this->storeRunQuantity($event, $log, 'reject', [
                        'recipe_stage_id' => $stage->id,
                        'stage_name'      => $stage->name,
                        'item_name'       => $stage->name,
                        'unit_name'       => $form['count_unit'],
                        'actual_quantity' => $figures['rejects'],
                        'reason'          => $form['reject_reason'] ?: null,
                        'disposition'     => $document ? 'rework' : 'waste',
                        'remaining_document_type' => $document ? $document::class : null,
                        'remaining_document_id'   => $document?->id,
                    ]);
                }

                if (!empty($form['has_leftover']) && $figures['leftover'] > 0 && $fgId) {
                    [$itemName, $unitName] = $this->lineRuns->itemAndUnitNames((int) $recipe->item_id, (int) $stage->output_item_unit_id);

                    $this->storeRunQuantity($event, $log, 'stage_leftover', [
                        'recipe_stage_id' => $stage->id,
                        'stage_name'      => $stage->name,
                        'item_type_id'    => $recipe->item_type_id,
                        'item_id'         => $recipe->item_id,
                        'item_unit_id'    => $stage->output_item_unit_id,
                        'item_name'       => $itemName,
                        'unit_name'       => $unitName,
                        'actual_quantity' => $figures['leftover'],
                        'disposition'     => 'stock',
                        'warehouse_id'    => $fgId,
                        'confirmed_at'    => $endedAt,
                    ]);

                    $ops[] = InventoryService::reserveProcess($fgId, $recipe->item_id, $stage->output_item_unit_id, $figures['leftover']);
                    $ops[] = InventoryService::confirmProcessIn($fgId, $recipe->item_id, $stage->output_item_unit_id, $figures['leftover'], $figures['leftover']);
                }
            }

            // ── What goes into stock: the last station's good output.
            $output = round($result['output'], 4);
            [$itemName, $unitName] = $this->lineRuns->itemAndUnitNames((int) $recipe->item_id, (int) $recipe->item_unit_id);

            $this->storeRunQuantity($event, $log, 'output', [
                'item_type_id'     => $recipe->item_type_id,
                'item_id'          => $recipe->item_id,
                'item_unit_id'     => $recipe->item_unit_id,
                'item_name'        => $itemName,
                'unit_name'        => $unitName,
                'planned_quantity' => round($batches * (float) $recipe->quantity_per_batch, 4),
                'actual_quantity'  => $output,
                'warehouse_id'     => $fgId,
                'confirmed_at'     => $endedAt,
            ]);

            if ($fgId && $output > 0) {
                $ops[] = InventoryService::reserveProcess($fgId, $recipe->item_id, $recipe->item_unit_id, $output);
                $ops[] = InventoryService::confirmProcessIn($fgId, $recipe->item_id, $recipe->item_unit_id, $output, $output);
            }

            $event->update(['needs_review' => (bool) $result['flagged']]);

            $this->inventory->applyOrFail($ops);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('swal:error', [
                'title' => 'Cannot end the run',
                'text'  => $e->getMessage(),
            ]);

            return;
        }

        $this->closeActionModal();
    }

    protected function storeRunQuantity(Event $event, $log, string $source, array $data): EventQuantity
    {
        return EventQuantity::create(array_merge([
            'event_id'            => $event->id,
            'event_status_log_id' => $log->id,
            'source'              => $source,
        ], $data));
    }

    /** Says on an auto-created document which run it came from. */
    protected function runDocumentNote(Event $event, string $kind, Carbon $endedAt): string
    {
        $planDate = $event->plan?->date ? Carbon::parse($event->plan->date)->format('d M Y') : null;

        return sprintf(
            'Auto-created from production run #%d "%s"%s%s, ended %s by %s. %s',
            $event->id,
            $event->recipe?->name ?? $event->name,
            $event->eventType?->name ? " ({$event->eventType->name})" : '',
            $planDate ? ", planned for {$planDate}" : '',
            $endedAt->format('d M Y H:i'),
            authUser()?->name ?: 'system',
            $kind === 'rework'
                ? 'Rejects reworked back into stock.'
                : 'Material left on the line at the end of the run, written off.'
        );
    }

    // ─── Plan mixes ──────────────────────────────────────────────────────────

    public function planMixes(int $eventId): void
    {
        authorizeRequest('production.event-create');

        $event = Event::with('eventType', 'recipe.stages', 'recipe.inputs', 'plan')
            ->where('plan_id', $this->plan->id)
            ->findOrFail($eventId);

        try {
            $result = DB::transaction(fn() => $this->lineRuns->planMixes($event, $this->durationService, $this->carryOverService));
        } catch (\Throwable $e) {
            $this->dispatch('swal:error', ['title' => 'Cannot plan the mixes', 'text' => $e->getMessage()]);

            return;
        }

        $this->dispatch('runNotice', message: count($result['created']) . ' mixes planned. ' . implode(' ', $result['notes']));

        // Show the run again, now with its mixes.
        $this->showEventDetails($event->id);
    }

    /** A run's planned mixes follow it when it's moved on the board. */
    protected function shiftPlannedMixes(Event $run, $oldFrom): void
    {
        if (!$run->from_time || (string) $oldFrom === (string) $run->from_time) {
            return;
        }

        $delta = Carbon::parse($run->from_time)->getTimestamp() - Carbon::parse($oldFrom)->getTimestamp();

        foreach ($run->feedingEvents()->whereNull('status')->whereNotNull('from_time')->get() as $mix) {
            $from = Carbon::parse($mix->from_time)->addSeconds($delta);
            $to   = $mix->to_time ? Carbon::parse($mix->to_time)->addSeconds($delta) : null;

            ['to_plan_id' => $toPlanId] = $this->carryOverService->carryOverLink($this->plan, $from->format('Y-m-d H:i:s'), $to?->format('Y-m-d H:i:s'));

            $mix->update([
                'from_time'  => $from->format('Y-m-d H:i:s'),
                'to_time'    => $to?->format('Y-m-d H:i:s'),
                'to_plan_id' => $toPlanId,
            ]);
        }
    }

    // ─── Details and history ─────────────────────────────────────────────────

    /** What the details modal shows of a run; null for any other event. */
    protected function runDetails(Event $event): ?array
    {
        $event->loadMissing('recipe.stages', 'recipe.inputs');

        if (!$this->lineRuns->isRun($event)) {
            return null;
        }

        $line   = $this->lineRuns->lineOf($event);
        $recipe = $event->recipe;
        $rate   = $line ? $this->lineRuns->lineRate($recipe, $line) : null;
        $mixes  = $event->feedingEvents()->with('eventType')->get();

        return [
            'line_name'      => $line?->name,
            'unit'           => $this->runUnit($recipe),
            'planned'        => $this->lineRuns->batchCount($event) * (float) $recipe->quantity_per_batch,
            'per_hour'       => ($rate['batches_per_hour'] ?? null) ? $rate['batches_per_hour'] * (float) $recipe->quantity_per_batch : null,
            'bottleneck'     => $rate['bottleneck'] ?? null,
            'stages'         => $rate['stages'] ?? $recipe->stages->map(fn(RecipeStage $stage) => ['stage' => $stage->name, 'station' => null, 'rate_label' => null, 'batches_per_hour' => null])->all(),
            'mixes'          => $mixes->map(fn(Event $mix) => [
                'id'     => $mix->id,
                'from'   => $mix->from_time ? Carbon::parse($mix->from_time)->format('H:i') : null,
                'to'     => $mix->to_time ? Carbon::parse($mix->to_time)->format('H:i') : null,
                'batch'  => $mix->batch_count,
                'status' => $mix->status ? (self::EVENT_STATUS_LABELS[$mix->status] ?? $mix->status) : 'Planned',
            ])->all(),
            'can_plan_mixes' => $line && $event->from_time && $event->status !== 'terminated'
                && !$mixes->contains(fn(Event $mix) => $mix->status),
            'counts'         => $this->countRows($event),
            'needs_review'   => (bool) $event->needs_review,
        ];
    }

    /** On a mix: the run it was planned for. */
    protected function feedsDetails(Event $mix): ?array
    {
        $run = Event::with('recipe')->find($mix->feeds_event_id);

        return $run ? [
            'id'   => $run->id,
            'name' => $run->recipe?->name ?? $run->name,
            'from' => $run->from_time ? Carbon::parse($run->from_time)->format('H:i') : null,
        ] : null;
    }

    /** What the history modal shows of an ended (or running) run. */
    protected function runHistory(Event $event): ?array
    {
        $event->loadMissing('recipe.stages');

        if (!$this->lineRuns->isRun($event)) {
            return null;
        }

        $quantities = EventQuantity::where('event_id', $event->id)
            ->whereIn('source', ['stage_output', 'reject', 'stage_leftover', 'input', 'input_leftover', 'output'])
            ->orderBy('id')
            ->get();

        return [
            'unit'         => $this->runUnit($event->recipe),
            'counts'       => $this->countRows($event),
            'summary'      => $this->lineRuns->summary($event),
            'needs_review' => (bool) $event->needs_review,
            'stages'       => $quantities->where('source', 'stage_output')->map(fn($q) => [
                'name'    => $q->stage_name,
                'unit'    => $q->unit_name,
                'made'    => (float) $q->actual_quantity,
                'counted' => $q->reason !== 'worked out',
                'rejects' => (float) $quantities->where('source', 'reject')->where('recipe_stage_id', $q->recipe_stage_id)->sum('actual_quantity'),
                'reject_reason' => $quantities->where('source', 'reject')->where('recipe_stage_id', $q->recipe_stage_id)->first()?->reason,
                'disposition'   => $quantities->where('source', 'reject')->where('recipe_stage_id', $q->recipe_stage_id)->first()?->disposition,
                'leftover' => (float) $quantities->where('source', 'stage_leftover')->where('recipe_stage_id', $q->recipe_stage_id)->sum('actual_quantity'),
            ])->values()->all(),
            'inputs'       => $quantities->where('source', 'input')->map(fn($q) => [
                'stage'    => $q->stage_name,
                'item'     => $q->item_name,
                'unit'     => $q->unit_name,
                'expected' => (float) $q->planned_quantity,
                'used'     => (float) $q->actual_quantity,
                'variance' => $q->percentage !== null ? (float) $q->percentage : null,
                'left'     => (float) $quantities->where('source', 'input_leftover')->where('item_id', $q->item_id)->sum('actual_quantity'),
            ])->values()->all(),
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** The unit a run's output (and its hourly count) is in, e.g. "48X40GR". */
    protected function runUnit(?Recipe $recipe): string
    {
        return $recipe ? ($this->lineRuns->unitName((int) $recipe->item_id, $recipe->item_unit_id) ?? 'units') : 'units';
    }

    protected function plainNumber($number): string
    {
        return rtrim(rtrim(number_format((float) $number, 4, '.', ''), '0'), '.') ?: '0';
    }
}
