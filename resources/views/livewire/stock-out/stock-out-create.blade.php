<div>
    <form wire:submit.prevent="submit">
        <div class="row">
            <div class="col-xl">

                {{-- Main Info Card --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $editing ? 'Edit' : 'Add' }} Stock Out</h5>
                        <a href="{{ route('item-stock-outs') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            {{-- Warehouse --}}
                            <div class="col-lg-6 col-sm-12">
                                <div wire:ignore>
                                    <label for="warehouse" class="form-label">
                                        Warehouse <span class="text-danger">*</span>
                                    </label>
                                    <select id="warehouse_id"
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
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div class="col-12">
                                <label class="form-label" for="notes">Notes</label>
                                <textarea class="form-control"
                                          id="notes"
                                          wire:model="notes"
                                          rows="3"
                                          placeholder="Stock Out Notes"></textarea>
                                @error('notes')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Items Card --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0">Stock Out Items</h5>
                            <span class="ir-count">{{ count($stockOutItems) }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="ir-editor">

                            {{-- Column header (desktop) --}}
                            <div class="ir-head ir-cols-basic">
                                <div class="ir-idx-h">#</div>
                                <div>Item</div>
                                <div>Unit</div>
                                <div>Quantity</div>
                                <div></div>
                            </div>

                            @forelse($stockOutItems as $index => $row)
                                <div class="ir-row ir-cols-basic" wire:key="stock-out-row-{{ $index }}">

                                    {{-- Index --}}
                                    <div class="ir-idx-cell">
                                        <span class="ir-idx">{{ $index + 1 }}</span>
                                    </div>

                                    {{-- Item --}}
                                    <div class="ir-cell">
                                        <span class="ir-cell-label">Item</span>
                                        <div wire:ignore>
                                            <select wire:model="stockOutItems.{{ $index }}.item_id"
                                                    id="item_{{ $index }}"
                                                    class="selectpicker w-100 item-select"
                                                    title="Select Item"
                                                    data-style="btn-default"
                                                    data-live-search="true"
                                                    data-icon-base="ti"
                                                    data-size="5"
                                                    data-tick-icon="ti-check text-white"
                                                    data-index="{{ $index }}">
                                                @foreach($items as $item)
                                                    <option value="{{ $item['id'] }}"
                                                        @selected($row['item_id'] == $item['id'])>
                                                        {{ $item['name'] }}
                                                        ({{ $item['code'] ?? '' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('stockOutItems.' . $index . '.item_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Unit — no wire:ignore so Livewire can update options --}}
                                    <div class="ir-cell">
                                        <span class="ir-cell-label">Unit</span>
                                        <div wire:ignore>
                                            <select wire:model="stockOutItems.{{ $index }}.item_unit_id"
                                                    id="unit_{{ $index }}"
                                                    class="selectpicker w-100 unit-select"
                                                    title="Select Unit"
                                                    data-style="btn-default"
                                                    data-live-search="true"
                                                    data-icon-base="ti"
                                                    data-size="5"
                                                    data-tick-icon="ti-check text-white"
                                                    data-index="{{ $index }}">
                                                @foreach($rowUnits[$index] ?? [] as $unit)
                                                    <option value="{{ $unit['id'] }}"
                                                        @selected($row['item_unit_id'] == $unit['id'])>
                                                        {{ $unit['name'] }}
                                                        ({{ $unit['symbol'] ?? '' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('stockOutItems.' . $index . '.item_unit_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Quantity --}}
                                    <div class="ir-cell">
                                        <span class="ir-cell-label">Quantity</span>
                                        <input type="text"
                                               wire:model="stockOutItems.{{ $index }}.quantity"
                                               id="quantity_{{ $index }}"
                                               class="form-control cleave-input"
                                               placeholder="Enter Quantity">
                                        @error('stockOutItems.' . $index . '.quantity')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Remove --}}
                                    <div class="ir-remove-cell">
                                        <button type="button"
                                                class="ir-remove"
                                                title="Remove item"
                                                wire:click="removeItem({{ $index }})"
                                                @disabled(count($stockOutItems) <= 1)>
                                            <i class="bi bi-trash"></i><span class="ir-remove-text">Remove</span>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="ir-empty">No items added yet.</div>
                            @endforelse

                            {{-- Add (bottom) --}}
                            <button type="button" class="ir-add" wire:click="addRow">
                                <i class="bi bi-plus-lg"></i> Add Stock Out Item
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="col-12 text-end mb-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Submit
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

        // New row added to DOM
        Livewire.hook('morph.added', ({ el }) => {
            $(el).find('.selectpicker').selectpicker();
            triggerCleave();
        });

        // Sync all selectpicker changes back to Livewire
        $(document).on('change', '.selectpicker', function () {
            const model = $(this).attr('wire:model');
            if (model) {
                $wire.set(model, $(this).val());
            }
        });

        $(document).on('change', '#warehouse_id', function () {
            if ($(this).val() === '') {
                return;
            }

            $wire.dispatch('getWarehouseItems', {
                warehouseId: $(this).val()
            });
        })

        $wire.on('setWarehouseItems', function (params) {
            let items = params[0];

            setOptions($('.item-select'), items);
        });

        $(document).on('change', '.item-select', function () {
            const index = $(this).data('index');
            const itemId = $(this).val();

            if (itemId === '') {
                return;
            }

            $wire.dispatch('getItemUnits', {
                itemId: itemId,
                index: index
            });
        });

        $wire.on('setItemUnits', function (params) {
            let index = params[0];
            let units = params[1];
            let autoUnitId = params[2] ?? null;

            setOptions($('#unit_' + index), units);

            // If the item has exactly one unit, select it automatically
            if (autoUnitId) {
                $('#unit_' + index).selectpicker('val', String(autoUnitId));
            }
        });
    </script>
    @endscript
</div>
