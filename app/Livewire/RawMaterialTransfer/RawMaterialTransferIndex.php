<?php

namespace App\Livewire\RawMaterialTransfer;

use App\Models\RawMaterialTransfer;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class RawMaterialTransferIndex extends Component
{
    use AuthorizesRequests;

    public $transfers;
    public $warehouseMap = [];

    public function mount(ApiService $api)
    {
        authorizeRequest('production.rawMaterialTransfer-list');

        $this->transfers = RawMaterialTransfer::with(['reportRawMaterials'])->latest()->get();

        $warehouses = $api->get('/v1/warehouses', [
            'module' => 'production'
        ])['data'] ?? [];

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.rawMaterialTransfer-delete');

        $transfer = RawMaterialTransfer::findOrFail($id);

        if ($transfer->status !== 'pending') {
            return $this->dispatch('swal:error', [
                'title' => 'Error',
                'text'  => 'Cannot delete transfer that has already been processed!'
            ]);
        }

        $transfer->reportRawMaterials()->delete();
        $transfer->delete();

        return to_route('raw-material-transfers')->with('success', 'Transfer deleted successfully.');
    }

    public function render()
    {
        return view('livewire.raw-material-transfer.raw-material-transfer-index');
    }
}