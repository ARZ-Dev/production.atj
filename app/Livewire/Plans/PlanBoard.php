<?php

namespace App\Livewire\Plans;

use App\Models\Event;
use App\Models\Line;
use App\Models\Plan;
use App\Models\Preparation;
use App\Models\ProductionLine;
use App\Services\ApiService;
use App\Services\RecipeDurationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

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

    #[On('eventsSaved')]
    public function refreshEvents(): void
    {
        // Events were created/edited in the modal — re-render to pick them up.
        // loadEvents() runs again on every render(), so there's nothing else to do here.
    }

    protected function loadEvents(): void
    {
        $events = Event::with('eventType')
            ->where('plan_id', $this->plan->id)
            ->orderBy('from_time')
            ->get();

        $this->allEvents = $events;

        $this->unplacedEvents = $events->whereNull('placeable_id')->values();
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
                    ->sortBy('from_time')
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

        $from = Carbon::parse($event->from_time);

        return $from->hour + $from->minute / 60;
    }

    protected function eventSpanHours(Event $event): float
    {
        if ($event->from_time && $event->to_time) {
            $minutes = Carbon::parse($event->from_time)->diffInMinutes(Carbon::parse($event->to_time));

            return max($minutes, 15) / 60;
        }

        $minutes = $event->calculated_duration ?: $event->planned_duration;

        return $minutes ? max((float) $minutes, 15) / 60 : 1.0;
    }

    public function dropEvent(int $eventId, int $productionLineId, string $placeableAlias, int $placeableId, int $hour): void
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

        $hour    = max(0, min(23, $hour));
        $newFrom = sprintf('%02d:00:00', $hour);

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
        $event = Event::with(['eventType', 'recipe', 'recipeType'])
            ->where('plan_id', $this->plan->id)
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

        $this->selectedEvent = [
            'type_name'            => $event->eventType?->name ?? 'No type',
            'color'                => $event->eventType?->color ?? '#818cf8',
            'has_recipe'           => $hasRecipe,
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

        return view('livewire.plans.plan-board');
    }
}
