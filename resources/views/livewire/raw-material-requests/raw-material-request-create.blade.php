<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            {{ $id ? 'Edit' : 'Add' }} Raw Material Request
                        </h6>
                        <a href="{{ route('raw-material-requests') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            {{-- intentionally empty header fields if none needed --}}
                        </div>
                    </div>
                </div>

                <div class="card mt-2">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Raw Material Request Items</h6>
                        <button type="button" class="btn btn-success btn-sm" wire:click="addRow">
                            <i class="ti ti-plus me-1"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        @if(count($items) > 0)
                        <div class="row g-3">
                            @foreach($items as $index => $item)
                            <div class="col-12" wire:key="raw-material-item-{{ $index }}">
                                <div class="border rounded p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label mb-0">
                                            Raw Material Item #{{ $index + 1 }}
                                        </label>
                                        <button type="button" class="btn btn-danger btn-sm"
                                            wire:click="removeRow({{ $index }})">
                                            <i class="ti ti-trash me-1"></i> Remove
                                        </button>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12 col-md-5">
                                            <label class="form-label" for="raw_material_{{ $index }}">
                                                Raw Material <span class="text-danger">*</span>
                                            </label>
                                            <div wire:ignore>
                                                <select wire:model="items.{{ $index }}.raw_material_id"
                                                    id="raw_material_{{ $index }}"
                                                    class="selectpicker w-100 item-raw-material"
                                                    title="Select Raw Material"
                                                    data-style="btn-default"
                                                    data-live-search="true"
                                                    data-icon-base="ti"
                                                    data-size="5"
                                                    data-tick-icon="ti-check text-white"
                                                    data-index="{{ $index }}">
                                                    @foreach($materials as $material)
                                                    <option value="{{ $material->id }}"
                                                        @selected($item['raw_material_id'] === $material->id)>
                                                        {{ $material->name }} ({{ $material->code }})
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('items.'.$index.'.raw_material_id')
                                            <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 col-md-3">
                                            <label class="form-label" for="quantity_{{ $index }}">
                                                Quantity <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                value="{{ $item['quantity'] ?? '' }}"
                                                id="quantity_{{ $index }}"
                                                class="form-control cleave-input quantity-input"
                                                placeholder="Qty"
                                                data-index="{{ $index }}">
                                            @error('items.'.$index.'.quantity')
                                            <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="unit_{{ $index }}_id">
                                                Unit <span class="text-danger">*</span>
                                            </label>
                                            <div wire:ignore>
                                                <select wire:model="items.{{ $index }}.unit_id"
                                                    id="unit_{{ $index }}_id"
                                                    class="selectpicker w-100 unit-select"
                                                    title="Select Unit"
                                                    data-style="btn-default"
                                                    data-live-search="true"
                                                    data-icon-base="ti"
                                                    data-size="5"
                                                    data-tick-icon="ti-check text-white"
                                                    data-index="{{ $index }}">
                                                    @foreach($units as $unit)
                                                    <option value="{{ $unit->id }}"
                                                        @selected($item['unit_id'] === $unit->id)>
                                                        {{ $unit->name }} ({{ $unit->symbol }})
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('items.'.$index.'.unit_id')
                                            <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No items added yet. Click "Add Item" to start.</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="card mt-2">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" wire:model="notes"
                                    placeholder="Enter any notes..." rows="3"></textarea>
                                @error('notes') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-end mt-2 mb-2">
                    <button type="button" class="btn btn-primary me-sm-3 me-1" wire:click="save">Submit</button>
                </div>
            </div>
        </div>
    </form>

    @script
    <script>
        $('.selectpicker').selectpicker()
        triggerCleave();

        Livewire.hook('morph.added', ({ el }) => {
            $('.selectpicker').selectpicker();
            triggerCleave();
        })

        $(document).on('change', '.selectpicker', function() {
            $wire.set($(this).attr('wire:model'), $(this).val())
        })

        // Handle quantity input change
        $(document).on('input', '.quantity-input', function() {
            let index = $(this).attr('data-index');
            let quantity = parseFloat($(this).val()) || 0;

            if (index !== undefined) {
                $wire.dispatch('updateQuantity', {
                    index: parseInt(index),
                    quantity: quantity
                });
            }
        });
    </script>
    @endscript
</div>