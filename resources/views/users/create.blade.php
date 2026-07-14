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
                            <label for="warehouses_ids" class="form-label">Factories</label>
                            <select class="selectpicker w-100 @error('warehouses_ids') is-invalid @enderror"
                                    name="warehouses_ids[]"
                                    id="warehouses_ids"
                                    title="Select Factories"
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

{{--                        --}}{{-- Item Types --}}
{{--                        <div class="col-md-6">--}}
{{--                            <label for="item_type_ids" class="form-label">Item Types</label>--}}
{{--                            <select class="selectpicker w-100 @error('item_type_ids') is-invalid @enderror"--}}
{{--                                    name="item_type_ids[]"--}}
{{--                                    id="item_type_ids"--}}
{{--                                    title="Select Item Types"--}}
{{--                                    data-style="btn-default"--}}
{{--                                    data-live-search="true"--}}
{{--                                    data-icon-base="ti"--}}
{{--                                    data-size="5"--}}
{{--                                    data-tick-icon="ti-check text-white"--}}
{{--                                    multiple>--}}
{{--                                @foreach($itemTypes ?? [] as $itemType)--}}
{{--                                    <option value="{{ $itemType['id'] }}"--}}
{{--                                        @selected(in_array($itemType['id'], $user['item_type_ids'] ?? old('item_type_ids', [])))>--}}
{{--                                        {{ $itemType['name'] }}--}}
{{--                                    </option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                            @error('item_type_ids')--}}
{{--                            <div class="invalid-feedback">{{ $message }}</div>--}}
{{--                            @enderror--}}
{{--                        </div>--}}

