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
                    <h5 class="card-title mb-0">Production Lines</h5>
                    @hasPermission('production.production-line-create')
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add Production Line
                    </button>
                    @endhasPermission
                </div>

                <div class="card-body" wire:ignore>
                    <table id="production-lines-table" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Factory</th>
                                <th>Preparations</th>
                                <th>Lines</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productionLines as $pl)
                            <tr>
                                <td>{{ $pl->id }}</td>
                                <td>{{ $pl->name }}</td>
                                <td>
                                    @php $dept = collect($departments)->firstWhere('id', $pl->department_id); @endphp
                                    {{ $dept['name'] ?? '—' }}
                                </td>
                                <td>
                                    @php $factory = collect($factories)->firstWhere('id', $pl->factory_id); @endphp
                                    {{ $factory['name'] ?? '—' }}
                                </td>
                                <td>
                                    @if($pl->preparations->count())
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($pl->preparations as $prep)
                                            <span class="badge bg-primary bg-opacity-75">{{ $prep->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($pl->lines->count())
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($pl->lines as $line)
                                            <span class="badge bg-success bg-opacity-75">{{ $line->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @hasPermission('production.production-line-edit')
                                    <button type="button" wire:click="edit({{ $pl->id }})"
                                        class="btn btn-light-primary icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endhasPermission
                                    @hasPermission('production.production-line-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $pl->id }}"
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
    <div class="modal fade" id="plModal" tabindex="-1" aria-labelledby="plModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="plModalLabel">
                        {{ $editing ? 'Edit Production Line' : 'New Production Line' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label">Production Line Name <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                wire:model="name"
                                placeholder="e.g. Line Alpha">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Choose Department <span class="text-danger">*</span></label>
                            <div wire:ignore>
                                <select id="pl_department_id"
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
                            <label class="form-label">Factory <span class="text-danger">*</span></label>
                            <div wire:ignore>
                                <select id="pl_factory_id"
                                    class="selectpicker w-100 @error('factory_id') is-invalid @enderror"
                                    title="Select factory warehouse…"
                                    data-style="btn-default"
                                    data-live-search="true">
                                    @forelse($factories as $factory)
                                    <option value="{{ $factory['id'] }}"
                                        data-dept="{{ $factory['department_id'] }}"
                                        @selected($factory_id == $factory['id'])>
                                        {{ $factory['name'] }}
                                    </option>
                                    @empty
                                    <option disabled>No factory warehouses available</option>
                                    @endforelse
                                </select>
                            </div>
                            @error('factory_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Preparations</label>
                            <div wire:ignore>
                                <select id="pl_preparations"
                                    class="selectpicker w-100"
                                    multiple
                                    title="Select preparations…"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-actions-box="true">
                                    @foreach($preparations as $prep)
                                    <option value="{{ $prep->id }}"
                                        @selected(in_array($prep->id, $selectedPreparations))>
                                        {{ $prep->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Lines</label>
                            <div wire:ignore>
                                <select id="pl_lines"
                                    class="selectpicker w-100"
                                    multiple
                                    title="Select lines…"
                                    data-style="btn-default"
                                    data-live-search="true"
                                    data-actions-box="true">
                                    @foreach($lines as $line)
                                    <option value="{{ $line->id }}"
                                        @selected(in_array($line->id, $selectedLines))>
                                        {{ $line->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
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
        const plModal = new bootstrap.Modal(document.getElementById('plModal'));

        function filterPlFactories(deptId) {
            $('#pl_factory_id').find('option').each(function () {
                const optDept = $(this).data('dept');
                $(this).prop('disabled', !!deptId && !!optDept && String(optDept) !== String(deptId));
            });
            $('#pl_factory_id').selectpicker('refresh');
        }

        $wire.on('openModal', () => {
            plModal.show();
            setTimeout(() => {
                ['#pl_department_id', '#pl_factory_id', '#pl_preparations', '#pl_lines'].forEach(id => {
                    $(id).selectpicker('destroy').selectpicker();
                });
                const deptId = $wire.get('department_id');
                $('#pl_department_id').selectpicker('val', String(deptId || ''));
                filterPlFactories(deptId);
                $('#pl_factory_id').selectpicker('val', String($wire.get('factory_id') || ''));
                $('#pl_preparations').selectpicker('val', ($wire.get('selectedPreparations') || []).map(String));
                $('#pl_lines').selectpicker('val', ($wire.get('selectedLines') || []).map(String));
            }, 150);
        });

        $(document).on('change', '#pl_department_id', function () {
            const deptId = parseInt($(this).val()) || null;
            $wire.set('department_id', deptId);
            $wire.set('factory_id', null);
            filterPlFactories(deptId);
            $('#pl_factory_id').selectpicker('val', '');
        });
        $(document).on('change', '#pl_factory_id', function () {
            $wire.set('factory_id', parseInt($(this).val()) || null);
        });
        $(document).on('change', '#pl_preparations', function () {
            $wire.set('selectedPreparations', ($(this).val() || []).map(Number));
        });
        $(document).on('change', '#pl_lines', function () {
            $wire.set('selectedLines', ($(this).val() || []).map(Number));
        });
    </script>
    @include('livewire.deleteConfirm')
    @endscript
</div>
