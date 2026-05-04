<?php

namespace App\Livewire\StockOut;

use App\Models\ReportRawMaterial;
use App\Models\StockOut;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class StockOutIndex extends Component
{
    use AuthorizesRequests;

    public $stockOuts;
    public $status;
    public $warehouseMap = [];

    public function mount(ApiService $api)
    {
        authorizeRequest('production.stockOut-list');

        $this->stockOuts = StockOut::with(['reportRawMaterials'])->latest()->get();

        $warehouses = $api->get('/v1/warehouses', [
            'module' => 'production'
        ])['data'] ?? [];

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.stockOut-delete');

        $stockOut = StockOut::findOrFail($id);
        $stockOut->reportRawMaterials()->delete();
        $stockOut->delete();

        return to_route('stock-outs')->with('success', 'Stock Out deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('production.stockOut-approve');

        $stockOut = StockOut::findOrFail($id);

        $warehouseId = $stockOut->warehouse_id;

        foreach ($stockOut->reportRawMaterials as $reportRawMaterial) {

            $itemId      = $reportRawMaterial->raw_material_id;
            $unitId      = $reportRawMaterial->unit_id;
            $stockOutQty = $reportRawMaterial->quantity;

            $availableStock = WarehouseInventory::where('warehouse_id', $warehouseId)
                ->where('raw_material_id', $itemId)
                ->where('unit_id', $unitId)
                ->value('quantity') ?? 0;

            if ($availableStock < $stockOutQty) {
                return $this->dispatch('swal:error', [
                    'title' => 'Error!',
                    'text'  => "Not enough stock for item ID {$itemId}. Available: {$availableStock}, Required: {$stockOutQty}",
                ]);
            } else {
                $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                    ->where('raw_material_id', $itemId)
                    ->where('unit_id', $unitId)
                    ->first();

                if ($inventory) {
                    $inventory->quantity -= $stockOutQty;
                    $inventory->save();
                }
            }
        }

        $stockOut->status = 'approved';
        $stockOut->save();

        return to_route('stock-outs')->with('success', 'Stock Out approved successfully.');
    }

    public function render()
    {
        return view('livewire.stock-out.stock-out-index');
    }
}