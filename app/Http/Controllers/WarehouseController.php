<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    public function __construct(
        private ApiService $api
    ) {}

    /**
     * GET /warehouses
     * List all warehouses.
     */
    public function index()
    {
        $data = [];
        $data['warehouses'] = $this->api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
        
        return view('warehouses.index', $data);
    }

    /**
     * GET /warehouses/create
     * Show form to create a new warehouse.
     */
    public function create()
    {
        $data = [];
        $data['route']             = route('warehouses.store');
        $data['editing']           = false;
        $data['warehouse_types']   = $this->api->get('/v1/warehouse-types', ['module' => 'production'])['data'] ?? [];
        $data['departments']       = $this->api->get('/v1/departments', ['module' => 'production'])['data'] ?? [];
        $data['item_types']        = $this->api->get('/v1/item-types', ['module' => 'production'])['data'] ?? [];

        return view('warehouses.create', $data);
    }

    /**
     * POST /warehouses
     * Store a new warehouse via the API.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:255',
            'shortname'          => 'required|string|max:50',
            'type_id'            => 'required|integer',
            'department_id'      => 'nullable|integer',
            'items_type_id'      => 'nullable|array',
            'items_type_id.*'    => 'integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $payload = [
            'name'               => $request->input('name'),
            'shortname'          => $request->input('shortname'),
            'type_id'            => $request->input('type_id'),
            'department_id'      => $request->input('department_id'),
            'items_type_id'      => $request->input('items_type_id', []),
        ];

        $response = $this->api->post('/v1/warehouses', $payload);

        if (!($response['success'] ?? false)) {
            return redirect()->back()
                ->with('error', $response['message'] ?? 'Failed to create warehouse.')
                ->withInput();
        }

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse created successfully.');
    }

    /**
     * GET /warehouses/{id}
     * Show a single warehouse.
     */
    public function show(int $id)
    {
        $response = $this->api->get("/v1/warehouses/{$id}");

        if (!($response['success'] ?? false)) {
            abort(404, $response['message'] ?? 'Warehouse not found.');
        }

        $data = [];
        $data['warehouse'] = $response['data'];
        $data['warehouse']['items_type_id'] = json_decode($data['warehouse']['items_type_id'], true) ?? [];

        return view('warehouses.show', $data);
    }

    /**
     * GET /warehouses/{id}/edit
     * Show the edit form for an existing warehouse.
     */
    public function edit(int $id)
    {
        $response = $this->api->get("/v1/warehouses/{$id}");

        if (!($response['success'] ?? false)) {
            abort(404, $response['message'] ?? 'Warehouse not found.');
        }

        $data = [];
        $data['warehouse']                  = $response['data'];
        $data['warehouse']['items_type_id'] = json_decode($data['warehouse']['items_type_id'], true) ?? [];
        $data['route']                      = route('warehouses.update', $id);
        $data['editing']                    = true;
        $data['warehouse_types']            = $this->api->get('/v1/warehouse-types', ['module' => 'production'])['data'] ?? [];
        $data['departments']                = $this->api->get('/v1/departments', ['module' => 'production'])['data'] ?? [];
        $data['item_types']                 = $this->api->get('/v1/item-types', ['module' => 'production'])['data'] ?? [];

        return view('warehouses.create', $data);
    }

    /**
     * PUT/PATCH /warehouses/{id}
     * Update an existing warehouse via the API.
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:255',
            'shortname'          => 'required|string|max:50',
            'type_id'            => 'required|integer',
            'department_id'      => 'nullable|integer',
            'items_type_id'      => 'nullable|array',
            'items_type_id.*'    => 'integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = [
            'name'               => $request->input('name'),
            'shortname'          => $request->input('shortname'),
            'type_id'            => $request->input('type_id'),
            // 'department_id'      => $request->input('department_id'),
            'items_type_id'      => $request->input('items_type_id', []),
        ];

        $response = $this->api->post("/v1/warehouses/{$id}", $data);

        if (!($response['success'] ?? false)) {
            return redirect()->back()
                ->with('error', $response['message'] ?? 'Failed to update warehouse.')
                ->withInput();
        }

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    /**
     * DELETE /warehouses/{id}
     * Soft-delete a warehouse via the API.
     */
    public function destroy(int $id)
    {
        $response = $this->api->get("/v1/warehouses/delete/{$id}", ['module' => 'production']);

        if (!($response['success'] ?? false)) {
            return redirect()->back()
                ->with('error', $response['message'] ?? 'Failed to delete warehouse.');
        }

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse deleted successfully.');
    }

    /**
     * GET /warehouses/department/{departmentId}
     * List warehouses filtered by department (AJAX-friendly).
     */
    public function byDepartment(int $departmentId)
    {
        $response = $this->api->get("/v1/departments/{$departmentId}/warehouses");

        return response()->json($response);
    }

    /**
     * GET /warehouses/{id}/item-types
     * Get item types for a specific warehouse (AJAX-friendly).
     */
    public function itemTypes(int $id)
    {
        $response = $this->api->get("/v1/warehouses/{$id}/item-types");

        return response()->json($response);
    }
}