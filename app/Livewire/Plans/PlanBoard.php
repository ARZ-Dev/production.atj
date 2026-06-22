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
use Livewire\Component;

class PlanBoard extends Component
{
    public Plan $plan;
    public $productionLines = [];
    public $unplacedEvents = [];
    public array $placedEvents = []; // "{productionLineId}:{alias}:{placeableId}" => Event[]
    public ?string $factoryName = null;

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

        $this->loadEvents();
    }

    protected function loadEvents(): void
    {
        $events = Event::with('eventType')
            ->where('plan_id', $this->plan->id)
            ->orderBy('from_time')
            ->get();

        $this->unplacedEvents = $events->whereNull('placeable_id')->values();

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

        $this->loadEvents();
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
        return view('livewire.plans.plan-board');
    }
}
