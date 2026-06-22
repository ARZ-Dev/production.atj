<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Plan;
use App\Models\Recipe;
use App\Models\RecipeType;
use App\Models\Shift;
use App\Services\ApiService;
use Carbon\Carbon;
use Livewire\Component;

class EventCreate extends Component
{
    public $planId;
    public $plan;
    public $eventTypes = [];
    public $events = [];
    public $removedEventIds = [];
    public array $shifts = [];

    // Per-row cascading option lists, keyed by row index.
    public array $itemTypesByRow = [];
    public array $itemsByRow = [];
    public array $recipeTypesByRow = [];
    public array $recipesByRow = [];

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount($planId): void
    {
        authorizeRequest('production.event-create');

        $this->planId     = $planId;
        $this->plan       = Plan::with('shift')->findOrFail($planId);
        $this->eventTypes = EventType::orderBy('name')->get();
        $this->shifts     = Shift::orderBy('from_time')
            ->get()
            ->map(fn($s) => [
                'id'   => $s->id,
                'name' => $s->name,
                'from' => Carbon::parse($s->from_time)->format('H:i'),
                'to'   => Carbon::parse($s->to_time)->format('H:i'),
            ])
            ->values()
            ->toArray();

        $this->loadExistingEvents();
    }

    public function loadExistingEvents(): void
    {
        $existing = Event::where('plan_id', $this->planId)->get();

        if ($existing->isEmpty()) {
            $this->addEventRow();
            return;
        }

        foreach ($existing as $event) {
            $hasRecipe = (bool) ($event->eventType?->has_recipe);

            $this->events[] = [
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
                'from_time'             => $event->from_time ? Carbon::parse($event->from_time)->format('H:i') : '',
                'to_time'               => $event->to_time   ? Carbon::parse($event->to_time)->format('H:i')   : '',
                'description'           => $event->description ?? '',
            ];

            $index = count($this->events) - 1;

            if ($hasRecipe) {
                $this->itemTypesByRow[$index] = $this->api->get('/v1/item-types')['data'] ?? [];

                if ($event->item_type_id) {
                    $this->itemsByRow[$index]     = $this->fetchItemsForType((int) $event->item_type_id);
                    $this->recipeTypesByRow[$index] = $this->fetchRecipeTypesForItemType((int) $event->item_type_id);
                }

                if ($event->recipe_type_id && $event->item_id) {
                    $this->recipesByRow[$index] = $this->fetchRecipes((int) $event->recipe_type_id, (int) $event->item_id);
                }
            }
        }
    }

