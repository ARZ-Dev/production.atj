<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Plan;
use Carbon\Carbon;
use Livewire\Component;

class EventCreate extends Component
{
    public $planId;
    public $plan;
    public $eventTypes = [];
    public $events = [];
    public $removedEventIds = [];

    public function mount($planId): void
    {
        authorizeRequest('production.event-create');

        $this->planId     = $planId;
        $this->plan       = Plan::with('shift')->findOrFail($planId);
        $this->eventTypes = EventType::orderBy('name')->get();

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
            $this->events[] = [
                'id'            => $event->id,
                'event_type_id' => $event->event_type_id,
                'from_time'     => $event->from_time ? Carbon::parse($event->from_time)->format('H:i') : '',
                'to_time'       => $event->to_time   ? Carbon::parse($event->to_time)->format('H:i')   : '',
                'description'   => $event->description ?? '',
            ];
        }
    }

    public function addEventRow(): void
    {
        $this->events[] = [
            'id'            => null,
            'event_type_id' => null,
            'from_time'     => '',
            'to_time'       => '',
            'description'   => '',
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

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->events as $index => $event) {
            $rules["events.{$index}.event_type_id"] = 'required|exists:event_types,id';
            $rules["events.{$index}.from_time"]      = 'required|date_format:H:i';
            $rules["events.{$index}.to_time"]        = 'nullable|date_format:H:i';
            $rules["events.{$index}.description"]    = 'nullable|string|max:1000';
        }

        return $rules;
    }

    protected $messages = [
        'events.*.event_type_id.required' => 'Event type is required.',
        'events.*.event_type_id.exists'   => 'Selected event type is invalid.',
        'events.*.from_time.required'     => 'From time is required.',
        'events.*.from_time.date_format'  => 'Invalid time format (H:i).',
        'events.*.to_time.date_format'    => 'Invalid time format (H:i).',
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
                $data = [
                    'plan_id'       => $this->planId,
                    'event_type_id' => $event['event_type_id'],
                    'name'          => $typeNames[$event['event_type_id']] ?? '',
                    'from_time'     => $event['from_time'],
                    'to_time'       => $event['to_time'] ?: null,
                    'description'   => $event['description'] ?: null,
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
