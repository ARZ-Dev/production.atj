<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;

class WarehouseTypeController extends Controller
{
    public function __construct(
        private ApiService $api
    ) {}

    /**
     * List Warehouse types — DataTable page.
     */
    public function index()
    {
        $data = [];
        $data['warehouse_types'] = $this->api->get('/v1/warehouse-types', ['module' => 'production'])['data'] ?? [];

        return view('warehouse-types.index', $data);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $data = [];
        $data['route'] = route('warehouse-types.store');
        $data['editing'] = false;

        return view('warehouse-types.create', $data);
    }

    /**
     * Store new warehouse type.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $result = $this->api->post('/v1/warehouse-types', [
            'module'                => 'production',
            'name'                  => $request->name,
            'is_internal'           => $request->boolean('is_internal'),
            'is_factory'            => $request->boolean('is_factory'),
            'can_receive_transfer'  => $request->boolean('can_receive_transfer'),
            'can_receive_local_incoming_order'  => $request->boolean('can_receive_local_incoming_order'),
            'can_receive_international_incoming_order'  => $request->boolean('can_receive_international_incoming_order'),
            'can_receive_production'  => $request->boolean('can_receive_production'),
            'can_send_transfer'  => $request->boolean('can_send_transfer'),
            'can_send_order'  => $request->boolean('can_send_order'),
            'can_request_product'  => $request->boolean('can_request_product'),
            'have_pos'  => $request->boolean('have_pos'),
            'can_distribute'  => $request->boolean('can_distribute'),
            'can_return_distribute'  => $request->boolean('can_return_distribute'),
        ]);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['error' => $result['message'] ?? 'Failed to create user.']
            );
        }

        return redirect()->route('warehouse-types.index')
            ->with('success', 'Warehouse type created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $data = [];
        $result = $this->api->get("/v1/warehouse-types/{$id}");

        if (!($result['success'] ?? false)) {
            return redirect()->route('warehouse-types.index')
                ->with('error', $result['message'] ?? 'Warehouse type not found.');
        }

        $warehouseType = $result['data'];

        // Some classification fields (e.g. is_factory) may be missing from the
        // single-item response. Backfill them from the list endpoint if needed.
        $fields = [
            'is_internal', 'is_factory', 'can_receive_transfer',
            'can_receive_local_incoming_order', 'can_receive_international_incoming_order',
            'can_receive_production', 'can_send_transfer', 'can_send_order',
            'can_request_product', 'have_pos', 'can_distribute', 'can_return_distribute',
        ];

        if (count(array_intersect($fields, array_keys($warehouseType))) < count($fields)) {
            $list = $this->api->get('/v1/warehouse-types', ['module' => 'production'])['data'] ?? [];
            $match = collect($list)->firstWhere('id', $warehouseType['id'] ?? $id);

            if ($match) {
                $warehouseType = array_merge($match, $warehouseType);
            }
        }

        $data['warehouse_type'] = $warehouseType;
        $data['route'] = route('warehouse-types.update', $id);
        $data['editing'] = true;

        return view('warehouse-types.create', $data);
    }

    /**
     * Update user.
     */
    public function update(Request $request, int $id)
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        $request->validate($rules);

        $data = [
            'module'       => 'production',
            'name'         => $request->name,
            'is_internal'  => $request->boolean('is_internal'),
            'is_factory'   => $request->boolean('is_factory'),
            'can_receive_transfer'  => $request->boolean('can_receive_transfer'),
            'can_receive_local_incoming_order'  => $request->boolean('can_receive_local_incoming_order'),
            'can_receive_international_incoming_order'  => $request->boolean('can_receive_international_incoming_order'),
            'can_receive_production'  => $request->boolean('can_receive_production'),
            'can_send_transfer'  => $request->boolean('can_send_transfer'),
            'can_send_order'  => $request->boolean('can_send_order'),
            'can_request_product'  => $request->boolean('can_request_product'),
            'have_pos'  => $request->boolean('have_pos'),
            'can_distribute'  => $request->boolean('can_distribute'),
            'can_return_distribute'  => $request->boolean('can_return_distribute'),
        ];

        $result = $this->api->post("/v1/warehouse-types/{$id}", $data);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['error' => $result['message'] ?? 'Failed to update warehouse type.']
            );
        }

        return redirect()->route('warehouse-types.index')
            ->with('success', 'Warehouse type updated successfully.');
    }

    /**
     * Delete user.
     */
    public function destroy(int $id)
    {
        $result = $this->api->get("/v1/warehouse-types/delete/{$id}", ['module' => 'production']);

        if (!($result['success'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'Failed to delete warehouse type.');
        }

        return redirect()->route('warehouse-types.index')
            ->with('success', 'Warehouse type deleted successfully.');
    }
}
