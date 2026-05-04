<?php

namespace App\Livewire\StockIn;


use App\Models\ReportRawMaterial;
use App\Models\StockIn;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class StockInIndex extends Component
{

    use AuthorizesRequests;

    public $stockIns;

    public $status;
    public $warehouseMap = [];

    public function mount(ApiService $api)
    {
        authorizeRequest('production.stockIn-list');
        $this->stockIns = StockIn::with(['reportRawMaterials'])->latest()->get();

        $warehouses = $api->get('/v1/warehouses', [
            'module' => 'production'
        ])['data'] ?? [];

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }


    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.stockIn-delete');

        $stockIn = StockIn::findOrFail($id);
        $stockIn->reportRawMaterials()->delete();
        $stockIn->delete();

        return to_route('stock-ins')->with('success', 'Stock In deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('stockIn-approve');

        $stockIn = StockIn::findOrFail($id);
        $stockIn->status = 'approved';
        $items = ReportRawMaterial::where('stock_in_id', $stockIn->id)->get();

        foreach ($items as $item) {
            $inventory = WarehouseInventory::where('warehouse_id', $stockIn->warehouse_id)
                ->where('raw_material_id', $item->raw_material_id)
                ->where('unit_id', $item->unit_id)
                ->first();

            if ($inventory) {
                $inventory->quantity += $item->quantity;
                $inventory->save();
            }
        }

        $stockIn->save();

        return to_route('stock-ins')->with('success', 'Stock In approved successfully.');
    }
    public function render()
    {
        return view('livewire.stock-in.stock-in-index');
    }
}
