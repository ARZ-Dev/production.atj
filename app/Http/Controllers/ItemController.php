<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(
        private ApiService $api
    ) {}

    /**
     * List Items — DataTable page.
     */
    public function index()
    {
        $data['items'] = $this->api->get('/v1/items')['data'] ?? [];
        return view('items.index', $data);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $data['item_types'] = $this->api->get('/v1/item-types')['data'] ?? [];
        $data['sub_types']  = [];
        $data['route']      = route('items.store');
        $data['editing']    = false;

        return view('items.create', $data);
    }

    public function getSubTypes($typeId)
    {
        if (!$typeId) {
            return response()->json([
                'success' => false,
                'message' => 'item_type_id is required.',
            ], 422);
        }

        $subTypes = $this->api->get("/v1/item-sub-types/$typeId")['data'] ?? [];

        return response()->json([
            'success' => true,
            'sub_types'    => $subTypes,
        ]);
    }

    /**
     * Store new item.
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_type_id' => 'required|integer',
            'sub_type_id'  => 'nullable|integer',
            'code'         => 'required|string|max:255',
            'name'         => 'required|string|max:255',
            'with_formula' => 'boolean',
            'weight'       => 'nullable|numeric|min:0',
            'volume'       => 'nullable|numeric|min:0',
            'vat'          => 'nullable|numeric|min:0|max:100',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'    => 'boolean',
        ]);

        $payload = [
            'item_type_id' => $request->item_type_id,
            'sub_type_id'  => $request->sub_type_id,
            'code'         => $request->code,
            'name'         => $request->name,
            'with_formula' => $request->boolean('with_formula'),
            'weight'       => $request->weight,
            'volume'       => $request->volume,
            'vat'          => $request->vat,
            'is_active'    => $request->boolean('is_active'),
        ];

        $result = $request->hasFile('image')
            ? $this->api->postMultipart('/v1/items', $payload, ['image' => $request->file('image')])
            : $this->api->post('/v1/items', $payload);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['error' => $result['message'] ?? 'Failed to create item.']
            );
        }

        return redirect()->route('items.index')
            ->with('success', 'Item created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $result = $this->api->get("/v1/items/{$id}");

        if (!($result['success'] ?? false)) {
            return redirect()->route('items.index')
                ->with('error', $result['message'] ?? 'Item not found.');
        }

        $item = $result['data'];

        // Fetch sub types filtered by the item's current item_type_id
        $sub_types = [];
        if (!empty($item['item_type_id'])) {
            $typeId = $item['item_type_id'];
            $sub_types = $this->api->get("/v1/item-sub-types/$typeId")['data'] ?? [];
        }

        $data['item']       = $item;
        $data['item_types'] = $this->api->get('/v1/item-types')['data'] ?? [];
        $data['sub_types']  = $sub_types;
        $data['route']      = route('items.update', $id);
        $data['editing']    = true;

        return view('items.create', $data);
    }

    /**
     * Update item.
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'item_type_id' => 'required|integer',
            'sub_type_id'  => 'nullable|integer',
            'code'         => 'required|string|max:255',
            'name'         => 'required|string|max:255',
            'with_formula' => 'boolean',
            'weight'       => 'nullable|numeric|min:0',
            'volume'       => 'nullable|numeric|min:0',
            'vat'          => 'nullable|numeric|min:0|max:100',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'    => 'boolean',
        ]);

        $payload = [
            'item_type_id' => $request->item_type_id,
            'sub_type_id'  => $request->sub_type_id,
            'code'         => $request->code,
            'name'         => $request->name,
            'with_formula' => $request->boolean('with_formula'),
            'weight'       => $request->weight,
            'volume'       => $request->volume,
            'vat'          => $request->vat,
            'is_active'    => $request->boolean('is_active'),
        ];

        $result = $request->hasFile('image')
            ? $this->api->postMultipart("/v1/items/{$id}", $payload, ['image' => $request->file('image')])
            : $this->api->post("/v1/items/{$id}", $payload);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['error' => $result['message'] ?? 'Failed to update item.']
            );
        }

        return redirect()->route('items.index')
            ->with('success', 'Item updated successfully.');
    }

    /**
     * Delete item.
     */
    public function destroy(int $id)
    {
        $result = $this->api->get("/v1/items/delete/{$id}");

        if (!($result['success'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'Failed to delete item.');
        }

        return redirect()->route('items.index')
            ->with('success', 'Item deleted successfully.');
    }
}
