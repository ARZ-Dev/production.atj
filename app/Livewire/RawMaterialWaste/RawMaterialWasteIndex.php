<?php

namespace App\Livewire\RawMaterialWaste;

use App\Models\RawMaterialWarehouseInventory;
use App\Models\RawMaterialWaste;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class RawMaterialWasteIndex extends Component
{
    use AuthorizesRequests;

    public $wastes = [];
    public $status;
    public $warehouseMap = [];

    public function mount(ApiService $api)
    {
        authorizeRequest('production.rawMaterialWaste-list');

        $this->wastes = RawMaterialWaste::with(['reportRawMaterials'])->latest()->get();

        $warehouses = $api->get('/v1/warehouses', [
            'module' => 'production'
        ])['data'] ?? [];

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.rawMaterialWaste-delete');

        $waste = RawMaterialWaste::findOrFail($id);
        $waste->reportRawMaterials()->delete();
        $waste->delete();

        return to_route('raw-material-wastes')->with('success', 'Waste deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('production.rawMaterialWaste-approve');

        $waste = RawMaterialWaste::findOrFail($id);

        if ($waste->status === 'approved') {
            return to_route('raw-material-wastes')->with('error', 'Waste is already approved.');
        }

        $warehouseId = $waste->warehouse_id;

        foreach ($waste->reportRawMaterials as $reportRawMaterial) {

            $itemId   = $reportRawMaterial->raw_material_id;
            $unitId   = $reportRawMaterial->unit_id;
            $wasteQty = $reportRawMaterial->quantity;

            $availableStock = RawMaterialWarehouseInventory::where('warehouse_id', $warehouseId)
                ->where('raw_material_id', $itemId)
                ->where('unit_id', $unitId)
                ->value('quantity') ?? 0;

            if ($availableStock < $wasteQty) {
                return $this->dispatch('swal:error', [
                    'title' => 'Error!',
                    'text'  => "Not enough stock for raw material ID {$itemId}. Available: {$availableStock}, Required: {$wasteQty}",
                ]);
            } else {
                $inventory = RawMaterialWarehouseInventory::where('warehouse_id', $warehouseId)
                    ->where('raw_material_id', $itemId)
                    ->where('unit_id', $unitId)
                    ->first();

                if ($inventory) {
                    $inventory->quantity -= $wasteQty;
                    $inventory->save();
                }
            }
        }

        $waste->status = 'approved';
        $waste->save();

        return to_route('raw-material-wastes')->with('success', 'Waste approved successfully.');
    }

    public function render()
    {
        return view('livewire.raw-material-waste.raw-material-waste-index');
    }
}