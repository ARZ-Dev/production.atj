<?php

namespace App\Livewire\ProductionLines;

use App\Models\Production;
use App\Models\ProductionLine;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
class ProductionLineIndex extends Component
{
    use AuthorizesRequests;

    public $productions;
    public $factoryId;

    public function mount($factoryId)
    {
        $this->authorize('productionLine-list');

        $this->factoryId = $factoryId; 

        $this->productions = ProductionLine::with(['warehouses', 'machines'])->where('factory_id', $factoryId)->get();
    }

    #[On('delete')]
    public function delete($id)
    {
        $this->authorize('productionLine-delete');

        $productionLine = ProductionLine::findOrFail($id);
        $productionLine->delete();

        return redirect()->route('production-lines', [
            'factoryId' => $this->factoryId
        ]);
    }

    public function render()
    {
        return view('livewire.productionlines.production-line-index');
    }
}
