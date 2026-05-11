<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;

class ItemTypeController extends Controller
{
    public function __construct(
        private ApiService $api
    ) {}

    /**
     * List Item types — DataTable page.
     */
    public function index()
    {
        $data = [];
        $data['item_types'] = $this->api->get('/v1/item-types')['data'] ?? [];
        $data['group_entity_relations'] = $this->api->get('/v1/item-types/group-entity-relations', ['module' => 'production'])['data'] ?? [];

        return view('item-types.index', $data);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $data = [];
        $data['group_entity_relations'] = $this->api->get('/v1/item-types/group-entity-relations', ['module' => 'production'])['data'] ?? [];
        $data['route'] = route('item-types.store');
        $data['editing'] = false;

        return view('item-types.create', $data);
    }

    /**
     * Store new item type.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'                     => 'required|string|max:255',
            'group_entity_relation_id' => 'required|integer',
        ]);

        $result = $this->api->post('/v1/item-types', [
            'name'                     => $request->name,
            'group_entity_relation_id' => $request->group_entity_relation_id,
            'has_pos_suppliers'        => $request->boolean('has_pos_suppliers'),
        ]);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['error' => $result['message'] ?? 'Failed to create item type.']
            );
        }

        return redirect()->route('item-types.index')
            ->with('success', 'Item type created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $data = [];
        $result = $this->api->get("/v1/item-types/{$id}");

        if (!($result['success'] ?? false)) {
            return redirect()->route('item-types.index')
                ->with('error', $result['message'] ?? 'Item type not found.');
        }

        $data['item_type'] = $result['data'];
        $data['group_entity_relations'] = $this->api->get('/v1/item-types/group-entity-relations', ['module' => 'production'])['data'] ?? [];
        $data['route'] = route('item-types.update', $id);
        $data['editing'] = true;

        return view('item-types.create', $data);
    }

    /**
     * Update item type.
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'                     => 'required|string|max:255',
            'group_entity_relation_id' => 'required|integer',
        ]);

        $result = $this->api->post("/v1/item-types/{$id}", [
            'name'                     => $request->name,
            'group_entity_relation_id' => $request->group_entity_relation_id,
            'has_pos_suppliers'        => $request->boolean('has_pos_suppliers'),
        ]);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['error' => $result['message'] ?? 'Failed to update item type.']
            );
        }

        return redirect()->route('item-types.index')
            ->with('success', 'Item type updated successfully.');
    }

    /**
     * Delete item type.
     */
    public function destroy(int $id)
    {
        $result = $this->api->get("/v1/item-types/delete/{$id}");

        if (!($result['success'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'Failed to delete item type.');
        }

        return redirect()->route('item-types.index')
            ->with('success', 'Item type deleted successfully.');
    }
}