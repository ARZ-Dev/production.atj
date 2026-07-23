<?php

namespace App\Livewire\StockOut;

use App\Models\StockOut;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($stockOut) {
            // Release the reservation if it was never approved
            if ($stockOut->status === 'pending') {
                foreach ($stockOut->reportItems as $item) {
                    WarehouseInventory::releasePendingOut(
                        $item->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity
                    );
                }
            }

            $stockOut->reportItems()->delete();
            $stockOut->delete();
        });

        return to_route('item-stock-outs')->with('success', 'Stock Out deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('production.itemStockOut-approve');

        $stockOut = StockOut::findOrFail($id);

        if ($stockOut->status === 'approved') {
            return to_route('item-stock-outs')->with('error', 'Stock Out is already approved.');
        }

        $warehouseId = $stockOut->warehouse_id;

        // Full pre-check pass — validate all items against actual stock before deducting any
        foreach ($stockOut->reportItems as $item) {
            $available = WarehouseInventory::availableQuantity(
                $warehouseId, $item->item_id, $item->item_unit_id
            );

            if ($available < $item->quantity) {
                return $this->dispatch('swal:error', [
                    'title' => 'Error!',
                    'text'  => "Not enough stock for item ID {$item->item_id}. Available: {$available}, Required: {$item->quantity}",
                ]);
            }
        }

        DB::transaction(function () use ($stockOut, $warehouseId) {
            foreach ($stockOut->reportItems as $item) {
                // Release the reservation and deduct actual stock
                WarehouseInventory::confirmOut(
                    $warehouseId, $item->item_id, $item->item_unit_id, (float) $item->quantity
                );
            }

            $stockOut->update(['status' => 'approved']);
        });

        return to_route('item-stock-outs')->with('success', 'Stock Out approved successfully.');
    }

    public function render()
    {
        return view('livewire.stock-out.stock-out-index');
    }
}
