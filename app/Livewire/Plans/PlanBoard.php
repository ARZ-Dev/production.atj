<?php

namespace App\Livewire\Plans;

use App\Exports\PlanEventsExport;
use App\Models\Event;
use App\Models\Line;
use App\Models\Plan;
use App\Models\Preparation;
use App\Models\ProductionLine;
use App\Services\ApiService;
use App\Services\PlanCarryOverService;
use App\Services\RecipeDurationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class PlanBoard extends Component
{
    public const EVENT_STATUS_LABELS = [
        'in_progress' => 'In Progress',
        'paused'      => 'Paused',
        'terminated'  => 'Terminated',
    ];

    public Plan $plan;
    public $productionLines = [];
    public $unplacedEvents = [];
    public ?string $factoryName = null;
    public ?array $selectedEvent = null;

    #[Url(except: 'board')]
    public string $view = 'board';

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
    }

    public function updatedView($value): void
    {
        if (!in_array($value, ['board', 'list'], true)) {
            $this->view = 'board';
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
        $events = Event::with('eventType', 'recipe', 'productionLine', 'placeable')
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

        return Event::with('eventType', 'recipe', 'productionLine', 'placeable')
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

    // ─── Calendar layout ──────────────────────────────────────────────────────

    public function laneRows(): array
    {
        $rows = [];

        foreach ($this->productionLines as $pl) {
            $lanes = collect($pl->preparations->map(fn($p) => ['type' => 'preparation', 'id' => $p->id, 'name' => $p->name]))
                ->concat($pl->lines->map(fn($l) => ['type' => 'line', 'id' => $l->id, 'name' => $l->name]))
                ->values();

            $laneRows = [];
            foreach ($lanes as $lane) {
                $placeableClass = $this->classForAlias($lane['type']);

                $laneEvents = $this->allEvents
                    ->where('production_line_id', $pl->id)
                    ->where('placeable_type', $placeableClass)
                    ->where('placeable_id', $lane['id'])
                    ->sortBy(fn(Event $e) => $this->eventFromHour($e))
                    ->values();

                $laneRows[] = [
                    'lane'   => $lane,
                    'layout' => $this->layoutTracks($laneEvents),
                ];
            }

            $rows[] = [
                'production_line' => $pl,
                'lanes'           => $laneRows,
            ];
        }

        return $rows;
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

        $transitions = [
            'start'     => ['from' => [null], 'to' => 'in_progress'],
            'pause'     => ['from' => ['in_progress'], 'to' => 'paused'],
            'resume'    => ['from' => ['paused'], 'to' => 'in_progress'],
            'terminate' => ['from' => ['in_progress', 'paused'], 'to' => 'terminated'],
        ];

        if (!isset($transitions[$action])) {
            return;
        }

        $event = Event::where('plan_id', $this->plan->id)->findOrFail($eventId);

        $current = $event->status ?: null;

        if (!in_array($current, $transitions[$action]['from'], true)) {
            $label = $current ? (self::EVENT_STATUS_LABELS[$current] ?? $current) : 'Planned';

            $this->dispatch('swal:error', [
                'title' => 'Action not allowed',
                'text'  => "You can't {$action} an event while it is \"{$label}\".",
            ]);

            return;
        }

        $event->status = $transitions[$action]['to'];
        $event->save();
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
