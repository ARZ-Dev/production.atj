<div>
    <form wire:submit.prevent="submit">
        <div class="row">
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $editing ? 'Edit' : 'Add' }} StockIn</h5>
                        <a href="{{ route('stock-ins') }}" class="btn btn-light-light text-muted"><i
                                class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                           

                            <div class="col-12">
                                 <div wire:ignore>
                                    <label for="warehouse" class="form-label">Warehouse <span
                                            class="text-danger">*</span></label>
                                    <select id="warehouse" class="selectpicker w-100" title="Select Warehouse"
                                        data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                        data-size="5" data-tick-icon="ti-check text-white" wire:model="warehouse_id">
                                        @foreach($warehouses as $warehouse) <option value="{{ $warehouse['id'] }}"
                                            @selected($warehouse_id==$warehouse['id'])>
                                            {{ $warehouse['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('warehouse_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="notes">Notes</label>
                                <textarea cols="15" rows="5" class="form-control" id="notes" name="notes"
                                    wire:model="notes" placeholder="StockIn Notes"></textarea>
                                @error('notes') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Raw Materials Section --}}
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
                                        <button type="button" class="btn btn-danger btn-sm"
                                            wire:click="removeItem({{ $index }})">
                                            <i class="ti ti-trash me-1"></i> Remove
                                        </button>
                                        @endif

                                    </div>

                                    <div class="row g-3">
                                        {{-- Raw Material Name --}}
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="raw_material_id_{{ $index }}">
                                                Raw Material <span class="text-danger">*</span>
                                            </label>
                                            <div wire:ignore>
                                                <select wire:model="rawMaterials.{{ $index }}.raw_material_id"
                                                    id="raw_material_id_{{ $index }}" class="selectpicker raw-material-select w-100"
                                                    title="Select Raw Material" data-style="btn-default" data-live-search="true"
                                                    data-icon-base="ti" data-size="5"
                                                    data-tick-icon="ti-check text-white" data-index="{{ $index }}">
                                                    @foreach($availableRawMaterials as $availableRawMaterial)
                                                    <option value="{{ $availableRawMaterial->id }}" @selected($availableRawMaterial->
                                                        id == ($rawMaterials[$index]['raw_material_id'] ?? ''))>
                                                        {{ $availableRawMaterial->name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('rawMaterials.' . $index . '.raw_material_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Raw Material Unit Name --}}
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="unit_{{ $index }}_id">
                                                Raw Material Unit <span class="text-danger">*</span>
                                            </label>
                                            <div wire:ignore>
                                                <select wire:model="rawMaterials.' . $index . '.unit_id"
                                                    id="unit_{{ $index }}_id" class="selectpicker unit-select w-100"
                                                    title="Select Raw Material Unit" data-style="btn-default"
                                                    data-live-search="true" data-icon-base="ti" data-size="5"
                                                    data-tick-icon="ti-check text-white" data-index="{{ $index }}">
                                                    @foreach($units as $unit)
                                                    <option value="{{ $unit->id }}" @selected($unit->id ==
                                                        ($rawMaterials[$index]['unit_id'] ?? ''))>
                                                        {{ $unit->name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('rawMaterials.' . $index . '.unit_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Quantity Selection --}}
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="quantity_{{ $index }}">
                                                Quantity <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" wire:model="rawMaterials.' . $index . '.quantity"
                                                id="quantity_{{ $index }}" class="form-control cleave-input"
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
                            <p class="text-muted mb-0">No items added yet. Click "Add Item" to start.</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Documents Upload Section -->
                {{-- <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">Documents Upload - Multiple Files Allowed</h6>
                        @error('documents')
                        <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="card-body">
                        <x-filepond :images="$documents" wire:model="documents" file-path="" is-multiple="true"
                            allow-remove="true" delete-event="deleteDocument" />
                    </div>
                </div> --}}


                {{-- Submit Button --}}
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </form>

    @script
    <script>
        $('.selectpicker').selectpicker();
        triggerCleave();

        $(document).on('change', '.selectpicker', function() {
            $wire.set($(this).attr('wire:model'), $(this).val());
        });

    
        Livewire.hook('morph.added',  ({ el }) => {
            $('.selectpicker').selectpicker()
            triggerCleave()
        })


    </script>
    @endscript
</div>