<div>
    @php
        // Column layout differs per mode:
        //   create/edit      → item · unit · loaded qty · remove
        //   approve load     → item · unit · loaded qty
        //   approve receive  → item · unit · loaded qty · received qty
        $irCols = $confirmStatus == 2 ? 'ir-cols-txrecv' : ($confirmStatus == 1 ? 'ir-cols-txload' : 'ir-cols-basic');
    @endphp
    <form>
        <div class="row">
            <div class="col-12">

                {{-- Header Card --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            @if($confirmStatus == 1)
                                Approve Transfer Load
                            @elseif($confirmStatus == 2)
                                Approve Transfer Receipt
                            @else
                                {{ $editing ? 'Edit' : 'Add' }} Transfer
                            @endif
                        </h6>
                        <a href="{{ route('item-transfers') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">

                            {{-- Department --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="department_id">
                                    Department <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select id="department_id"
                                            class="selectpicker w-100"
                                            title="Select Department"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white"
                                            wire:model="department_id"
                                            {{ $confirmStatus ? 'disabled' : '' }}>
                                        @foreach($departments as $department)
                                            <option value="{{ $department['id'] }}"
                                                @selected($department['id'] == $department_id)>
                                                {{ $department['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('department_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Internal transfer --}}
                            <div class="col-12 col-md-6 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_internal"
                                           wire:model.live="is_internal"
                                           {{ $confirmStatus ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="is_internal">
                                        Internal Transfer
                                        <span class="text-muted small d-block">
                                            Limits Warehouse From to internal warehouses. Warehouse To is
                                            unaffected.
                                        </span>
                                    </label>
                                </div>
                            </div>

                            {{-- Warehouse From --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="warehouse_from_id">
                                    Warehouse From <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select id="warehouse_from_id"
                                            class="selectpicker w-100"
                                            title="Select Warehouse"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white"
                                            wire:model="warehouse_from_id"
                                            {{ $confirmStatus ? 'disabled' : '' }}>
                                        @foreach($warehousesFrom as $warehouse)
                                            <option value="{{ $warehouse['id'] }}"
                                                @selected($warehouse['id'] == $warehouse_from_id)>
                                                {{ $warehouse['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('warehouse_from_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Warehouse To --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="warehouse_to_id">
                                    Warehouse To <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select id="warehouse_to_id"
                                            class="selectpicker w-100"
                                            title="Select Warehouse"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white"
                                            wire:model="warehouse_to_id"
                                            {{ $confirmStatus ? 'disabled' : '' }}>
                                        @foreach($warehousesTo as $warehouse)
                                            <option value="{{ $warehouse['id'] }}"
                                                @selected($warehouse['id'] == $warehouse_to_id)>
                                                {{ $warehouse['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('warehouse_to_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Items Card --}}
                <div class="card mt-2">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0">Transfer Items</h6>
                            <span class="ir-count">{{ count($transferItems) }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="ir-editor">

                            {{-- Column header (desktop) --}}
                            <div class="ir-head {{ $irCols }}">
                                <div class="ir-idx-h">#</div>
                                <div>Item</div>
                                <div>Unit</div>
                                <div>Loaded Qty</div>
                                @if($confirmStatus == 2)
                                    <div>Received Qty</div>
                                @endif
                                @if(!$confirmStatus)
                                    <div></div>
                                @endif
                            </div>

                            @forelse($transferItems as $index => $row)
                                <div class="ir-row {{ $irCols }}" wire:key="transfer-row-{{ $index }}">

                                    {{-- Index --}}
                                    <div class="ir-idx-cell">
                                        <span class="ir-idx">{{ $index + 1 }}</span>
                                    </div>

                                    {{-- Item --}}
                                    <div class="ir-cell">
                                        <span class="ir-cell-label">Item</span>
                                        <div wire:ignore>
                                            <select wire:model="transferItems.{{ $index }}.item_id"
                                                    id="item_{{ $index }}"
                                                    class="selectpicker w-100 item-select"
                                                    title="Select Item"
                                                    data-style="btn-default"
                                                    data-live-search="true"
                                                    data-icon-base="ti"
                                                    data-size="5"
                                                    data-tick-icon="ti-check text-white"
                                                    data-index="{{ $index }}"
                                                    {{ $confirmStatus ? 'disabled' : '' }}>
                                                @foreach($items as $item)
                                                    <option value="{{ $item['id'] }}"
                                                        @selected($row['item_id'] == $item['id'])>
                                                        {{ $item['name'] }}
                                                        ({{ $item['code'] ?? '' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('transferItems.' . $index . '.item_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Unit — no wire:ignore so Livewire can update options --}}
                                    <div class="ir-cell">
                                        <span class="ir-cell-label">Unit</span>
                                        <div wire:ignore>
                                            <select wire:model="transferItems.{{ $index }}.item_unit_id"
                                                    id="unit_{{ $index }}"
                                                    class="selectpicker w-100 unit-select"
                                                    title="Select Unit"
                                                    data-style="btn-default"
                                                    data-live-search="true"
                                                    data-icon-base="ti"
                                                    data-size="5"
                                                    data-tick-icon="ti-check text-white"
                                                    data-index="{{ $index }}"
                                                    {{ $confirmStatus ? 'disabled' : '' }}>
                                                @foreach($rowUnits[$index] ?? [] as $unit)
                                                    <option value="{{ $unit['id'] }}"
                                                        @selected($row['item_unit_id'] == $unit['id'])>
                                                        {{ $unit['name'] }}
                                                        ({{ $unit['symbol'] ?? '' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('transferItems.' . $index . '.item_unit_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Loaded Quantity --}}
                                    <div class="ir-cell">
                                        <span class="ir-cell-label">Loaded Qty</span>
                                        <input type="text"
                                               wire:model.live="transferItems.{{ $index }}.quantity"
                                               id="quantity_{{ $index }}"
                                               class="form-control cleave-input"
                                               placeholder="Enter Quantity"
                                               {{ $confirmStatus == 2 ? 'readonly' : '' }}>
                                        @error('transferItems.' . $index . '.quantity')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Received Quantity (approve receive only) --}}
                                    @if($confirmStatus == 2)
                                        <div class="ir-cell">
                                            <span class="ir-cell-label">Received Qty</span>
                                            <input type="text"
                                                   wire:model.live="transferItems.{{ $index }}.received_quantity"
                                                   id="received_quantity_{{ $index }}"
                                                   class="form-control cleave-input"
                                                   placeholder="Enter Received Quantity">
                                            @error('transferItems.' . $index . '.received_quantity')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endif

                                    {{-- Remove (create / edit only) --}}
                                    @if(!$confirmStatus)
                                        <div class="ir-remove-cell">
                                            <button type="button"
                                                    class="ir-remove"
                                                    title="Remove item"
                                                    wire:click="removeItem({{ $index }})"
                                                    @disabled(count($transferItems) <= 1)>
                                                <i class="bi bi-trash"></i><span class="ir-remove-text">Remove</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="ir-empty">No items added yet.</div>
                            @endforelse

                            {{-- Add (bottom, create / edit only) --}}
                            @if(!$confirmStatus)
                                <button type="button" class="ir-add" wire:click="addRow">
                                    <i class="bi bi-plus-lg"></i> Add Transfer Item
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Submit Buttons --}}
                <div class="col-12 text-end mt-2 mb-2">
                    @if($confirmStatus == 1)
                        <button type="button" class="btn btn-primary" wire:click="confirmLoad">
                            <i class="bi bi-check-lg me-1"></i> Approve Load
                        </button>
                    @elseif($confirmStatus == 2)
                        <button type="button" class="btn btn-primary" wire:click="confirmReceive">
                            <i class="bi bi-check-lg me-1"></i> Approve Receive
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="submit">
                            <i class="bi bi-check-lg me-1"></i> Submit
                        </button>
                    @endif
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

        // Fetch items common to both warehouses whenever either warehouse changes
        function dispatchTransferItems() {
            const fromId = $('#warehouse_from_id').val();
            const toId   = $('#warehouse_to_id').val();

            if (!fromId || !toId) {
                return;
            }

            $wire.dispatch('getTransferItems', {
                warehouseFromId: fromId,
                warehouseToId: toId
            });
        }

        $(document).on('change', '#warehouse_from_id, #warehouse_to_id', function () {
            dispatchTransferItems();
        });

        // Department / internal toggle changed server-side — refill both warehouse pickers
        $wire.on('setTransferWarehouses', function (params) {
            const fromList = params[0] ?? [];
            const toList   = params[1] ?? [];
            const fromId   = params[2] ?? null;
            const toId     = params[3] ?? null;

            setOptions($('#warehouse_from_id'), fromList);
            setOptions($('#warehouse_to_id'), toList);

            $('#warehouse_from_id').selectpicker('val', fromId ? String(fromId) : '');
            $('#warehouse_to_id').selectpicker('val', toId ? String(toId) : '');
        });

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
