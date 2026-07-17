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
                    <h5 class="card-title mb-0">Guides</h5>
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add Guide
                    </button>
                </div>

                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Name (English)</th>
                                <th>Name (Português)</th>
                                <th>Sections</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($guides as $guide)
                            <tr>
                                <td>{{ $guide->id }}</td>
                                <td>{{ $guide->category?->name_en ?? '—' }}</td>
                                <td>{{ $guide->name_en }}</td>
                                <td>{{ $guide->name_pr }}</td>
                                <td><span class="badge bg-light-primary">{{ $guide->sections_count }}</span></td>
                                <td>
                                    <a href="{{ route('guides.manage', $guide->id) }}"
                                        class="btn btn-light-success icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Manage Content">
                                        <i class="bi bi-list-nested"></i>
                                    </a>

                                    <a href="{{ route('guides.view.show', $guide->id) }}"
                                        class="btn btn-light-info icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Preview">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <button type="button" wire:click="edit({{ $guide->id }})"
                                        class="btn btn-light-primary icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $guide->id }}"
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
    <div class="modal fade" id="guideModal" tabindex="-1" aria-labelledby="guideModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="guideModalLabel">
                        {{ $editing ? 'Edit Guide' : 'New Guide' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="g_category" class="form-label">Guide Category <span class="text-danger">*</span></label>
                        <select id="g_category" class="form-select @error('guide_category_id') is-invalid @enderror"
                            wire:model="guide_category_id">
                            <option value="">Select a category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name_en }} / {{ $category->name_pr }}</option>
                            @endforeach
                        </select>
                        @error('guide_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="g_name_en" class="form-label">Name (English) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name_en') is-invalid @enderror"
                            id="g_name_en" wire:model="name_en" placeholder="e.g. How to create a plan">
                        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="g_name_pr" class="form-label">Name (Português) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name_pr') is-invalid @enderror"
                            id="g_name_pr" wire:model="name_pr" placeholder="ex. Como criar um plano">
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
        const guideModal = new bootstrap.Modal(document.getElementById('guideModal'));

        $wire.on('openModal', () => guideModal.show());
        $wire.on('closeModal', () => guideModal.hide());
    </script>
    @endscript

    @script
        @include('livewire.deleteConfirm')
    @endscript
</div>
