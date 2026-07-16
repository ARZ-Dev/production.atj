<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0">
                            <i class="bi bi-journal-text me-1"></i>
                            {{ $guide->name_en }}
                            <span class="text-muted fs-13">/ {{ $guide->name_pr }}</span>
                        </h5>
                        <span class="badge bg-light-info mt-1">{{ $guide->category?->name_en ?? 'No category' }}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('guides') }}" class="btn btn-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </a>
                        <a href="{{ route('guides.view.show', $guide->id) }}" class="btn btn-light-info">
                            <i class="bi bi-eye me-1"></i> Preview
                        </a>
                        <button type="button" class="btn btn-primary" wire:click="createSection">
                            <i class="bi bi-plus-lg me-1"></i> Add Section
                        </button>
                    </div>
                </div>
            </div>

            @forelse($guide->sections as $section)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">{{ $loop->iteration }}</span>
                        <h6 class="mb-0">{{ $section->name_en }} <span class="text-muted fw-normal">/ {{ $section->name_pr }}</span></h6>
                        <span class="badge bg-light-secondary">{{ $section->blocks->count() }} {{ Str::plural('block', $section->blocks->count()) }}</span>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-light icon-btn-sm" wire:click="moveSection({{ $section->id }}, 'up')"
                            @disabled($loop->first) title="Move up">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-light icon-btn-sm" wire:click="moveSection({{ $section->id }}, 'down')"
                            @disabled($loop->last) title="Move down">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                        <button type="button" class="btn btn-light-success btn-sm" wire:click="createBlock({{ $section->id }})">
                            <i class="bi bi-plus-lg me-1"></i> Add Block
                        </button>
                        <button type="button" class="btn btn-light-primary icon-btn-sm" wire:click="editSection({{ $section->id }})"
                            title="Edit section">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button type="button" class="btn btn-light-danger icon-btn-sm delete-section-button"
                            data-id="{{ $section->id }}" title="Delete section">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($section->blocks->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-card-text fs-3 d-block mb-1"></i>
                        No blocks yet. Click "Add Block" to add content to this section.
                    </div>
                    @else
                    <table class="table table-striped mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width:60px">#</th>
                                <th>Title (English)</th>
                                <th>Title (Português)</th>
                                <th>Subtitle (English)</th>
                                <th style="width:180px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($section->blocks as $block)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $block->title_en }}</td>
                                <td>{{ $block->title_pr }}</td>
                                <td>{{ $block->subtitle_en ?: '—' }}</td>
                                <td>
                                    <button type="button" class="btn btn-light icon-btn-sm"
                                        wire:click="moveBlock({{ $block->id }}, 'up')" @disabled($loop->first) title="Move up">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button type="button" class="btn btn-light icon-btn-sm"
                                        wire:click="moveBlock({{ $block->id }}, 'down')" @disabled($loop->last) title="Move down">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-light-primary icon-btn-sm"
                                        wire:click="editBlock({{ $block->id }})" title="Edit block">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-block-button"
                                        data-id="{{ $block->id }}" title="Delete block">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
            @empty
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-journal-plus fs-1 d-block mb-2"></i>
                    <p class="mb-2">This guide has no sections yet.</p>
                    <button type="button" class="btn btn-primary" wire:click="createSection">
                        <i class="bi bi-plus-lg me-1"></i> Add the first section
                    </button>
                </div>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Section Modal -->
    <div class="modal fade" id="guideSectionModal" tabindex="-1" aria-labelledby="guideSectionModalLabel"
        aria-hidden="true" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="guideSectionModalLabel">
                        {{ $editingSection ? 'Edit Section' : 'New Section' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetSectionForm"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="gs_name_en" class="form-label">Name (English) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('section_name_en') is-invalid @enderror"
                            id="gs_name_en" wire:model="section_name_en" placeholder="e.g. Introduction">
                        @error('section_name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="gs_name_pr" class="form-label">Name (Português) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('section_name_pr') is-invalid @enderror"
                            id="gs_name_pr" wire:model="section_name_pr" placeholder="ex. Introdução">
                        @error('section_name_pr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"
                        wire:click="resetSectionForm">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="saveSection"
                        wire:loading.attr="disabled" wire:target="saveSection">
                        <span wire:loading wire:target="saveSection" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $editingSection ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Block Modal -->
    <div class="modal fade" id="guideBlockModal" tabindex="-1" aria-labelledby="guideBlockModalLabel"
        aria-hidden="true" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="guideBlockModalLabel">
                        {{ $editingBlock ? 'Edit Block' : 'New Block' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetBlockForm"></button>
                </div>
                <div class="modal-body">
                    @error('block_section_id')<div class="alert alert-danger">{{ $message }}</div>@enderror

                    <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="gb-tab-en" data-bs-toggle="tab"
                                data-bs-target="#gb-pane-en" type="button" role="tab">
                                <i class="bi bi-translate me-1"></i> English
                                @if($errors->has('title_en'))<i class="bi bi-exclamation-circle text-danger ms-1"></i>@endif
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="gb-tab-pr" data-bs-toggle="tab"
                                data-bs-target="#gb-pane-pr" type="button" role="tab">
                                <i class="bi bi-translate me-1"></i> Português
                                @if($errors->has('title_pr'))<i class="bi bi-exclamation-circle text-danger ms-1"></i>@endif
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="gb-pane-en" role="tabpanel">
                            <div class="mb-3">
                                <label for="gb_title_en" class="form-label">Title (English) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title_en') is-invalid @enderror"
                                    id="gb_title_en" wire:model="title_en" placeholder="Block title">
                                @error('title_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="gb_subtitle_en" class="form-label">Subtitle (English)</label>
                                <input type="text" class="form-control @error('subtitle_en') is-invalid @enderror"
                                    id="gb_subtitle_en" wire:model="subtitle_en" placeholder="Optional subtitle">
                                @error('subtitle_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Content (English)</label>
                                <div wire:ignore>
                                    <div id="quill_content_en" style="min-height: 220px;"></div>
                                </div>
                                @error('content_en')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="tab-pane fade" id="gb-pane-pr" role="tabpanel">
                            <div class="mb-3">
                                <label for="gb_title_pr" class="form-label">Título (Português) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title_pr') is-invalid @enderror"
                                    id="gb_title_pr" wire:model="title_pr" placeholder="Título do bloco">
                                @error('title_pr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="gb_subtitle_pr" class="form-label">Subtítulo (Português)</label>
                                <input type="text" class="form-control @error('subtitle_pr') is-invalid @enderror"
                                    id="gb_subtitle_pr" wire:model="subtitle_pr" placeholder="Subtítulo opcional">
                                @error('subtitle_pr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Conteúdo (Português)</label>
                                <div wire:ignore>
                                    <div id="quill_content_pr" style="min-height: 220px;"></div>
                                </div>
                                @error('content_pr')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"
                        wire:click="resetBlockForm">Cancel</button>
                    <button type="button" id="gb_save_btn" class="btn btn-primary"
                        wire:loading.attr="disabled" wire:target="saveBlock">
                        <span wire:loading wire:target="saveBlock" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $editingBlock ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const guideSectionModal = new bootstrap.Modal(document.getElementById('guideSectionModal'));
        const guideBlockModal = new bootstrap.Modal(document.getElementById('guideBlockModal'));

        const quillToolbar = [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ color: [] }, { background: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ indent: '-1' }, { indent: '+1' }],
            [{ align: [] }],
            ['link', 'blockquote', 'code-block'],
            ['clean'],
        ];

        const quillEn = new Quill('#quill_content_en', { theme: 'snow', modules: { toolbar: quillToolbar } });
        const quillPr = new Quill('#quill_content_pr', { theme: 'snow', modules: { toolbar: quillToolbar } });

        $wire.on('openSectionModal', () => guideSectionModal.show());
        $wire.on('closeSectionModal', () => guideSectionModal.hide());

        $wire.on('openBlockModal', ({ contentEn, contentPr }) => {
            quillEn.root.innerHTML = contentEn || '';
            quillPr.root.innerHTML = contentPr || '';
            guideBlockModal.show();
        });
        $wire.on('closeBlockModal', () => guideBlockModal.hide());

        // Read the editors' HTML right before submitting, so no content is
        // ever lost between Quill and Livewire state.
        document.getElementById('gb_save_btn').addEventListener('click', () => {
            $wire.set('content_en', quillEn.root.innerHTML, false);
            $wire.set('content_pr', quillPr.root.innerHTML, false);
            $wire.call('saveBlock');
        });

        $wire.on('guide-toast', ({ message }) => {
            toastr.success(message, 'Success', {
                positionClass: 'toast-top-right',
                progressBar: true,
                timeOut: 3000,
                closeButton: true,
            });
        });

        const confirmDelete = (event, id) => {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                customClass: {
                    confirmButton: 'btn btn-danger me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch(event, { id: id });
                }
            });
        };

        document.addEventListener('click', function (e) {
            const sectionBtn = e.target.closest('.delete-section-button');
            if (sectionBtn) {
                confirmDelete('deleteSection', sectionBtn.dataset.id);
                return;
            }

            const blockBtn = e.target.closest('.delete-block-button');
            if (blockBtn) {
                confirmDelete('deleteBlock', blockBtn.dataset.id);
            }
        });
    </script>
    @endscript
</div>
