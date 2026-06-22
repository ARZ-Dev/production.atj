<?php

namespace App\Livewire\Plans;

use App\Models\Capacity;
use App\Models\Event;
use App\Models\Line;
use App\Models\Plan;
use App\Models\Preparation;
use App\Models\ProductionLine;
use App\Models\Recipe;
use App\Services\ApiService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class PlanBoard extends Component
{
    public Plan $plan;
    public $productionLines = [];
    public $unplacedEvents = [];
    public array $placedEvents = []; // "{productionLineId}:{alias}:{placeableId}" => Event[]
    public ?string $factoryName = null;

    public string $boardView = 'calendar'; // 'calendar' | 'kanban'

    protected Collection $allEvents;
    public $unscheduledEvents = []; // events with no production_line_id (calendar tray)

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount($id): void
    {
        $this->plan = Plan::findOrFail($id);

        $this->productionLines = ProductionLine::with('preparations', 'lines')
            ->where('factory_id', $this->plan->factory_id)
            ->orderBy('name')
            ->get();

        if ($this->plan->factory_id) {
            $warehouses = $this->api->get('/v1/warehouses', ['related_to_production' => true])['data'] ?? [];
            $this->factoryName = collect($warehouses)->firstWhere('id', $this->plan->factory_id)['name'] ?? null;
        }
    }

    public function setView(string $view): void
    {
        $this->boardView = in_array($view, ['calendar', 'kanban'], true) ? $view : $this->boardView;
    }

    protected function loadEvents(): void
    {
        $events = Event::with('eventType')
            ->where('plan_id', $this->plan->id)
            ->orderBy('from_time')
            ->get();

        $this->allEvents = $events;

        $this->unplacedEvents     = $events->whereNull('placeable_id')->values();
        $this->unscheduledEvents  = $events->whereNull('production_line_id')->values();

        $placed = [];
        foreach ($events->whereNotNull('placeable_id') as $event) {
            $alias = $this->aliasForClass($event->placeable_type);
            if (!$alias) {
                continue;
            }
            $key = $this->laneKey($event->production_line_id, $alias, $event->placeable_id);
            $placed[$key][] = $event;
        }
        $this->placedEvents = $placed;
    }

    // ─── Calendar layout ──────────────────────────────────────────────────────

    public function calendarRows(): array
    {
        $rows = [];

        foreach ($this->productionLines as $pl) {
            $rowEvents = $this->allEvents
                ->where('production_line_id', $pl->id)
                ->sortBy('from_time')
                ->values();

            $rows[] = [
                'production_line' => $pl,
                'layout'           => $this->layoutTracks($rowEvents),
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

    public function rescheduleEvent(int $eventId, ?int $productionLineId, int $hour): void
    {
        $event = Event::with('eventType')
            ->where('plan_id', $this->plan->id)
            ->findOrFail($eventId);

        if ($productionLineId) {
            $hour    = max(0, min(23, $hour));
            $newFrom = sprintf('%02d:00:00', $hour);

            if ($event->from_time && $event->to_time) {
                $spanMinutes = Carbon::parse($event->from_time)->diffInMinutes(Carbon::parse($event->to_time));
                $event->to_time = Carbon::parse($newFrom)->addMinutes($spanMinutes)->format('H:i:s');
            }
            $event->from_time = $newFrom;
        }

        if ((int) $event->production_line_id !== (int) $productionLineId) {
            $event->production_line_id  = $productionLineId;
            $event->placeable_type      = null;
            $event->placeable_id        = null;
            $event->calculated_duration = null;
        }

        $event->save();
    }

    public function laneKey($productionLineId, string $alias, $placeableId): string
    {
        return "{$productionLineId}:{$alias}:{$placeableId}";
    }

    protected function aliasForClass(?string $class): ?string
    {
        return match ($class) {
            Preparation::class => 'preparation',
            Line::class => 'line',
            default => null,
        };
    }

    protected function classForAlias(string $alias): ?string
    {
        return match ($alias) {
            'preparation' => Preparation::class,
            'line' => Line::class,
            default => null,
        };
    }

    public function placeEvent(int $eventId, ?int $productionLineId, ?string $placeableAlias, ?int $placeableId): void
    {
        $event = Event::with('eventType')
            ->where('plan_id', $this->plan->id)
            ->findOrFail($eventId);

        $placeableClass = $placeableAlias ? $this->classForAlias($placeableAlias) : null;

        $event->production_line_id = $placeableClass ? $productionLineId : null;
        $event->placeable_type     = $placeableClass;
        $event->placeable_id       = $placeableClass ? $placeableId : null;
        $event->calculated_duration = null;

        if ($placeableClass && $event->eventType?->has_recipe && $event->recipe_id && $event->item_id) {
            $event->calculated_duration = $this->computeDuration($event, $placeableClass, $placeableId);
        }

        $event->save();
    }

    protected function computeDuration(Event $event, string $placeableClass, int $placeableId): ?string
    {
        $recipe = Recipe::find($event->recipe_id);

        if (!$recipe || !$recipe->quantity_per_batch) {
            return null;
        }

        $capacity = Capacity::where('capacityable_type', $placeableClass)
            ->where('capacityable_id', $placeableId)
            ->where('item_id', $event->item_id)
            ->first();

        if (!$capacity || (float) $capacity->capacity <= 0) {
            return null;
        }

        $batchCount  = (float) ($event->batch_count ?: 1);
        $requiredQty = (float) $recipe->quantity_per_batch * $batchCount;

        $units      = $this->api->get("/v1/items/{$event->item_id}")['data']['units'] ?? [];
        $recipeUnit = collect($units)->firstWhere('id', $recipe->item_unit_id);

        $basicQty = $requiredQty;
        if ($recipeUnit && empty($recipeUnit['basic'])) {
            $formula  = (float) ($recipeUnit['formula'] ?? 1) ?: 1;
            $basicQty = $requiredQty / $formula;
        }

        $durationHours = $basicQty / (float) $capacity->capacity;

        return (string) round($durationHours * 60);
    }

    public function render()
    {
        $this->loadEvents();

        return view('livewire.plans.plan-board');
    }
}
