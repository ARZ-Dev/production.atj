<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private ApiService $api
    ) {}

    /**
     * List users — DataTable page.
     */
    public function index()
    {
        $data = [];
        $data['users'] = $this->api->get('/v1/users', ['module' => 'production'])['data'] ?? [];

        return view('users.index', $data);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $data = [];
        $rolesResult = $this->api->get('/v1/users/available-roles', ['module' => 'production']);
        $data['roles'] = $rolesResult['data'] ?? [];

        $departmentsResult = $this->api->get('/v1/departments', ['filter' => 'production']);
        $data['departments'] = $departmentsResult['data'] ?? [];

        $data['route'] = route('users.store');
        $data['editing'] = false;
        $data['user'] = null;

        return view('users.create', $data);
    }

    /**
     * Store new user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'username'              => 'required|string|max:255',
            'email'                 => 'required|email|max:255',
            'phone'                 => 'required|string|max:20',
            'role_name'             => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'department_ids'        => 'nullable|array',
            'department_ids.*'      => 'integer',
            'warehouse_ids'         => 'nullable|array',
            'warehouse_ids.*'       => 'integer',
            'item_type_ids'         => 'nullable|array',
            'item_type_ids.*'       => 'integer',
            'supervisor_ids'        => 'nullable|array',
            'supervisor_ids.*'      => 'integer',
        ]);

        $result = $this->api->post('/v1/users', [
            'module'                => 'production',
            'name'                  => $request->name,
            'username'              => $request->username,
            'email'                 => $request->email,
            'phone'                 => $request->phone,
            'role_name'             => $request->role_name,
            'password'              => $request->password,
            'password_confirmation' => $request->password_confirmation,
            'department_ids'        => $request->input('department_ids', []),
            'warehouse_ids'         => $request->input('warehouse_ids', []),
            'item_type_ids'         => $request->input('item_type_ids', []),
            'supervisor_ids'        => $request->input('supervisor_ids', []),
        ]);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['error' => $result['message'] ?? 'Failed to create user.']
            );
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        $data = [];
        $userResult = $this->api->get("/v1/users/{$id}");

        if (!($userResult['success'] ?? false)) {
            return redirect()->route('users.index')
                ->with('error', $userResult['message'] ?? 'User not found.');
        }

        $rolesResult = $this->api->get('/v1/users/available-roles', ['module' => 'production']);

        $departmentsResult = $this->api->get('/v1/departments', ['filter' => 'productions']);

        $data['user'] = $userResult['data'];
        $data['roles'] = $rolesResult['data'] ?? [];
        $data['departments'] = $departmentsResult['data'] ?? [];
        $data['route'] = route('users.update', $id);
        $data['editing'] = true;

        // Pre-load warehouses and supervisors for existing department selection
        $data['warehouses']  = [];
        $data['itemTypes']   = [];
        $data['supervisors'] = [];
        $firstDeptId = $data['user']['department_ids'][0] ?? null;
        if ($firstDeptId) {
            $warehousesResult  = $this->api->get('/warehouses', ['department_id' => $firstDeptId]);
            $data['warehouses'] = $warehousesResult['data'] ?? [];

            $supervisorsResult  = $this->api->get("/v1/departments/{$firstDeptId}/users");
            $data['supervisors'] = $supervisorsResult['data'] ?? [];
        }
        // Pre-load item types for all selected warehouses
        $warehouseIds = $data['user']['warehouse_ids'] ?? [];
        $itemTypes = [];
        $seen = [];
        foreach ($warehouseIds as $wId) {
            $result = $this->api->get("/warehouses/{$wId}/item-types");
            foreach ($result['data'] ?? [] as $itemType) {
                if (!isset($seen[$itemType['id']])) {
                    $seen[$itemType['id']] = true;
                    $itemTypes[] = $itemType;
                }
            }
        }
        $data['itemTypes'] = $itemTypes;

        return view('users.create', $data);
    }

    /**
     * Update user.
     */
    public function update(Request $request, int $id)
    {
        $rules = [
            'name'              => 'required|string|max:255',
            'username'          => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'phone'             => 'required|string|max:20',
            'role_name'         => 'required|string',
            'department_ids'    => 'nullable|array',
            'department_ids.*'  => 'integer',
            'warehouse_ids'     => 'nullable|array',
            'warehouse_ids.*'   => 'integer',
            'item_type_ids'     => 'nullable|array',
            'item_type_ids.*'   => 'integer',
            'supervisor_ids'    => 'nullable|array',
            'supervisor_ids.*'  => 'integer',
        ];

        // Password optional on update
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        $request->validate($rules);

        $data = [
            'name'           => $request->name,
            'username'       => $request->username,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'role_name'      => $request->role_name,
            'module'         => 'production',
            'department_ids' => $request->input('department_ids', []),
            'warehouse_ids'  => $request->input('warehouse_ids', []),
            'item_type_ids'  => $request->input('item_type_ids', []),
            'supervisor_ids' => $request->input('supervisor_ids', []),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
            $data['password_confirmation'] = $request->password_confirmation;
        }

        $result = $this->api->post("/v1/users/{$id}", $data);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['error' => $result['message'] ?? 'Failed to update user.']
            );
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Return warehouses filtered by department_id (AJAX).
     */
    public function getWarehouses(Request $request): JsonResponse
    {
        $result = $this->api->get('/warehouses', ['department_id' => $request->department_id]);
        return response()->json($result['data'] ?? []);
    }

    /**
     * Return item types for a given warehouse (AJAX).
     */
    public function getItemTypes(int $id): JsonResponse
    {
        $result = $this->api->get("/warehouses/{$id}/item-types");
        return response()->json($result['data'] ?? []);
    }

    /**
     * Return users (supervisors) for a given department (AJAX).
     */
    public function getDepartmentUsers(int $id): JsonResponse
    {
        $result = $this->api->get("/v1/departments/{$id}/users");
        return response()->json($result['data'] ?? []);
    }

    /**
     * Delete user.
     */
    public function destroy(int $id)
    {
        $result = $this->api->get("/v1/users/delete/{$id}", ['module' => 'production']);

        if (!($result['success'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'Failed to delete user.');
        }

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
