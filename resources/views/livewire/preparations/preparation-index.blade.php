<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Preparations</h5>
                    @hasPermission('production.preparation-create')
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add Preparation
                    </button>
                    @endhasPermission
                </div>

                <div class="card-body" wire:ignore>
                    <table id="preparations-table" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>RM Warehouse</th>
                                <th>FG Warehouse</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preparations as $prep)
                            <tr>
                                <td>{{ $prep->id }}</td>
                                <td>{{ $prep->name }}</td>
                                <td>
                                    @php
                                        $dept = collect($departments)->firstWhere('id', $prep->department_id);
                                    @endphp
                                    {{ $dept['name'] ?? '—' }}
                                </td>
                                <td>
                                    @php
                                        $rmWh = collect($warehouses)->firstWhere('id', $prep->rm_warehouse_id);
                                    @endphp
                                    {{ $rmWh['name'] ?? '—' }}
                                </td>
                                <td>
                                    @php
                                        $fgWh = collect($warehouses)->firstWhere('id', $prep->fg_warehouse_id);
                                    @endphp
                                    {{ $fgWh['name'] ?? '—' }}
                                </td>
                                <td>
                                    <a href="{{ route('preparations.capacity', $prep->id) }}"
                                        class="btn btn-light-success icon-btn-sm"
                                        title="Manage Capacity">
                                        <i class="bi bi-speedometer2"></i>
                                    </a>
                                    @hasPermission('production.preparation-edit')
                                    <button type="button" wire:click="edit({{ $prep->id }})"
                                        class="btn btn-light-primary icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endhasPermission
                                    @hasPermission('production.preparation-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $prep->id }}"
                                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endhasPermission
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="preparationModal" tabindex="-1" aria-labelledby="preparationModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="preparationModalLabel">
                        {{ $editing ? 'Edit Preparation' : 'New Preparation' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label">Preparation Name <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                wire:model="name"
                                placeholder="e.g. Dough Preparation">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Choose Department <span class="text-danger">*</span></label>
                            <div wire:ignore>
                                <select id="prep_department_id"
                                    class="selectpicker w-100 @error('department_id') is-invalid @enderror"
                                    title="Select department…"
                                    data-style="btn-default"
                                    data-live-search="true">
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept['id'] }}"
                                        @selected($department_id == $dept['id'])>
                                        {{ $dept['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('department_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Raw Material Warehouse <span class="text-danger">*</span></label>
                            <div wire:ignore>
                                <select id="prep_rm_warehouse_id"
                                    class="selectpicker w-100 @error('rm_warehouse_id') is-invalid @enderror"
                                    title="Select warehouse…"
                                    data-style="btn-default"
                                    data-live-search="true">
                                    @foreach($warehouses as $wh)
                                    <option value="{{ $wh['id'] }}"
                                        data-dept="{{ $wh['department_id'] }}"
                                        @selected($rm_warehouse_id == $wh['id'])>
                                        {{ $wh['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('rm_warehouse_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Finished Good Warehouse <span class="text-danger">*</span></label>
                            <div wire:ignore>
                                <select id="prep_fg_warehouse_id"
                                    class="selectpicker w-100 @error('fg_warehouse_id') is-invalid @enderror"
                                    title="Select warehouse…"
                                    data-style="btn-default"
                                    data-live-search="true">
                                    @foreach($warehouses as $wh)
                                    <option value="{{ $wh['id'] }}"
                                        data-dept="{{ $wh['department_id'] }}"
                                        @selected($fg_warehouse_id == $wh['id'])>
                                        {{ $wh['name'] }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('fg_warehouse_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal" wire:click="resetForm">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="submit" wire:loading.attr="disabled">
                        <span wire:loading wire:target="submit" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $editing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const prepModal = new bootstrap.Modal(document.getElementById('preparationModal'));

        function filterPrepWarehouses(deptId) {
            ['#prep_rm_warehouse_id', '#prep_fg_warehouse_id'].forEach(id => {
                $(id).find('option').each(function () {
                    const optDept = $(this).data('dept');
                    $(this).prop('disabled', !!deptId && !!optDept && String(optDept) !== String(deptId));
                });
                $(id).selectpicker('refresh');
            });
        }

        $wire.on('openModal', () => {
            prepModal.show();
            setTimeout(() => {
                ['#prep_department_id', '#prep_rm_warehouse_id', '#prep_fg_warehouse_id'].forEach(id => {
                    $(id).selectpicker('destroy').selectpicker();
                });
                const deptId = $wire.get('department_id');
                $('#prep_department_id').selectpicker('val', String(deptId || ''));
                filterPrepWarehouses(deptId);
                $('#prep_rm_warehouse_id').selectpicker('val', String($wire.get('rm_warehouse_id') || ''));
                $('#prep_fg_warehouse_id').selectpicker('val', String($wire.get('fg_warehouse_id') || ''));
            }, 150);
        });

        $(document).on('change', '#prep_department_id', function () {
            const deptId = parseInt($(this).val()) || null;
            $wire.set('department_id', deptId);
            $wire.set('rm_warehouse_id', null);
            $wire.set('fg_warehouse_id', null);
            filterPrepWarehouses(deptId);
            $('#prep_rm_warehouse_id').selectpicker('val', '');
            $('#prep_fg_warehouse_id').selectpicker('val', '');
        });
        $(document).on('change', '#prep_rm_warehouse_id', function () {
            $wire.set('rm_warehouse_id', parseInt($(this).val()) || null);
        });
        $(document).on('change', '#prep_fg_warehouse_id', function () {
            $wire.set('fg_warehouse_id', parseInt($(this).val()) || null);
        });
    </script>
    @include('livewire.deleteConfirm')
    @endscript
</div>
