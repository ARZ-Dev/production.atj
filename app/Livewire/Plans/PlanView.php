<?php

namespace App\Livewire\Plans;

use App\Models\Plan;
use Livewire\Component;

class PlanView extends Component
{
    public $plan;
    public $events;

    public function mount($id)
    {
        // authorizeRequest('production.plan-list');

        $this->plan = Plan::with([
            'events' => function ($query) {
                $query->orderBy('created_at', 'asc')->whereNull('deleted_at');
            },
            'events.eventType',
        ])->findOrFail($id);

        $this->events = $this->plan->events;
    }

    public function render()
    {
        return view('livewire.plans.plan-view');
    }
}