{{--                        --}}{{-- Supervisors --}}
{{--                        <div class="col-md-6">--}}
{{--                            <label for="supervisor_ids" class="form-label">Supervisors</label>--}}
{{--                            <select class="selectpicker w-100 @error('supervisor_ids') is-invalid @enderror"--}}
{{--                                    name="supervisor_ids[]"--}}
{{--                                    id="supervisor_ids"--}}
{{--                                    title="Select Supervisors"--}}
{{--                                    data-style="btn-default"--}}
{{--                                    data-live-search="true"--}}
{{--                                    data-icon-base="ti"--}}
{{--                                    data-size="5"--}}
{{--                                    data-tick-icon="ti-check text-white"--}}
{{--                                    multiple>--}}
{{--                                @foreach($supervisors ?? [] as $supervisor)--}}
{{--                                    <option value="{{ $supervisor['id'] }}"--}}
{{--                                        @selected(in_array($supervisor['id'], $user['supervisor_ids'] ?? old('supervisor_ids', [])))>--}}
{{--                                        {{ $supervisor['name'] }}--}}
{{--                                    </option>--}}
{{--                                @endforeach--}}
{{--                            </select>--}}
{{--                            @error('supervisor_ids')--}}
{{--                            <div class="invalid-feedback">{{ $message }}</div>--}}
{{--                            @enderror--}}
{{--                        </div>--}}

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
                                <div class="col-lg-6 col-sm-12">
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

                                {{-- Production Lines --}}
                                <div class="col-lg-6 col-sm-12">
                                    <label for="production_line_ids" class="form-label">Production Lines</label>
                                    <select class="selectpicker w-100 @error('production_line_ids') is-invalid @enderror"
                                            name="production_line_ids[]"
                                            id="production_line_ids"
                                            title="Select Production Lines"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white"
                                            multiple>
                                        @foreach($productionLines ?? [] as $productionLine)
                                            <option value="{{ $productionLine->id }}"
                                                @selected(in_array($productionLine->id, old('production_line_ids', $userInfo?->productionLines->pluck('id')->all() ?? [])))>
                                                {{ $productionLine->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('production_line_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Preparations --}}
                                <div class="col-lg-6 col-sm-12">
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
                                <div class="col-lg-6 col-sm-12">
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
    const warehousesBaseUrl  = '{{ url('/admin/users/departments') }}';
    const itemTypesBaseUrl   = '{{ url('/admin/users/warehouses') }}';
    const deptUsersBaseUrl   = '{{ url('/admin/users/departments') }}';
    const productionLinesUrl = '{{ route('users.production-lines') }}';
    const plPreparationsUrl  = '{{ route('users.production-line-preparations') }}';
    const plLinesUrl         = '{{ route('users.production-line-lines') }}';

    function loadWarehouses(departmentId, selectedIds) {
        if (!departmentId) {
            $('#warehouses_ids').empty().selectpicker('refresh');
            $('#item_type_ids').empty().selectpicker('refresh');
            return;
        }

        $.get(`${warehousesBaseUrl}/${departmentId}/warehouses`, function (data) {
            const options = data.map(function (w) {
                const sel = selectedIds && selectedIds.includes(w.id) ? 'selected' : '';
                return `<option value="${w.id}" ${sel}>${w.name}</option>`;
            }).join('');

            $('#warehouses_ids').empty().append(options).selectpicker('destroy')
                .selectpicker();

            if (selectedIds && selectedIds.length) {
                loadItemTypes(selectedIds, @json($user['item_type_ids'] ?? []));
            }
        });
    }

    function loadItemTypes(warehouseIds, selectedIds) {
        const $itemTypes = $('#item_type_ids');
        $itemTypes.empty().selectpicker('refresh');

        if (!warehouseIds || !warehouseIds.length) return;

        const seen = {};
        let pending = warehouseIds.length;
        let options = '';

        warehouseIds.forEach(function (wId) {
            $.get(`${itemTypesBaseUrl}/${wId}/item-types`, function (data) {
                data.forEach(function (t) {
                    if (!seen[t.id]) {
                        seen[t.id] = true;
                        const sel = selectedIds && selectedIds.includes(t.id) ? 'selected' : '';
                        options += `<option value="${t.id}" ${sel}>${t.name}</option>`;
                    }
                });
                pending--;
                if (pending === 0) {
                    $itemTypes.empty().append(options).selectpicker('destroy').selectpicker();
                }
            });
        });
    }

    function loadSupervisors(departmentId, selectedIds) {
        if (!departmentId) {
            $('#supervisor_ids').empty().selectpicker('refresh');
            return;
        }

        $.get(`${deptUsersBaseUrl}/${departmentId}/users`, function (data) {
            const options = data.map(function (u) {
                const name = u.name ?? `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim();
                const sel  = selectedIds && selectedIds.includes(u.id) ? 'selected' : '';
                return `<option value="${u.id}" ${sel}>${name}</option>`;
            }).join('');

            $('#supervisor_ids').empty().append(options).selectpicker('destroy').selectpicker();
        });
    }

    function loadProductionLines(warehouseIds, selectedIds) {
        const $productionLines = $('#production_line_ids');
        const sel = (selectedIds ?? []).map(Number);

        if (!warehouseIds || !warehouseIds.length) {
            $productionLines.empty().selectpicker('refresh');
            loadPreparations([], []);
            loadLines([], []);
            return;
        }

        $.get(productionLinesUrl, { warehouse_ids: warehouseIds }, function (data) {
            const options = data.map(function (pl) {
                const s = sel.includes(pl.id) ? 'selected' : '';
                return `<option value="${pl.id}" ${s}>${pl.name}</option>`;
            }).join('');

            $productionLines.empty().append(options).selectpicker('destroy').selectpicker();

            // Refresh dependents with the production lines that survived the reload
            const remaining = $productionLines.val() ?? [];
            loadPreparations(remaining, $('#preparation_ids').val() ?? []);
            loadLines(remaining, $('#line_ids').val() ?? []);
        });
    }

    function loadPreparations(productionLineIds, selectedIds) {
        const $preparations = $('#preparation_ids');
        const sel = (selectedIds ?? []).map(Number);

        if (!productionLineIds || !productionLineIds.length) {
            $preparations.empty().selectpicker('refresh');
            return;
        }

        $.get(plPreparationsUrl, { production_line_ids: productionLineIds }, function (data) {
            const options = data.map(function (p) {
                const s = sel.includes(p.id) ? 'selected' : '';
                return `<option value="${p.id}" ${s}>${p.name}</option>`;
            }).join('');

            $preparations.empty().append(options).selectpicker('destroy').selectpicker();
        });
    }

    function loadLines(productionLineIds, selectedIds) {
        const $lines = $('#line_ids');
        const sel = (selectedIds ?? []).map(Number);

        if (!productionLineIds || !productionLineIds.length) {
            $lines.empty().selectpicker('refresh');
            return;
        }

        $.get(plLinesUrl, { production_line_ids: productionLineIds }, function (data) {
            const options = data.map(function (l) {
                const s = sel.includes(l.id) ? 'selected' : '';
                return `<option value="${l.id}" ${s}>${l.name}</option>`;
            }).join('');

            $lines.empty().append(options).selectpicker('destroy').selectpicker();
        });
    }

    function toggleShiftManagerFields() {
        if ($('#is_shift_manager').is(':checked')) {
            $('#shift_manager_fields').show();
            //$('#shift_manager_fields .selectpicker').selectpicker('refresh');
        } else {
            $('#shift_manager_fields').hide();
        }
    }

    $(document).ready(function () {

        $(document).on('change', '#department_id', function () {
            const deptId = $(this).val() || null;
            loadWarehouses(deptId, []);
            loadSupervisors(deptId, []);
            loadProductionLines([], []);
        });

        $(document).on('changed.bs.select', '#warehouses_ids', function () {
            const warehouseIds = $(this).val() ?? [];
            loadItemTypes(warehouseIds, []);
            loadProductionLines(warehouseIds, $('#production_line_ids').val() ?? []);
        });

        $(document).on('changed.bs.select', '#production_line_ids', function () {
            const productionLineIds = $(this).val() ?? [];
            loadPreparations(productionLineIds, $('#preparation_ids').val() ?? []);
            loadLines(productionLineIds, $('#line_ids').val() ?? []);
        });

        $(document).on('change', '#is_shift_manager', toggleShiftManagerFields);
        toggleShiftManagerFields();

    });
</script>
@endpush
</x-layouts.app>

