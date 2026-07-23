<?php

namespace App\Livewire\StockIn;


use App\Models\StockIn;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
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
        authorizeRequest('production.itemStockIn-list');
        $this->stockIns = StockIn::with(['reportItems'])->latest()->get();

        $warehouses = $api->get('/v1/warehouses', [
            'module' => 'production'
        ])['data'] ?? [];

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }


    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.itemStockIn-delete');

        $stockIn = StockIn::findOrFail($id);

        DB::transaction(function () use ($stockIn) {
            // Release the reservation if it was never approved
            if ($stockIn->status === 'pending') {
                foreach ($stockIn->reportItems as $item) {
                    WarehouseInventory::releasePendingIn(
                        $item->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity
                    );
                }
            }

            $stockIn->reportItems()->delete();
            $stockIn->delete();
        });

        return to_route('item-stock-ins')->with('success', 'Stock In deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('production.itemStockIn-approve');

        $stockIn = StockIn::findOrFail($id);

        if ($stockIn->status === 'approved') {
            return to_route('item-stock-ins')->with('error', 'Stock In is already approved.');
        }

        DB::transaction(function () use ($stockIn) {
            foreach ($stockIn->reportItems as $item) {
                // Move the reserved incoming quantity into actual stock
                WarehouseInventory::confirmIn(
                    $stockIn->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity
                );
            }

            $stockIn->update(['status' => 'approved']);
        });

        return to_route('item-stock-ins')->with('success', 'Stock In approved successfully.');
    }

    public function render()
    {
        return view('livewire.stock-in.stock-in-index');
    }
}
