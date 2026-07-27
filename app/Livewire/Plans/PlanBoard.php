<?php

namespace App\Livewire\Plans;

use App\Exports\PlanEventsExport;
use App\Models\Event;
use App\Models\EventPauseActivity;
use App\Models\EventQuantity;
use App\Models\EventStatusLog;
use App\Models\EventType;
use App\Models\Line;
use App\Models\Plan;
use App\Models\Preparation;
use App\Models\ProductionLine;
use App\Models\RecipeInput;
use App\Models\RecipeSideProduct;
use App\Services\ApiService;
use App\Services\PlanCarryOverService;
use App\Services\RecipeDurationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class PlanBoard extends Component
{
    public const EVENT_STATUS_LABELS = [
        'in_progress' => 'In Progress',
        'paused'      => 'Paused',
        'terminated'  => 'Ended',
    ];

    public const EVENT_TRANSITIONS = [
        'start'     => ['from' => [null], 'to' => 'in_progress'],
        'pause'     => ['from' => ['in_progress'], 'to' => 'paused'],
        'resume'    => ['from' => ['paused'], 'to' => 'in_progress'],
        'terminate' => ['from' => ['in_progress', 'paused'], 'to' => 'terminated'],
    ];

    public Plan $plan;
    public $productionLines = [];
    public $unplacedEvents = [];
    public ?string $factoryName = null;
    public ?array $selectedEvent = null;

    // ─── Status-action modal state ────────────────────────────────────────────
    public ?array $eventAction = null;        // ['action', 'event_id', 'type_name', …]
    public array $actionInputs = [];          // start: recipe input rows
    public array $actionOutputs = [];         // terminate: produced item rows
    public array $actionSideProducts = [];    // terminate: side product rows
    public ?string $actionNotes = null;       // start/end global notes
    public ?string $actionReason = null;      // pause/resume reason
    public ?string $actionTime = null;        // when the action happened (defaults to now)
    public array $pauseEventTypes = [];       // emergency events: dropdown options
    public array $pauseActivityRows = [];     // emergency events: rows being added
    public ?array $endingActivity = null;     // emergency event being ended: ['id', 'time', 'note']

    // ─── History modal state ──────────────────────────────────────────────────
    public ?array $eventHistory = null;

    /** Per-request cache of items fetched from the API (id => data). */
    protected array $itemCache = [];

    /** Per-request cache of item type names (id => name), lazily loaded. */
    protected ?array $itemTypeNames = null;

    #[Url(except: 'board')]
    public string $view = 'board';

    // Which board grid is shown: planned (editable), actual (real run times
    // with emergency segments) or downtime (emergency events only).
    #[Url(except: 'planned')]
    public string $boardMode = 'planned';

    protected Collection $allEvents;

    protected ApiService $api;
    protected RecipeDurationService $durationService;
    protected PlanCarryOverService $carryOverService;

    public function boot(ApiService $api, RecipeDurationService $durationService, PlanCarryOverService $carryOverService): void
    {
        $this->api              = $api;
        $this->durationService  = $durationService;
        $this->carryOverService = $carryOverService;
    }

    public function mount($id): void
    {
        $this->loadPlan($id);

        if (!in_array($this->view, ['board', 'list'], true)) {
            $this->view = 'board';
        }

        if (!in_array($this->boardMode, ['planned', 'actual', 'downtime'], true)) {
            $this->boardMode = 'planned';
        }
    }

    public function updatedView($value): void
    {
        if (!in_array($value, ['board', 'list'], true)) {
            $this->view = 'board';
        }
    }

    public function updatedBoardMode($value): void
    {
        if (!in_array($value, ['planned', 'actual', 'downtime'], true)) {
            $this->boardMode = 'planned';
        }
    }

    protected function loadPlan(int $id): void
    {
        $this->plan = Plan::findOrFail($id);

        $this->productionLines = ProductionLine::with('preparations.eventTypes', 'lines.eventTypes')
            ->where('factory_id', $this->plan->factory_id)
            ->orderBy('name')
            ->get();

        $this->factoryName = null;

        if ($this->plan->factory_id) {
            $warehouses = $this->api->get('/v1/warehouses', ['related_to_production' => true])['data'] ?? [];
            $this->factoryName = collect($warehouses)->firstWhere('id', $this->plan->factory_id)['name'] ?? null;
        }
    }

    // ─── Date navigation: jump to the sibling plan (same factory) for the
    //     previous/next day, if one exists ─────────────────────────────────────
    public function goToPreviousDay()
    {
        return $this->navigateToDate(-1);
    }

    public function goToNextDay()
    {
        return $this->navigateToDate(1);
    }

    protected function navigateToDate(int $dayOffset)
    {
        $targetDate = Carbon::parse($this->plan->date)->addDays($dayOffset);

        $targetPlan = Plan::where('factory_id', $this->plan->factory_id)
            ->whereDate('date', $targetDate->format('Y-m-d'))
            ->first();

        if (!$targetPlan) {
            $this->dispatch('swal:error', [
                'title' => 'No plan found',
                'text'  => 'There is no plan for ' . $targetDate->format('d F Y') . ($this->factoryName ? " at {$this->factoryName}" : '') . '.',
            ]);

            return;
        }

        return redirect()->route('plans.view', array_filter([
            'id'   => $targetPlan->id,
            'view' => $this->view !== 'board' ? $this->view : null,
        ]));
    }

    protected function loadEvents(): void
    {
        $events = Event::with('eventType', 'recipe', 'productionLine', 'placeable', 'statusLogs', 'pauseActivities')
            ->withExists(['pauseActivities as has_open_emergencies' => fn($q) => $q->whereNull('ended_at')])
            ->where('plan_id', $this->plan->id)
            ->orderBy('from_time')
            ->get();

        $this->unplacedEvents = $events->whereNull('placeable_id')->values();

        $this->allEvents = $events->concat($this->carryOverEvents());
    }

    /**
     * Placed events from earlier days (same factory) still running when
     * this board's day starts — they spill into this board from 00:00 up
     * to their end (or the whole day, for events spanning several days).
     */
    protected function carryOverEvents(): Collection
    {
        if (!$this->plan->factory_id) {
            return collect();
        }

        $dayStart = Carbon::parse($this->plan->date)->startOfDay();

        return Event::with('eventType', 'recipe', 'productionLine', 'placeable', 'statusLogs', 'pauseActivities')
            ->where('plan_id', '!=', $this->plan->id)
            ->whereNotNull('placeable_id')
            ->whereNotNull('from_time')
            ->whereNotNull('to_time')
            ->where('from_time', '<', $dayStart)
            ->where('to_time', '>', $dayStart)
            ->whereHas('plan', fn($q) => $q->where('factory_id', $this->plan->factory_id))
            ->get()
            ->each(fn(Event $e) => $e->setAttribute('is_carry_over', true));
    }

    protected function adjacentPlan(string $direction): ?Plan
    {
        $query = Plan::where('factory_id', $this->plan->factory_id);

        return $direction === 'prev'
            ? $query->whereDate('date', '<', $this->plan->date)->orderByDesc('date')->first()
            : $query->whereDate('date', '>', $this->plan->date)->orderBy('date')->first();
    }

    // ─── List view ────────────────────────────────────────────────────────────

    /**
     * Every event on this board (placed, unplaced and carry-over) in
     * chronological order, for the list view.
     */
    public function listRows(): Collection
    {
        return $this->allEvents
            ->sortBy(fn(Event $e) => [$e->is_carry_over ? 0 : 1, $this->eventFromHour($e)])
            ->values();
    }

    /**
     * Actual run times for the list view, from the status logs: the first
     * "start" and the last "end" (terminate). Delay is how many minutes the
     * actual start ran past the planned from_time (positive = late, negative
     * = early, null = not started or not scheduled).
     */
    public function eventActualTimes(Event $event): array
    {
        $startLog = $event->statusLogs->firstWhere('action', 'start');
        $endLog   = $event->statusLogs->where('action', 'terminate')->last();

        $actualStart = $startLog ? $this->logHappenedAt($startLog) : null;
        $actualEnd   = $endLog ? $this->logHappenedAt($endLog) : null;

        $delay = null;
        if ($actualStart && $event->from_time) {
            $delay = (int) round(
                ($actualStart->getTimestamp() - Carbon::parse($event->from_time)->getTimestamp()) / 60
            );
        }

        return [
            'start'   => $actualStart,
            'end'     => $actualEnd,
            'running' => $startLog && !$endLog,
            'delay'   => $delay,
        ];
    }

    // ─── Calendar layout ──────────────────────────────────────────────────────

    public function laneRows(): array
    {
        $rows = [];

        foreach ($this->productionLines as $pl) {
            $laneRows = [];
            foreach ($this->laneList($pl) as $lane) {
                $laneRows[] = [
                    'lane'   => $lane,
                    'layout' => $this->layoutTracks(
                        $this->laneEvents($pl->id, $lane)
                            ->sortBy(fn(Event $e) => $this->eventFromHour($e))
                            ->values()
                    ),
                ];
            }

            $rows[] = [
                'production_line' => $pl,
                'lanes'           => $laneRows,
            ];
        }

        return $rows;
    }

    protected function laneList($productionLine): Collection
    {
        return collect($productionLine->preparations->map(fn($p) => ['type' => 'preparation', 'id' => $p->id, 'name' => $p->name]))
            ->concat($productionLine->lines->map(fn($l) => ['type' => 'line', 'id' => $l->id, 'name' => $l->name]))
            ->values();
    }

    protected function laneEvents(int $productionLineId, array $lane): Collection
    {
        return $this->allEvents
            ->where('production_line_id', $productionLineId)
            ->where('placeable_type', $this->classForAlias($lane['type']))
            ->where('placeable_id', $lane['id']);
    }

    protected function layoutTracks(Collection $events): array
    {
        $items     = [];
        $trackEnds = [];

        foreach ($events as $event) {
            $fromHour  = $this->eventFromHour($event);
            $spanHours = $this->eventSpanHours($event);
            $end       = $fromHour + $spanHours;

            $track = null;
            foreach ($trackEnds as $i => $endHour) {
                if ($fromHour >= $endHour - 0.001) {
                    $track          = $i;
                    $trackEnds[$i]  = $end;
                    break;
                }
            }
            if ($track === null) {
                $trackEnds[] = $end;
                $track       = count($trackEnds) - 1;
            }

            $items[] = [
                'event'     => $event,
                'track'     => $track,
                'fromHour'  => $fromHour,
                'spanHours' => $spanHours,
            ];
        }

        return ['tracks' => max(count($trackEnds), 1), 'items' => $items];
    }

    protected function eventFromHour(Event $event): float
    {
        if (!$event->from_time) {
            return 0.0;
        }

        $dayStart = Carbon::parse($this->plan->date)->startOfDay()->getTimestamp();
        $from     = Carbon::parse($event->from_time)->getTimestamp();

        // Clamp into this board's 24h window — carry-overs start before it
        // and render from midnight.
        return max(0, min(($from - $dayStart) / 60, 24 * 60)) / 60;
    }

    protected function eventSpanHours(Event $event): float
    {
        if ($event->from_time && $event->to_time) {
            $dayStart = Carbon::parse($this->plan->date)->startOfDay()->getTimestamp();
            $dayEnd   = $dayStart + 24 * 60 * 60;

            // Only the part inside this board's day is drawn; the rest
            // renders on the neighbouring days' boards.
            $from = max(Carbon::parse($event->from_time)->getTimestamp(), $dayStart);
            $to   = min(Carbon::parse($event->to_time)->getTimestamp(), $dayEnd);

            return max(($to - $from) / 60, 15) / 60;
        }

        $minutes = $event->calculated_duration ?: $event->planned_duration;

        return $minutes ? max((float) $minutes, 15) / 60 : 1.0;
    }

    // ─── Actual & Downtime grids ──────────────────────────────────────────────

    /**
     * Lane rows for the read-only grids: 'actual' shows events at their real
     * run times (start → end status logs) with emergency events as red
     * segments; 'downtime' shows only the emergency events.
     */
    public function readonlyLaneRows(string $mode): array
    {
        $rows = [];

        foreach ($this->productionLines as $pl) {
            $laneRows = [];
            foreach ($this->laneList($pl) as $lane) {
                $events = $this->laneEvents($pl->id, $lane);

                $items = $mode === 'actual'
                    ? $this->actualItems($events)
                    : $this->downtimeItems($events);

                $laneRows[] = [
                    'lane'   => $lane,
                    'layout' => $this->packTracks($items),
                ];
            }

            $rows[] = [
                'production_line' => $pl,
                'lanes'           => $laneRows,
            ];
        }

        return $rows;
    }

    /**
     * The real run window of an event: first start log → last end log, or
     * "still running" (now) when it hasn't been ended yet.
     */
    protected function actualWindow(Event $event): ?array
    {
        $startLog = $event->statusLogs->firstWhere('action', 'start');

        if (!$startLog) {
            return null;
        }

        $endLog  = $event->statusLogs->where('action', 'terminate')->last();
        $running = !$endLog;

        $start = $this->logHappenedAt($startLog);
        $end   = $endLog ? $this->logHappenedAt($endLog) : now();

        if ($end->lte($start)) {
            $end = $start->copy()->addMinutes(15);
        }

        return [$start, $end, $running];
    }

    protected function actualItems(Collection $events): array
    {
        $items = [];

        foreach ($events as $event) {
            $window = $this->actualWindow($event);

            if (!$window) {
                continue;
            }

            [$start, $end, $running] = $window;

            $hours = $this->hoursWindow($start, $end);

            if (!$hours) {
                continue;
            }

            [$fromHour, $spanHours] = $hours;

            $segments       = [];
            $emergencyCount = 0;

            foreach ($event->pauseActivities as $activity) {
                $segStart = $activity->happened_at ? Carbon::parse($activity->happened_at) : $activity->created_at;
                $segEnd   = $activity->ended_at ? Carbon::parse($activity->ended_at) : now();

                $segHours = $this->hoursWindow($segStart, $segEnd);

                if (!$segHours) {
                    continue;
                }

                [$segFromHour, $segSpanHours] = $segHours;

                // Position relative to the card, clamped inside it.
                $left  = ($segFromHour - $fromHour) / $spanHours * 100;
                $width = $segSpanHours / $spanHours * 100;

                if ($left >= 100 || $left + $width <= 0) {
                    continue;
                }

                $left  = max($left, 0);
                $width = min($width, 100 - $left);

                $typeName = $activity->event_type_name ?: ($activity->eventType?->name ?? 'Emergency');

                $segments[] = [
                    'left'  => round($left, 2),
                    'width' => round(max($width, 1), 2),
                    'title' => $typeName . ': ' . $segStart->format('H:i') . ' – ' . ($activity->ended_at ? $segEnd->format('H:i') : 'ongoing'),
                ];
                $emergencyCount++;
            }

            $items[] = [
                'key'       => 'actual-' . $event->id . ($event->is_carry_over ?? false ? '-carry' : ''),
                'event_id'  => $event->id,
                'color'     => $event->eventType?->color ?? '#818cf8',
                'label'     => $event->eventType?->name ?? 'No type',
                'sub'       => $start->format('H:i') . ' – ' . ($running ? 'now' : $end->format('H:i')),
                'title'     => ($event->eventType?->name ?? 'No type')
                    . ' — actual ' . $start->format('d M H:i') . ' – ' . ($running ? 'still running' : $end->format('d M H:i'))
                    . ($emergencyCount ? " · {$emergencyCount} emergency event" . ($emergencyCount > 1 ? 's' : '') : ''),
                'running'   => $running,
                'fromHour'  => $fromHour,
                'spanHours' => $spanHours,
                'segments'  => $segments,
            ];
        }

        return $items;
    }

    protected function downtimeItems(Collection $events): array
    {
        $items = [];

        foreach ($events as $event) {
            foreach ($event->pauseActivities as $activity) {
                $start   = $activity->happened_at ? Carbon::parse($activity->happened_at) : $activity->created_at;
                $ongoing = !$activity->ended_at;
                $end     = $activity->ended_at ? Carbon::parse($activity->ended_at) : now();

                $hours = $this->hoursWindow($start, $end);

                if (!$hours) {
                    continue;
                }

                [$fromHour, $spanHours] = $hours;

                $typeName = $activity->event_type_name ?: ($activity->eventType?->name ?? 'Emergency');

                $items[] = [
                    'key'       => 'downtime-' . $activity->id,
                    'event_id'  => $event->id,
                    'color'     => '#dc3545',
                    'label'     => $typeName,
                    'sub'       => $start->format('H:i') . ' – ' . ($ongoing ? 'ongoing' : $end->format('H:i')),
                    'title'     => $typeName . ' — ' . ($event->eventType?->name ?? $event->name)
                        . ' · ' . $start->format('d M H:i') . ' – ' . ($ongoing ? 'ongoing' : $end->format('d M H:i'))
                        . ($activity->end_note ? ' · ' . $activity->end_note : ''),
                    'running'   => $ongoing,
                    'fromHour'  => $fromHour,
                    'spanHours' => $spanHours,
                    'segments'  => [],
                ];
            }
        }

        return $items;
    }

    /**
     * Converts a datetime window to board hours, clipped to this board's
     * 24h day; null when the window falls entirely outside it.
     */
    protected function hoursWindow($from, $to): ?array
    {
        $dayStart = Carbon::parse($this->plan->date)->startOfDay()->getTimestamp();
        $dayEnd   = $dayStart + 24 * 60 * 60;

        $fromTs = max(Carbon::parse($from)->getTimestamp(), $dayStart);
        $toTs   = min(Carbon::parse($to)->getTimestamp(), $dayEnd);

        if ($toTs <= $fromTs) {
            return null;
        }

        return [
            ($fromTs - $dayStart) / 3600,
            max(($toTs - $fromTs) / 3600, 0.25),
        ];
    }

    /**
     * Same track packing as layoutTracks, for pre-computed item arrays.
     */
    protected function packTracks(array $items): array
    {
        usort($items, fn($a, $b) => $a['fromHour'] <=> $b['fromHour']);

        $trackEnds = [];

        foreach ($items as &$item) {
            $end   = $item['fromHour'] + $item['spanHours'];
            $track = null;

            foreach ($trackEnds as $i => $endHour) {
                if ($item['fromHour'] >= $endHour - 0.001) {
                    $track         = $i;
                    $trackEnds[$i] = $end;
                    break;
                }
            }

            if ($track === null) {
                $trackEnds[] = $end;
                $track       = count($trackEnds) - 1;
            }

            $item['track'] = $track;
        }
        unset($item);

        return ['tracks' => max(count($trackEnds), 1), 'items' => $items];
    }

    public function dropEvent(int $eventId, int $productionLineId, string $placeableAlias, int $placeableId, int $slot): void
    {
        $event = Event::with('eventType')
            ->where('plan_id', $this->plan->id)
            ->findOrFail($eventId);

        $placeableClass = $this->classForAlias($placeableAlias);

        if (!$placeableClass) {
            return;
        }

        $target = $placeableClass::find($placeableId);

        if (!$target || !$event->event_type_id || !$target->eventTypes()->where('event_types.id', $event->event_type_id)->exists()) {
            $this->dispatch('dropRejected', message: "\"{$event->eventType?->name}\" is not an allowed event type on \"" . ($target->name ?? 'this lane') . '".');

            return;
        }

        // The board is divided into 15-minute slots (96 per day); the drop
        // reports which slot the card landed on.
        $slot    = max(0, min(95, $slot));
        $newFrom = Carbon::parse($this->plan->date)->startOfDay()
            ->addMinutes($slot * 15)
            ->format('Y-m-d H:i:s');

        $laneChanged = (int) $event->production_line_id !== $productionLineId
            || $event->placeable_type !== $placeableClass
            || (int) $event->placeable_id !== $placeableId;

        $event->production_line_id = $productionLineId;
        $event->placeable_type     = $placeableClass;
        $event->placeable_id       = $placeableId;

        $hasRecipe = (bool) $event->eventType?->has_recipe;

        if ($hasRecipe && $laneChanged) {
            $event->calculated_duration = ($event->recipe_id && $event->item_id)
                ? $this->durationService->compute($event, $placeableClass, $placeableId)
                : null;
        }

        $event->from_time = $newFrom;

        $durationMinutes = $hasRecipe ? $event->calculated_duration : $event->planned_duration;
        $event->to_time   = $durationMinutes
            ? Carbon::parse($newFrom)->addMinutes((int) $durationMinutes)->format('Y-m-d H:i:s')
            : null;

        // Runs past midnight → link the event to the plan of the day it
        // ends on, creating that plan (and month plan) when missing.
        // Moved back before midnight → the link is cleared.
        ['to_plan_id' => $toPlanId, 'created' => $createdPlans] =
            $this->carryOverService->carryOverLink($this->plan, $event->from_time, $event->to_time);

        $event->to_plan_id = $toPlanId;
        $event->save();

        if ($message = $this->carryOverService->describeCreatedPlans($createdPlans)) {
            $this->dispatch('carryOverPlanCreated', message: "This event runs past midnight — {$message}");
        }
    }

    public function unplaceEvent(int $eventId): void
    {
        $event = Event::where('plan_id', $this->plan->id)->findOrFail($eventId);

        $event->production_line_id  = null;
        $event->placeable_type      = null;
        $event->placeable_id        = null;
        $event->calculated_duration = null;
        $event->from_time           = null;
        $event->to_time             = null;
        $event->to_plan_id          = null;
        $event->save();
    }

    // ─── Event lifecycle ──────────────────────────────────────────────────────

    public function updateEventStatus(int $eventId, string $action): void
    {
        authorizeRequest('production.event-create');

        if (!isset(self::EVENT_TRANSITIONS[$action])) {
            return;
        }

        $event = Event::with('eventType', 'recipe.inputs', 'recipe.sideProducts')
            ->where('plan_id', $this->plan->id)
            ->findOrFail($eventId);

        if (!$this->assertTransitionAllowed($event, $action)) {
            return;
        }

        // Resuming or ending requires every emergency event to be closed first.
        if (in_array($action, ['resume', 'terminate'], true) && $this->blockedByOpenEmergencies($event, $action)) {
            return;
        }

        $this->resetActionForm();

        $hasRecipe = (bool) $event->eventType?->has_recipe && $event->recipe;

        // Start shows the used-items table for recipe events (recipe inputs)
        // and for non-recipe events that have item types configured (e.g.
        // cleaning consuming detergent). Terminate stays recipe-only.
        $needsStartModal     = $action === 'start' && ($hasRecipe || !empty($event->eventType?->item_type_ids));
        $needsTerminateModal = $action === 'terminate' && $hasRecipe;

        // Pause/resume always collect a reason.
        if ($action === 'pause' || $action === 'resume' || $needsStartModal || $needsTerminateModal) {
            match ($action) {
                'start'     => $this->prepareStartModal($event),
                'terminate' => $this->prepareTerminateModal($event),
                'pause'     => $this->preparePauseModal($event),
                'resume'    => $this->prepareResumeModal($event),
            };

            $this->dispatch('openEventActionModal');

            return;
        }

        // Nothing extra to record — apply and log immediately.
        DB::transaction(fn() => $this->applyStatusChange($event, $action));
    }

    protected function assertTransitionAllowed(Event $event, string $action): bool
    {
        $current = $event->status ?: null;

        if (in_array($current, self::EVENT_TRANSITIONS[$action]['from'], true)) {
            return true;
        }

        $label = $current ? (self::EVENT_STATUS_LABELS[$current] ?? $current) : 'Planned';
        $verb  = $action === 'terminate' ? 'end' : $action;

        $this->dispatch('swal:error', [
            'title' => 'Action not allowed',
            'text'  => "You can't {$verb} an event while it is \"{$label}\".",
        ]);

        return false;
    }

    protected function openEmergencyCount(int $eventId): int
    {
        return EventPauseActivity::where('event_id', $eventId)
            ->whereNull('ended_at')
            ->count();
    }

    /**
     * Resume/end are blocked while emergency events are still ongoing, so
     * every one of them gets an end time and note.
     */
    protected function blockedByOpenEmergencies(Event $event, string $action): bool
    {
        $count = $this->openEmergencyCount($event->id);

        if ($count === 0) {
            return false;
        }

        $verb   = $action === 'terminate' ? 'ending' : 'resuming';
        $plural = $count > 1;

        $this->dispatch('swal:error', [
            'title' => 'Ongoing emergency events',
            'text'  => "This event has {$count} ongoing emergency event" . ($plural ? 's' : '')
                . '. Use the "Emergency" button to end ' . ($plural ? 'them' : 'it')
                . " before {$verb} the event.",
        ]);

        return true;
    }

    protected function resetActionForm(): void
    {
        $this->eventAction        = null;
        $this->actionInputs       = [];
        $this->actionOutputs      = [];
        $this->actionSideProducts = [];
        $this->actionNotes        = null;
        $this->actionReason       = null;
        $this->actionTime         = now()->format('Y-m-d\TH:i');
        $this->pauseEventTypes    = [];
        $this->pauseActivityRows  = [];
        $this->endingActivity     = null;

        $this->resetValidation();
    }

    protected function baseActionPayload(Event $event, string $action): array
    {
        return [
            'action'     => $action,
            'event_id'   => $event->id,
            'event_name' => $event->name,
            'type_name'  => $event->eventType?->name ?? 'No type',
            'color'      => $event->eventType?->color ?? '#818cf8',
        ];
    }

    protected function prepareStartModal(Event $event): void
    {
        $hasRecipe = (bool) $event->eventType?->has_recipe && $event->recipe;

        if ($hasRecipe) {
            // Recipe events: the recipe's inputs, with their planned quantity.
            $this->actionInputs = $event->recipe->inputs
                ->map(fn(RecipeInput $input) => $this->quantityRow(
                    $input->item_type_id,
                    $input->item_id,
                    $input->item_unit_id,
                    (float) $input->quantity,
                    recipeInputId: $input->id,
                ))
                ->values()
                ->all();
        } else {
            // Non-recipe events: every item of the event type's item types —
            // no planned quantity, so the Original Qty column is hidden.
            $this->actionInputs = $this->itemTypeQuantityRows($event->eventType);
        }

        $this->eventAction = array_merge($this->baseActionPayload($event, 'start'), [
            'has_recipe' => $hasRecipe,
        ]);
    }

    /**
     * Quantity rows for every item belonging to an event type's item types,
     * defaulting each to its basic unit. Used for non-recipe start events.
     */
    protected function itemTypeQuantityRows(?EventType $eventType): array
    {
        $rows = [];

        foreach ($eventType?->item_type_ids ?? [] as $itemTypeId) {
            $typeName = $this->itemTypeName((int) $itemTypeId);

            foreach ($this->itemsForItemType((int) $itemTypeId) as $item) {
                $units = $this->unitsForItem($item);
                $basic = collect($units)->firstWhere('basic', true) ?: ($units[0] ?? null);

                $unitLabel = $basic
                    ? ($basic['name'] ?? '') . (!empty($basic['symbol']) ? " ({$basic['symbol']})" : '')
                    : null;

                $rows[] = [
                    'recipe_input_id'        => null,
                    'recipe_side_product_id' => null,
                    'item_type_id'           => (int) $itemTypeId,
                    'item_type_name'         => $typeName ?: 'Other',
                    'item_id'                => $item['id'] ?? null,
                    'item_unit_id'           => $basic['id'] ?? null,
                    'item_name'              => $item['name'] ?? null,
                    'unit_name'              => $unitLabel,
                    'planned_quantity'       => null,
                    'actual_quantity'        => null,
                    'percentage'             => null,
                ];
            }
        }

        return $rows;
    }

    protected function prepareTerminateModal(Event $event): void
    {
        $recipe = $event->recipe;

        $this->actionOutputs = [$this->quantityRow(
            $recipe->item_type_id,
            $event->item_id ?: $recipe->item_id,
            $recipe->item_unit_id,
            (float) ($recipe->quantity_per_batch ?? 0),
        )];

        $this->actionSideProducts = $recipe->sideProducts
            ->map(fn(RecipeSideProduct $side) => $this->quantityRow(
                $side->item_type_id,
                $side->item_id,
                $side->item_unit_id,
                (float) $side->quantity,
                recipeSideProductId: $side->id,
            ))
            ->values()
            ->all();

        $this->eventAction = $this->baseActionPayload($event, 'terminate');
    }

    protected function preparePauseModal(Event $event): void
    {
        $this->eventAction = $this->baseActionPayload($event, 'pause');
    }

    protected function prepareResumeModal(Event $event): void
    {
        $pauseLog = $this->latestPauseLog($event->id);
        $pausedAt = $pauseLog ? $this->logHappenedAt($pauseLog) : null;

        // Emergency events of this pause; for pauses recorded before status
        // logging existed (no pause log), fall back to all of the event's.
        $activities = $pauseLog
            ? $this->activitiesFor($pauseLog)
            : $this->eventActivities($event->id);

        $expectedTotal = collect($activities)->sum(fn($a) => (int) ($a['expected_duration'] ?? 0)) ?: null;

        $this->eventAction = array_merge($this->baseActionPayload($event, 'resume'), [
            'activities'        => $activities,
            'pause_reason'      => $pauseLog?->reason,
            'expected_duration' => $expectedTotal,
            'paused_at'         => $pausedAt?->format('d M Y H:i'),
            'paused_at_raw'     => $pausedAt?->format('Y-m-d H:i:s'),
            'actual_duration'   => $pausedAt ? $this->minutesBetween($pausedAt, now()) : null,
        ]);
    }

    /**
     * When a status change happened: the user-entered time, falling back to
     * the row's insert time for logs recorded before the time field existed.
     */
    protected function logHappenedAt(EventStatusLog $log): Carbon
    {
        return $log->happened_at ? Carbon::parse($log->happened_at) : $log->created_at;
    }

    protected function latestPauseLog(int $eventId): ?EventStatusLog
    {
        return EventStatusLog::where('event_id', $eventId)
            ->where('action', 'pause')
            ->latest('id')
            ->first();
    }

    /**
     * Activities (Cleaning, Maintenance, …) recorded under a pause log.
     */
    protected function activitiesFor(?EventStatusLog $pauseLog): array
    {
        if (!$pauseLog) {
            return [];
        }

        return $pauseLog->pauseActivities()
            ->with('eventType', 'quantities')
            ->orderBy('id')
            ->get()
            ->map(fn(EventPauseActivity $activity) => $this->activityRow($activity))
            ->all();
    }

    /**
     * Every activity ever recorded for an event, across all its pauses.
     */
    protected function eventActivities(int $eventId): array
    {
        return EventPauseActivity::with('eventType', 'quantities')
            ->where('event_id', $eventId)
            ->orderBy('id')
            ->get()
            ->map(fn(EventPauseActivity $activity) => $this->activityRow($activity))
            ->all();
    }

    protected function activityRow(EventPauseActivity $activity): array
    {
        $startedAt = $activity->happened_at ? Carbon::parse($activity->happened_at) : $activity->created_at;

        return [
            'id'                => $activity->id,
            'type_name'         => $activity->event_type_name ?: ($activity->eventType?->name ?? '—'),
            'reason'            => $activity->reason,
            'expected_duration' => $activity->expected_duration,
            'at'                => $startedAt->format('d M Y H:i'),
            'by'                => $activity->created_by_name,
            'ended_at'          => $activity->ended_at ? Carbon::parse($activity->ended_at)->format('d M Y H:i') : null,
            'end_note'          => $activity->end_note,
            'actual_duration'   => $activity->ended_at ? $this->minutesBetween($startedAt, $activity->ended_at) : null,
            'items'             => $activity->quantities->map(fn(EventQuantity $qty) => [
                'item_name' => $qty->item_name ?: ($qty->item_id ? "Item #{$qty->item_id}" : '—'),
                'unit_name' => $qty->unit_name,
                'quantity'  => (float) $qty->actual_quantity,
            ])->all(),
        ];
    }

    protected function minutesBetween($from, $to): int
    {
        return max(0, (int) round(Carbon::parse($from)->diffInMinutes(Carbon::parse($to))));
    }

    /**
     * The user-entered action time, falling back to now.
     */
    protected function parsedActionTime(): Carbon
    {
        return $this->actionTime ? Carbon::parse($this->actionTime) : now();
    }

    /**
     * One row of the quantity tables shown in the start/terminate modals.
     * Item and unit names come from the items API (cached per request).
     */
    protected function quantityRow(?int $itemTypeId, ?int $itemId, ?int $itemUnitId, float $planned, ?int $recipeInputId = null, ?int $recipeSideProductId = null): array
    {
        [$itemName, $unitName] = $this->itemAndUnitNames($itemId, $itemUnitId);

        return [
            'recipe_input_id'        => $recipeInputId,
            'recipe_side_product_id' => $recipeSideProductId,
            'item_type_id'           => $itemTypeId,
            'item_type_name'         => $this->itemTypeName($itemTypeId),
            'item_id'                => $itemId,
            'item_unit_id'           => $itemUnitId,
            'item_name'              => $itemName,
            'unit_name'              => $unitName,
            'planned_quantity'       => $planned,
            'actual_quantity'        => null,
            'percentage'             => null,
        ];
    }

    /**
     * Resolve an item type id to its name (Raw Material, Packaging, …) from
     * the external item-types list, loaded once per request.
     */
    protected function itemTypeName(?int $itemTypeId): ?string
    {
        if (!$itemTypeId) {
            return null;
        }

        if ($this->itemTypeNames === null) {
            $this->itemTypeNames = collect($this->api->get('/v1/item-types')['data'] ?? [])
                ->pluck('name', 'id')
                ->all();
        }

        return $this->itemTypeNames[$itemTypeId] ?? null;
    }

    protected function itemAndUnitNames(?int $itemId, ?int $itemUnitId): array
    {
        if (!$itemId) {
            return [null, null];
        }

        if (!array_key_exists($itemId, $this->itemCache)) {
            $this->itemCache[$itemId] = $this->api->get("/v1/items/{$itemId}")['data'] ?? [];
        }

        $item = $this->itemCache[$itemId];
        $unit = collect($item['units'] ?? [])->firstWhere('id', $itemUnitId);

        $unitName = $unit
            ? ($unit['name'] ?? '') . (!empty($unit['symbol']) ? " ({$unit['symbol']})" : '')
            : null;

        return [$item['name'] ?? "Item #{$itemId}", $unitName ?: null];
    }

    /**
     * Live recalculations while typing in the action modals.
     */
    public function updated($property, $value): void
    {
        if (preg_match('/^(actionInputs|actionOutputs|actionSideProducts)\.(\d+)\.actual_quantity$/', $property, $matches)) {
            $rows  = $matches[1];
            $index = (int) $matches[2];

            $this->{$rows}[$index]['percentage'] = $this->percentageOf(
                $this->{$rows}[$index]['planned_quantity'] ?? null,
                $value
            );
        }

        // Picking an emergency event type loads the items it may consume.
        if (preg_match('/^pauseActivityRows\.(\d+)\.event_type_id$/', $property, $matches)) {
            $this->loadPauseActivityItems((int) $matches[1], $value);
        }

        // Changing the resume time re-computes the pause's actual duration.
        if ($property === 'actionTime'
            && ($this->eventAction['action'] ?? null) === 'resume'
            && !empty($this->eventAction['paused_at_raw'])
            && strtotime((string) $value) !== false
        ) {
            $this->eventAction['actual_duration'] = $this->minutesBetween($this->eventAction['paused_at_raw'], $value);
        }
    }

    /**
     * Variance between actual and planned: (actual − planned) / planned × 100.
     * Negative = under the original quantity, positive = over.
     */
    protected function percentageOf($planned, $actual): ?float
    {
        if ($actual === null || $actual === '' || !is_numeric($actual) || (float) $planned <= 0) {
            return null;
        }

        return round(((float) $actual - (float) $planned) / (float) $planned * 100, 2);
    }

    // ─── Pause activities (Cleaning, Maintenance, … while paused) ─────────────

    public function openPauseActivities(int $eventId): void
    {
        authorizeRequest('production.event-create');

        $event = Event::with('eventType')
            ->where('plan_id', $this->plan->id)
            ->findOrFail($eventId);

        $isPaused = $event->status === 'paused';

        // Open for paused events (view + add), and for any event that still
        // has ongoing emergency events, so they can always be ended.
        if (!$isPaused && $this->openEmergencyCount($event->id) === 0) {
            $this->dispatch('swal:error', [
                'title' => 'Action not allowed',
                'text'  => 'Emergency events can only be added while the event is paused.',
            ]);

            return;
        }

        $this->resetActionForm();

        $this->pauseEventTypes = EventType::where('has_recipe', false)
            ->orderBy('name')
            ->get(['id', 'name', 'duration'])
            ->map(fn(EventType $type) => [
                'id'       => $type->id,
                'name'     => $type->name,
                'duration' => $type->duration,
            ])
            ->all();

        $this->pauseActivityRows = [$this->blankPauseActivityRow()];

        $pauseLog = $this->latestPauseLog($event->id);

        $this->eventAction = array_merge($this->baseActionPayload($event, 'activities'), [
            'pause_log_id'        => $pauseLog?->id,
            'paused_at'           => $pauseLog ? $this->logHappenedAt($pauseLog)->format('d M Y H:i') : null,
            // New emergency events can only be added while paused; otherwise
            // the modal is just for ending the ongoing ones.
            'can_add'             => $isPaused,
            // All activities ever recorded for the event, so earlier pauses
            // (and legacy pauses without a log) stay visible.
            'existing_activities' => $this->eventActivities($event->id),
        ]);

        $this->dispatch('openEventActionModal');
    }

    public function addPauseActivityRow(): void
    {
        $this->pauseActivityRows[] = $this->blankPauseActivityRow();
    }

    protected function blankPauseActivityRow(): array
    {
        return ['event_type_id' => null, 'items' => []];
    }

    /**
     * Items that may be consumed during an emergency event, taken from the
     * item types configured on the selected event type.
     */
    protected function loadPauseActivityItems(int $index, $eventTypeId): void
    {
        if (!isset($this->pauseActivityRows[$index])) {
            return;
        }

        $eventType = $eventTypeId ? EventType::find((int) $eventTypeId) : null;

        if (!$eventType) {
            $this->pauseActivityRows[$index]['items'] = [];

            return;
        }

        $rows = [];

        foreach ($eventType->item_type_ids ?? [] as $itemTypeId) {
            $typeName = $this->itemTypeName((int) $itemTypeId);

            foreach ($this->itemsForItemType((int) $itemTypeId) as $item) {
                $units = $this->unitsForItem($item);
                $basic = collect($units)->firstWhere('basic', true) ?: ($units[0] ?? null);

                $rows[] = [
                    'item_type_id'   => (int) $itemTypeId,
                    'item_type_name' => $typeName ?: 'Other',
                    'item_id'        => $item['id'] ?? null,
                    'item_name'      => $item['name'] ?? null,
                    'units'          => collect($units)->map(fn($u) => [
                        'id'    => $u['id'] ?? null,
                        'label' => ($u['name'] ?? '') . (!empty($u['symbol']) ? " ({$u['symbol']})" : ''),
                    ])->values()->all(),
                    'item_unit_id'   => $basic['id'] ?? null,
                    'quantity'       => null,
                ];
            }
        }

        $this->pauseActivityRows[$index]['items'] = $rows;
    }

    protected function itemsForItemType(int $itemTypeId): array
    {
        $typeName = $this->itemTypeName($itemTypeId);

        if (!$typeName) {
            return [];
        }

        return $this->api->get('/v1/items', ['item_type' => $typeName, 'is_active' => true])['data'] ?? [];
    }

    /**
     * Units for an item — from the list payload when it carries them, else
     * from the item endpoint (cached per request).
     */
    protected function unitsForItem(array $item): array
    {
        if (!empty($item['units']) && is_array($item['units'])) {
            return $item['units'];
        }

        $itemId = $item['id'] ?? null;

        if (!$itemId) {
            return [];
        }

        if (!array_key_exists($itemId, $this->itemCache)) {
            $this->itemCache[$itemId] = $this->api->get("/v1/items/{$itemId}")['data'] ?? [];
        }

        return $this->itemCache[$itemId]['units'] ?? [];
    }

    public function removePauseActivityRow(int $index): void
    {
        if (count($this->pauseActivityRows) <= 1) {
            return;
        }

        unset($this->pauseActivityRows[$index]);
        $this->pauseActivityRows = array_values($this->pauseActivityRows);
    }

    // ─── Ending an emergency event (time + note) ──────────────────────────────

    public function beginEndActivity(int $activityId): void
    {
        $this->endingActivity = [
            'id'   => $activityId,
            'time' => now()->format('Y-m-d\TH:i'),
            'note' => null,
        ];

        $this->resetValidation();
    }

    public function cancelEndActivity(): void
    {
        $this->endingActivity = null;
        $this->resetValidation();
    }

    public function submitEndActivity(): void
    {
        authorizeRequest('production.event-create');

        if (!$this->endingActivity) {
            return;
        }

        $this->validate(
            [
                'endingActivity.time' => 'required|date',
                'endingActivity.note' => 'nullable|string',
            ],
            ['endingActivity.time.required' => 'Please enter the end time.', 'endingActivity.time.date' => 'Please enter a valid end time.']
        );

        $activity = EventPauseActivity::whereHas('event', fn($q) => $q->where('plan_id', $this->plan->id))
            ->whereNull('ended_at')
            ->findOrFail($this->endingActivity['id']);

        $activity->update([
            'ended_at' => Carbon::parse($this->endingActivity['time'])->format('Y-m-d H:i:s'),
            'end_note' => $this->endingActivity['note'] ?: null,
        ]);

        $this->endingActivity = null;

        // Refresh the list shown in the open modal.
        if (($this->eventAction['action'] ?? null) === 'activities') {
            $this->eventAction['existing_activities'] = $this->eventActivities($this->eventAction['event_id']);
        }
    }

    protected function submitPauseActivities(Event $event): void
    {
        if ($event->status !== 'paused') {
            $this->dispatch('swal:error', [
                'title' => 'Action not allowed',
                'text'  => 'Emergency events can only be added while the event is paused.',
            ]);
            $this->closeActionModal();

            return;
        }

        $allowedIds = collect($this->pauseEventTypes)->pluck('id')->implode(',');

        $this->validate(
            [
                'actionReason'                      => 'required|string|max:1000',
                'pauseActivityRows'                 => 'required|array|min:1',
                'pauseActivityRows.*.event_type_id' => "required|in:{$allowedIds}",
                'pauseActivityRows.*.items.*.quantity' => 'nullable|numeric|min:0',
            ],
            [
                'actionReason.required'                      => 'Please enter a reason.',
                'pauseActivityRows.*.event_type_id.required' => 'Please select an event type.',
                'pauseActivityRows.*.event_type_id.in'       => 'Please select a valid event type.',
                'pauseActivityRows.*.items.*.quantity.numeric' => 'Quantity must be a number.',
            ]
        );

        $pauseLog = $this->latestPauseLog($event->id);

        DB::transaction(function () use ($event, $pauseLog) {
            foreach ($this->pauseActivityRows as $row) {
                $type = collect($this->pauseEventTypes)->firstWhere('id', (int) $row['event_type_id']);

                $activity = EventPauseActivity::create([
                    'event_id'            => $event->id,
                    'event_status_log_id' => $pauseLog?->id,
                    'event_type_id'       => (int) $row['event_type_id'],
                    'event_type_name'     => $type['name'] ?? null,
                    'reason'              => $this->actionReason,
                    'expected_duration'   => $type['duration'] ?? null,
                    'happened_at'         => $this->parsedActionTime(),
                    'created_by'          => authUser()?->id,
                    'created_by_name'     => authUser()?->name,
                ]);

                $this->storeEmergencyQuantities($event, $pauseLog, $activity, $row['items'] ?? []);
            }
        });

        $this->closeActionModal();
    }

    /**
     * Persist only the emergency items the user actually entered a quantity
     * for — the table lists every candidate item, most stay empty.
     */
    protected function storeEmergencyQuantities(Event $event, ?EventStatusLog $pauseLog, EventPauseActivity $activity, array $items): void
    {
        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? null;

            if ($quantity === null || $quantity === '' || (float) $quantity <= 0) {
                continue;
            }

            $unitLabel = collect($item['units'] ?? [])->firstWhere('id', (int) ($item['item_unit_id'] ?? 0))['label'] ?? null;

            EventQuantity::create([
                'event_id'                => $event->id,
                'event_status_log_id'     => $pauseLog?->id,
                'event_pause_activity_id' => $activity->id,
                'source'                  => 'emergency',
                'item_type_id'            => $item['item_type_id'] ?? null,
                'item_id'                 => $item['item_id'] ?? null,
                'item_unit_id'            => $item['item_unit_id'] ?? null,
                'item_name'               => $item['item_name'] ?? null,
                'unit_name'               => $unitLabel,
                'planned_quantity'        => null,
                'actual_quantity'         => (float) $quantity,
                'percentage'              => null,
            ]);
        }
    }

    public function submitEventAction(): void
    {
        authorizeRequest('production.event-create');

        if (!$this->eventAction) {
            return;
        }

        $action = $this->eventAction['action'];

        $event = Event::with('eventType')
            ->where('plan_id', $this->plan->id)
            ->findOrFail($this->eventAction['event_id']);

        $this->validate(
            ['actionTime' => 'required|date'],
            ['actionTime.required' => 'Please enter the time.', 'actionTime.date' => 'Please enter a valid time.']
        );

        // Recording emergency events is not a status transition.
        if ($action === 'activities') {
            $this->submitPauseActivities($event);

            return;
        }

        // The board may have changed since the modal opened.
        if (!$this->assertTransitionAllowed($event, $action)) {
            $this->closeActionModal();

            return;
        }

        if (in_array($action, ['resume', 'terminate'], true) && $this->blockedByOpenEmergencies($event, $action)) {
            $this->closeActionModal();

            return;
        }

        match ($action) {
            'start'     => $this->submitStart($event),
            'terminate' => $this->submitTerminate($event),
            'pause'     => $this->submitPause($event),
            'resume'    => $this->submitResume($event),
        };
    }

    protected function submitStart(Event $event): void
    {
        $hasRecipe = (bool) ($this->eventAction['has_recipe'] ?? false);

        // Recipe inputs are all consumed, so each quantity is required;
        // non-recipe items are candidates — only the ones used are recorded.
        $this->validate(
            ['actionInputs.*.actual_quantity' => ($hasRecipe ? 'required' : 'nullable') . '|numeric|min:0'],
            [],
            ['actionInputs.*.actual_quantity' => 'used quantity']
        );

        DB::transaction(function () use ($event, $hasRecipe) {
            $log = $this->applyStatusChange($event, 'start', [
                'notes'       => $this->actionNotes,
                'happened_at' => $this->parsedActionTime(),
            ]);

            foreach ($this->actionInputs as $row) {
                $quantity = $row['actual_quantity'] ?? null;

                // For non-recipe events, skip items with no entered quantity.
                if (!$hasRecipe && ($quantity === null || $quantity === '' || (float) $quantity <= 0)) {
                    continue;
                }

                $this->storeQuantity($event, $log, 'input', $row);
            }
        });

        $this->closeActionModal();
    }

    protected function submitTerminate(Event $event): void
    {
        $this->validate(
            [
                'actionOutputs.*.actual_quantity'      => 'required|numeric|min:0',
                'actionSideProducts.*.actual_quantity' => 'required|numeric|min:0',
            ],
            [],
            [
                'actionOutputs.*.actual_quantity'      => 'produced quantity',
                'actionSideProducts.*.actual_quantity' => 'produced quantity',
            ]
        );

        DB::transaction(function () use ($event) {
            $log = $this->applyStatusChange($event, 'terminate', [
                'notes'       => $this->actionNotes,
                'happened_at' => $this->parsedActionTime(),
            ]);

            foreach ($this->actionOutputs as $row) {
                $this->storeQuantity($event, $log, 'output', $row);
            }

            foreach ($this->actionSideProducts as $row) {
                $this->storeQuantity($event, $log, 'side_product', $row);
            }
        });

        $this->closeActionModal();
    }

    protected function submitPause(Event $event): void
    {
        DB::transaction(fn() => $this->applyStatusChange($event, 'pause', [
            'reason'      => $this->actionReason,
            'happened_at' => $this->parsedActionTime(),
        ]));

        $this->closeActionModal();
    }

    protected function submitResume(Event $event): void
    {
        $this->validate(
            ['actionReason' => 'required|string|max:1000'],
            ['actionReason.required' => 'Please enter a reason.']
        );

        $pauseLog = $this->latestPauseLog($event->id);
        $resumeAt = $this->parsedActionTime();

        $actualDuration = $pauseLog
            ? $this->minutesBetween($this->logHappenedAt($pauseLog), $resumeAt)
            : null;

        DB::transaction(function () use ($event, $pauseLog, $resumeAt, $actualDuration) {
            $this->applyStatusChange($event, 'resume', [
                'actual_duration' => $actualDuration,
                'reason'          => $this->actionReason,
                'happened_at'     => $resumeAt,
            ]);

            // Close the pause entry with how long it actually lasted.
            $pauseLog?->update(['actual_duration' => $actualDuration]);
        });

        $this->closeActionModal();
    }

    protected function applyStatusChange(Event $event, string $action, array $logData = []): EventStatusLog
    {
        $from = $event->status ?: null;

        $event->status = self::EVENT_TRANSITIONS[$action]['to'];
        $event->save();

        return EventStatusLog::create(array_merge([
            'event_id'        => $event->id,
            'action'          => $action,
            'from_status'     => $from,
            'to_status'       => $event->status,
            'happened_at'     => now(),
            'changed_by'      => authUser()?->id,
            'changed_by_name' => authUser()?->name,
        ], $logData));
    }

    protected function storeQuantity(Event $event, EventStatusLog $log, string $source, array $row): void
    {
        EventQuantity::create([
            'event_id'               => $event->id,
            'event_status_log_id'    => $log->id,
            'source'                 => $source,
            'recipe_input_id'        => $row['recipe_input_id'] ?? null,
            'recipe_side_product_id' => $row['recipe_side_product_id'] ?? null,
            'item_type_id'           => $row['item_type_id'],
            'item_id'                => $row['item_id'],
            'item_unit_id'           => $row['item_unit_id'],
            'item_name'              => $row['item_name'],
            'unit_name'              => $row['unit_name'],
            'planned_quantity'       => ($row['planned_quantity'] ?? null) !== null ? (float) $row['planned_quantity'] : null,
            'actual_quantity'        => (float) ($row['actual_quantity'] ?? 0),
            'percentage'             => $this->percentageOf($row['planned_quantity'] ?? null, $row['actual_quantity'] ?? null),
        ]);
    }

    public function closeActionModal(): void
    {
        $this->resetActionForm();
        $this->dispatch('closeEventActionModal');
    }

    // ─── Event history ────────────────────────────────────────────────────────

    public function showEventHistory(int $eventId): void
    {
        // Same visibility rule as showEventDetails: this plan's events plus
        // carry-overs from the same factory (read only).
        $event = Event::with([
                'eventType',
                'statusLogs' => fn($q) => $q->with('quantities', 'pauseActivities.eventType', 'pauseActivities.quantities')->orderByDesc('id'),
                // Activities recorded without a pause log (pauses that predate
                // status logging) — shown in their own block.
                'pauseActivities' => fn($q) => $q->whereNull('event_status_log_id')->with('eventType', 'quantities')->orderBy('id'),
            ])
            ->where(function ($query) {
                $query->where('plan_id', $this->plan->id)
                    ->orWhereHas('plan', fn($q) => $q->where('factory_id', $this->plan->factory_id));
            })
            ->findOrFail($eventId);

        $this->eventHistory = [
            'event_id'   => $event->id,
            'event_name' => $event->name,
            'type_name'  => $event->eventType?->name ?? 'No type',
            'color'      => $event->eventType?->color ?? '#818cf8',
            'other_activities' => $event->pauseActivities
                ->map(fn(EventPauseActivity $activity) => $this->activityRow($activity))
                ->all(),
            'logs'       => $event->statusLogs->map(fn(EventStatusLog $log) => [
                'action'          => $log->action,
                'from'            => $log->from_status ? (self::EVENT_STATUS_LABELS[$log->from_status] ?? $log->from_status) : 'Planned',
                'to'              => self::EVENT_STATUS_LABELS[$log->to_status] ?? $log->to_status,
                'at'              => $this->logHappenedAt($log)->format('d M Y H:i'),
                'by'              => $log->changed_by_name,
                'actual_duration' => $log->actual_duration,
                'reason'          => $log->reason,
                'notes'           => $log->notes,
                'activities'      => $log->pauseActivities
                    ->map(fn(EventPauseActivity $activity) => $this->activityRow($activity))
                    ->all(),
                'quantities'        => $log->quantities->map(fn(EventQuantity $qty) => [
                    'source'     => $qty->source,
                    'item_name'  => $qty->item_name ?: ($qty->item_id ? "Item #{$qty->item_id}" : '—'),
                    'unit_name'  => $qty->unit_name,
                    'planned'    => (float) $qty->planned_quantity,
                    'actual'     => (float) $qty->actual_quantity,
                    'percentage' => $qty->percentage !== null ? (float) $qty->percentage : null,
                ])->all(),
            ])->all(),
        ];

        $this->dispatch('openEventHistoryModal');
    }

    public function showEventDetails(int $eventId): void
    {
        // Carry-over cards belong to earlier days' plans of the same
        // factory, so allow those events here too (view only — see
        // is_carry_over below).
        $event = Event::with(['eventType', 'recipe', 'recipeType', 'plan'])
            ->where(function ($query) {
                $query->where('plan_id', $this->plan->id)
                    ->orWhereHas('plan', fn($q) => $q->where('factory_id', $this->plan->factory_id));
            })
            ->findOrFail($eventId);

        $hasRecipe = (bool) ($event->eventType?->has_recipe);

        $placeableName = null;
        if ($event->placeable_type && $event->placeable_id) {
            $placeableName = $event->placeable_type::find($event->placeable_id)?->name;
        }

        $productionLineName = $event->production_line_id
            ? ProductionLine::find($event->production_line_id)?->name
            : null;

        $itemName = null;
        if ($event->item_id) {
            $itemName = $this->api->get("/v1/items/{$event->item_id}")['data']['name'] ?? null;
        }

        $isCarryOver     = (int) $event->plan_id !== (int) $this->plan->id;
        $crossesMidnight = $this->carryOverService->crossesMidnight($event->from_time, $event->to_time);

        // How many days after its start day the event ends (0 = same day),
        // for the "+N day(s)" hint next to the end time.
        $toDayOffset = $crossesMidnight
            ? (int) Carbon::parse($event->from_time)->startOfDay()
                ->diffInDays(Carbon::parse($event->to_time)->subSecond()->startOfDay())
            : 0;

        $this->selectedEvent = [
            'id'                   => $event->id,
            'type_name'            => $event->eventType?->name ?? 'No type',
            'color'                => $event->eventType?->color ?? '#818cf8',
            'has_recipe'           => $hasRecipe,
            'is_carry_over'        => $isCarryOver,
            'crosses_midnight'     => $crossesMidnight,
            'to_day_offset'        => $toDayOffset,
            'plan_date'            => Carbon::parse($event->plan->date)->format('d M Y'),
            'from_time'            => $event->from_time ? Carbon::parse($event->from_time)->format('H:i') : null,
            'to_time'              => $event->to_time ? Carbon::parse($event->to_time)->format('H:i') : null,
            'duration'             => $hasRecipe ? $event->calculated_duration : $event->planned_duration,
            'batch_count'          => $event->batch_count,
            'item_name'            => $itemName,
            'recipe_type_name'     => $event->recipeType?->name,
            'recipe_name'          => $event->recipe?->name,
            'production_line_name' => $productionLineName,
            'placeable_kind'       => $event->placeable_type === Preparation::class ? 'Preparation' : ($event->placeable_type === Line::class ? 'Line' : null),
            'placeable_name'       => $placeableName,
            'description'          => $event->description,
            'status'               => $event->status
                ? (self::EVENT_STATUS_LABELS[$event->status] ?? ucfirst($event->status))
                : 'Planned',
        ];

        $this->dispatch('openEventModal');
    }

    public function deleteEvent(int $eventId): void
    {
        Event::where('plan_id', $this->plan->id)->where('id', $eventId)->delete();

        $this->selectedEvent = null;
        $this->dispatch('closeEventDetailsModal');
    }

    public function exportExcel()
    {
        return Excel::download(
            new PlanEventsExport($this->plan->id),
            "plan-{$this->plan->id}-events.xlsx"
        );
    }

    protected function classForAlias(string $alias): ?string
    {
        return match ($alias) {
            'preparation' => Preparation::class,
            'line' => Line::class,
            default => null,
        };
    }

    public function render()
    {
        $this->loadEvents();

        return view('livewire.plans.plan-board', [
            'prevPlan' => $this->adjacentPlan('prev'),
            'nextPlan' => $this->adjacentPlan('next'),
        ]);
    }
}
