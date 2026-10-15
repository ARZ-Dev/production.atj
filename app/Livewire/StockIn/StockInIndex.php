<?php

namespace App\Livewire\StockIn;


use App\Models\StockIn;
use App\Services\ApiService;
use App\Services\InventoryService;
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

    protected InventoryService $inventory;

    public function boot(InventoryService $inventory): void
    {
        $this->inventory = $inventory;
    }

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

        DB::beginTransaction();
        try {
            $stockIn = StockIn::lockForUpdate()->findOrFail($id);

            $ops = [];

            // Release the reservation if it was never approved
            if ($stockIn->status === 'pending') {
                foreach ($stockIn->reportItems as $item) {
                    $ops[] = InventoryService::releaseIn(
                        $item->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity
                    );
                }
            }

            $stockIn->reportItems()->delete();
            $stockIn->delete();

            $this->inventory->applyOrFail($ops);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }

        return to_route('item-stock-ins')->with('success', 'Stock In deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('production.itemStockIn-approve');

        DB::beginTransaction();
        try {
            // Locked and re-read inside the transaction, so a double click or a
            // second tab can't confirm the same document twice.
            $stockIn = StockIn::lockForUpdate()->findOrFail($id);

            if ($stockIn->status !== 'pending') {
                DB::rollBack();
                return to_route('item-stock-ins')->with('error', 'Stock In is already approved.');
            }

            $ops = [];

            // Move the reserved incoming quantity into actual stock
            foreach ($stockIn->reportItems as $item) {
                $ops[] = InventoryService::confirmIn(
                    $stockIn->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity, (float) $item->quantity
                );
            }

            $this->inventory->applyOrFail($ops);

            $stockIn->update(['status' => 'approved']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }

        return to_route('item-stock-ins')->with('success', 'Stock In approved successfully.');
    }

    public function render()
    {
        return view('livewire.stock-in.stock-in-index');
    }
}
