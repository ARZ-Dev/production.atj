<?php

namespace App\Livewire\ItemRequests;

use App\Models\ItemRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\ApiService;

class ItemRequestIndex extends Component
{
    use AuthorizesRequests;


    public $requests;
    public $warehouseMap = [];

    public function mount(ApiService $api)
    {
            authorizeRequest('production.item-request-list');
        $this->requests = ItemRequest::all();
        $warehouses = $api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.item-request-delete');
         
        $request = ItemRequest::findOrFail($id);
        $request->inputs()->delete(); // Soft delete related items
        $request->delete();

        return redirect()->route('item-requests')->with('success', 'Request deleted successfully.');
    }


    #[On('updateStatus')]
    public function updateStatus(int $id, string $status)
    {
        $allowed = ['approved', 'rejected', 'completed'];

        if (!in_array($status, $allowed)) {
            return to_route('item-requests')->with('error', 'Invalid status transition.');
        }

        $transitions = [
            'approved' => ['pending'],
            'rejected' => ['pending'],
            'completed' => ['approved'],
        ];

        $request = ItemRequest::findOrFail($id);

        if (!in_array($request->status, $transitions[$status])) {
            return to_route('item-requests')->with('error', 'Invalid status transition.');
        }


        $request->status = $status;
        $request->save();

        return to_route('item-requests')->with('success', 'Status updated successfully.');
    }

    public function render()
    {
        return view('livewire.item-requests.item-request-index');
    }
}