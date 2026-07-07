<?php

namespace App\Http\Controllers;

use App\Models\Line;
use App\Models\Preparation;
use App\Models\Shift;
use App\Models\UserInfo;
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

        // Shift manager assignments (local production DB)
        $data['shifts'] = Shift::orderBy('from_time')->get();
        $data['userInfo'] = null;
        $data['preparations'] = [];
        $data['lines'] = [];

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
            'department_id'         => 'nullable|integer',
            'warehouses_ids'         => 'nullable|array',
            'warehouses_ids.*'       => 'integer',
            'item_type_ids'         => 'nullable|array',
            'item_type_ids.*'       => 'integer',
            'supervisor_ids'        => 'nullable|array',
            'supervisor_ids.*'      => 'integer',
            'is_shift_manager'      => 'nullable|boolean',
            'shift_id'              => 'required_if:is_shift_manager,1|nullable|integer|exists:shifts,id',
            'preparation_ids'       => 'nullable|array',
            'preparation_ids.*'     => 'integer|exists:preparations,id',
            'line_ids'              => 'nullable|array',
            'line_ids.*'            => 'integer|exists:lines,id',
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
            'department_id' => $request->input('department_id'),
            'warehouses_ids'         => $request->input('warehouses_ids', []),
            'item_type_ids'         => $request->input('item_type_ids', []),
            'supervisor_ids'        => $request->input('supervisor_ids', []),
        ]);

        if (!($result['success'] ?? false)) {
            return back()->withInput()->withErrors(
                $result['errors'] ?? ['error' => $result['message'] ?? 'Failed to create user.']
            );
        }

        $newUserId = $result['data']['id'] ?? $result['data']['user']['id'] ?? null;

        if ($newUserId) {
            $this->syncShiftManagerInfo((int) $newUserId, $request);
        } elseif ($request->boolean('is_shift_manager')) {
            return redirect()->route('users.index')
                ->with('success', 'User created successfully.')
                ->with('error', 'Shift manager assignments were not saved because the auth service did not return the new user id. Edit the user to set them.');
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

        $departmentsResult = $this->api->get('/v1/departments', ['filter' => 'production']);

        $data['user'] = $userResult['data'];
        $data['roles'] = $rolesResult['data'] ?? [];
        $data['departments'] = $departmentsResult['data'] ?? [];
        $data['route'] = route('users.update', $id);
        $data['editing'] = true;

        // Shift manager assignments (local production DB)
        $data['shifts'] = Shift::orderBy('from_time')->get();
        $data['userInfo'] = UserInfo::with(['preparations', 'lines'])->where('user_id', $id)->first();

        // Pre-load warehouses and supervisors for existing department selection
        $data['warehouses']  = [];
        $data['warehouses_ids']  = $data['user']['warehouses_ids'] ?? [];
        $data['itemTypes']   = [];
        $data['supervisors'] = [];
        $data['preparations'] = [];
        $data['lines']        = [];
        $departmentId = $data['user']['department_id'] ?? null;
        if ($departmentId) {
            $warehousesResult  = $this->api->get("/v1/departments/{$departmentId}/warehouses");
            $data['warehouses'] = $warehousesResult['data'] ?? [];

            $supervisorsResult  = $this->api->get("/v1/departments/{$departmentId}/users");
            $data['supervisors'] = $supervisorsResult['data'] ?? [];

            $data['preparations'] = Preparation::where('department_id', $departmentId)->get();
            $data['lines']        = Line::where('department_id', $departmentId)->get();
        }
        // Pre-load item types for all selected warehouses
        $warehouseIds = $data['user']['warehouses_ids'] ?? [];
        $itemTypes = [];
        $seen = [];
        foreach ($warehouseIds as $wId) {
            $wId = (int) $wId;
            $result = $this->api->get("/v1/warehouses/{$wId}/item-types");
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
            'department_id'     => 'nullable|integer',
            'warehouses_ids'     => 'nullable|array',
            'warehouses_ids.*'   => 'integer',
            'item_type_ids'     => 'nullable|array',
            'item_type_ids.*'   => 'integer',
            'supervisor_ids'    => 'nullable|array',
            'supervisor_ids.*'  => 'integer',
            'is_shift_manager'  => 'nullable|boolean',
            'shift_id'          => 'required_if:is_shift_manager,1|nullable|integer|exists:shifts,id',
            'preparation_ids'   => 'nullable|array',
            'preparation_ids.*' => 'integer|exists:preparations,id',
            'line_ids'          => 'nullable|array',
            'line_ids.*'        => 'integer|exists:lines,id',
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
            'department_id' => $request->input('department_id'),
            'warehouses_ids'  => $request->input('warehouses_ids', []),
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

        $this->syncShiftManagerInfo($id, $request);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Save shift manager assignments in the local production DB.
     */
    private function syncShiftManagerInfo(int $userId, Request $request): void
    {
        if ($request->boolean('is_shift_manager')) {
            $userInfo = UserInfo::updateOrCreate(
                ['user_id' => $userId],
                [
                    'is_shift_manager' => true,
                    'shift_id'         => $request->input('shift_id'),
                ]
            );

            $userInfo->preparations()->sync($request->input('preparation_ids', []));
            $userInfo->lines()->sync($request->input('line_ids', []));
        } else {
            UserInfo::where('user_id', $userId)->delete();
        }
    }

    /**
     * Return warehouses filtered by department_id (AJAX).
     */
    public function getWarehouses(int $id): JsonResponse
    {
        $result = $this->api->get("/v1/departments/{$id}/warehouses");
        return response()->json($result['data'] ?? []);
    }

    /**
     * Return item types for a given warehouse (AJAX).
     */
    public function getItemTypes(int $id): JsonResponse
    {
        $result = $this->api->get("/v1/warehouses/{$id}/item-types");
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
     * Return preparations for a given department (AJAX, local DB).
     */
    public function getPreparations(int $id): JsonResponse
    {
        return response()->json(
            Preparation::where('department_id', $id)->get(['id', 'name'])
        );
    }

    /**
     * Return lines for a given department (AJAX, local DB).
     */
    public function getLines(int $id): JsonResponse
    {
        return response()->json(
            Line::where('department_id', $id)->get(['id', 'name'])
        );
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

        UserInfo::where('user_id', $id)->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
