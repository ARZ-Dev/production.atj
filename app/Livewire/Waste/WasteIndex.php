<?php

namespace App\Livewire\Waste;

use App\Models\Waste;
use App\Services\ApiService;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class WasteIndex extends Component
{
    use AuthorizesRequests;

    public $wastes = [];
    public $status;
    public $warehouseMap = [];

    protected InventoryService $inventory;

    public function boot(InventoryService $inventory): void
    {
        $this->inventory = $inventory;
    }

    public function mount(ApiService $api)
    {
        authorizeRequest('production.itemWaste-list');

        $this->wastes = Waste::with(['reportItems'])->latest()->get();

        $warehouses = $api->get('/v1/warehouses', [
            'module' => 'production',
        ])['data'] ?? [];

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.itemWaste-delete');

        $waste = Waste::findOrFail($id);

        DB::beginTransaction();
        try {
            $ops = [];

            // Release the reservation if it was never approved
            if ($waste->status === 'pending') {
                foreach ($waste->reportItems as $item) {
                    $ops[] = InventoryService::releaseOut(
                        $item->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity
                    );
                }
            }

            $waste->reportItems()->delete();
            $waste->delete();

            $this->inventory->applyOrFail($ops);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'An error occurred: ' . $e->getMessage(),
            ]);
        }

        return to_route('item-wastes')->with('success', 'Waste deleted successfully.');
    }

    #[On('approve')]
    public function approve($id)
    {
        authorizeRequest('production.itemWaste-approve');

        $waste = Waste::findOrFail($id);

        if ($waste->status === 'approved') {
            return to_route('item-wastes')->with('error', 'Waste is already approved.');
        }

        DB::beginTransaction();
        try {
            $ops = [];

            // Release the reservation and deduct actual stock. The parent validates
            // availability for every line and rejects the whole batch if any is short.
            foreach ($waste->reportItems as $item) {
                $ops[] = InventoryService::confirmOut(
                    $waste->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity, (float) $item->quantity
                );
            }

            $this->inventory->applyOrFail($ops);

            $waste->update(['status' => 'approved']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text'  => $e->getMessage(),
            ]);
        }

        return to_route('item-wastes')->with('success', 'Waste approved successfully.');
    }

    public function render()
    {
        return view('livewire.waste.waste-index');
    }
}
