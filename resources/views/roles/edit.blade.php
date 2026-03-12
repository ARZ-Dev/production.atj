<x-layouts.app title="Roles Management">

    <div class="row justify-content-center">
        <div class="col-12">

            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h2 class="mb-0">Create New Role</h2>
                    <p class="text-muted mb-0">Create a role for the production module</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST" id="roleForm">
                        @csrf

                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalRoleName">Name <span
                                    class="text-danger">*</span></label>
                            <input wire:model.defer="name" type="text" id="modalRoleName" name="modalRoleName"
                                   class="form-control" placeholder="Enter a role name" />
                            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <h5>Role Permission</h5>
                            <div class="table-responsive">
                                <table class="table table-flush-spacing">
                                    <tbody>
                                    <tr>
                                        <td class="text-nowrap fw-semibold">
                                            Administrator Access
                                            <i class="ti ti-info-circle" data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="Allows full access to the system"></i>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input wire:click="selectAllPermissions" class="form-check-input" {{
                                                        $isAllPermissionsSelected ? "checked" : "" }} type="checkbox"
                                                       id="selectAll" {{ $allowPermissionEditing ? "" : "disabled"
                                                        }} />
                                                <label class="form-check-label" for="selectAll">Select All</label>
                                            </div>
                                        </td>
                                    </tr>

                                    @foreach($permissions as $key => $permissionss)
                                        <tr class="bg-white">
                                            <td class="text-nowrap fw-semibold text-center">{{ ucfirst($key) }}
                                                Management</td>
                                            <td>
                                                <div class="d-flex">
                                                    @foreach($permissionss as $permission)
                                                        <div class="form-check me-3 me-lg-5">
                                                            <input
                                                                wire:click="togglePermission('{{ $key . '-' . $permission['name'] }}')"
                                                                class="form-check-input" type="checkbox"
                                                                id="permission_{{ $permission['id'] }}"
                                                                value="{{ $permission['id'] }}" {{ in_array($key . '-' .
                                                            $permission['name'], $selectedPermissions) ? 'checked' : ''
                                                            }} {{ $allowPermissionEditing ? '' : 'disabled' }} />
                                                            <label class="form-check-label"
                                                                   for="permission_{{ $permission['id'] }}">
                                                                {{ ucfirst($permission['name']) }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-layouts.app>

