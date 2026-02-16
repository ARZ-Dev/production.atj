<?php

namespace App\Livewire\Plans;

use App\Models\Plan;
use Livewire\Component;

class PlanView extends Component
{
    public $plan;
    public $events;
    public $status;

    public function mount($id, $status)
    {
        $this->status = $status;
        $this->plan = Plan::with([
            'company',
            'productionLine',
            'events' => function ($query) {
                $query->orderBy('from_time', 'asc')->where('deleted_at', null);
            },
            'events.eventType',
            'events.recipe',
        ])->findOrFail($id);

        $this->events = $this->plan->events;
    }

    public function render()
    {
        return view('livewire.plans.plan-view');
    }
}