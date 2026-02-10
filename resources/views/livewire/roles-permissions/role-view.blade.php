<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Role List</h5>
                    @can('role-create')
                    <button wire:click="clearData" data-bs-target="#saveRoleModal" data-bs-toggle="modal"
                        class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Role
                    </button>
                    @endcan
                </div>
                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Company</th>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $key => $role)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                @php
                                $companyId = $role->company_id;
                                $company = \App\Models\Company::find($companyId);
                                @endphp
                                <td>{{ $company->name ?? 'N/A' }}</td>
                                <td>
                                    <span class='d-flex align-items-center'>
                                        @if(isset(\App\Utils\Constants::ROLE_SETTINGS[$role->id]))
                                        <?php echo \App\Utils\Constants::ROLE_SETTINGS[$role->id]['badge']; ?>
                                        @else
                                        <span
                                            class="badge badge-center rounded-pill bg-label-info me-3 w-px-30 h-px-30">
                                            <i class="ti ti-user ti-sm"></i>
                                        </span>
                                        @endif
                                        {{ $role->name }}
                                    </span>
                                </td>
                                <td>
                                    @if($role->id != \App\Utils\Constants::SUPER_ADMIN_ROLE_ID)
                                    @can('role-edit')
                                    <a wire:click="edit({{ $role->id }})" href="#" data-bs-toggle="modal"
                                        data-bs-target="#saveRoleModal" class="btn btn-light-primary icon-btn-sm"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @endcan
                                    @can('role-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $role->id }}" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endcan
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Role Modal -->
    {{-- <div wire:ignore.self class="modal fade" id="saveRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-add-new-role">
            <div class="modal-content p-3 p-md-5">
                <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h3 class="role-title mb-2">{{ $editing ? 'Edit' : 'Add' }} Role</h3>
                        <p class="text-muted">Set Role Permissions</p>
                    </div>
                    <!-- Add role form -->
                    <form wire:submit.prevent="store" id="addRoleForm" class="row g-3">
                        @if (authUser()->hasRole('Super Admin'))
                        <div class="col-12" wire:ignore>
                            <label class="form-label" for="company_id">Company <span
                                    class="text-danger">*</span></label>
                            <select wire:model="company_id" id="company_id" class="form-select selectpicker w-100"
                                aria-label="Default select example" title="Select Company" data-style="btn-default"
                                data-live-search="true" data-icon-base="ti" data-tick-icon="ti-check text-white">
                                @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected($company->id == $company_id)>
                                    {{ $company->name }}
                                </option>
                                @endforeach
                            </select>

                            @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        @endif
                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalRoleName">Name <span
                                    class="text-danger">*</span></label>
                            <input wire:model.defer="name" type="text" id="modalRoleName" name="modalRoleName"
                                class="form-control" placeholder="Enter a role name" tabindex="-1" />
                            @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <h5>Role Permission</h5>
                            <!-- Permission table -->
                            <div class="table-responsive">
                                <table class="table table-flush-spacing">
                                    <tbody>
                                        <tr>
                                            <td class="text-nowrap fw-semibold">
                                                Administrator Access
                                                <i class="ti ti-info-circle" data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Allows a full access to the system"></i>
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
                                        @foreach($filteredPermissions as $key => $permissions)
                                        <tr class="{{ $loop->iteration % 2 == 0 ? 'bg-light' : '' }}">
                                            <td class="text-nowrap fw-semibold text-center">{{ ucfirst($key) }}
                                                Management</td>
                                            <td>
                                                <div class="d-flex">
                                                    @foreach($permissions as $permission)
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
                            <!-- Permission table -->
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
                            <button type="reset" class="btn btn-label-secondary close-modal" data-bs-dismiss="modal"
                                aria-label="Close">Cancel</button>
                        </div>
                    </form>
                    <!--/ Add role form -->
                </div>
            </div>
        </div>
    </div> --}}
    <!--/ Add Role Modal -->


    <!-- Start:: Static Modal -->
    <div wire:ignore.self class="modal fade" id="saveRoleModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="saveRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <div class="modal-header">
                    <h5 class="modal-title" id="saveRoleModalLabel">{{ $editing ? 'Edit' : 'Add' }} Role</h5>
                    <button type="button" class="btn-close icon-btn-sm" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ri-close-large-line fw-semibold"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="store" id="addRoleForm" class="row g-3">
                        @if(authUser()->hasRole('Super Admin'))
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="company_id">Company</label>
                            <div wire:ignore>
                                <select wire:model="company_id" id="company_id" class="selectpicker w-100"
                                    title="Select Company" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($companies as $company)
                                    <option value="{{ $company->id }}" @selected($company->id == $company_id)>{{
                                        $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                        </div>
                        @endif

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

                                        @foreach($filteredPermissions as $key => $permissions)
                                        <tr class="bg-white">
                                            <td class="text-nowrap fw-semibold text-center">{{ ucfirst($key) }}
                                                Management</td>
                                            <td>
                                                <div class="d-flex">
                                                    @foreach($permissions as $permission)
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

                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
                            <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- End modal -->

    @script
    @include('livewire.deleteConfirm')
    @endscript

    @script
    <script>
        $('.selectpicker').selectpicker()

        $(document).on('change', '.selectpicker', function() {
            $wire.set($(this).attr('wire:model'), $(this).val())
        })

        $wire.on('dismissModal', function() {
            $('#saveRoleModal').modal('hide')
        })

        $wire.on('setData', function (params) {
            let role = params[0];
            $('#company_id').selectpicker('val', role.company_id.toString());
        })

    </script>
    @endscript
</div>
