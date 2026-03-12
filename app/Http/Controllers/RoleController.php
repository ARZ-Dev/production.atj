<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        private ApiService $apiService
    ) {}

    /**
     * Display roles page with DataTable.
     */
    public function index()
    {
        $data = [];
        $data['roles'] = $this->apiService->get('/v1/roles')['data'] ?? [];

        return view('roles.index', $data);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $result = $this->apiService->get('/v1/permissions', ['module' => 'production']);
        $permissions = $result['data']['grouped'] ?? [];
        $route = route('roles.store');

        return view('roles.create', compact('permissions', 'route'));
    }

    /**
     * Store a new role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'permissions'   => 'array',
            'permissions.*' => 'string',
        ]);

        $result = $this->apiService->post('/v1/roles', [
            'name'        => $request->name,
            'permissions' => $request->permissions ?? [],
            'module'      => 'production',
        ]);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['name' => $result['message'] ?? 'Failed to create role.']
            );
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $roleResult = $this->apiService->get("/v1/roles/{$id}", ['module' => 'production']);;

        if (!($roleResult['success'] ?? false)) {
            return redirect()->route('roles.index')
                ->with('error', $roleResult['message'] ?? 'Role not found.');
        }

        $permResult = $this->apiService->get('/v1/permissions', ['module' => 'production']);

        $role = $roleResult['data'];
        $allPermissions = $permResult['data']['all'] ?? [];
        $permissions = $permResult['data']['grouped'] ?? [];

        $isAllPermissionsSelected = count($allPermissions) == count($role['permissions'] ?? []);
        $allowPermissionEditing = !in_array('Super Admin', $role['permissions'] ?? []);
        $selectedPermissions = $role['permissions'] ?? [];

        $route = route('roles.update', $id);

        return view('roles.create', compact('role', 'permissions', 'isAllPermissionsSelected', 'allowPermissionEditing', 'selectedPermissions', 'route'));
    }

    /**
     * Update a role.
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'permissions'   => 'array',
            'permissions.*' => 'string',
        ]);

        $result = $this->apiService->post("/v1/roles/{$id}", [
            'name'        => $request->name,
            'permissions' => $request->permissions ?? [],
            'module'      => 'production',
        ]);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['name' => $result['message'] ?? 'Failed to update role.']
            );
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Delete a role.
     */
    public function destroy(int $id)
    {
        $result = $this->apiService->delete("/v1/roles/{$id}", ['module' => 'production']);

        if (!($result['success'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'Failed to delete role.');
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
