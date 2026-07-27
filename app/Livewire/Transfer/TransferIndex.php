<?php

namespace App\Livewire\Transfer;

use App\Models\Transfer;
use App\Services\ApiService;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class TransferIndex extends Component
{
    use AuthorizesRequests;

    public $transfers;
    public $warehouseMap = [];

    protected InventoryService $inventory;

    public function boot(InventoryService $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function mount(ApiService $api): void
    {
        authorizeRequest('production.itemTransfer-list');

        $this->transfers = Transfer::with(['reportItems'])->latest()->get();

        $warehouses = $api->get('/v1/warehouses', [
            'module' => 'production',
        ])['data'] ?? [];

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }

    #[On('delete')]
    public function delete($id): mixed
    {
        authorizeRequest('production.itemTransfer-delete');

        $transfer = Transfer::findOrFail($id);

        if ($transfer->status !== 'pending') {
            return $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'Cannot delete a transfer that has already been processed!',
            ]);
        }

        DB::beginTransaction();
        try {
            $ops = [];

            // Release the reservation on both warehouses (transfer is still pending)
            foreach ($transfer->reportItems as $item) {
                $ops[] = InventoryService::releaseOut(
                    $transfer->warehouse_from_id, $item->item_id, $item->item_unit_id, (float) $item->quantity
                );
                $ops[] = InventoryService::releaseIn(
                    $transfer->warehouse_to_id, $item->item_id, $item->item_unit_id, (float) $item->quantity
                );
            }

            $transfer->reportItems()->delete();
            $transfer->delete();

            $this->inventory->applyOrFail($ops);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }

        return to_route('item-transfers')->with('success', 'Transfer deleted successfully.');
    }

    public function render()
    {
        return view('livewire.transfer.transfer-index');
    }
}
