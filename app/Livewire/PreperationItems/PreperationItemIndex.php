<?php

namespace App\Livewire\PreperationItems;

use App\Models\PreperationItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class PreperationItemIndex extends Component
{
    use AuthorizesRequests;

    public $preperationItems;

    public function mount()
    {
        authorizeRequest('production.preperationItem-list');

        $this->preperationItems = PreperationItem::orderBy('created_at', 'desc')->get();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.preperationItem-delete');

        $preperationItem = PreperationItem::findOrFail($id);
        $preperationItem->delete();
        $preperationItem->preperationItemUnits()->delete();

        return redirect()->route('preperation-items')->with('success', 'Preperation Item deleted successfully.');
    }

    public function activate($id)
    {
        $preperationItem = PreperationItem::find($id);
        if ($preperationItem) {
            $preperationItem->is_active = true;
            $preperationItem->save();

            return to_route('preperation-items')->with('success', 'Preperation Item activated successfully');
        }
        $this->mount();
    }

    public function deactivate($id)
    {
        // dd($id);
        $preperationItem = PreperationItem::find($id);
        if ($preperationItem) {
            $preperationItem->is_active = false;
            $preperationItem->save();
            return to_route('preperation-items')->with('success', 'Preperation Item deactivated successfully');
        }
        $this->mount();
    }
    public function render()
    {
        return view('livewire.preperation-items.preperation-item-index');
    }
}
