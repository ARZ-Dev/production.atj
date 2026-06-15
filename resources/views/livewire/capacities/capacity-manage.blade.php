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
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Add Item Type</label>
                            <div wire:ignore>
                                <select
                                    id="cap_item_type"
                                    class="selectpicker form-control"
                                    title="Choose item type…"
                                    data-style="btn-default"
                                    data-live-search="true">
                                    @foreach($this->availableItemTypes() as $type)
                                    <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="button" class="btn btn-primary" wire:click="addItemType" @disabled(!$selected_item_type_id)>
                                <i class="bi bi-plus-lg me-1"></i> Add
                            </button>
                        </div>
                    </div>

                    {{-- Item type sections --}}
                    @if(empty($sections))
                    <div class="alert alert-secondary">
                        <i class="bi bi-arrow-up-circle me-2"></i>
                        Select an item type above and click Add to set capacity values.
                    </div>
                    @else
                    <form wire:submit.prevent="save">
                        @foreach($sections as $index => $section)
                        <div class="mb-4" wire:key="cap-section-{{ $section['item_type_id'] }}">
                            <h6 class="fw-semibold mb-2 text-capitalize">{{ $section['item_type_name'] }}</h6>

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

    @script
    <script>
        $('.selectpicker').selectpicker();

        $(document).on('change', '#cap_item_type', function () {
            const val = parseInt($(this).val()) || null;
            $wire.set('selected_item_type_id', val);
        });

        $wire.on('itemTypesUpdated', ({ itemTypes }) => {
            const $sel = $('#cap_item_type');
            $sel.empty();
            (itemTypes || []).forEach(type => {
                $sel.append($('<option>', { value: type.id, text: type.name }));
            });
            $sel.val('').selectpicker('refresh');
        });
    </script>
    @endscript
</div>
