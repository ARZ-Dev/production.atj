<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Plan;
use App\Models\Recipe;
use App\Services\ApiService;
use App\Services\RecipeDurationService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class EventCreate extends Component
{
    public $planId;
    public $plan;
    public $eventTypes = [];
    public $events = [];
    public $removedEventIds = [];
    public ?int $editingEventId = null;

    // Per-row cascading option lists, keyed by row index.
    public array $itemTypesByRow = [];
    public array $itemsByRow = [];
    public array $recipesByRow = [];

    protected ApiService $api;
    protected RecipeDurationService $durationService;

    public function boot(ApiService $api, RecipeDurationService $durationService): void
    {
        $this->api             = $api;
        $this->durationService = $durationService;
    }

    public function mount($planId): void
    {
        authorizeRequest('production.event-create');

        $this->planId     = $planId;
        $this->plan       = Plan::with('shift')->findOrFail($planId);
        $this->eventTypes = EventType::orderBy('name')->get();

        $this->openForCreate();
    }

    #[On('openForCreate')]
    public function openForCreate(): void
    {
        $this->reset('events', 'removedEventIds', 'itemTypesByRow', 'itemsByRow', 'recipesByRow');
        $this->editingEventId = null;
        $this->addEventRow();
        $this->resetValidation();
    }

    #[On('openForEdit')]
    public function openForEdit(int $eventId): void
    {
        $this->reset('events', 'removedEventIds', 'itemTypesByRow', 'itemsByRow', 'recipesByRow');
        $this->editingEventId = $eventId;

        $event = Event::where('plan_id', $this->planId)->find($eventId);

        if (!$event) {
            $this->editingEventId = null;
            $this->addEventRow();
            return;
        }

        $this->events[] = $this->buildRowFromEvent($event);

        $hasRecipe = (bool) ($event->eventType?->has_recipe);
        $this->loadCascadeOptionsForRow(0, $event);

        // Not placed on a line yet — show a live estimate instead of
        // the persisted (still-null) calculated_duration.
        if ($hasRecipe && !$event->placeable_type) {
            $this->recalculateDuration(0);
        }

        $this->resetValidation();
    }

    private function buildRowFromEvent(Event $event): array
    {
        $hasRecipe = (bool) ($event->eventType?->has_recipe);

        return [
            'id'                    => $event->id,
            'key'                   => 'event-' . $event->id,
            'event_type_id'         => $event->event_type_id,
            'event_type_has_recipe' => $hasRecipe,
            'item_type_id'          => $event->item_type_id,
            'item_id'               => $event->item_id,
            'recipe_type_id'        => $event->recipe_type_id,
            'recipe_id'             => $event->recipe_id,
            'batch_count'           => $event->batch_count ?? '',
            'duration'              => $hasRecipe ? $event->calculated_duration : $event->planned_duration,
            'placeable_type'        => $event->placeable_type,
            'placeable_id'          => $event->placeable_id,
            'production_line_id'    => $event->production_line_id,
            'from_time_raw'         => $event->from_time,
            'scheduled_label'       => $event->from_time
                ? Carbon::parse($event->from_time)->format('H:i') . ($event->to_time ? ' – ' . Carbon::parse($event->to_time)->format('H:i') : '')
                : null,
            'description'           => $event->description ?? '',
        ];
    }

    private function loadCascadeOptionsForRow(int $index, Event $event): void
    {
        $hasRecipe = (bool) ($event->eventType?->has_recipe);

        $this->itemTypesByRow[$index] = $this->allowedItemTypesFor($event->eventType);

        if (!$hasRecipe) {
            return;
        }

        if ($event->item_type_id) {
            $this->itemsByRow[$index] = $this->fetchItemsForType((int) $event->item_type_id);
        }

        if ($event->item_id) {
            $this->recipesByRow[$index] = $this->fetchRecipes((int) $event->item_id);
        }
    }

    // ─── Item types assigned to an event type, filtering the full external
    //     item-types list; falls back to the unfiltered list if the event
    //     type has none assigned (e.g. legacy event types). ─────────────────
    private function allowedItemTypesFor(?EventType $eventType): array
    {
        $itemTypes  = $this->api->get('/v1/item-types')['data'] ?? [];
        $allowedIds = $eventType?->item_type_ids ?? [];

        if (empty($allowedIds)) {
            return $itemTypes;
        }

        return collect($itemTypes)
            ->filter(fn($type) => in_array($type['id'], $allowedIds))
            ->values()
            ->toArray();
    }

    public function addEventRow(): void
    {
        if ($this->editingEventId !== null) {
            return;
        }

        $this->events[] = [
            'id'                    => null,
            'key'                   => 'new-' . uniqid(),
            'event_type_id'         => null,
            'event_type_has_recipe' => false,
            'item_type_id'          => null,
            'item_id'               => null,
            'recipe_type_id'        => null,
            'recipe_id'             => null,
            'batch_count'           => '',
            'duration'              => null,
            'placeable_type'        => null,
            'placeable_id'          => null,
            'production_line_id'    => null,
            'from_time_raw'         => null,
            'scheduled_label'       => null,
            'description'           => '',
        ];
    }

    public function removeEventRow(int $index): void
    {
        if ($this->editingEventId !== null) {
            return;
        }

        $event = $this->events[$index] ?? null;

        if (!$event) {
            return;
        }

        if (!empty($event['id'])) {
            $this->removedEventIds[] = $event['id'];
        }

        unset($this->events[$index]);
        $this->events = array_values($this->events);
    }

    // ─── Cascading option fetchers ───────────────────────────────────────────

    private function fetchItemsForType(int $itemTypeId): array
    {
        $type     = collect($this->api->get('/v1/item-types')['data'] ?? [])->firstWhere('id', $itemTypeId);
        $typeName = $type['name'] ?? null;

        if (!$typeName) {
            return [];
        }

        return $this->api->get('/v1/items', ['item_type' => $typeName, 'is_active' => true])['data'] ?? [];
    }

    private function fetchRecipes(int $itemId): array
    {
        return Recipe::where('item_id', $itemId)
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    // ─── Cascade handlers (called explicitly via wire:change) ───────────────

    public function onEventTypeChanged(int $index, $eventTypeId): void
    {
        $eventTypeId = $eventTypeId !== '' ? (int) $eventTypeId : null;
        $eventType   = $eventTypeId ? EventType::find($eventTypeId) : null;
        $hasRecipe   = (bool) ($eventType?->has_recipe);

        $this->events[$index]['event_type_id']         = $eventTypeId;
        $this->events[$index]['event_type_has_recipe']  = $hasRecipe;
        $this->events[$index]['item_type_id']           = null;
        $this->events[$index]['item_id']                = null;
        $this->events[$index]['recipe_type_id']          = null;
        $this->events[$index]['recipe_id']               = null;
        $this->events[$index]['batch_count']             = '';

        unset($this->itemsByRow[$index], $this->recipesByRow[$index]);

        $this->itemTypesByRow[$index] = $this->allowedItemTypesFor($eventType);

        $this->events[$index]['duration'] = $hasRecipe ? null : $eventType?->duration;
    }

    public function onItemTypeChanged(int $index, $itemTypeId): void
    {
        $itemTypeId = $itemTypeId !== '' ? (int) $itemTypeId : null;

        $this->events[$index]['item_type_id']   = $itemTypeId;
        $this->events[$index]['item_id']        = null;
        $this->events[$index]['recipe_type_id'] = null;
        $this->events[$index]['recipe_id']      = null;
        $this->events[$index]['duration']       = null;

        unset($this->recipesByRow[$index]);

        if ($itemTypeId) {
            $this->itemsByRow[$index] = $this->fetchItemsForType($itemTypeId);
        } else {
            unset($this->itemsByRow[$index]);
        }
    }

    public function onItemChanged(int $index, $itemId): void
    {
        $itemId = $itemId !== '' ? (int) $itemId : null;

        $this->events[$index]['item_id']        = $itemId;
        $this->events[$index]['recipe_type_id'] = null;
        $this->events[$index]['recipe_id']      = null;

        if ($itemId) {
            $this->recipesByRow[$index] = $this->fetchRecipes($itemId);
        } else {
            unset($this->recipesByRow[$index]);
        }

        $this->recalculateDuration($index);
    }

    public function onRecipeChanged(int $index, $recipeId): void
    {
        $recipeId = $recipeId !== '' ? (int) $recipeId : null;

        $this->events[$index]['recipe_id'] = $recipeId;
        // The dropdown is gone — the recipe type is carried along from the
        // chosen recipe so it still gets stored and shown on the board.
        $this->events[$index]['recipe_type_id'] = $recipeId
            ? Recipe::find($recipeId)?->recipe_type_id
            : null;

        $this->recalculateDuration($index);
    }

    public function onBatchCountChanged(int $index, $batchCount): void
    {
        $this->events[$index]['batch_count'] = $batchCount;

        $this->recalculateDuration($index);
    }

    /**
     * Preview a recipe event's duration as soon as recipe, item and batch
     * count are known — using any capacity defined for that item as an
     * estimate, since the event isn't placed on a specific line/preparation
     * yet. It's recalculated against the actual line once dropped on the
     * calendar (see PlanBoard::dropEvent).
     */
    private function recalculateDuration(int $index): void
    {
        $event = $this->events[$index] ?? null;

        if (!$event || empty($event['event_type_has_recipe'])) {
            return;
        }

        if (!$event['recipe_id'] || !$event['item_id']) {
            $this->events[$index]['duration'] = null;
            return;
        }

        $this->events[$index]['duration'] = $this->durationService->computeEstimate(Event::make($event));
    }

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->events as $index => $event) {
            $hasRecipe = !empty($event['event_type_has_recipe']);

            $rules["events.{$index}.event_type_id"] = 'required|exists:event_types,id';
            $rules["events.{$index}.description"]    = 'nullable|string|max:1000';

            $requiredIfRecipe = $hasRecipe ? 'required' : 'nullable';
            $rules["events.{$index}.item_type_id"]   = "{$requiredIfRecipe}|integer";
            $rules["events.{$index}.item_id"]        = "{$requiredIfRecipe}|integer";
            $rules["events.{$index}.recipe_type_id"] = 'nullable|integer|exists:recipe_types,id';
            $rules["events.{$index}.recipe_id"]      = "{$requiredIfRecipe}|integer|exists:recipes,id";
            $rules["events.{$index}.batch_count"]    = "{$requiredIfRecipe}|string|max:255";
        }

        return $rules;
    }

    protected $messages = [
        'events.*.event_type_id.required'  => 'Event type is required.',
        'events.*.event_type_id.exists'    => 'Selected event type is invalid.',
        'events.*.item_type_id.required'   => 'Item type is required.',
        'events.*.item_id.required'        => 'Item is required.',
        'events.*.recipe_id.required'      => 'Recipe is required.',
        'events.*.batch_count.required'    => 'Batch number is required.',
    ];

    /**
     * For an event that's already placed on the calendar, recompute its
     * duration (recipe events: from batch/recipe/capacity; otherwise: from
     * the event type) and shift its to_time accordingly, since the from_time
     * placement itself doesn't change here.
     */
    private function recomputePlacedDuration(array $event, bool $hasRecipe): array
    {
        $duration = $hasRecipe ? null : $event['duration'];

        if (!empty($event['placeable_type']) && !empty($event['placeable_id'])) {
            if ($hasRecipe && $event['recipe_id'] && $event['item_id']) {
                $duration = $this->durationService->compute(
                    Event::make($event),
                    $event['placeable_type'],
                    (int) $event['placeable_id']
                );
            }

            $toTime = ($event['from_time_raw'] && $duration)
                ? Carbon::parse($event['from_time_raw'])->addMinutes((int) $duration)->format('H:i:s')
                : null;
        } else {
            $toTime = null;
        }

        return [
            'calculated_duration' => $hasRecipe ? $duration : null,
            'planned_duration'    => $hasRecipe ? null : $duration,
            'to_time'             => $toTime,
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $typeNames = EventType::whereIn('id',
            array_filter(array_column($this->events, 'event_type_id'))
        )->pluck('name', 'id');

        try {
            \DB::beginTransaction();

            if (!empty($this->removedEventIds)) {
                Event::whereIn('id', $this->removedEventIds)
                    ->where('plan_id', $this->planId)
                    ->delete();
            }

            foreach ($this->events as $event) {
                $hasRecipe = !empty($event['event_type_has_recipe']);

                $data = [
                    'plan_id'        => $this->planId,
                    'event_type_id'  => $event['event_type_id'],
                    'name'           => $typeNames[$event['event_type_id']] ?? '',
                    'description'    => $event['description'] ?: null,
                    'item_type_id'   => $event['item_type_id'] ?: null,
                    'item_id'        => $hasRecipe ? $event['item_id'] : null,
                    'recipe_type_id' => $hasRecipe ? $event['recipe_type_id'] : null,
                    'recipe_id'      => $hasRecipe ? $event['recipe_id'] : null,
                    'batch_count'    => $hasRecipe ? $event['batch_count'] : null,
                ] + $this->recomputePlacedDuration($event, $hasRecipe);

                if (!empty($event['id'])) {
                    Event::where('id', $event['id'])
                        ->where('plan_id', $this->planId)
                        ->update($data);
                } else {
                    // New events start unplaced — their time slot is set by
                    // dragging them onto the plan board calendar.
                    Event::create($data);
                }
            }

            \DB::commit();
            $this->removedEventIds = [];

            // Full page reload so the plan board re-renders with the saved event(s).
            $this->redirect(route('plans.view', $this->planId));

        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Failed to save events: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.events.event-create');
    }
}
