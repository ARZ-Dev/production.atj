<?php

namespace App\Livewire\StockOut;

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
        authorizeRequest('production.itemStockOut-list');

        $this->stockOuts = StockOut::with(['reportItems'])->latest()->get();

        $warehouses = $api->get('/v1/warehouses', [
            'module' => 'production',
        ])['data'] ?? [];

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.itemStockOut-delete');

        $stockOut = StockOut::findOrFail($id);
        $stockOut->reportItems()->delete();
        $stockOut->delete();

        return to_route('item-stock-outs')->with('success', 'Stock Out deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('production.itemStockOut-approve');

        $stockOut    = StockOut::findOrFail($id);
        $warehouseId = $stockOut->warehouse_id;

        foreach ($stockOut->reportItems as $item) {
            $available = WarehouseInventory::where('warehouse_id', $warehouseId)
                ->where('item_id', $item->item_id)
                ->where('item_unit_id', $item->item_unit_id)
                ->value('quantity') ?? 0;

            if ($available < $item->quantity) {
                return $this->dispatch('swal:error', [
                    'title' => 'Error!',
                    'text'  => "Not enough stock for item ID {$item->item_id}. Available: {$available}, Required: {$item->quantity}",
                ]);
            }
        }

        // All checks passed — deduct inventory
        foreach ($stockOut->reportItems as $item) {
            $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                ->where('item_id', $item->item_id)
                ->where('item_unit_id', $item->item_unit_id)
                ->first();

            if ($inventory) {
                $inventory->quantity -= $item->quantity;
                $inventory->save();
            }
        }

        $stockOut->status = 'approved';
        $stockOut->save();

        return to_route('item-stock-outs')->with('success', 'Stock Out approved successfully.');
    }

    public function render()
    {
        return view('livewire.stock-out.stock-out-index');
    }
}