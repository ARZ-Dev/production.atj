<?php

namespace App\Livewire\Events;

use App\Models\Company;
use App\Models\Event;
use App\Models\EventType;
use Livewire\Attributes\On;
use Livewire\Component;

class EventCreate extends Component
{
    public $company_id = null;
    public $companies = [];
    public $eventTypes = [];

    public $events = [];
    public $planId;

    public $removedEventIds = [];
    public $pendingRemoveIndex = null;

    /*
    |--------------------------------------------------------------------------
    | Lifecycle
    |--------------------------------------------------------------------------
    */

    public function mount($planId)
    {
        $this->planId = $planId;

        if (auth()->user()->hasRole('Super Admin')) {
            $this->companies = Company::orderBy('name')->get();
        } else {
            $this->company_id = auth()->user()->company_id;
            $this->eventTypes = EventType::where('company_id', auth()->user()->company_id)->get();
        }

        $this->loadExistingEvents();
    }

    /*
    |--------------------------------------------------------------------------
    | Load Existing Events (Edit Mode)
    |--------------------------------------------------------------------------
    */

    public function loadExistingEvents()
    {
        $existingEvents = Event::where('plan_id', $this->planId)->get();

        if ($existingEvents->isEmpty()) {
            $this->addEventRow();
            return;
        }

        // Super Admin: set company from existing events
        if (auth()->user()->hasRole('Super Admin') && $existingEvents->first()) {
            $this->company_id = $existingEvents->first()->company_id;
            $this->eventTypes = EventType::where('company_id', $this->company_id)->get();
        }

        foreach ($existingEvents as $event) {
            $type = $this->eventTypes->firstWhere('id', $event->event_type_id);
            $hasRecipe = $type ? (bool) $type->has_recipe : false;

            // Get base duration: from recipe if has_recipe, otherwise from event type
            if ($hasRecipe && $event->recipe_id) {
                $recipe = \App\Models\Recipe::find($event->recipe_id);
                $baseDuration = $recipe ? (int) $recipe->duration : 0;
            } else {
                $baseDuration = $type ? (int) $type->duration : 0;
            }

            $calculatedDuration = (int) $event->calculated_duration;
            $batchCount = ($hasRecipe && $baseDuration > 0)
                ? max(1, intdiv($calculatedDuration, $baseDuration))
                : 1;

            $fromTime = $event->from_time
                ? \Carbon\Carbon::parse($event->from_time)->format('H:i')
                : '';
            $toTime = $event->to_time
                ? \Carbon\Carbon::parse($event->to_time)->format('H:i')
                : '';

            $this->events[] = [
                'id'             => $event->id,
                'event_type_id'  => $event->event_type_id,
                'recipe_id'      => $event->recipe_id,
                'name'           => $event->name,
                'has_recipe'     => $hasRecipe,
                'duration'       => $baseDuration,
                'batch_count'    => $batchCount,
                'total_duration' => $calculatedDuration,
                'from_time'      => $fromTime,
                'to_time'        => $toTime,
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Company Change (Super Admin)
    |--------------------------------------------------------------------------
    */

    #[On('getEventTypes')]
    public function getEventTypes($companyId)
    {
        $this->company_id = $companyId;
        $this->eventTypes = EventType::where('company_id', $this->company_id)->get();

        $this->events = [];
        $this->removedEventIds = [];
        $this->addEventRow();

        $this->dispatch('setEventTypes', $this->eventTypes);
    }

    /*
    |--------------------------------------------------------------------------
    | Add / Remove Event Rows
    |--------------------------------------------------------------------------
    */

    public function addEventRow()
    {
        $this->events[] = [
            'id'             => null,
            'event_type_id'  => null,
            'recipe_id'      => null,
            'name'           => '',
            'has_recipe'     => false,
            'duration'       => 0,
            'batch_count'    => 1,
            'total_duration' => 0,
            'from_time'      => '',
            'to_time'        => '',
        ];
    }

    public function removeEventRow($index)
    {
        $event = $this->events[$index] ?? null;

        if (!$event) {
            return;
        }

        // Existing DB record — store index and ask for confirmation
        if (!empty($event['id'])) {
            $this->pendingRemoveIndex = $index;
            $this->dispatch('swal:confirm', [
                'title'             => 'Confirm Removal',
                'text'              => 'Are you sure you want to remove this event? This will permanently delete it on save.',
                'confirmButtonText' => 'Yes, remove it',
                'cancelButtonText'  => 'Cancel',
            ]);
            return;
        }

        // New row — remove immediately
        unset($this->events[$index]);
        $this->events = array_values($this->events);
    }

    public function removeEventRowConfirmed()
    {
        if (is_null($this->pendingRemoveIndex)) {
            return;
        }

        $index = $this->pendingRemoveIndex;
        $event = $this->events[$index] ?? null;

        if ($event && !empty($event['id'])) {
            $this->removedEventIds[] = $event['id'];
        }

        unset($this->events[$index]);
        $this->events = array_values($this->events);
        $this->pendingRemoveIndex = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Event Type Changed
    |--------------------------------------------------------------------------
    */

    public function onEventTypeChanged($index, $eventTypeId)
    {
        $type = collect($this->eventTypes)->firstWhere('id', (int) $eventTypeId);

        if (!$type) {
            return;
        }

        $hasRecipe = (bool) $type['has_recipe'];

        $this->events[$index]['event_type_id'] = $eventTypeId;
        $this->events[$index]['has_recipe']    = $hasRecipe;
        $this->events[$index]['name']          = $type['name'];

        if ($hasRecipe) {
            $recipe = \App\Models\Recipe::find($type['recipe_id']);
            $this->events[$index]['recipe_id'] = $recipe?->id;
            $this->events[$index]['duration']  = $recipe?->duration ?? 0;
        } else {
            $this->events[$index]['recipe_id'] = null;
            $this->events[$index]['duration']  = $type['duration'] ?? 0;
        }

        $this->events[$index]['batch_count'] = 1;

        $this->recalculate($index);
    }

    /*
    |--------------------------------------------------------------------------
    | Auto-Recalculate on Field Update
    |--------------------------------------------------------------------------
    */

    public function updatedEvents($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $index = (int) $parts[0];
            $field = $parts[1];

            if (in_array($field, ['batch_count', 'from_time'])) {
                $this->recalculate($index);
            }
        }
    }

    public function recalculate($index)
    {
        $event = &$this->events[$index];

        $duration   = (int) $event['duration'];
        $batchCount = max(1, (int) ($event['batch_count'] ?? 1));

        $event['total_duration'] = $event['has_recipe']
            ? $duration * $batchCount
            : $duration;

        if (!empty($event['from_time'])) {
            try {
                $from = \Carbon\Carbon::createFromFormat('H:i', $event['from_time']);
                $to   = $from->copy()->addMinutes($event['total_duration']);
                $event['to_time'] = $to->format('H:i');
            } catch (\Exception $e) {
                $event['to_time'] = '';
            }
        } else {
            $event['to_time'] = '';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    protected function rules()
    {
        $rules = [];

        if (auth()->user()->hasRole('Super Admin')) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        foreach ($this->events as $index => $event) {
            $rules["events.{$index}.event_type_id"] = 'required';
            $rules["events.{$index}.name"]           = 'required|string|max:255';
            $rules["events.{$index}.from_time"]      = 'required|date_format:H:i';
            if (!empty($event['has_recipe'])) {
                $rules["events.{$index}.batch_count"] = 'required|integer|min:1';
            }
        }

        return $rules;
    }

    protected $messages = [
        'events.*.event_type_id.required' => 'Event type is required.',
        'events.*.name.required'          => 'Event name is required.',
        'events.*.from_time.required'     => 'From time is required.',
        'events.*.batch_count.required'   => 'Batch count is required.',
        'events.*.batch_count.min'        => 'Batch count must be at least 1.',
    ];

    /**
     * Check for time overlaps between events.
     * Event A conflicts with Event B if A starts before B ends AND B starts before A ends.
     */
    public function validateTimeConflicts()
    {
        $errors = [];

        // Build time ranges for each event
        $ranges = [];
        foreach ($this->events as $index => $event) {
            if (empty($event['from_time']) || empty($event['to_time'])) {
                continue;
            }

            $ranges[] = [
                'index' => $index,
                'name'  => $event['name'] ?: 'Event #' . ($index + 1),
                'from'  => \Carbon\Carbon::createFromFormat('H:i', $event['from_time']),
                'to'    => \Carbon\Carbon::createFromFormat('H:i', $event['to_time']),
            ];
        }

        // Compare each pair
        for ($i = 0; $i < count($ranges); $i++) {
            for ($j = $i + 1; $j < count($ranges); $j++) {
                $a = $ranges[$i];
                $b = $ranges[$j];

                // Overlap: A starts before B ends AND B starts before A ends
                if ($a['from']->lt($b['to']) && $b['from']->lt($a['to'])) {
                    $errors["events.{$a['index']}.from_time"] = "Time conflict: \"{$a['name']}\" ({$a['from']->format('H:i')}–{$a['to']->format('H:i')}) overlaps with \"{$b['name']}\" ({$b['from']->format('H:i')}–{$b['to']->format('H:i')}).";
                    $errors["events.{$b['index']}.from_time"] = "Time conflict: \"{$b['name']}\" ({$b['from']->format('H:i')}–{$b['to']->format('H:i')}) overlaps with \"{$a['name']}\" ({$a['from']->format('H:i')}–{$a['to']->format('H:i')}).";
                }
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $field => $message) {
                $this->addError($field, $message);
            }

            $this->dispatch('swal:error', [
                'title' => 'Time Conflict',
                'text'  => 'Some events have overlapping times. Please fix the conflicts before saving.',
            ]);

            throw new \Illuminate\Validation\ValidationException(
                validator: \Illuminate\Support\Facades\Validator::make([], []),
                response: null
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Submit (Create + Update + Delete)
    |--------------------------------------------------------------------------
    */

    public function submit()
    {
        $this->validate();
        $this->validateTimeConflicts();

        $companyId = auth()->user()->hasRole('Super Admin')
            ? $this->company_id
            : auth()->user()->company_id;

        try {
            \DB::beginTransaction();

            // 1) Delete removed events
            if (!empty($this->removedEventIds)) {
                Event::whereIn('id', $this->removedEventIds)
                    ->where('plan_id', $this->planId)
                    ->delete();
            }

            // 2) Update existing or create new
            foreach ($this->events as $event) {
                $data = [
                    'company_id'          => $companyId,
                    'plan_id'             => $this->planId,
                    'event_type_id'       => $event['event_type_id'],
                    'recipe_id'           => $event['recipe_id'] ?? null,
                    'name'                => $event['name'],
                    'planned_duration'    => $event['duration'],
                    'calculated_duration' => $event['total_duration'],
                    'from_time'           => $event['from_time'] ?: null,
                    'to_time'             => $event['to_time'] ?: null,
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

            return redirect()->route('plans')->with('success', 'Events saved successfully.');

        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Failed to save events: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.events.event-create');
    }
}