<div>
    <form wire:submit.prevent="submit">
        <div class="row">
            <div class="col-xl">

                {{-- Main Info Card --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $editing ? 'Edit' : 'Add' }} Waste</h5>
                        <a href="{{ route('raw-material-wastes') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            {{-- Warehouse --}}
                            <div class="col-12">
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
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div class="col-12">
                                <label class="form-label" for="notes">Notes</label>
                                <textarea cols="15" rows="5"
                                    class="form-control"
                                    id="notes"
                                    wire:model="notes"
                                    placeholder="Waste Notes"></textarea>
                                @error('notes')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Raw Materials Card --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Raw Materials</h5>
                        <button type="button" class="btn btn-success btn-sm" wire:click="addRow">
                            <i class="ti ti-plus me-1"></i> Add Raw Material
                        </button>
                    </div>
                    <div class="card-body">
                        @if(count($rawMaterials) > 0)
                            <div class="row g-3">
                                @foreach($rawMaterials as $index => $item)
                                    <div class="col-12" wire:key="item-{{ $index }}">
                                        <div class="border rounded p-3">

                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <label class="form-label mb-0">
                                                    Raw Material #{{ $index + 1 }}
                                                </label>
                                                @if(count($rawMaterials) > 1)
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm"
                                                        wire:click="removeItem({{ $index }})">
                                                        <i class="ti ti-trash me-1"></i> Remove
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="row g-3">

                                                {{-- Raw Material Select --}}
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label" for="raw_material_id_{{ $index }}">
                                                        Raw Material <span class="text-danger">*</span>
                                                    </label>
                                                    <div wire:ignore>
                                                        <select
                                                            wire:model="rawMaterials.{{ $index }}.raw_material_id"
                                                            id="raw_material_id_{{ $index }}"
                                                            class="selectpicker raw-material-select w-100"
                                                            title="Select Raw Material"
                                                            data-style="btn-default"
                                                            data-live-search="true"
                                                            data-icon-base="ti"
                                                            data-size="5"
                                                            data-tick-icon="ti-check text-white"
                                                            data-index="{{ $index }}">
                                                            @foreach($availableRawMaterials as $rm)
                                                                <option value="{{ $rm->id }}"
                                                                    @selected($rm->id == ($item['raw_material_id'] ?? ''))>
                                                                    {{ $rm->name }}{{ $rm->code ? ' (' . $rm->code . ')' : '' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @error('rawMaterials.' . $index . '.raw_material_id')
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Unit Select --}}
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label" for="unit_{{ $index }}_id">
                                                        Raw Material Unit <span class="text-danger">*</span>
                                                    </label>
                                                    <div wire:ignore>
                                                        <select
                                                            wire:model="rawMaterials.{{ $index }}.unit_id"
                                                            id="unit_{{ $index }}_id"
                                                            class="selectpicker unit-select w-100"
                                                            title="Select Unit"
                                                            data-style="btn-default"
                                                            data-live-search="true"
                                                            data-icon-base="ti"
                                                            data-size="5"
                                                            data-tick-icon="ti-check text-white"
                                                            data-index="{{ $index }}">
                                                            {{-- Pre-render saved units on edit --}}
                                                            @foreach($item['units'] ?? [] as $unit)
                                                                <option value="{{ $unit['id'] }}"
                                                                    @selected(($item['unit_id'] ?? '') == $unit['id'])>
                                                                    {{ $unit['name'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @error('rawMaterials.' . $index . '.unit_id')
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                {{-- Quantity --}}
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label" for="quantity_{{ $index }}">
                                                        Quantity <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text"
                                                        wire:model="rawMaterials.{{ $index }}.quantity"
                                                        id="quantity_{{ $index }}"
                                                        class="form-control cleave-input"
                                                        placeholder="Enter Quantity">
                                                    @error('rawMaterials.' . $index . '.quantity')
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

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

                {{-- Submit --}}
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i> Submit
                    </button>
                </div>

            </div>
        </div>
    </form>

    @script
    <script>
        // Init on load
        $('.selectpicker').selectpicker();
        triggerCleave();

        // Sync selectpicker changes back to Livewire
        $(document).on('change', '.selectpicker', function () {
            $wire.set($(this).attr('wire:model'), $(this).val());
        });

        // Init selectpicker and cleave on new rows added by Livewire
        Livewire.hook('morph.added', ({ el }) => {
            $('.selectpicker').selectpicker();
            triggerCleave();
        });

        // When raw material changes, fetch its units from server
        $(document).on('change', '.raw-material-select', function () {
            let index         = $(this).attr('data-index');
            let rawMaterialId = $(this).val();

            if (index !== undefined && rawMaterialId) {
                $wire.dispatch('getUnits', {
                    rawMaterialId: rawMaterialId,
                    index: index
                });
            }
        });

        // Receive units from server and populate the unit select
        $wire.on('setUnits', function (params) {
            let data       = params[0];
            let units      = data.units;
            let index      = data.index;
            let selectedId = data.selectedUnitId ?? null;

            let $select = $('#unit_' + index + '_id');

            setOptions($select, units);

            if (selectedId) {
                $select.val(selectedId);
            }

            $select.selectpicker('');
        });
    </script>
    @endscript
</div>