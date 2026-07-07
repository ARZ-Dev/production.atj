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
                                   >
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
                                   >
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
                                   >
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="col-md-6">
                            <label for="role_name" class="form-label">
                                Role <span class="text-danger">*</span>
                            </label>
                            <select class="selectpicker w-100 @error('role_name') is-invalid @enderror"
                                    name="role_name"
                                    id="role_name"
                                    title="Select Role"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5"
                                    >
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

                        {{-- Department --}}
                        <div class="col-md-6">
                            <label for="department_id" class="form-label">Department</label>
                            <select class="selectpicker w-100 @error('department_id') is-invalid @enderror"
                                    name="department_id"
                                    id="department_id"
                                    title="Select Department"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5">
                                @foreach($departments as $department)
                                    <option value="{{ $department['id'] }}"
                                        @selected(old('department_id', $user['department_id'] ?? null) == $department['id'])>
                                        {{ $department['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Warehouses --}}
                        <div class="col-md-6">
                            <label for="warehouses_ids" class="form-label">Warehouses</label>
                            <select class="selectpicker w-100 @error('warehouses_ids') is-invalid @enderror"
                                    name="warehouses_ids[]"
                                    id="warehouses_ids"
                                    title="Select Warehouses"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5"
                                    data-tick-icon="ti-check text-white"
                                    multiple>
                                @foreach($warehouses ?? [] as $warehouse)
                                    <option value="{{ $warehouse['id'] }}"
                                        @selected(in_array($warehouse['id'], $user['warehouses_ids'] ?? old('warehouses_ids', [])))>
                                        {{ $warehouse['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouses_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Item Types --}}
                        <div class="col-md-6">
                            <label for="item_type_ids" class="form-label">Item Types</label>
                            <select class="selectpicker w-100 @error('item_type_ids') is-invalid @enderror"
                                    name="item_type_ids[]"
                                    id="item_type_ids"
                                    title="Select Item Types"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5"
                                    data-tick-icon="ti-check text-white"
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
                            <select class="selectpicker w-100 @error('supervisor_ids') is-invalid @enderror"
                                    name="supervisor_ids[]"
                                    id="supervisor_ids"
                                    title="Select Supervisors"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-icon-base="ti"
                                    data-size="5"
                                    data-tick-icon="ti-check text-white"
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

                        {{-- Shift Manager --}}
                        <div class="col-12">
                            <hr>
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       name="is_shift_manager"
                                       id="is_shift_manager"
                                       value="1"
                                       @checked(old('is_shift_manager', $userInfo->is_shift_manager ?? false))>
                                <label class="form-check-label" for="is_shift_manager">Is Shift Manager?</label>
                            </div>
                        </div>

                        <div class="col-12" id="shift_manager_fields" style="display: none;">
                            <div class="row g-3">
                                {{-- Shift --}}
                                <div class="col-md-4">
                                    <label for="shift_id" class="form-label">
                                        Shift <span class="text-danger">*</span>
                                    </label>
                                    <select class="selectpicker w-100 @error('shift_id') is-invalid @enderror"
                                            name="shift_id"
                                            id="shift_id"
                                            title="Select Shift"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5">
                                        @foreach($shifts as $shift)
                                            <option value="{{ $shift->id }}"
                                                @selected(old('shift_id', $userInfo->shift_id ?? null) == $shift->id)>
                                                {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->from_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($shift->to_time)->format('h:i A') }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('shift_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Preparations --}}
                                <div class="col-md-4">
                                    <label for="preparation_ids" class="form-label">Preparations</label>
                                    <select class="selectpicker w-100 @error('preparation_ids') is-invalid @enderror"
                                            name="preparation_ids[]"
                                            id="preparation_ids"
                                            title="Select Preparations"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white"
                                            multiple>
                                        @foreach($preparations ?? [] as $preparation)
                                            <option value="{{ $preparation->id }}"
                                                @selected(in_array($preparation->id, old('preparation_ids', $userInfo?->preparations->pluck('id')->all() ?? [])))>
                                                {{ $preparation->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('preparation_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Lines --}}
                                <div class="col-md-4">
                                    <label for="line_ids" class="form-label">Lines</label>
                                    <select class="selectpicker w-100 @error('line_ids') is-invalid @enderror"
                                            name="line_ids[]"
                                            id="line_ids"
                                            title="Select Lines"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white"
                                            multiple>
                                        @foreach($lines ?? [] as $line)
                                            <option value="{{ $line->id }}"
                                                @selected(in_array($line->id, old('line_ids', $userInfo?->lines->pluck('id')->all() ?? [])))>
                                                {{ $line->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('line_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
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
    const warehousesBaseUrl = '{{ url('/admin/users/departments') }}';
    const itemTypesBaseUrl  = '{{ url('/admin/users/warehouses') }}';
    const deptUsersBaseUrl  = '{{ url('/admin/users/departments') }}';

    function loadWarehouses(departmentId, selectedIds) {
        $('#warehouses_ids').html('');
        $('#item_type_ids').html('');

        if (!departmentId) return;

        $.get(`${warehousesBaseUrl}/${departmentId}/warehouses`, function (data) {
            data.forEach(function (w) {
                const sel = selectedIds && selectedIds.includes(w.id) ? 'selected' : '';
                $('#warehouses_ids').append(`<option value="${w.id}" ${sel}>${w.name}</option>`);
            });
            $('#warehouses_ids').selectpicker();
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
                if (pending === 0) $itemTypes.selectpicker();
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
            $('#supervisor_ids').selectpicker();
        });
    }

    function loadPreparations(departmentId, selectedIds) {
        $('#preparation_ids').html('');

        if (!departmentId) return;

        $.get(`${deptUsersBaseUrl}/${departmentId}/preparations`, function (data) {
            data.forEach(function (p) {
                const sel = selectedIds && selectedIds.includes(p.id) ? 'selected' : '';
                $('#preparation_ids').append(`<option value="${p.id}" ${sel}>${p.name}</option>`);
            });
            $('#preparation_ids').selectpicker();
        });
    }

    function loadLines(departmentId, selectedIds) {
        $('#line_ids').html('');

        if (!departmentId) return;

        $.get(`${deptUsersBaseUrl}/${departmentId}/lines`, function (data) {
            data.forEach(function (l) {
                const sel = selectedIds && selectedIds.includes(l.id) ? 'selected' : '';
                $('#line_ids').append(`<option value="${l.id}" ${sel}>${l.name}</option>`);
            });
            $('#line_ids').selectpicker();
        });
    }

    function toggleShiftManagerFields() {
        if ($('#is_shift_manager').is(':checked')) {
            $('#shift_manager_fields').show();
            $('#shift_manager_fields .selectpicker').selectpicker();
        } else {
            $('#shift_manager_fields').hide();
        }
    }

    $(document).ready(function () {

        $(document).on('changed.bs.select', '#department_id', function () {
            const deptId = $(this).val() || null;
            loadWarehouses(deptId, []);
            loadSupervisors(deptId, []);
            loadPreparations(deptId, []);
            loadLines(deptId, []);
        });

        $(document).on('changed.bs.select', '#warehouses_ids', function () {
            loadItemTypes($(this).val() ?? [], []);
        });

        $(document).on('change', '#is_shift_manager', toggleShiftManagerFields);
        toggleShiftManagerFields();

        @if($editing && !empty($user['department_ids']))
        loadWarehouses(
            {{ $user['department_ids'][0] }},
            @json($user['warehouses_ids'] ?? [])
        );
        loadSupervisors(
            {{ $user['department_ids'][0] }},
            @json($user['supervisor_ids'] ?? [])
        );
        loadPreparations(
            {{ $user['department_ids'][0] }},
            @json($userInfo?->preparations->pluck('id')->all() ?? [])
        );
        loadLines(
            {{ $user['department_ids'][0] }},
            @json($userInfo?->lines->pluck('id')->all() ?? [])
        );
        @endif
    });
</script>
@endpush
</x-layouts.app>

