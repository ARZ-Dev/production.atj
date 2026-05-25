<div>
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
                                        @foreach($warehouses as $warehouse)
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
                                        @foreach($warehouses as $warehouse)
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
                        <h6 class="mb-0">Transfer Raw Materials</h6>
                        @if(!$confirmStatus)
                            <button type="button" class="btn btn-success btn-sm" wire:click="addRow">
                                <i class="ti ti-plus me-1"></i> Add Raw Material
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        @if(count($rawMaterials) > 0)
                            <div class="row g-3">
                                @foreach($rawMaterials as $index => $row)
                                    <div class="col-12" wire:key="transfer-row-{{ $index }}">
                                        <div class="border rounded p-3">

                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <label class="form-label mb-0">Raw Material #{{ $index + 1 }}</label>
                                                @if(!$confirmStatus && count($rawMaterials) > 1)
                                                    <button type="button"
                                                            class="btn btn-danger btn-sm"
                                                            wire:click="removeItem({{ $index }})">
                                                        <i class="ti ti-trash me-1"></i> Remove
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="row g-3 align-items-end">

                                                {{-- Item --}}
                                                <div class="col-12 {{ $confirmStatus ? 'col-md-3' : 'col-md-4' }}">
                                                    <label class="form-label" for="item_{{ $index }}">
                                                        Item <span class="text-danger">*</span>
                                                    </label>
                                                    <div wire:ignore>
                                                        <select wire:model="rawMaterials.{{ $index }}.item_id"
                                                                id="item_{{ $index }}"
                                                                class="selectpicker w-100"
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
                                                    @error('rawMaterials.' . $index . '.item_id')
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Unit — no wire:ignore so Livewire can update options --}}
                                                <div class="col-12 {{ $confirmStatus ? 'col-md-3' : 'col-md-4' }}">
                                                    <label class="form-label" for="unit_{{ $index }}">
                                                        Unit <span class="text-danger">*</span>
                                                    </label>
                                                    <select wire:model="rawMaterials.{{ $index }}.item_unit_id"
                                                            id="unit_{{ $index }}"
                                                            class="selectpicker w-100 unit-selectpicker"
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
                                                    @error('rawMaterials.' . $index . '.item_unit_id')
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Loaded Quantity --}}
                                                <div class="col-12 {{ $confirmStatus ? 'col-md-3' : 'col-md-4' }}">
                                                    <label class="form-label" for="quantity_{{ $index }}">
                                                        Loaded Quantity <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text"
                                                           wire:model.live="rawMaterials.{{ $index }}.quantity"
                                                           id="quantity_{{ $index }}"
                                                           class="form-control cleave-input"
                                                           placeholder="Enter Quantity"
                                                           {{ $confirmStatus == 2 ? 'readonly' : '' }}>
                                                    @error('rawMaterials.' . $index . '.quantity')
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Received Quantity (approve receive only) --}}
                                                @if($confirmStatus == 2)
                                                    <div class="col-12 col-md-3">
                                                        <label class="form-label" for="received_quantity_{{ $index }}">
                                                            Received Quantity <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text"
                                                               wire:model.live="rawMaterials.{{ $index }}.received_quantity"
                                                               id="received_quantity_{{ $index }}"
                                                               class="form-control cleave-input"
                                                               placeholder="Enter Received Quantity">
                                                        @error('rawMaterials.' . $index . '.received_quantity')
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">No items added yet. Click "Add Raw Material" to start.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Submit Buttons --}}
                <div class="col-12 text-end mt-2 mb-2">
                    @if($confirmStatus == 1)
                        <button type="button" class="btn btn-primary" wire:click="confirmLoad">
                            <i class="ti ti-check me-1"></i> Approve Load
                        </button>
                    @elseif($confirmStatus == 2)
                        <button type="button" class="btn btn-primary" wire:click="confirmReceive">
                            <i class="ti ti-check me-1"></i> Approve Receive
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="submit">
                            <i class="ti ti-check me-1"></i> Submit
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

        // After every Livewire round-trip, refresh unit selectpickers
        // (item selects have wire:ignore so they're unaffected)
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
    </script>
    @endscript
</div>