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
        $data['group_entity_relations'] = $this->api->get('/v1/group-entity-relations', ['module' => 'production'])['data'] ?? [];

        return view('item-types.index', $data);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $data = [];
        $data['group_entity_relations'] = $this->api->get('/v1/group-entity-relations', ['module' => 'production'])['data'] ?? [];
        $data['route']     = route('item-types.store');
        $data['editing']   = false;
        $data['item_type'] = [];

        return view('item-types.create', $data);
    }

    /**
     * Store new item type.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'                      => 'required|string|max:255',
            'group_entity_relation_id'  => 'required|integer',
            'subTypes'                  => 'nullable|array',
            'subTypes.*.name'           => 'required|string|max:255',
        ]);

        $result = $this->api->post('/v1/item-types', [
            'name'                     => $request->name,
            'group_entity_relation_id' => $request->group_entity_relation_id,
            'has_pos_suppliers'        => $request->boolean('has_pos_suppliers'),
            'sub_types'                => $this->mapSubTypes($request->input('subTypes', [])),
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

    $itemType = $result['data'];
    // API returns 'subTypes' (camelCase) — normalise to snake_case for the blade
    $itemType['sub_types'] = $itemType['subTypes'] ?? [];

    $data['item_type']              = $itemType;
    $data['group_entity_relations'] = $this->api->get('/v1/group-entity-relations', ['module' => 'production'])['data'] ?? [];
    $data['route']                  = route('item-types.update', $id);
    $data['editing']                = true;

    return view('item-types.create', $data);
}
    /**
     * Update item type.
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'                      => 'required|string|max:255',
            'group_entity_relation_id'  => 'required|integer',
            'subTypes'                  => 'nullable|array',
            'subTypes.*.name'           => 'required|string|max:255',
        ]);

        $result = $this->api->post("/v1/item-types/{$id}", [
            'name'                     => $request->name,
            'group_entity_relation_id' => $request->group_entity_relation_id,
            'has_pos_suppliers'        => $request->boolean('has_pos_suppliers'),
            'sub_types'                => $this->mapSubTypes($request->input('subTypes', [])),
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

    /**
     * Normalise the subTypes array from the form into a clean array for the API.
     * Filters out any rows with an empty name.
     */
    private function mapSubTypes(array $subTypes): array
    {
        return collect($subTypes)
            ->filter(fn($st) => !empty($st['name']))
            ->map(fn($st) => array_filter([
                'id'   => $st['id'] ?? null,   // null on new rows
                'name' => $st['name'],
            ], fn($v) => $v !== null))
            ->values()
            ->toArray();
    }
}