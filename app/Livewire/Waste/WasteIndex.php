<?php

namespace App\Livewire\Waste;

use App\Models\Waste;
use App\Models\WarehouseInventory;
use App\Services\ApiService;
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

        DB::transaction(function () use ($waste) {
            // Release the reservation if it was never approved
            if ($waste->status === 'pending') {
                foreach ($waste->reportItems as $item) {
                    WarehouseInventory::releasePendingOut(
                        $item->warehouse_id, $item->item_id, $item->item_unit_id, (float) $item->quantity
                    );
                }
            }

            $waste->reportItems()->delete();
            $waste->delete();
        });

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

        $warehouseId = $waste->warehouse_id;

        // Full pre-check pass — validate all items against actual stock before deducting any
        foreach ($waste->reportItems as $item) {
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

        DB::transaction(function () use ($waste, $warehouseId) {
            foreach ($waste->reportItems as $item) {
                // Release the reservation and deduct actual stock
                WarehouseInventory::confirmOut(
                    $warehouseId, $item->item_id, $item->item_unit_id, (float) $item->quantity
                );
            }

            $waste->update(['status' => 'approved']);
        });

        return to_route('item-wastes')->with('success', 'Waste approved successfully.');
    }

    public function render()
    {
        return view('livewire.waste.waste-index');
    }
}
