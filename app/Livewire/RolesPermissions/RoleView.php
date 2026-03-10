<?php

namespace App\Livewire\RolesPermissions;

use App\Models\Company;
use App\Utils\Constants;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleView extends Component
{
    use AuthorizesRequests;

    public $roles = [];
    public $role;

    public $permissions;
    public $filteredPermissions;
    public $selectedPermissions = [];
    public bool $editing = false;
    public bool $isAllPermissionsSelected = false;
    public bool $allowPermissionEditing = true;

    public $name = "";


    protected $listeners = [
        'delete',
    ];

    public function mount()
    {
        authorizeRequest('production.role-list');

        $this->permissions = Permission::all();
        foreach ($this->permissions as $permission) {
            $prefix = explode('-', $permission->name)[0];
            if (count(explode('-', $permission->name)) > 1) {
                $this->filteredPermissions[$prefix][] = [
                    'id' => $permission->id,
                    'name' => explode('-', $permission->name)[1],
                ];
            }
        }

        $this->roles = Role::all();
    }

    public function clearData()
    {
        $this->reset('editing', 'name', 'selectedPermissions', 'role');
        $this->resetValidation();
    }

    public function togglePermission($permission)
    {
        if (in_array($permission, $this->selectedPermissions)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$permission]);
        } else {
            $this->selectedPermissions[] = $permission;
        }
        $this->isAllPermissionsSelected = count($this->selectedPermissions) == count($this->permissions);
    }

    public function selectAllPermissions()
    {
        $this->reset('selectedPermissions');
        if (!$this->isAllPermissionsSelected) {
            foreach ($this->permissions as $permission) {
                $this->selectedPermissions[] = $permission->name;
            }
        }
        $this->isAllPermissionsSelected = !$this->isAllPermissionsSelected;
    }

    public function edit($id)
    {
        $this->clearData();
        $this->editing = true;
        $this->allowPermissionEditing = $id != Constants::SUPER_ADMIN_ROLE_ID;
        $this->role = Role::findById($id);
        $this->name = $this->role->name;
        $this->selectedPermissions = $this->role->permissions()->pluck('name')->toArray();
        $this->isAllPermissionsSelected = count($this->selectedPermissions) == count($this->permissions);
        $this->dispatch('setData', $this->role);
    }

    public function rules()
    {
        return [
            'name' => ['required', 'max:255', Rule::unique('roles', 'name')->ignore($this->role?->id)],
        ];
    }

    public function store()
    {
        $this->validate();

        if ($this->role) {
            authorizeRequest('production.role-edit');

            $this->role->update([
                'name' => $this->name,
            ]);

        } else {
            authorizeRequest('production.role-create');

            $this->role = Role::create([
                'name' => $this->name,
            ]);
        }
        $this->role->syncPermissions($this->selectedPermissions);

        $this->dispatch('dismissModal');

        // $this->dispatch('swal:success', [
        //     'title' => 'Great!',
        //     'text' => "Role has been saved successfully!",
        // ]);

        return to_route('roles')->with('success', 'Role has been saved successfully!');

    }

    public function delete($id)
    {
        authorizeRequest('production.role-delete');
        // check if role has users before deleting
        $role = Role::findById($id);
        if (count($role->users)) {
            $this->dispatch('swal:error', [
                'title' => 'Error!',
                'text' => "You have to unassign this role from users before deleting!",
            ]);
            return;
        }

        $role->delete();

        $this->dispatch('swal:success', [
            'title' => 'Great!',
            'text' => "Role has been deleted successfully!",
        ]);

        return to_route('roles')->with('success', 'Role has been deleted successfully!');
    }

    public function render()
    {
        $data = [];

        return view('livewire.roles-permissions.role-view', $data);
    }
}
