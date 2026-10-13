<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Plan;
use App\Models\Recipe;
use App\Services\ApiService;
use App\Services\LinkedEventService;
use App\Services\PlanCarryOverService;
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

    // The same option lists for each row's companion event (the event type
    // executed with the row's type — e.g. packaging alongside production),
    // keyed by the lead row's index.
    public array $companionItemTypesByRow = [];
    public array $companionItemsByRow = [];
    public array $companionRecipesByRow = [];

    /** Every per-row option map, reset and re-indexed together. */
    private const ROW_MAPS = [
        'itemTypesByRow', 'itemsByRow', 'recipesByRow',
        'companionItemTypesByRow', 'companionItemsByRow', 'companionRecipesByRow',
    ];

    protected ApiService $api;
    protected RecipeDurationService $durationService;
    protected PlanCarryOverService $carryOverService;
    protected LinkedEventService $linkedEvents;

    public function boot(ApiService $api, RecipeDurationService $durationService, PlanCarryOverService $carryOverService, LinkedEventService $linkedEvents): void
    {
        $this->api              = $api;
        $this->durationService  = $durationService;
        $this->carryOverService = $carryOverService;
        $this->linkedEvents     = $linkedEvents;
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
        $this->reset('events', 'removedEventIds', ...self::ROW_MAPS);
        $this->editingEventId = null;
        $this->addEventRow();
        $this->resetValidation();
    }

    #[On('openForEdit')]
    public function openForEdit(int $eventId): void
    {
        $this->reset('events', 'removedEventIds', ...self::ROW_MAPS);
        $this->editingEventId = $eventId;

        $event = Event::where('plan_id', $this->planId)->find($eventId);

        // A companion is edited together with the event it runs with — its
        // fields live in that event's card.
        if ($event?->linked_event_id && ($lead = Event::where('plan_id', $this->planId)->find($event->linked_event_id))) {
            $event = $lead;
            $this->editingEventId = $lead->id;
        }

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
            'scheduled_label'       => $this->scheduledLabel($event),
            'description'           => $event->description ?? '',
            'companion'             => $this->companionRowFromEvent($event),
        ];
    }

    /**
     * The companion part of a lead row: the saved companion event, or — when
     * the type was given a companion after this event was created — a blank
     * one, so saving creates it. Not for an event that has already started:
     * the pair starts together, so a companion added now could never be
     * started — and the lead could then never be ended.
     */
    private function companionRowFromEvent(Event $event): ?array
    {
        $companion = $event->companionEvent()->with('eventType')->first();

        if ($companion) {
            return [
                'id'             => $companion->id,
                'event_type_id'  => $companion->event_type_id,
                'type_name'      => $companion->eventType?->name ?? 'No type',
                'color'          => $companion->eventType?->color ?? '#818cf8',
                'item_type_id'   => $companion->item_type_id,
                'item_id'        => $companion->item_id,
                'recipe_type_id' => $companion->recipe_type_id,
                'recipe_id'      => $companion->recipe_id,
                'batch_count'    => $companion->batch_count ?? '',
                'batch_auto'     => null,
            ];
        }

        $companionType = $event->eventType?->companionEventType;

        return $companionType && !$event->status ? $this->blankCompanion($companionType) : null;
    }

    private function blankCompanion(EventType $companionType, ?int $id = null): array
    {
        return [
            'id'             => $id,
            'event_type_id'  => $companionType->id,
            'type_name'      => $companionType->name,
            'color'          => $companionType->color ?? '#818cf8',
            'item_type_id'   => null,
            'item_id'        => null,
            'recipe_type_id' => null,
            'recipe_id'      => null,
            'batch_count'    => '',
            'batch_auto'     => null,
        ];
    }

    private function scheduledLabel(Event $event): ?string
    {
        if (!$event->from_time) {
            return null;
        }

        $label = Carbon::parse($event->from_time)->format('H:i');

        if ($event->to_time) {
            $label .= ' – ' . Carbon::parse($event->to_time)->format('H:i');

            // Ends on a later calendar day (an end at exactly 00:00 still
            // belongs to the start day).
            $plusDays = (int) Carbon::parse($event->from_time)->startOfDay()
                ->diffInDays(Carbon::parse($event->to_time)->subSecond()->startOfDay());

            if ($plusDays >= 1) {
                $label .= " (+{$plusDays}d)";
            }
        }

        return $label;
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

        $this->loadCompanionOptionsForRow($index);
    }

    private function loadCompanionOptionsForRow(int $index): void
    {
        $companion = $this->events[$index]['companion'] ?? null;

        if (!$companion) {
            return;
        }

        $this->companionItemTypesByRow[$index] = $this->allowedItemTypesFor(EventType::find($companion['event_type_id']));

        if ($companion['item_type_id']) {
            $this->companionItemsByRow[$index] = $this->fetchItemsForType((int) $companion['item_type_id']);
        }

        if ($companion['item_id']) {
            $this->companionRecipesByRow[$index] = $this->fetchCompanionRecipes($index);
        }
    }

    // ─── Item types assigned to an event type, filtering the full external
    //     item-types list; falls back to the unfiltered list if the event
    //     type has none assigned (e.g. legacy event types). ─────────────────
    private function allowedItemTypesFor(?EventType $eventType): array
    {
        $itemTypes  = $this->api->get('/v1/item-types')['data'] ?? [];
        $allowedIds = $eventType?->item_type_ids ?? [];

//        if (empty($allowedIds)) {
//            return $itemTypes;
//        }

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
            'companion'             => null,
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

        // Its companion goes with it.
        if (!empty($event['companion']['id'])) {
            $this->removedEventIds[] = $event['companion']['id'];
        }

        unset($this->events[$index]);
        $this->events = array_values($this->events);

        // Keep the per-row option maps aligned with the re-indexed rows.
        foreach (self::ROW_MAPS as $map) {
            $this->{$map} = $this->reindexAfterRemoval($this->{$map}, $index);
        }
    }

    private function reindexAfterRemoval(array $map, int $removedIndex): array
    {
        $result = [];

        foreach ($map as $i => $value) {
            if ($i === $removedIndex) {
                continue;
            }
            $result[$i > $removedIndex ? $i - 1 : $i] = $value;
        }

        return $result;
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

    /** Companion recipes for the row: only those consuming what the lead makes. */
    private function fetchCompanionRecipes(int $index): array
    {
        $companionItemId = (int) ($this->events[$index]['companion']['item_id'] ?? 0);

        if (!$companionItemId) {
            return [];
        }

        $leadItemId = (int) ($this->events[$index]['item_id'] ?? 0) ?: null;

        return $this->linkedEvents->companionRecipes($companionItemId, $leadItemId)->toArray();
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

        // Refresh the dependent selectpickers on the client (they are
        // wire:ignore'd, so Livewire won't re-render their options).
        $this->dispatch('ec-cascade',
            key: $this->events[$index]['key'] ?? null,
            hasRecipe: $hasRecipe,
            itemTypes: array_values($this->itemTypesByRow[$index] ?? []),
            items: [],
            recipes: [],
        );

        $this->setCompanionFor($index, $eventType);
    }

    /**
     * Attach, replace or drop the row's companion to match its event type's
     * "executed with another event type" setting. A saved companion keeps its
     * id when the companion type stays the same; otherwise it's removed on save.
     */
    private function setCompanionFor(int $index, ?EventType $eventType): void
    {
        $existing      = $this->events[$index]['companion'] ?? null;
        $companionType = $eventType?->companionEventType;

        unset($this->companionItemsByRow[$index], $this->companionRecipesByRow[$index]);

        $keepId = $existing && $companionType && (int) $existing['event_type_id'] === $companionType->id
            ? $existing['id']
            : null;

        if (!empty($existing['id']) && !$keepId) {
            $this->removedEventIds[] = $existing['id'];
        }

        // A started event can't gain a companion — see companionRowFromEvent().
        $started = !empty($this->events[$index]['id'])
            && Event::whereKey($this->events[$index]['id'])->whereNotNull('status')->exists();

        if (!$companionType || ($started && !$keepId)) {
            $this->events[$index]['companion'] = null;
            unset($this->companionItemTypesByRow[$index]);

            return;
        }

        $this->events[$index]['companion']     = $this->blankCompanion($companionType, $keepId);
        $this->companionItemTypesByRow[$index] = $this->allowedItemTypesFor($companionType);

        $this->dispatchCompanionCascade($index,
            itemTypes: array_values($this->companionItemTypesByRow[$index]),
            items: [],
            recipes: [],
        );
    }

    /** Push fresh options to the companion's wire:ignore'd selectpickers. */
    private function dispatchCompanionCascade(int $index, ?array $itemTypes = null, ?array $items = null, ?array $recipes = null): void
    {
        $this->dispatch('ec-cascade', ...array_filter([
            'key'        => $this->events[$index]['key'] ?? null,
            'cItemTypes' => $itemTypes,
            'cItems'     => $items,
            'cRecipes'   => $recipes,
        ], fn($value) => $value !== null));
    }

    // ─── Companion cascade (item type → item → recipe → batch count) ─────────

    public function onCompanionItemTypeChanged(int $index, $itemTypeId): void
    {
        if (empty($this->events[$index]['companion'])) {
            return;
        }

        $itemTypeId = $itemTypeId !== '' ? (int) $itemTypeId : null;

        $this->events[$index]['companion']['item_type_id']   = $itemTypeId;
        $this->events[$index]['companion']['item_id']        = null;
        $this->events[$index]['companion']['recipe_type_id'] = null;
        $this->events[$index]['companion']['recipe_id']      = null;
        $this->events[$index]['companion']['batch_auto']     = null;

        unset($this->companionRecipesByRow[$index]);

        if ($itemTypeId) {
            $this->companionItemsByRow[$index] = $this->fetchItemsForType($itemTypeId);
        } else {
            unset($this->companionItemsByRow[$index]);
        }

        $this->dispatchCompanionCascade($index,
            items: array_values($this->companionItemsByRow[$index] ?? []),
            recipes: [],
        );
    }

    public function onCompanionItemChanged(int $index, $itemId): void
    {
        if (empty($this->events[$index]['companion'])) {
            return;
        }

        $this->events[$index]['companion']['item_id'] = $itemId !== '' ? (int) $itemId : null;

        $this->refreshCompanionRecipes($index);
    }

    public function onCompanionRecipeChanged(int $index, $recipeId): void
    {
        if (empty($this->events[$index]['companion'])) {
            return;
        }

        $recipeId = $recipeId !== '' ? (int) $recipeId : null;

        $this->events[$index]['companion']['recipe_id']      = $recipeId;
        $this->events[$index]['companion']['recipe_type_id'] = $recipeId ? Recipe::find($recipeId)?->recipe_type_id : null;

        $this->recalculateCompanionBatch($index);
    }

    /**
     * Reload the companion's recipe list (it depends on the lead's item too)
     * and clear the chosen recipe, which the list may no longer offer.
     */
    private function refreshCompanionRecipes(int $index): void
    {
        if (empty($this->events[$index]['companion'])) {
            return;
        }

        $this->events[$index]['companion']['recipe_type_id'] = null;
        $this->events[$index]['companion']['recipe_id']      = null;
        $this->events[$index]['companion']['batch_auto']     = null;

        $this->companionRecipesByRow[$index] = $this->fetchCompanionRecipes($index);

        $this->dispatchCompanionCascade($index, recipes: array_values($this->companionRecipesByRow[$index]));
    }

    /**
     * Fill the companion's batch count from the lead: as many companion
     * batches as the lead's output fills. Left as it is when it can't be
     * worked out; the field stays editable either way.
     */
    private function recalculateCompanionBatch(int $index): void
    {
        $row       = $this->events[$index] ?? null;
        $companion = $row['companion'] ?? null;

        if (!$companion || !$companion['recipe_id'] || !$row['recipe_id']) {
            return;
        }

        $count = $this->linkedEvents->companionBatchCount(
            Recipe::find($row['recipe_id']),
            $row['batch_count'],
            Recipe::with('inputs')->find($companion['recipe_id']),
            $row['item_id'] ? (int) $row['item_id'] : null,
        );

        if ($count !== null) {
            $this->events[$index]['companion']['batch_count'] = $count;
        }

        $this->events[$index]['companion']['batch_auto'] = $count;
    }

    public function onItemTypeChanged(int $index, $itemTypeId): void
    {
        $itemTypeId = $itemTypeId !== '' ? (int) $itemTypeId : null;

        $this->events[$index]['item_type_id'] = $itemTypeId;

        // For non-recipe events the item type is just metadata — it must not
        // reset the item/recipe cascade or the event type's own duration.
        $hasRecipe = !empty($this->events[$index]['event_type_has_recipe']);

        if (!$hasRecipe) {
            return;
        }

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

        $this->dispatch('ec-cascade',
            key: $this->events[$index]['key'] ?? null,
            items: array_values($this->itemsByRow[$index] ?? []),
            recipes: [],
        );

        $this->refreshCompanionRecipes($index);
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

        $this->dispatch('ec-cascade',
            key: $this->events[$index]['key'] ?? null,
            recipes: array_values($this->recipesByRow[$index] ?? []),
        );

        $this->recalculateDuration($index);

        // The companion only offers recipes consuming the lead's item.
        $this->refreshCompanionRecipes($index);
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
        $this->recalculateCompanionBatch($index);
    }

    public function onBatchCountChanged(int $index, $batchCount): void
    {
        $this->events[$index]['batch_count'] = $batchCount;

        $this->recalculateDuration($index);
        $this->recalculateCompanionBatch($index);
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

            // The companion is always a recipe event type.
            if (!empty($event['companion'])) {
                $rules["events.{$index}.companion.item_type_id"] = 'required|integer';
                $rules["events.{$index}.companion.item_id"]      = 'required|integer';
                $rules["events.{$index}.companion.recipe_id"]    = 'required|integer|exists:recipes,id';
                $rules["events.{$index}.companion.batch_count"]  = 'required|string|max:255';
            }
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
        'events.*.companion.item_type_id.required' => 'Item type is required.',
        'events.*.companion.item_id.required'      => 'Item is required.',
        'events.*.companion.recipe_id.required'    => 'Recipe is required.',
        'events.*.companion.batch_count.required'  => 'Batch number is required.',
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
                ? Carbon::parse($event['from_time_raw'])->addMinutes((int) $duration)->format('Y-m-d H:i:s')
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

    /**
     * Create or update the companion event run in parallel with $lead. New
     * companions start unplaced, like any new event; a placed one has its
     * duration recomputed for its lane, as the lead's is.
     */
    private function saveCompanion(Event $lead, array $companion): \Illuminate\Support\Collection
    {
        $data = [
            'plan_id'         => $this->planId,
            'linked_event_id' => $lead->id,
            'event_type_id'   => $companion['event_type_id'],
            'name'            => EventType::find($companion['event_type_id'])?->name ?? '',
            'item_type_id'    => $companion['item_type_id'] ?: null,
            'item_id'         => $companion['item_id'],
            'recipe_type_id'  => $companion['recipe_type_id'],
            'recipe_id'       => $companion['recipe_id'],
            'batch_count'     => $companion['batch_count'],
        ];

        $existing = !empty($companion['id'])
            ? Event::where('plan_id', $this->planId)->find($companion['id'])
            : null;

        if (!$existing) {
            Event::create($data);

            return collect();
        }

        $placement = $this->recomputePlacedDuration($data + [
            'placeable_type' => $existing->placeable_type,
            'placeable_id'   => $existing->placeable_id,
            'from_time_raw'  => $existing->from_time,
            'duration'       => null,
        ], true);

        // The pair's shared window is set by syncPair() once both are saved;
        // this keeps the link right when only the companion is placed.
        ['to_plan_id' => $toPlanId, 'created' => $created] =
            $this->carryOverService->carryOverLink($this->plan, $existing->from_time, $placement['to_time']);

        $existing->update($data + $placement + ['to_plan_id' => $toPlanId]);

        return $created;
    }

    public function submit(): void
    {
        $this->validate();

        $typeNames = EventType::whereIn('id',
            array_filter(array_column($this->events, 'event_type_id'))
        )->pluck('name', 'id');

        \DB::beginTransaction();
        try {

            if (!empty($this->removedEventIds)) {
                Event::whereIn('id', $this->removedEventIds)
                    ->where('plan_id', $this->planId)
                    ->delete();
            }

            $createdPlans = collect();

            foreach ($this->events as $event) {
                $hasRecipe = !empty($event['event_type_has_recipe']);

                $data = [
                    'plan_id'        => $this->planId,
                    'event_type_id'  => $event['event_type_id'],
                    'name'           => $typeNames[$event['event_type_id']] ?? '',
                    'description'    => $event['description'] ?: null,
                    // Item type only applies to recipe event types.
                    'item_type_id'   => $hasRecipe ? ($event['item_type_id'] ?: null) : null,
                    'item_id'        => $hasRecipe ? $event['item_id'] : null,
                    'recipe_type_id' => $hasRecipe ? $event['recipe_type_id'] : null,
                    'recipe_id'      => $hasRecipe ? $event['recipe_id'] : null,
                    'batch_count'    => $hasRecipe ? $event['batch_count'] : null,
                ] + $this->recomputePlacedDuration($event, $hasRecipe);

                // A duration change can push a placed event past midnight
                // (or pull it back): to_plan_id links it to the plan of the
                // day it now ends on, creating that plan — and, on a month
                // boundary, its month plan — when missing.
                ['to_plan_id' => $toPlanId, 'created' => $created] = $this->carryOverService->carryOverLink(
                    $this->plan,
                    $event['from_time_raw'] ?? null,
                    $data['to_time']
                );

                $data['to_plan_id'] = $toPlanId;
                $createdPlans       = $createdPlans->merge($created);

                if (!empty($event['id'])) {
                    Event::where('id', $event['id'])
                        ->where('plan_id', $this->planId)
                        ->update($data);

                    $lead = Event::where('plan_id', $this->planId)->find($event['id']);
                } else {
                    // New events start unplaced — their time slot is set by
                    // dragging them onto the plan board calendar.
                    $lead = Event::create($data);
                }

                if ($lead && !empty($event['companion'])) {
                    $createdPlans = $createdPlans->merge($this->saveCompanion($lead, $event['companion']));

                    // Both run on one window — as long as the longer of the two.
                    $createdPlans = $createdPlans->merge($this->linkedEvents->syncPair($lead->fresh()));
                }
            }

            \DB::commit();
            $this->removedEventIds = [];

            if ($message = $this->carryOverService->describeCreatedPlans($createdPlans)) {
                session()->flash('success', "An event runs past midnight — {$message}");
            }

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
