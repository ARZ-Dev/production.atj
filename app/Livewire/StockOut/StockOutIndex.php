<?php

namespace App\Livewire\StockOut;

use App\Models\StockOut;
use App\Services\ApiService;
use App\Services\InventoryService;
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

    protected InventoryService $inventory;

    public function boot(InventoryService $inventory): void
    {
        $this->inventory = $inventory;
    }

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

        DB::beginTransaction();
        try {
            $stockOut = StockOut::lockForUpdate()->findOrFail($id);

            $ops = [];

            // Release the reservation if it was never approved
            if ($stockOut->status === 'pending') {
                foreach ($stockOut->reportItems as $item) {
                    $ops[] = InventoryService::releaseOut(
                        $item->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity
                    );
                }
            }

            $stockOut->reportItems()->delete();
            $stockOut->delete();

            $this->inventory->applyOrFail($ops);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }

        return to_route('item-stock-outs')->with('success', 'Stock Out deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('production.itemStockOut-approve');

        DB::beginTransaction();
        try {
            // Locked and re-read inside the transaction, so a double click or a
            // second tab can't confirm the same document twice.
            $stockOut = StockOut::lockForUpdate()->findOrFail($id);

            if ($stockOut->status !== 'pending') {
                DB::rollBack();
                return to_route('item-stock-outs')->with('error', 'Stock Out is already approved.');
            }

            $ops = [];

            // Release the reservation and deduct actual stock. The parent validates
            // availability for every line and rejects the whole batch if any is short.
            foreach ($stockOut->reportItems as $item) {
                $ops[] = InventoryService::confirmOut(
                    $stockOut->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity, (float) $item->quantity
                );
            }

            $this->inventory->applyOrFail($ops);

            $stockOut->update(['status' => 'approved']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text'  => $e->getMessage(),
            ]);
        }

        return to_route('item-stock-outs')->with('success', 'Stock Out approved successfully.');
    }

    public function render()
    {
        return view('livewire.stock-out.stock-out-index');
    }
}
