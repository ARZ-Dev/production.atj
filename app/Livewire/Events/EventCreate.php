<?php

namespace App\Livewire\Events;

use App\Livewire\Concerns\PreventsDuplicateSubmission;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Packaging;
use App\Models\Plan;
use App\Models\Recipe;
use App\Services\ApiService;
use App\Services\LinkedEventService;
use App\Services\PlanCarryOverService;
use App\Services\RecipeDurationService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class EventCreate extends Component
{
    use PreventsDuplicateSubmission;

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

    // Option lists for each row's companion events (the event type executed
    // with the row's type — e.g. packaging alongside production, once per
    // packaging lane), keyed by the lead row's index. Item types and
    // packagings are the same for every companion of a row; items and recipes
    // are per companion, keyed by its stable key within the row.
    public array $companionItemTypesByRow = [];
    public array $companionPackagingsByRow = [];
    public array $companionItemsByRow = [];
    public array $companionRecipesByRow = [];

    /** Every per-row option map, reset and re-indexed together. */
    private const ROW_MAPS = [
        'itemTypesByRow', 'itemsByRow', 'recipesByRow',
        'companionItemTypesByRow', 'companionPackagingsByRow', 'companionItemsByRow', 'companionRecipesByRow',
    ];

    /** Per-request cache of items by item type (id => items). */
    protected array $itemsByTypeCache = [];

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
        // The popup stays on the board between uses: each opening is a new form.
        $this->renewSubmissionKey();
        $this->reset('events', 'removedEventIds', ...self::ROW_MAPS);
        $this->editingEventId = null;
        $this->addEventRow();
        $this->resetValidation();
    }

    #[On('openForEdit')]
    public function openForEdit(int $eventId): void
    {
        $this->renewSubmissionKey();
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
            'companions'            => $this->companionRowsFromEvent($event),
            // Started: stock was reserved from its type/recipe/batch and End
            // settles against them, so only the description stays editable.
            // Display/handler hint only — submit() re-reads the status.
            'started'               => (bool) $event->status,
        ];
    }

    /** The row belongs to an event that has started (see buildRowFromEvent()). */
    private function rowStarted(int $index): bool
    {
        return !empty($this->events[$index]['started']);
    }

    /**
     * The companion part of a lead row, keyed by each companion's stable key:
     * the saved companion events, or — when the type was given a companion
     * after this event was created — one blank one, so saving creates it. Not
     * for an event that has already started: the group starts together, so a
     * companion added now could never be started — and the lead could then
     * never be ended.
     */
    private function companionRowsFromEvent(Event $event): array
    {
        $rows = [];

        foreach ($event->companionEvents()->with('eventType')->get() as $companion) {
            $row = $this->companionRowFromEvent($companion);
            $rows[$row['key']] = $row;
        }

        $companionType = $event->eventType?->companionEventType;

        if (!$rows && $companionType && !$event->status) {
            $row = $this->blankCompanion($companionType);
            $rows[$row['key']] = $row;
        }

        return $rows;
    }

    private function companionRowFromEvent(Event $companion): array
    {
        $onPackaging = $companion->placeable_type === Packaging::class && $companion->placeable_id;

        return [
            'key'            => 'e' . $companion->id,
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
            // Where it runs: the packaging it's placed on, else the one it was
            // planned on (it goes there when its lead is dropped on the board).
            'packaging_id'   => $onPackaging
                ? (int) $companion->placeable_id
                : ($companion->planned_packaging_id ? (int) $companion->planned_packaging_id : null),
            'placed'         => (bool) $companion->placeable_id,
        ];
    }

    /**
     * A new companion. The key is random rather than positional: wire:model
     * paths embed it, so it must survive another companion being removed.
     */
    private function blankCompanion(EventType $companionType, ?int $id = null): array
    {
        return [
            'key'            => 'c' . bin2hex(random_bytes(5)),
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
            'packaging_id'   => null,
            'placed'         => false,
        ];
    }

    /** The row's companion, if it has one under that key. */
    private function companionAt(int $index, $key): ?array
    {
        return $this->events[$index]['companions'][(string) $key] ?? null;
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
        $companions = $this->events[$index]['companions'] ?? [];

        if (!$companions) {
            return;
        }

        $companionType = EventType::find(reset($companions)['event_type_id']);

        $this->companionItemTypesByRow[$index]  = $this->allowedItemTypesFor($companionType);
        $this->companionPackagingsByRow[$index] = $this->packagingOptions($companionType?->id);

        foreach ($companions as $key => $companion) {
            if ($companion['item_type_id']) {
                $this->companionItemsByRow[$index][$key] = $this->fetchItemsForType((int) $companion['item_type_id']);
            }

            if ($companion['item_id']) {
                $this->companionRecipesByRow[$index][$key] = $this->fetchCompanionRecipes($index, $key);
            }
        }
    }

    /** The plan's factory's packagings a companion of this type can run on, for its picker. */
    private function packagingOptions(?int $companionTypeId): array
    {
        return $this->linkedEvents->packagingsFor($companionTypeId, $this->plan->factory_id)
            ->map(fn($packaging) => ['id' => $packaging->id, 'name' => $packaging->name])
            ->all();
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
            'companions'            => [],
            'started'               => false,
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

        // Its companions go with it.
        foreach ($event['companions'] ?? [] as $companion) {
            if (!empty($companion['id'])) {
                $this->removedEventIds[] = $companion['id'];
            }
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
        if (array_key_exists($itemTypeId, $this->itemsByTypeCache)) {
            return $this->itemsByTypeCache[$itemTypeId];
        }

        $type     = collect($this->api->get('/v1/item-types')['data'] ?? [])->firstWhere('id', $itemTypeId);
        $typeName = $type['name'] ?? null;

        return $this->itemsByTypeCache[$itemTypeId] = $typeName
            ? ($this->api->get('/v1/items', ['item_type' => $typeName, 'is_active' => true])['data'] ?? [])
            : [];
    }

    private function fetchRecipes(int $itemId): array
    {
        return Recipe::where('item_id', $itemId)
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /** A companion's recipes: only those consuming what the lead makes. */
    private function fetchCompanionRecipes(int $index, string $key): array
    {
        $companionItemId = (int) ($this->companionAt($index, $key)['item_id'] ?? 0);

        if (!$companionItemId) {
            return [];
        }

        $leadItemId = (int) ($this->events[$index]['item_id'] ?? 0) ?: null;

        return $this->linkedEvents->companionRecipes($companionItemId, $leadItemId)->toArray();
    }

    // ─── Cascade handlers (called explicitly via wire:change) ───────────────

    public function onEventTypeChanged(int $index, $eventTypeId): void
    {
        // A started event keeps its type, item, recipe and batch count.
        if ($this->rowStarted($index)) {
            return;
        }

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
     * Attach, replace or drop the row's companions to match its event type's
     * "executed with another event type" setting. Saved companions keep their
     * ids (and so their place on the board) when the companion type stays the
     * same, starting over on the rest like the lead does; otherwise they're
     * removed on save. Every companion gets a new key, so its pickers are drawn
     * afresh rather than keeping what was picked under the old type.
     */
    private function setCompanionFor(int $index, ?EventType $eventType): void
    {
        $existing      = $this->events[$index]['companions'] ?? [];
        $companionType = $eventType?->companionEventType;

        unset(
            $this->companionItemTypesByRow[$index], $this->companionPackagingsByRow[$index],
            $this->companionItemsByRow[$index], $this->companionRecipesByRow[$index],
        );

        $kept = collect($existing)->filter(fn(array $companion) => !empty($companion['id'])
            && $companionType && (int) $companion['event_type_id'] === $companionType->id);

        foreach ($existing as $companion) {
            if (!empty($companion['id']) && !$kept->contains('id', $companion['id'])) {
                $this->removedEventIds[] = $companion['id'];
            }
        }

        // A started event can't gain a companion — see companionRowsFromEvent().
        $started = !empty($this->events[$index]['id'])
            && Event::whereKey($this->events[$index]['id'])->whereNotNull('status')->exists();

        if (!$companionType || ($started && $kept->isEmpty())) {
            $this->events[$index]['companions'] = [];

            return;
        }

        $companions = [];

        foreach ($kept->isEmpty() ? [null] : $kept->all() as $previous) {
            $companion = $this->blankCompanion($companionType, $previous['id'] ?? null);

            if ($previous) {
                $companion['packaging_id'] = $previous['packaging_id'] ?? null;
                $companion['placed']       = $previous['placed'] ?? false;
            }

            $companions[$companion['key']] = $companion;
        }

        $this->events[$index]['companions']     = $companions;
        $this->companionItemTypesByRow[$index]  = $this->allowedItemTypesFor($companionType);
        $this->companionPackagingsByRow[$index] = $this->packagingOptions($companionType->id);
    }

    /** Push fresh options to a companion's wire:ignore'd selectpickers. */
    private function dispatchCompanionCascade(int $index, string $key, ?array $items = null, ?array $recipes = null): void
    {
        $this->dispatch('ec-cascade', ...array_filter([
            'key'      => $this->events[$index]['key'] ?? null,
            'ckey'     => $key,
            'cItems'   => $items,
            'cRecipes' => $recipes,
        ], fn($value) => $value !== null));
    }

    // ─── Companions: add / remove ────────────────────────────────────────────

    /**
     * Another companion of the same type — e.g. packing part of the run on a
     * second packaging lane. It starts as a copy of the last one bar the
     * packaging (most often the same product packed elsewhere), and the run is
     * split again between all of them.
     */
    public function addCompanion(int $index): void
    {
        $companions = $this->events[$index]['companions'] ?? [];

        if (!$companions || $this->rowStarted($index)) {
            return;
        }

        $last          = end($companions);
        $companionType = EventType::find($last['event_type_id']);

        if (!$companionType) {
            return;
        }

        $companion = array_merge($this->blankCompanion($companionType), [
            'item_type_id'   => $last['item_type_id'],
            'item_id'        => $last['item_id'],
            'recipe_type_id' => $last['recipe_type_id'],
            'recipe_id'      => $last['recipe_id'],
        ]);

        $key = $companion['key'];

        $this->events[$index]['companions'][$key] = $companion;

        if (isset($this->companionItemsByRow[$index][$last['key']])) {
            $this->companionItemsByRow[$index][$key] = $this->companionItemsByRow[$index][$last['key']];
        }

        if (isset($this->companionRecipesByRow[$index][$last['key']])) {
            $this->companionRecipesByRow[$index][$key] = $this->companionRecipesByRow[$index][$last['key']];
        }

        $this->applySuggestedSplit($index);
    }

    /** Drop one companion — never the last: the event type runs with one. */
    public function removeCompanion(int $index, string $key): void
    {
        $companion = $this->companionAt($index, $key);

        if (!$companion || $this->rowStarted($index) || count($this->events[$index]['companions']) <= 1) {
            return;
        }

        if (!empty($companion['id'])) {
            $this->removedEventIds[] = $companion['id'];
        }

        unset(
            $this->events[$index]['companions'][$key],
            $this->companionItemsByRow[$index][$key],
            $this->companionRecipesByRow[$index][$key],
        );

        $this->applySuggestedSplit($index);
    }

    // ─── Companion cascade (item type → item → recipe → batch count) ─────────

    public function onCompanionItemTypeChanged(int $index, string $key, $itemTypeId): void
    {
        // A started event keeps its type, item, recipe and batch count.
        if ($this->rowStarted($index) || !$this->companionAt($index, $key)) {
            return;
        }

        $itemTypeId = $itemTypeId !== '' ? (int) $itemTypeId : null;

        $this->events[$index]['companions'][$key]['item_type_id']   = $itemTypeId;
        $this->events[$index]['companions'][$key]['item_id']        = null;
        $this->events[$index]['companions'][$key]['recipe_type_id'] = null;
        $this->events[$index]['companions'][$key]['recipe_id']      = null;
        $this->events[$index]['companions'][$key]['batch_auto']     = null;

        unset($this->companionRecipesByRow[$index][$key]);

        if ($itemTypeId) {
            $this->companionItemsByRow[$index][$key] = $this->fetchItemsForType($itemTypeId);
        } else {
            unset($this->companionItemsByRow[$index][$key]);
        }

        $this->dispatchCompanionCascade($index, $key,
            items: array_values($this->companionItemsByRow[$index][$key] ?? []),
            recipes: [],
        );

        $this->applySuggestedSplit($index);
    }

    public function onCompanionItemChanged(int $index, string $key, $itemId): void
    {
        // A started event keeps its type, item, recipe and batch count.
        if ($this->rowStarted($index) || !$this->companionAt($index, $key)) {
            return;
        }

        $this->events[$index]['companions'][$key]['item_id'] = $itemId !== '' ? (int) $itemId : null;

        $this->refreshCompanionRecipes($index, $key);
        $this->applySuggestedSplit($index);
    }

    public function onCompanionRecipeChanged(int $index, string $key, $recipeId): void
    {
        // A started event keeps its type, item, recipe and batch count.
        if ($this->rowStarted($index) || !$this->companionAt($index, $key)) {
            return;
        }

        $recipeId = $recipeId !== '' ? (int) $recipeId : null;

        $this->events[$index]['companions'][$key]['recipe_id']      = $recipeId;
        $this->events[$index]['companions'][$key]['recipe_type_id'] = $recipeId ? Recipe::find($recipeId)?->recipe_type_id : null;

        $this->applySuggestedSplit($index);
    }

    /**
     * Which packaging the companion runs on. Its capacity decides the
     * companion's share of the run; the companion lands there when its lead
     * is placed (or moves there on save, when it's already on the board).
     */
    public function onCompanionPackagingChanged(int $index, string $key, $packagingId): void
    {
        if ($this->rowStarted($index) || !$this->companionAt($index, $key)) {
            return;
        }

        $packagingId = $packagingId !== '' && $packagingId !== null ? (int) $packagingId : null;

        // Only what the picker offers; validation re-checks on save.
        if ($packagingId && !in_array($packagingId, array_column($this->companionPackagingsByRow[$index] ?? [], 'id'), true)) {
            $packagingId = null;
        }

        $this->events[$index]['companions'][$key]['packaging_id'] = $packagingId;

        $this->applySuggestedSplit($index);
    }

    /** A hand-typed batch count: kept as typed — the split panel shows what it leaves over. */
    public function onCompanionBatchChanged(int $index, string $key, $batchCount): void
    {
        if ($this->rowStarted($index) || !$this->companionAt($index, $key)) {
            return;
        }

        $this->events[$index]['companions'][$key]['batch_count'] = (string) $batchCount;
    }

    /** The split panel's "Split by capacity" button: back to the suggested batch counts. */
    public function resplitCompanions(int $index): void
    {
        if ($this->rowStarted($index)) {
            return;
        }

        $this->applySuggestedSplit($index);
    }

    /**
     * Reload a companion's recipe list (it depends on the lead's item too)
     * and clear the chosen recipe, which the list may no longer offer.
     */
    private function refreshCompanionRecipes(int $index, string $key): void
    {
        if (!$this->companionAt($index, $key)) {
            return;
        }

        $this->events[$index]['companions'][$key]['recipe_type_id'] = null;
        $this->events[$index]['companions'][$key]['recipe_id']      = null;
        $this->events[$index]['companions'][$key]['batch_auto']     = null;

        $this->companionRecipesByRow[$index][$key] = $this->fetchCompanionRecipes($index, $key);

        $this->dispatchCompanionCascade($index, $key, recipes: array_values($this->companionRecipesByRow[$index][$key]));
    }

    /** Every companion of the row: the lead's item changed, so their recipes did too. */
    private function refreshAllCompanionRecipes(int $index): void
    {
        foreach (array_keys($this->events[$index]['companions'] ?? []) as $key) {
            $this->refreshCompanionRecipes($index, (string) $key);
        }
    }

    /**
     * How the row's run is shared out between its companions — see
     * LinkedEventService::distribution(). Null until the lead has a recipe.
     */
    private function distributionFor(int $index): ?array
    {
        $row = $this->events[$index] ?? null;

        if (!$row || empty($row['companions']) || empty($row['recipe_id'])) {
            return null;
        }

        return $this->linkedEvents->distribution(
            Recipe::find($row['recipe_id']),
            $row['batch_count'],
            $row['item_id'] ? (int) $row['item_id'] : null,
            array_map(fn(array $companion) => [
                'recipe_id'    => $companion['recipe_id'],
                'item_id'      => $companion['item_id'],
                'packaging_id' => $companion['packaging_id'] ?? null,
                'batch_count'  => $companion['batch_count'],
            ], $row['companions']),
        );
    }

    /**
     * Fill every companion's batch count with its suggested share of the run
     * (by packaging capacity, else evenly). A companion whose share can't be
     * worked out keeps what it has; the fields stay editable either way.
     */
    private function applySuggestedSplit(int $index): void
    {
        $distribution = $this->distributionFor($index);

        foreach (array_keys($this->events[$index]['companions'] ?? []) as $key) {
            $suggested = $distribution['companions'][$key]['suggested_batches'] ?? null;

            if ($suggested !== null) {
                $this->events[$index]['companions'][$key]['batch_count'] = $suggested;
            }

            $this->events[$index]['companions'][$key]['batch_auto'] = $suggested;
        }
    }

    public function onItemTypeChanged(int $index, $itemTypeId): void
    {
        // A started event keeps its type, item, recipe and batch count.
        if ($this->rowStarted($index)) {
            return;
        }

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

        $this->refreshAllCompanionRecipes($index);
    }

    public function onItemChanged(int $index, $itemId): void
    {
        // A started event keeps its type, item, recipe and batch count.
        if ($this->rowStarted($index)) {
            return;
        }

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

        // Companions only offer recipes consuming the lead's item.
        $this->refreshAllCompanionRecipes($index);
    }

    public function onRecipeChanged(int $index, $recipeId): void
    {
        // A started event keeps its type, item, recipe and batch count.
        if ($this->rowStarted($index)) {
            return;
        }

        $recipeId = $recipeId !== '' ? (int) $recipeId : null;

        $this->events[$index]['recipe_id'] = $recipeId;
        // The dropdown is gone — the recipe type is carried along from the
        // chosen recipe so it still gets stored and shown on the board.
        $this->events[$index]['recipe_type_id'] = $recipeId
            ? Recipe::find($recipeId)?->recipe_type_id
            : null;

        $this->recalculateDuration($index);
        $this->applySuggestedSplit($index);
    }

    public function onBatchCountChanged(int $index, $batchCount): void
    {
        // A started event keeps its type, item, recipe and batch count.
        if ($this->rowStarted($index)) {
            return;
        }

        $this->events[$index]['batch_count'] = $batchCount;

        $this->recalculateDuration($index);
        $this->applySuggestedSplit($index);
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

            // A companion is always a recipe event type. Its packaging is
            // optional — without one it's dragged onto the board by hand.
            foreach ($event['companions'] ?? [] as $key => $companion) {
                $path       = "events.{$index}.companions.{$key}";
                $packagings = $this->linkedEvents->packagingsFor((int) $companion['event_type_id'], $this->plan->factory_id)->pluck('id')->all();

                $rules["{$path}.item_type_id"] = 'required|integer';
                $rules["{$path}.item_id"]      = 'required|integer';
                $rules["{$path}.recipe_id"]    = 'required|integer|exists:recipes,id';
                $rules["{$path}.batch_count"]  = 'required|string|max:255';
                $rules["{$path}.packaging_id"] = ['nullable', 'integer', Rule::in($packagings)];
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
        'events.*.companions.*.item_type_id.required' => 'Item type is required.',
        'events.*.companions.*.item_id.required'      => 'Item is required.',
        'events.*.companions.*.recipe_id.required'    => 'Recipe is required.',
        'events.*.companions.*.batch_count.required'  => 'Batch number is required.',
        'events.*.companions.*.packaging_id.in'       => 'This packaging can\'t run this event type.',
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
     * Create or update one companion event run in parallel with $lead. New
     * companions start unplaced, like any new event, remembering the packaging
     * they were planned on — LinkedEventService::syncGroup() puts them there
     * once the lead is on the board. A placed companion given another
     * packaging here moves to it; either way its duration is recomputed for
     * its lane, as the lead's is.
     */
    private function saveCompanion(Event $lead, array $companion): \Illuminate\Support\Collection
    {
        $packagingId = !empty($companion['packaging_id']) ? (int) $companion['packaging_id'] : null;

        $data = [
            'plan_id'              => $this->planId,
            'linked_event_id'      => $lead->id,
            'event_type_id'        => $companion['event_type_id'],
            'name'                 => EventType::find($companion['event_type_id'])?->name ?? '',
            'item_type_id'         => $companion['item_type_id'] ?: null,
            'item_id'              => $companion['item_id'],
            'recipe_type_id'       => $companion['recipe_type_id'],
            'recipe_id'            => $companion['recipe_id'],
            'batch_count'          => $companion['batch_count'],
            'planned_packaging_id' => $packagingId,
        ];

        $existing = !empty($companion['id'])
            ? Event::where('plan_id', $this->planId)->find($companion['id'])
            : null;

        if (!$existing) {
            Event::create($data + ['submission_key' => $this->submissionKey]);

            return collect();
        }

        $lane = ['placeable_type' => $existing->placeable_type, 'placeable_id' => $existing->placeable_id];

        if ($existing->placeable_id && !$existing->status) {
            $onPackaging = $existing->placeable_type === Packaging::class;

            if ($packagingId && !($onPackaging && (int) $existing->placeable_id === $packagingId)) {
                // Moved in the popup. Its start time stays; syncGroup() lines
                // the group's window up again after the save.
                $lane = ['placeable_type' => Packaging::class, 'placeable_id' => $packagingId, 'production_line_id' => null];
            } elseif (!$packagingId && $onPackaging) {
                // Placed by hand and no packaging picked: the plan follows the board.
                $data['planned_packaging_id'] = (int) $existing->placeable_id;
            }
        }

        $placement = $this->recomputePlacedDuration($data + $lane + [
            'from_time_raw' => $existing->from_time,
            'duration'      => null,
        ], true);

        // The group's shared window is set by syncGroup() once all are saved;
        // this keeps the link right when only the companion is placed.
        ['to_plan_id' => $toPlanId, 'created' => $created] =
            $this->carryOverService->carryOverLink($this->plan, $existing->from_time, $placement['to_time']);

        $existing->update($data + $lane + $placement + ['to_plan_id' => $toPlanId]);

        return $created;
    }

    public function submit(): void
    {
        // One opening of the popup saves once. A repeated Save — a click queued
        // while the first ran, or one made while the board reloads — finds the
        // events the first save created (they carry this key) and just reloads.
        // A save that only edited existing events created nothing to find, and
        // repeating it rewrites the same values.
        if ($this->alreadySubmitted(Event::class)) {
            $this->redirect(route('plans.view', $this->planId));

            return;
        }

        $this->validate();

        $typeNames = EventType::whereIn('id',
            array_filter(array_column($this->events, 'event_type_id'))
        )->pluck('name', 'id');

        // A started event's stock was reserved from its type, recipe and batch
        // count, and End settles against them — so only its description is
        // saved. Read from the database: the rows' own "started" flag is just a
        // hint for the form.
        $startedIds = Event::whereIn('id', array_filter(array_column($this->events, 'id')))
            ->whereNotNull('status')
            ->pluck('id')
            ->all();

        if ($this->removedEventIds
            && Event::whereIn('id', $this->removedEventIds)->whereNotNull('status')->exists()) {
            session()->flash('error', "A started event can't be removed — its stock movements are tied to it.");

            return;
        }

        \DB::beginTransaction();
        try {

            if (!empty($this->removedEventIds)) {
                Event::whereIn('id', $this->removedEventIds)
                    ->where('plan_id', $this->planId)
                    ->delete();
            }

            $createdPlans = collect();

            foreach ($this->events as $event) {
                if (!empty($event['id']) && in_array((int) $event['id'], $startedIds, true)) {
                    Event::where('id', $event['id'])
                        ->where('plan_id', $this->planId)
                        ->update(['description' => $event['description'] ?: null]);

                    continue;
                }

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
                    $lead = Event::create($data + ['submission_key' => $this->submissionKey]);
                }

                if ($lead && !empty($event['companions'])) {
                    foreach ($event['companions'] as $companion) {
                        $createdPlans = $createdPlans->merge($this->saveCompanion($lead, $companion));
                    }

                    // The group runs on one window — as long as the longest of
                    // them; companions planned on a packaging follow a placed lead.
                    $createdPlans = $createdPlans->merge($this->linkedEvents->syncGroup($lead->fresh()));
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
        // Worked out on every render rather than stored, so the split panel
        // can never lag behind the fields it describes.
        $distributions = [];

        foreach (array_keys($this->events) as $index) {
            if (!empty($this->events[$index]['companions'])) {
                $distributions[$index] = $this->distributionFor($index);
            }
        }

        return view('livewire.events.event-create', [
            'distributions' => $distributions,
        ]);
    }
}