    public function addEventRow(): void
    {
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
            'from_time'             => '',
            'to_time'               => '',
            'description'           => '',
        ];
    }

    public function removeEventRow(int $index): void
    {
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

    private function fetchRecipeTypesForItemType(int $itemTypeId): array
    {
        return RecipeType::query()
            ->whereJsonContains('item_type_ids', $itemTypeId)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    private function fetchRecipes(int $recipeTypeId, int $itemId): array
    {
        return Recipe::where('recipe_type_id', $recipeTypeId)
            ->where('item_id', $itemId)
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

        unset($this->itemsByRow[$index], $this->recipeTypesByRow[$index], $this->recipesByRow[$index]);

        if ($hasRecipe) {
            $this->events[$index]['duration'] = null;
            $this->itemTypesByRow[$index]     = $this->api->get('/v1/item-types')['data'] ?? [];
        } else {
            $this->events[$index]['duration'] = $eventType?->duration;
            unset($this->itemTypesByRow[$index]);
        }
    }

    public function onItemTypeChanged(int $index, $itemTypeId): void
    {
        $itemTypeId = $itemTypeId !== '' ? (int) $itemTypeId : null;

        $this->events[$index]['item_type_id']   = $itemTypeId;
        $this->events[$index]['item_id']        = null;
        $this->events[$index]['recipe_type_id'] = null;
        $this->events[$index]['recipe_id']      = null;

        unset($this->recipesByRow[$index]);

        if ($itemTypeId) {
            $this->itemsByRow[$index]       = $this->fetchItemsForType($itemTypeId);
            $this->recipeTypesByRow[$index] = $this->fetchRecipeTypesForItemType($itemTypeId);
        } else {
            unset($this->itemsByRow[$index], $this->recipeTypesByRow[$index]);
        }
    }

    public function onItemChanged(int $index, $itemId): void
    {
        $itemId = $itemId !== '' ? (int) $itemId : null;

        $this->events[$index]['item_id']    = $itemId;
        $this->events[$index]['recipe_id']  = null;

        $recipeTypeId = $this->events[$index]['recipe_type_id'] ?? null;

        if ($recipeTypeId && $itemId) {
            $this->recipesByRow[$index] = $this->fetchRecipes((int) $recipeTypeId, $itemId);
        } else {
            unset($this->recipesByRow[$index]);
        }
    }

    public function onRecipeTypeChanged(int $index, $recipeTypeId): void
    {
        $recipeTypeId = $recipeTypeId !== '' ? (int) $recipeTypeId : null;

        $this->events[$index]['recipe_type_id'] = $recipeTypeId;
        $this->events[$index]['recipe_id']      = null;

        $itemId = $this->events[$index]['item_id'] ?? null;

        if ($recipeTypeId && $itemId) {
            $this->recipesByRow[$index] = $this->fetchRecipes($recipeTypeId, (int) $itemId);
        } else {
            unset($this->recipesByRow[$index]);
        }
    }

    public function onRecipeChanged(int $index, $recipeId): void
    {
        $this->events[$index]['recipe_id'] = $recipeId !== '' ? (int) $recipeId : null;
    }

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->events as $index => $event) {
            $hasRecipe = !empty($event['event_type_has_recipe']);

            $rules["events.{$index}.event_type_id"] = 'required|exists:event_types,id';
            $rules["events.{$index}.from_time"]      = 'required|date_format:H:i';
            $rules["events.{$index}.to_time"]        = 'nullable|date_format:H:i';
            $rules["events.{$index}.description"]    = 'nullable|string|max:1000';

            $requiredIfRecipe = $hasRecipe ? 'required' : 'nullable';
            $rules["events.{$index}.item_type_id"]   = "{$requiredIfRecipe}|integer";
            $rules["events.{$index}.item_id"]        = "{$requiredIfRecipe}|integer";
            $rules["events.{$index}.recipe_type_id"] = "{$requiredIfRecipe}|integer|exists:recipe_types,id";
            $rules["events.{$index}.recipe_id"]      = "{$requiredIfRecipe}|integer|exists:recipes,id";
            $rules["events.{$index}.batch_count"]    = "{$requiredIfRecipe}|string|max:255";
        }

        return $rules;
    }

    protected $messages = [
        'events.*.event_type_id.required'  => 'Event type is required.',
        'events.*.event_type_id.exists'    => 'Selected event type is invalid.',
        'events.*.from_time.required'      => 'From time is required.',
        'events.*.from_time.date_format'   => 'Invalid time format (H:i).',
        'events.*.to_time.date_format'     => 'Invalid time format (H:i).',
        'events.*.item_type_id.required'   => 'Item type is required.',
        'events.*.item_id.required'        => 'Item is required.',
        'events.*.recipe_type_id.required' => 'Recipe type is required.',
        'events.*.recipe_id.required'      => 'Recipe is required.',
        'events.*.batch_count.required'    => 'Batch number is required.',
    ];

    private function validateShiftTimes(): bool
    {
        if (!$this->plan->shift) {
            return true;
        }

        $shiftFrom  = Carbon::parse($this->plan->shift->from_time);
        $shiftTo    = Carbon::parse($this->plan->shift->to_time);
        $shiftLabel = $shiftFrom->format('H:i') . ' – ' . $shiftTo->format('H:i');
        $hasErrors  = false;

        foreach ($this->events as $index => $event) {
            if (empty($event['from_time'])) {
                continue;
            }

            $eventFrom = Carbon::parse($event['from_time']);
            $eventTo   = !empty($event['to_time']) ? Carbon::parse($event['to_time']) : null;

            if ($eventFrom->lt($shiftFrom) || $eventFrom->gt($shiftTo)) {
                $this->addError("events.{$index}.from_time", "Must be within shift hours ({$shiftLabel}).");
                $hasErrors = true;
            }

            if ($eventTo) {
                if ($eventTo->gt($shiftTo)) {
                    $this->addError("events.{$index}.to_time", "Must be within shift hours ({$shiftLabel}).");
                    $hasErrors = true;
                }
                if ($eventTo->lte($eventFrom)) {
                    $this->addError("events.{$index}.to_time", 'Must be after the from time.');
                    $hasErrors = true;
                }
            }
        }

        return !$hasErrors;
    }

    private function validateNoOverlap(): bool
    {
        $count     = count($this->events);
        $hasErrors = false;

        for ($i = 0; $i < $count; $i++) {
            $a = $this->events[$i];
            if (empty($a['from_time'])) {
                continue;
            }

            $aFrom = Carbon::parse($a['from_time']);
            $aTo   = !empty($a['to_time']) ? Carbon::parse($a['to_time']) : null;

            for ($j = $i + 1; $j < $count; $j++) {
                $b = $this->events[$j];
                if (empty($b['from_time'])) {
                    continue;
                }

                $bFrom = Carbon::parse($b['from_time']);
                $bTo   = !empty($b['to_time']) ? Carbon::parse($b['to_time']) : null;

                $overlaps = false;

                if ($aTo && $bTo) {
                    // Both have ranges: overlap when aFrom < bTo AND bFrom < aTo
                    $overlaps = $aFrom->lt($bTo) && $bFrom->lt($aTo);
                } elseif ($aTo) {
                    // A is a range, B is a point
                    $overlaps = $bFrom->gte($aFrom) && $bFrom->lt($aTo);
                } elseif ($bTo) {
                    // B is a range, A is a point
                    $overlaps = $aFrom->gte($bFrom) && $aFrom->lt($bTo);
                } else {
                    // Both are points — overlap only if identical
                    $overlaps = $aFrom->eq($bFrom);
                }

                if ($overlaps) {
                    $this->addError(
                        "events.{$i}.from_time",
                        "Event #" . ($i + 1) . " overlaps with event #" . ($j + 1) . "."
                    );
                    $this->addError(
                        "events.{$j}.from_time",
                        "Event #" . ($j + 1) . " overlaps with event #" . ($i + 1) . "."
                    );
                    $hasErrors = true;
                }
            }
        }

        return !$hasErrors;
    }

    public function submit(): void
    {
        $this->validate();

        if (!$this->validateShiftTimes()) {
            return;
        }

        if (!$this->validateNoOverlap()) {
            return;
        }

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
                    'from_time'      => $event['from_time'],
                    'to_time'        => $event['to_time'] ?: null,
                    'description'    => $event['description'] ?: null,
                    'item_type_id'   => $hasRecipe ? $event['item_type_id'] : null,
                    'item_id'        => $hasRecipe ? $event['item_id'] : null,
                    'recipe_type_id' => $hasRecipe ? $event['recipe_type_id'] : null,
                    'recipe_id'      => $hasRecipe ? $event['recipe_id'] : null,
                    'batch_count'    => $hasRecipe ? $event['batch_count'] : null,
                    'planned_duration' => $hasRecipe ? null : $event['duration'],
                ];

                if (!empty($event['id'])) {
                    Event::where('id', $event['id'])
                        ->where('plan_id', $this->planId)
                        ->update($data);
                } else {
                    Event::create($data);
                }
            }

            \DB::commit();
            $this->removedEventIds = [];

            redirect()->route('plans.view', $this->planId)
                ->with('success', 'Events saved successfully.');

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
