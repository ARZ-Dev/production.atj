<?php

namespace App\Livewire\Events;

use App\Livewire\Concerns\PreventsDuplicateSubmission;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Line;
use App\Models\Plan;
use App\Models\Recipe;
use App\Services\ApiService;
use App\Services\InventoryService;
use App\Services\LineRunService;
use App\Services\PlanCarryOverService;
use App\Services\RecipeDurationService;
use Carbon\Carbon;
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

    /** Every per-row option map, reset and re-indexed together. */
    private const ROW_MAPS = ['itemTypesByRow', 'itemsByRow', 'recipesByRow'];

    /** Per-request cache of items by item type (id => items). */
    protected array $itemsByTypeCache = [];

    /** Per-request cache of warehouse names (id => name). */
    protected ?array $warehouseNames = null;

    protected ApiService $api;
    protected RecipeDurationService $durationService;
    protected PlanCarryOverService $carryOverService;
    protected LineRunService $lineRuns;
    protected InventoryService $inventory;

    public function boot(ApiService $api, RecipeDurationService $durationService, PlanCarryOverService $carryOverService, LineRunService $lineRuns, InventoryService $inventory): void
    {
        $this->api              = $api;
        $this->durationService  = $durationService;
        $this->carryOverService = $carryOverService;
        $this->lineRuns         = $lineRuns;
        $this->inventory        = $inventory;
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
            // Started: its type, recipe and batch count are what End settles
            // against, so only the description stays editable. Display and
            // handler hint only — submit() re-reads the status.
            'started'               => (bool) $event->status,
        ];
    }

    /** The row belongs to an event that has started (see buildRowFromEvent()). */
    private function rowStarted(int $index): bool
    {
        return !empty($this->events[$index]['started']);
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
    }

    // ─── Item types assigned to an event type, filtering the full external
    //     item-types list. ───────────────────────────────────────────────────
    private function allowedItemTypesFor(?EventType $eventType): array
    {
        $itemTypes  = $this->api->get('/v1/item-types')['data'] ?? [];
        $allowedIds = $eventType?->item_type_ids ?? [];

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
    }

    public function onBatchCountChanged(int $index, $batchCount): void
    {
        // A started event keeps its type, item, recipe and batch count.
        if ($this->rowStarted($index)) {
            return;
        }

        $this->events[$index]['batch_count'] = $batchCount;

        $this->recalculateDuration($index);
    }

    /**
     * Preview a recipe event's duration as soon as recipe, item and batch
     * count are known — using any capacity defined for that item (or, for a
     * line run, the first line with rates) as an estimate, since the event
     * isn't placed yet. It's recalculated against the actual lane once dropped
     * on the calendar (see PlanBoard::dropEvent).
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
     * duration (recipe events: from batch/recipe/capacity or the line's
     * stations; otherwise: from the event type) and shift its to_time
     * accordingly, since the from_time placement itself doesn't change here.
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

        // A started event's settings are what End settles against — so only
        // its description is saved. Read from the database: the rows' own
        // "started" flag is just a hint for the form.
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
                } else {
                    // New events start unplaced — their time slot is set by
                    // dragging them onto the plan board calendar.
                    Event::create($data + ['submission_key' => $this->submissionKey]);
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

    // ─── Line runs ───────────────────────────────────────────────────────────

    /**
     * What the popup shows for a row whose recipe has stages: the line's
     * stations and which is slowest, the run's duration at that rate, and what
     * it uses with what each warehouse holds now. The line is the one the
     * event is placed on, or else the first line taking its event type that
     * has stations — an estimate until it's placed. Null for anything else.
     */
    private function runInfo(array $row): ?array
    {
        if (empty($row['event_type_has_recipe']) || empty($row['recipe_id'])) {
            return null;
        }

        $recipe = Recipe::with('stages', 'inputs')->find($row['recipe_id']);

        if (!$this->lineRuns->isStaged($recipe)) {
            return null;
        }

        $placed = ($row['placeable_type'] ?? null) === Line::class && !empty($row['placeable_id']);
        $line   = $placed
            ? Line::with('stations')->find($row['placeable_id'])
            : Line::with('stations')
                ->whereHas('stations')
                ->when($row['event_type_id'] ?? null, fn($q, $typeId) => $q->whereHas('eventTypes', fn($t) => $t->where('event_types.id', $typeId)))
                ->orderBy('name')
                ->first();

        $batches  = $this->lineRuns->number($row['batch_count'] ?? null) ?: 1.0;
        $perBatch = (float) ($recipe->quantity_per_batch ?: 1);
        $rate     = $line ? $this->lineRuns->lineRate($recipe, $line) : null;
        $perHour  = $rate['batches_per_hour'] ?? null;

        $materials = array_values(array_map(function (array $material) use ($line) {
            $warehouseId = $line ? $this->lineRuns->warehouseFor($line, $material['item_type_id']) : null;

            return $material + [
                'warehouse_name' => $warehouseId ? $this->warehouseName($warehouseId) : null,
                'available'      => $warehouseId ? $this->inventory->available($warehouseId, $material['item_id'], $material['item_unit_id']) : null,
            ];
        }, $this->lineRuns->materials($recipe, $batches)));

        return [
            'line_name'       => $line?->name,
            'placed'          => $placed,
            'stages'          => $rate['stages'] ?? [],
            'bottleneck'      => $rate['bottleneck'] ?? null,
            'missing'         => $rate['missing'] ?? [],
            'per_batch'       => $perBatch,
            'per_hour'        => $perHour ? $perHour * $perBatch : null,
            'duration'        => $perHour ? (int) round($batches / $perHour * 60) : null,
            'output_quantity' => $batches * $perBatch,
            'output_unit'     => $this->lineRuns->unitName((int) $recipe->item_id, $recipe->item_unit_id) ?? '',
            'materials'       => $materials,
        ];
    }

    private function warehouseName(int $warehouseId): string
    {
        if ($this->warehouseNames === null) {
            $this->warehouseNames = collect($this->api->get('/v1/warehouses')['data'] ?? [])->pluck('name', 'id')->all();
        }

        return $this->warehouseNames[$warehouseId] ?? "Warehouse #{$warehouseId}";
    }

    public function render()
    {
        // Worked out on every render rather than stored, so the panel can never
        // lag behind the fields it describes.
        $runs = [];

        foreach ($this->events as $index => $row) {
            if ($info = $this->runInfo($row)) {
                $runs[$index] = $info;
            }
        }

        return view('livewire.events.event-create', [
            'runs' => $runs,
        ]);
    }
}
