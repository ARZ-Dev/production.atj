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
                    <h5 class="card-title mb-0">Guide Categories</h5>
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add Guide Category
                    </button>
                </div>

                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name (English)</th>
                                <th>Name (Português)</th>
                                <th>Guides</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                            <tr>
                                <td>{{ $category->id }}</td>
                                <td>{{ $category->name_en }}</td>
                                <td>{{ $category->name_pr }}</td>
                                <td><span class="badge bg-light-primary">{{ $category->guides_count }}</span></td>
                                <td>
                                    <button type="button" wire:click="edit({{ $category->id }})"
                                        class="btn btn-light-primary icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $category->id }}"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
    <div class="modal fade" id="guideCategoryModal" tabindex="-1" aria-labelledby="guideCategoryModalLabel"
        aria-hidden="true" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="guideCategoryModalLabel">
                        {{ $editing ? 'Edit Guide Category' : 'New Guide Category' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="gc_name_en" class="form-label">Name (English) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name_en') is-invalid @enderror"
                            id="gc_name_en" wire:model="name_en" placeholder="e.g. Getting Started">
                        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="gc_name_pr" class="form-label">Name (Português) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name_pr') is-invalid @enderror"
                            id="gc_name_pr" wire:model="name_pr" placeholder="ex. Primeiros Passos">
                        @error('name_pr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"
                        wire:click="resetForm">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="submit"
                        wire:loading.attr="disabled" wire:target="submit">
                        <span wire:loading wire:target="submit" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $editing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const guideCategoryModal = new bootstrap.Modal(document.getElementById('guideCategoryModal'));

        $wire.on('openModal', () => guideCategoryModal.show());
        $wire.on('closeModal', () => guideCategoryModal.hide());
    </script>
    @endscript

    @script
        @include('livewire.deleteConfirm')
    @endscript
</div>
