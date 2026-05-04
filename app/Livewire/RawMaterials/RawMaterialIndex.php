<?php

namespace App\Livewire\RawMaterials;

use App\Models\RawMaterial;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class RawMaterialIndex extends Component
{

    use AuthorizesRequests;

    public $rawMaterials;

    public function mount()
    {
        authorizeRequest('production.raw-material-list');
        $this->rawMaterials = RawMaterial::with(['baseUnit', 'purchaseUnit'])->get();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.raw-material-delete');
        $rawMaterial = RawMaterial::findOrFail($id);
        $rawMaterial->delete();

        return redirect()->route('raw-materials')->with('success', 'Raw material deleted successfully.');
    }
    public function render()
    {
        return view('livewire.raw-materials.raw-material-index');
    }
}
