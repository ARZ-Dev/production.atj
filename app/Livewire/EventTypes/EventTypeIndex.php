<?php

namespace App\Livewire\EventTypes;

use App\Models\EventType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class EventTypeIndex extends Component
{

    use AuthorizesRequests;

    public $eventTypes;

    public function mount()
    {
        $this->authorize('eventType-list');

        if(auth()->user()->hasRole('Super Admin'))
            {
                $this->eventTypes = EventType::all();
            }else{
                $this->eventTypes = EventType::where('company_id', auth()->user()->company_id)->get();
            }
    }

    #[On('delete')]
    public function delete($id)
    {
        $eventType = EventType::findOrFail($id);
        $this->authorize('eventType-delete');
        $eventType->delete();

        return to_route('event-types')->with('success', 'Event Type deleted successfully.');
    }
    public function render()
    {
        return view('livewire.event-types.event-type-index');
    }
}
