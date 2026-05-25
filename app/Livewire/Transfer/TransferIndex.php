<?php

namespace App\Livewire\Transfer;

use App\Models\Transfer;
use App\Services\ApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class TransferIndex extends Component
{
    use AuthorizesRequests;

    public $transfers;
    public $warehouseMap = [];

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

        $transfer->reportItems()->delete();
        $transfer->delete();

        return to_route('item-transfers')->with('success', 'Transfer deleted successfully.');
    }

    public function render()
    {
        return view('livewire.transfer.transfer-index');
    }
}