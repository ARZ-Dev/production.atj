<x-layouts.app title="Roles Management">

    <div class="row">
        <form action="{{ $route }}" method="POST" id="roleForm">
            @csrf

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Add Role</h6>
                    <a href="{{ route('roles.index') }}" class="btn btn-light-light text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
                <div class="card-body">
                    <div class="col-12 mb-4">
                        <label class="form-label" for="name">Name <span
                                class="text-danger">*</span></label>
                        <input name="name" type="text" id="name" class="form-control" placeholder="Enter a role name" value="{{ $role['name'] ?? "" }}" />
                        @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <h5>Role Permissions</h5>
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
                                            <input wire:click="selectAllPermissions" class="form-check-input"  type="checkbox" id="selectAll"
                                                @checked($isAllPermissionsSelected ?? false)
                                                @disabled(!($allowPermissionEditing ?? true))
                                            />
                                            <label class="form-check-label" for="selectAll">Select All</label>
                                        </div>
                                    </td>
                                </tr>

                                @foreach($permissions as $key => $filtered)
                                    <tr class="bg-white">
                                        <td class="text-nowrap fw-semibold text-center">{{ ucfirst($key) }}</td>
                                        <td>
                                            <div class="d-flex">
                                                @foreach($filtered as $permissionKey => $permission)
                                                    <div class="form-check me-3 me-lg-5">
                                                        <input
                                                            name="permissions[]"
                                                            class="form-check-input" type="checkbox"
                                                            id="permission_{{ $permission['id'] }}"
                                                            value="{{ $key . '-' . $permission['name'] }}"
                                                            @checked(in_array($key . '-' . $permission['name'], $selectedPermissions ?? []))
                                                            @disabled(!($allowPermissionEditing ?? true))
                                                        />
                                                        <label class="form-check-label" for="permission_{{ $permission['id'] }}">
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
                </div>
                <div class="card-footer">
                    {{-- Submit --}}
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Submit
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

</x-layouts.app>

