<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
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
        $data['route'] = route('users.store');

        return view('users.create', $data);
    }

    /**
     * Store new user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'username'              => 'required|string|max:255',
            'email'                 => 'required|email|max:255',
            'phone'                 => 'required|string|max:20',
            'role_name'             => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
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

        $data['user'] = $userResult['data'];
        $data['roles'] = $rolesResult['data'] ?? [];
        $data['route'] = route('users.update', $id);

        return view('users.edit', $data);
    }

    /**
     * Update user.
     */
    public function update(Request $request, int $id)
    {
        $rules = [
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'required|string|max:20',
            'role_name'  => 'required|string',
        ];

        // Password optional on update
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        $request->validate($rules);

        $data = [
            'name'       => $request->name,
            'username'   => $request->username,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'role_name'  => $request->role_name,
            'module'     => 'production',
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
