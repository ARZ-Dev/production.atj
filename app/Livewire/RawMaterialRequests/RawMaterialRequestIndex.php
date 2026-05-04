<?php

namespace App\Livewire\RawMaterialRequests;

use App\Models\RawMaterialRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\ApiService;

class RawMaterialRequestIndex extends Component
{
    use AuthorizesRequests;


    public $requests;
    public $warehouseMap = [];

    public function mount(ApiService $api)
    {
            authorizeRequest('production.raw-material-request-list');

        $this->warehouseMap = collect($warehouses)->pluck('name', 'id')->toArray();
    }

    #[On('delete')]
    public function delete($id)
    {
        authorizeRequest('production.raw-material-request-delete');
         
        $request = RawMaterialRequest::findOrFail($id);
        $request->items()->delete(); // Soft delete related items
        $request->delete();

        return redirect()->route('raw-material-requests')->with('success', 'Request deleted successfully.');
    }


    #[On('updateStatus')]
    public function updateStatus(int $id, string $status)
    {
        $allowed = ['approved', 'rejected', 'completed'];

        if (!in_array($status, $allowed)) {
            return to_route('raw-material-requests')->with('error', 'Invalid status transition.');
        }

        $transitions = [
            'approved' => ['pending'],
            'rejected' => ['pending'],
            'completed' => ['approved'],
        ];

        $request = RawMaterialRequest::findOrFail($id);

        if (!in_array($request->status, $transitions[$status])) {
            return to_route('raw-material-requests')->with('error', 'Invalid status transition.');
        }


        $request->status = $status;
        $request->save();

        return to_route('raw-material-requests')->with('success', 'Status updated successfully.');
    }

    public function render()
    {
        return view('livewire.raw-material-requests.raw-material-request-index');
    }
}