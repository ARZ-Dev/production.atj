<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Production Line Configuration</h6>
                        <a href="{{ url()->previous() }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-success btn-sm mb-4" wire:click="addRow">
                            <i class="bi bi-plus-circle me-1"></i>Add Row
                        </button>
                        @foreach($rows as $index => $row)
                        <div class="card mb-3 border">
                            <div class="card-body">
                                <!-- First Row: Radio Buttons and Delete Button -->
                                <div class="row align-items-center mb-3">
                                    <div class="col-md-11">
                                        <label class="form-label">Type <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="type_{{ $index }}"
                                                    id="warehouse_{{ $index }}" value="warehouse"
                                                    wire:model.live="rows.{{ $index }}.type">
                                                <label class="form-check-label" for="warehouse_{{ $index }}">
                                                    Warehouse
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="type_{{ $index }}"
                                                    id="machine_{{ $index }}" value="machine"
                                                    wire:model.live="rows.{{ $index }}.type">
                                                <label class="form-check-label" for="machine_{{ $index }}">
                                                    Machine
                                                </label>
                                            </div>
                                        </div>
                                        @error("rows.$index.type")<div class="text-danger small mt-1">{{ $message }}
                                        </div>@enderror
                                    </div>

                                    <!-- Delete Button -->
                                    <div class="col-md-1 text-end">
                                        @if(count($rows) > 1)
                                        <button type="button" class="btn btn-danger btn-sm"
                                            wire:click="removeRow({{ $index }})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Second Row: Conditional Fields Based on Selection -->
                                @if(isset($row['type']))
                                <div class="row">
                                    @if($row['type'] === 'warehouse')
                                    <!-- Warehouse Type Dropdown -->
                                    <div class="col-md-6">
                                        <label for="warehouse_type_{{ $index }}" class="form-label">
                                            Warehouse Type <span class="text-danger">*</span>
                                        </label>
                                        <select class="selectpicker w-100" title="Select Warehouse Type"
                                            data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                            data-size="5" data-tick-icon="ti-check text-white"
                                            id="warehouse_type_{{ $index }}"
                                            wire:model="rows.{{ $index }}.warehouse_type">
                                            @foreach($warehouseTypes as $type)
                                            <option value="{{ $type->id }}" @selected($row['warehouse_type'] == $type->id)>{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("rows.$index.warehouse_type")<div class="text-danger small mt-1">{{
                                            $message }}</div>@enderror
                                    </div>

                                    <!-- Warehouse Name -->
                                    <div class="col-md-6">
                                        <label for="warehouse_name_{{ $index }}" class="form-label">
                                            Warehouse Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="warehouse_name_{{ $index }}"
                                            wire:model="rows.{{ $index }}.name" placeholder="Enter warehouse name">
                                        @error("rows.$index.name")<div class="text-danger small mt-1">{{ $message }}
                                        </div>@enderror
                                    </div>

                                    @elseif($row['type'] === 'machine')
                                    <!-- Machine Type Dropdown -->
                                    <div class="col-md-6">
                                        <label for="machine_type_{{ $index }}" class="form-label">
                                            Machine Type <span class="text-danger">*</span>
                                        </label>
                                        <select class="selectpicker w-100" title="Select Machine Type"
                                            data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                            data-size="5" data-tick-icon="ti-check text-white"
                                            id="machine_type_{{ $index }}" wire:model="rows.{{ $index }}.machine_type">
                                            @foreach($machineTypes as $type)
                                            <option value="{{ $type->id }}" @selected($row['machine_type'] == $type->id)>{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("rows.$index.machine_type")<div class="text-danger small mt-1">{{
                                            $message }}</div>@enderror
                                    </div>

                                    <!-- Machine Name -->
                                    <div class="col-md-6">
                                        <label for="machine_name_{{ $index }}" class="form-label">
                                            Machine Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="machine_name_{{ $index }}"
                                            wire:model="rows.{{ $index }}.name" placeholder="Enter machine name">
                                        @error("rows.$index.name")<div class="text-danger small mt-1">{{ $message }}
                                        </div>@enderror
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-12 text-end mt-3">
                    <button type="button" class="btn btn-primary me-2" wire:click="submit">
                        {{ $editing ? 'Update' : 'Submit' }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    @script
    <script>
        // Initialize selectpicker on page load
        $('.selectpicker').selectpicker();
        
        // Reinitialize selectpicker when Livewire adds new elements
        Livewire.hook('morph.added', ({ el }) => {
            $('.selectpicker').selectpicker();
        });

    </script>
    @endscript
</div>