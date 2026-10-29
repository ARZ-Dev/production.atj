<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="card-title mb-0">Station Types</h5>
                        <small class="text-muted">What a station does. A recipe stage picks one and runs on the line's stations of that type.</small>
                    </div>
                    <div class="d-flex gap-2">
                        @hasPermission('production.line-list')
                        <a href="{{ route('stations') }}" class="btn btn-light-info">
                            <i class="bi bi-gear-wide-connected me-1"></i>Stations
                        </a>
                        @endhasPermission
                        @hasPermission('production.line-create')
                        <button type="button" class="btn btn-primary" wire:click="create">
                            <i class="bi bi-plus-lg me-1"></i> Add Station Type
                        </button>
                        @endhasPermission
                    </div>
                </div>

                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Name</th>
                                <th>Stations</th>
                                <th>Used in</th>
                                <th>Recipe stages</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($types as $type)
                            <tr>
                                <td>{{ $type->sequence }}</td>
                                <td class="fw-semibold">{{ $type->name }}</td>
                                <td>{{ $type->stations_count }}</td>
                                <td>
                                    @forelse($type->stations->pluck('department_id')->filter()->unique()->sort() as $departmentId)
                                    <span class="badge bg-light-primary text-primary">{{ $departmentNames[$departmentId] ?? 'Department #' . $departmentId }}</span>
                                    @empty
                                    <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                                <td>{{ $type->recipe_stages_count }}</td>
                                <td class="text-wrap" style="min-width: 220px;">{{ $type->description }}</td>
                                <td>
                                    @hasPermission('production.line-edit')
                                    <button type="button" wire:click="edit({{ $type->id }})"
                                        class="btn btn-light-primary icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endhasPermission

                                    @hasPermission('production.line-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $type->id }}"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Delete">
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
    <div class="modal fade" id="stationTypeModal" tabindex="-1" aria-labelledby="stationTypeModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <form class="modal-content" wire:submit.prevent="submit">
                <div class="modal-header">
                    <h5 class="modal-title" id="stationTypeModalLabel">{{ $editing ? 'Edit Station Type' : 'New Station Type' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-8">
                            <label for="st_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="st_name" wire:model="name" placeholder="e.g. Cutting, Cooking">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-4">
                            <label for="st_sequence" class="form-label">Order <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" class="form-control @error('sequence') is-invalid @enderror"
                                id="st_sequence" wire:model="sequence">
                            @error('sequence')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="st_description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" rows="2"
                                id="st_description" wire:model="description"></textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @if($editing)
                        <div class="col-12">
                            <div class="alert alert-light border small mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Renaming it renames the recipe stages of this type too.
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"
                        wire:click="resetForm">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading wire:target="submit" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $editing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @script
    <script>
        const stationTypeModal = new bootstrap.Modal(document.getElementById('stationTypeModal'));
        $wire.on('openModal', () => stationTypeModal.show());
        $wire.on('closeModal', () => stationTypeModal.hide());
    </script>
    @endscript

    @script
        @include('livewire.deleteConfirm')
    @endscript
</div>
