<?php

namespace App\Livewire\Plans;

use App\Exports\PlanEventsExport;
use App\Models\Event;
use App\Models\Line;
use App\Models\Plan;
use App\Models\Preparation;
use App\Models\ProductionLine;
use App\Services\ApiService;
use App\Services\RecipeDurationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class PlanBoard extends Component
{
    public Plan $plan;
    public $productionLines = [];
    public $unplacedEvents = [];
    public ?string $factoryName = null;
    public ?array $selectedEvent = null;

    protected Collection $allEvents;

    protected ApiService $api;
    protected RecipeDurationService $durationService;

    public function boot(ApiService $api, RecipeDurationService $durationService): void
    {
        $this->api             = $api;
        $this->durationService = $durationService;
    }

    public function mount($id): void
    {
        $this->plan = Plan::findOrFail($id);

        $this->productionLines = ProductionLine::with('preparations.eventTypes', 'lines.eventTypes')
            ->where('factory_id', $this->plan->factory_id)
            ->orderBy('name')
            ->get();

        if ($this->plan->factory_id) {
            $warehouses = $this->api->get('/v1/warehouses', ['related_to_production' => true])['data'] ?? [];
            $this->factoryName = collect($warehouses)->firstWhere('id', $this->plan->factory_id)['name'] ?? null;
        }
    }

    protected function loadEvents(): void
    {
        $events = Event::with('eventType')
            ->where('plan_id', $this->plan->id)
            ->orderBy('from_time')
            ->get();

        $this->unplacedEvents = $events->whereNull('placeable_id')->values();

        $this->allEvents = $events->concat($this->carryOverEvents());
    }

    /**
     * The same factory's plan for the calendar day right before this one,
     * if it exists.
     */
    protected function prevDayPlan(): ?Plan
    {
        return Plan::where('factory_id', $this->plan->factory_id)
            ->whereDate('date', Carbon::parse($this->plan->date)->subDay())
            ->first();
    }

    /**
     * Placed events from the previous day's plan that cross midnight
     * (to_time wrapped past 24:00, so it reads earlier than from_time) —
     * they spill into this board and are shown from 00:00 up to their end.
     */
    protected function carryOverEvents(): Collection
    {
        $prevPlan = $this->prevDayPlan();

        if (!$prevPlan) {
            return collect();
        }

        return Event::with('eventType')
            ->where('plan_id', $prevPlan->id)
            ->whereNotNull('placeable_id')
            ->whereNotNull('from_time')
            ->whereNotNull('to_time')
            ->whereColumn('to_time', '<=', 'from_time')
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
        // Started on the previous day — on this board it runs from midnight.
        if ($event->is_carry_over) {
            return 0.0;
        }

        if (!$event->from_time) {
            return 0.0;
        }

        return $this->timeToMinutes($event->from_time) / 60;
    }

    protected function eventSpanHours(Event $event): float
    {
        if ($event->from_time && $event->to_time) {
            $from = $this->timeToMinutes($event->from_time);
            $to   = $this->timeToMinutes($event->to_time);

            // Carry-over from yesterday: only the 00:00 → to_time part is here.
            if ($event->is_carry_over) {
                return max($to, 15) / 60;
            }

            // Crosses midnight: clip at the end of the board; the remainder
            // renders on the next day's plan as a carry-over.
            $minutes = $to > $from ? $to - $from : 24 * 60 - $from;

            return max($minutes, 15) / 60;
        }

        $minutes = $event->calculated_duration ?: $event->planned_duration;

        return $minutes ? max((float) $minutes, 15) / 60 : 1.0;
    }

    protected function timeToMinutes(string $time): int
    {
        $t = Carbon::parse($time);

        return $t->hour * 60 + $t->minute;
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
        $minutes = $slot * 15;
        $newFrom = sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);

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
            ? Carbon::parse($newFrom)->addMinutes((int) $durationMinutes)->format('H:i:s')
            : null;

        $event->save();
    }

    public function unplaceEvent(int $eventId): void
    {
        $event = Event::where('plan_id', $this->plan->id)->findOrFail($eventId);

        $event->production_line_id  = null;
        $event->placeable_type      = null;
        $event->placeable_id        = null;
        $event->calculated_duration = null;
        $event->save();
    }

    public function showEventDetails(int $eventId): void
    {
        // Carry-over cards belong to the previous day's plan, so allow that
        // plan's events here too (view only — see is_carry_over below).
        $planIds = array_filter([$this->plan->id, $this->prevDayPlan()?->id]);

        $event = Event::with(['eventType', 'recipe', 'recipeType', 'plan'])
            ->whereIn('plan_id', $planIds)
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
        $crossesMidnight = $event->from_time && $event->to_time
            && $this->timeToMinutes($event->to_time) <= $this->timeToMinutes($event->from_time);

        $this->selectedEvent = [
            'id'                   => $event->id,
            'type_name'            => $event->eventType?->name ?? 'No type',
            'color'                => $event->eventType?->color ?? '#818cf8',
            'has_recipe'           => $hasRecipe,
            'is_carry_over'        => $isCarryOver,
            'crosses_midnight'     => $crossesMidnight,
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
            'status'               => $event->status,
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
