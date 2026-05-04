<?php

namespace App\Livewire\RawMaterialStockIn;


use App\Models\ReportRawMaterial;
use App\Models\RawMaterialStockIn;
use App\Models\RawMaterialWarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class RawMaterialStockInIndex extends Component
{

    use AuthorizesRequests;

    public $stockIns;

    public $status;
    public $warehouseMap = [];

    public function mount(ApiService $api)
    {
        authorizeRequest('production.rawMaterialStockIn-list');
        $this->stockIns = RawMaterialStockIn::with(['reportRawMaterials'])->latest()->get();

        $warehouses = $api->get('/v1/warehouses', [
            'module' => 'production'
        ])['data'] ?? [];

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }


    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.rawMaterialStockIn-delete');

        $stockIn = RawMaterialStockIn::findOrFail($id);
        $stockIn->reportRawMaterials()->delete();
        $stockIn->delete();

        return to_route('raw-material-stock-ins')->with('success', 'Stock In deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('production.rawMaterialStockIn-approve');

        $stockIn = RawMaterialStockIn::findOrFail($id);
        $stockIn->status = 'approved';
        $items = ReportRawMaterial::where('stock_in_id', $stockIn->id)->get();

        foreach ($items as $item) {
            $inventory = RawMaterialWarehouseInventory::where('warehouse_id', $stockIn->warehouse_id)
                ->where('raw_material_id', $item->raw_material_id)
                ->where('unit_id', $item->unit_id)
                ->first();

            if ($inventory) {
                $inventory->quantity += $item->quantity;
                $inventory->save();
            }
        }

        $stockIn->save();

        return to_route('raw-material-stock-ins')->with('success', 'Stock In approved successfully.');
    }
    public function render()
    {
        return view('livewire.raw-material-stock-in.raw-material-stock-in-index');
    }
}
