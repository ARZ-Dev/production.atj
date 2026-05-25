<div>
    <form>
        <div class="row">
            <div class="col-12">

                {{-- Header card --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ $id ? 'Edit' : 'Add' }} Item Request</h6>
                        <a href="{{ route('item-requests') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <div wire:ignore>
                                    <label for="warehouse" class="form-label">
                                        Warehouse <span class="text-danger">*</span>
                                    </label>
                                    <select id="warehouse"
                                            class="selectpicker w-100"
                                            title="Select Warehouse"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white"
                                            wire:model="warehouse_id">
                                        @foreach($warehouses as $warehouse)
                                            <option value="{{ $warehouse['id'] }}"
                                                @selected($warehouse_id == $warehouse['id'])>
                                                {{ $warehouse['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('warehouse_id')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items card --}}
                <div class="card mt-2">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Item Request Inputs</h6>
                        <button type="button" class="btn btn-success btn-sm" wire:click="addRow">
                            <i class="ti ti-plus me-1"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        @if(count($items) > 0)
                            <div class="row g-3">
                                @foreach($items as $index => $item)
                                    <div class="col-12" wire:key="item-request-input-{{ $index }}">
                                        <div class="border rounded p-3">

                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <label class="form-label mb-0">
                                                    Item Request Input #{{ $index + 1 }}
                                                </label>
                                                <button type="button"
                                                        class="btn btn-danger btn-sm"
                                                        wire:click="removeRow({{ $index }})">
                                                    <i class="ti ti-trash me-1"></i> Remove
                                                </button>
                                            </div>

                                            <div class="row g-3 align-items-end">

                                                {{-- Item --}}
                                                <div class="col-12 col-md-5">
                                                    <label class="form-label" for="item_{{ $index }}">
                                                        Item <span class="text-danger">*</span>
                                                    </label>
                                                    <div wire:ignore>
                                                        <select wire:model="items.{{ $index }}.item_id"
                                                                id="item_{{ $index }}"
                                                                class="selectpicker w-100"
                                                                title="Select Item"
                                                                data-style="btn-default"
                                                                data-live-search="true"
                                                                data-icon-base="ti"
                                                                data-size="5"
                                                                data-tick-icon="ti-check text-white"
                                                                data-index="{{ $index }}">
                                                            @foreach($materials as $material)
                                                                <option value="{{ $material['id'] }}"
                                                                    @selected($item['item_id'] == $material['id'])>
                                                                    {{ $material['name'] }}
                                                                    ({{ $material['code'] ?? '' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @error('items.' . $index . '.item_id')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Quantity --}}
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
                                                    @error('items.' . $index . '.quantity')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Unit — NO wire:ignore so Livewire can update options --}}
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label" for="item_unit_{{ $index }}">
                                                        Unit <span class="text-danger">*</span>
                                                    </label>
                                                    <select wire:model="items.{{ $index }}.item_unit_id"
                                                            id="item_unit_{{ $index }}"
                                                            class="selectpicker w-100 unit-selectpicker"
                                                            title="Select Unit"
                                                            data-style="btn-default"
                                                            data-live-search="true"
                                                            data-icon-base="ti"
                                                            data-size="5"
                                                            data-tick-icon="ti-check text-white"
                                                            data-index="{{ $index }}">
                                                        @foreach($rowUnits[$index] ?? [] as $unit)
                                                            <option value="{{ $unit['id'] }}"
                                                                @selected($item['item_unit_id'] == $unit['id'])>
                                                                {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('items.' . $index . '.item_unit_id')
                                                        <div class="text-danger small">{{ $message }}</div>
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

                {{-- Notes card --}}
                <div class="card mt-2">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control"
                                          id="notes"
                                          wire:model="notes"
                                          placeholder="Enter any notes..."
                                          rows="3"></textarea>
                                @error('notes')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-end mt-2 mb-2">
                    <button type="button" class="btn btn-primary me-sm-3 me-1" wire:click="save">
                        Submit
                    </button>
                </div>

            </div>
        </div>
    </form>

    @script
    <script>
        // Initial boot
        $('.selectpicker').selectpicker();
        triggerCleave();

        // When a new row is added to the DOM
        Livewire.hook('morph.added', ({ el }) => {
            $(el).find('.selectpicker').selectpicker();
            triggerCleave();
        });

        // After every Livewire round-trip, refresh unit selectpickers
        // (item selectpickers have wire:ignore so they don't need this)
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                $nextTick(() => {
                    $('.unit-selectpicker').each(function () {
                        $(this).selectpicker('destroy').selectpicker();
                    });
                });
            });
        });

        // Sync all selectpicker changes back to Livewire
        $(document).on('change', '.selectpicker', function () {
            const model = $(this).attr('wire:model');
            if (model) {
                $wire.set(model, $(this).val());
            }
        });

        // Quantity cleave input → dispatch to Livewire
        $(document).on('input', '.quantity-input', function () {
            const index    = $(this).data('index');
            const quantity = parseFloat($(this).val()) || 0;
            if (index !== undefined) {
                $wire.dispatch('updateQuantity', {
                    index:    parseInt(index),
                    quantity: quantity,
                });
            }
        });
    </script>
    @endscript
</div>