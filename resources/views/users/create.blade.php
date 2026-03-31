<x-layouts.app title="Users Management">

    <div class="row">
        <form action="{{ $route }}" method="POST" id="userForm">
            @csrf

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ $editing ? "Edit" : "Create" }} User</h6>
                    <a href="{{ route('users.index') }}" class="btn btn-light-light text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
                <div class="card-body">
                    {{-- Validation Errors --}}
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <strong><i class="bi bi-exclamation-triangle"></i> Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row g-3">
                        {{-- Name --}}
                        <div class="col-md-6">
                            <label for="name" class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   name="name"
                                   id="name"
                                   value="{{ $user['name'] ?? old('name') }}"
                                   placeholder="Enter Name"
                                   required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Username --}}
                        <div class="col-md-6">
                            <label for="username" class="form-label">
                                Username <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('username') is-invalid @enderror"
                                   name="username"
                                   id="username"
                                   value="{{ $user['username'] ?? old('username') }}"
                                   placeholder="Enter Username"
                                   required>
                            @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="col-md-6">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   name="email"
                                   id="email"
                                   value="{{ $user['email'] ?? old('email') }}"
                                   placeholder="Enter Email"
                                   required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="col-md-6">
                            <label for="role_name" class="form-label">
                                Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('role_name') is-invalid @enderror"
                                    name="role_name"
                                    id="role_name"
                                    required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role['name'] }}"
                                        @selected(($user['role'] ?? old('role_name')) == $role['name'])>
                                        {{ $role['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Departments --}}
                        <div class="col-md-6">
                            <label for="department_ids" class="form-label">Departments</label>
                            <select class="form-select @error('department_ids') is-invalid @enderror"
                                    name="department_ids[]"
                                    id="department_ids"
                                    multiple>
                                @foreach($departments as $department)
                                    <option value="{{ $department['id'] }}"
                                        @selected(in_array($department['id'], $user['department_ids'] ?? old('department_ids', [])))>
                                        {{ $department['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Warehouses --}}
                        <div class="col-md-6">
                            <label for="warehouse_ids" class="form-label">Warehouses</label>
                            <select class="form-select @error('warehouse_ids') is-invalid @enderror"
                                    name="warehouse_ids[]"
                                    id="warehouse_ids"
                                    multiple>
                                @foreach($warehouses ?? [] as $warehouse)
                                    <option value="{{ $warehouse['id'] }}"
                                        @selected(in_array($warehouse['id'], $user['warehouse_ids'] ?? old('warehouse_ids', [])))>
                                        {{ $warehouse['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Item Types --}}
                        <div class="col-md-6">
                            <label for="item_type_ids" class="form-label">Item Types</label>
                            <select class="form-select @error('item_type_ids') is-invalid @enderror"
                                    name="item_type_ids[]"
                                    id="item_type_ids"
                                    multiple>
                                @foreach($itemTypes ?? [] as $itemType)
                                    <option value="{{ $itemType['id'] }}"
                                        @selected(in_array($itemType['id'], $user['item_type_ids'] ?? old('item_type_ids', [])))>
                                        {{ $itemType['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('item_type_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Supervisors --}}
                        <div class="col-md-6">
                            <label for="supervisor_ids" class="form-label">Supervisors</label>
                            <select class="form-select @error('supervisor_ids') is-invalid @enderror"
                                    name="supervisor_ids[]"
                                    id="supervisor_ids"
                                    multiple>
                                @foreach($supervisors ?? [] as $supervisor)
                                    <option value="{{ $supervisor['id'] }}"
                                        @selected(in_array($supervisor['id'], $user['supervisor_ids'] ?? old('supervisor_ids', [])))>
                                        {{ $supervisor['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supervisor_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div class="col-md-6">
                            <label for="phone" class="form-label">
                                Phone <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   name="phone"
                                   id="phone"
                                   value="{{ $user['phone'] ?? old('phone') }}"
                                   placeholder="Enter Phone Number"
                                   required>
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Divider --}}
                        <div class="col-12">
                            <hr>
                            <h6 class="mb-0"><i class="bi bi-lock"></i> Password</h6>
                        </div>

                        {{-- Password --}}
                        <div class="col-md-6">
                            <label for="password" class="form-label">
                                Password <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password"
                                   id="password"
                                   placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                   @if(!$editing) required @endif
                            >
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Minimum 8 characters</div>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   class="form-control"
                                   name="password_confirmation"
                                   id="password_confirmation"
                                   placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                   @if(!$editing) required @endif
                            >
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

@push('scripts')
<script>
    const warehousesUrl    = '{{ route('users.warehouses') }}';
    const itemTypesBaseUrl = '{{ url('/admin/users/warehouses') }}';
    const deptUsersBaseUrl = '{{ url('/admin/users/departments') }}';

    function loadWarehouses(departmentId, selectedIds) {
        $('#warehouse_ids').html('');
        $('#item_type_ids').html('');

        if (!departmentId) return;

        $.get(warehousesUrl, { department_id: departmentId }, function (data) {
            data.forEach(function (w) {
                const sel = selectedIds && selectedIds.includes(w.id) ? 'selected' : '';
                $('#warehouse_ids').append(`<option value="${w.id}" ${sel}>${w.name}</option>`);
            });
            if (selectedIds && selectedIds.length) {
                loadItemTypes(selectedIds, @json($user['item_type_ids'] ?? []));
            }
        });
    }

    function loadItemTypes(warehouseIds, selectedIds) {
        const $itemTypes = $('#item_type_ids');
        $itemTypes.html('');

        if (!warehouseIds || !warehouseIds.length) return;

        const seen = {};
        let pending = warehouseIds.length;

        warehouseIds.forEach(function (wId) {
            $.get(`${itemTypesBaseUrl}/${wId}/item-types`, function (data) {
                data.forEach(function (t) {
                    if (!seen[t.id]) {
                        seen[t.id] = true;
                        const sel = selectedIds && selectedIds.includes(t.id) ? 'selected' : '';
                        $itemTypes.append(`<option value="${t.id}" ${sel}>${t.name}</option>`);
                    }
                });
                pending--;
            });
        });
    }

    function loadSupervisors(departmentId, selectedIds) {
        $('#supervisor_ids').html('');

        if (!departmentId) return;

        $.get(`${deptUsersBaseUrl}/${departmentId}/users`, function (data) {
            data.forEach(function (u) {
                const name = u.name ?? `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim();
                const sel  = selectedIds && selectedIds.includes(u.id) ? 'selected' : '';
                $('#supervisor_ids').append(`<option value="${u.id}" ${sel}>${name}</option>`);
            });
        });
    }

    $(document).ready(function () {
        $('#department_ids').on('change', function () {
            const deptIds   = $(this).val() ?? [];
            const firstDept = deptIds[0] ?? null;
            loadWarehouses(firstDept, []);
            loadSupervisors(firstDept, []);
        });

        $('#warehouse_ids').on('change', function () {
            loadItemTypes($(this).val() ?? [], []);
        });

        @if($editing && !empty($user['department_ids']))
        loadWarehouses(
            {{ $user['department_ids'][0] }},
            @json($user['warehouse_ids'] ?? [])
        );
        loadSupervisors(
            {{ $user['department_ids'][0] }},
            @json($user['supervisor_ids'] ?? [])
        );
        @endif
    });
</script>
@endpush
</x-layouts.app>

