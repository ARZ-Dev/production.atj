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
                    <div>
                        <h5 class="card-title mb-0">
                            Manage Capacity — {{ $modelName }}
                        </h5>
                        <small class="text-muted text-capitalize">{{ $modelType }}</small>
                    </div>
                    <a href="{{ $modelType === 'preparation' ? route('preparations') : route('lines') }}"
                        class="btn btn-light-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>

                <div class="card-body">

                    {{-- Add Item Type --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Add Item Type</label>
                        @if(empty($this->availableItemTypes()))
                        <p class="text-muted small mb-0">All item types have been added.</p>
                        @else
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($this->availableItemTypes() as $type)
                            <button type="button" wire:click="addItemType({{ $type['id'] }})"
                                class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> {{ $type['name'] }}
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    {{-- Item type sections --}}
                    @if(empty($sections))
                    <div class="alert alert-secondary">
                        <i class="bi bi-arrow-up-circle me-2"></i>
                        Add an item type above to set capacity values.
                    </div>
                    @else
                    <form wire:submit.prevent="save">
                        @foreach($sections as $index => $section)
                        <div class="card mb-3" wire:key="cap-section-{{ $section['item_type_id'] }}">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <h6 class="fw-semibold mb-0 text-capitalize">{{ $section['item_type_name'] }}</h6>
                                <button type="button" class="btn btn-light-danger icon-btn-sm" wire:click="removeItemType({{ $index }})" title="Remove">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="card-body">

                            @if(!empty($section['items']))
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:60%">Item</th>
                                            <th style="width:40%">Output / Hr</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($section['items'] as $item)
                                        <tr>
                                            <td class="align-middle">{{ $item['name'] }}</td>
                                            <td>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="form-control form-control-sm @error('sections.'.$index.'.capacityRows.'.$item['id']) is-invalid @enderror"
                                                    wire:model="sections.{{ $index }}.capacityRows.{{ $item['id'] }}"
                                                    placeholder="0.00"
                                                />
                                                @error('sections.'.$index.'.capacityRows.'.$item['id'])
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No items found for this type.
                            </div>
                            @endif
                            </div>
                        </div>
                        @endforeach

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="bi bi-check-circle me-1"></i> Save Capacities
                            </button>
                        </div>
                    </form>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
