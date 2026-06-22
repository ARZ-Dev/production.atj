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
                    <h5 class="card-title mb-0">Event Types</h5>
                    @hasPermission('production.eventType-create')
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add Event Type
                    </button>
                    @endhasPermission
                </div>

                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>With Recipe</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eventTypes as $eventType)
                            <tr>
                                <td>{{ $eventType->id }}</td>
                                <td>{{ $eventType->name }}</td>
                                <td>
                                    @if($eventType->has_recipe)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>{{ $eventType->has_recipe ? '—' : ($eventType->duration ? $eventType->duration . ' min' : '—') }}</td>
                                <td>
                                    @hasPermission('production.eventType-edit')
                                    <button type="button" wire:click="edit({{ $eventType->id }})"
                                        class="btn btn-light-primary icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endhasPermission

                                    @hasPermission('production.eventType-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $eventType->id }}"
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
    <div class="modal fade" id="eventTypeModal" tabindex="-1" aria-labelledby="eventTypeModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventTypeModalLabel">
                        {{ $editing ? 'Edit Event Type' : 'New Event Type' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="et_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="et_name" wire:model="name" placeholder="e.g. Cleaning, Production, Maintenance">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Duration source <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="et_duration_mode" id="et_mode_recipe"
                                wire:model.live="has_recipe" value="1" autocomplete="off">
                            <label class="btn btn-outline-primary" for="et_mode_recipe">
                                <i class="bi bi-droplet-half me-1"></i> Has Recipe
                            </label>

                            <input type="radio" class="btn-check" name="et_duration_mode" id="et_mode_fixed"
                                wire:model.live="has_recipe" value="0" autocomplete="off">
                            <label class="btn btn-outline-primary" for="et_mode_fixed">
                                <i class="bi bi-clock me-1"></i> Fixed Duration
                            </label>
                        </div>
                        <div class="form-text">
                            "Has Recipe" events calculate their duration per event from the recipe and the
                            capacity of the preparation/line they're placed on. "Fixed Duration" events use the
                            duration set below for every event of this type.
                        </div>
                    </div>

                    @if(!$has_recipe)
                    <div class="mb-3">
                        <label for="et_duration" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                        <input type="number" min="1" class="form-control @error('duration') is-invalid @enderror"
                            id="et_duration" wire:model="duration" placeholder="e.g. 30">
                        @error('duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif
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
        const eventTypeModal = new bootstrap.Modal(document.getElementById('eventTypeModal'));

        $wire.on('openModal', () => eventTypeModal.show());
        $wire.on('closeModal', () => eventTypeModal.hide());
    </script>
    @endscript

    @script
        @include('livewire.deleteConfirm')
    @endscript
</div>
