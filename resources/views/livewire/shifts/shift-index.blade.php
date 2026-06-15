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
                    <h5 class="card-title mb-0">Shifts</h5>
                    @hasPermission('production.shift-create')
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add Shift
                    </button>
                    @endhasPermission
                </div>

                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shifts as $shift)
                            <tr>
                                <td>{{ $shift->id }}</td>
                                <td>{{ $shift->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($shift->from_time)->format('h:i A') }}</td>
                                <td>{{ \Carbon\Carbon::parse($shift->to_time)->format('h:i A') }}</td>
                                <td>
                                    @hasPermission('production.shift-edit')
                                    <button type="button" wire:click="edit({{ $shift->id }})"
                                        class="btn btn-light-primary icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endhasPermission

                                    @hasPermission('production.shift-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $shift->id }}"
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
    <div class="modal fade" id="shiftModal" tabindex="-1" aria-labelledby="shiftModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shiftModalLabel">{{ $editing ? 'Edit Shift' : 'New Shift' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="shift_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="shift_name" wire:model="name" placeholder="e.g. Morning, Night">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-6">
                            <label for="from_time" class="form-label">From <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('from_time') is-invalid @enderror"
                                id="from_time" wire:model="from_time">
                            @error('from_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-6">
                            <label for="to_time" class="form-label">To <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('to_time') is-invalid @enderror"
                                id="to_time" wire:model="to_time">
                            @error('to_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"
                        wire:click="resetForm">Cancel</button>
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
        const shiftModal = new bootstrap.Modal(document.getElementById('shiftModal'));
        $wire.on('openModal', () => shiftModal.show());
        $wire.on('closeModal', () => shiftModal.hide());
    </script>
    @endscript

    @script
        @include('livewire.deleteConfirm')
    @endscript
</div>
