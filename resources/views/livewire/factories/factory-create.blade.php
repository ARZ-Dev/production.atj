<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ $editing ? 'Edit Factory' : 'Add Factory' }}</h6>
                        <a href="{{ route('factories') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            @if(auth()->user()->hasRole('Super Admin'))
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="company_id">Company</label>
                                <div wire:ignore>
                                    <select wire:model="company_id" id="company_id" class="selectpicker w-100"
                                        title="Select Company" data-style="btn-default" data-live-search="true"
                                        data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($companies as $company)
                                        <option value="{{ $company->id }}" @selected($company->id == $company_id)>{{
                                            $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            @endif
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" wire:model="name"
                                    placeholder="Enter content title">
                                @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" wire:model="address"
                                    placeholder="Enter Address address" rows="5"></textarea>
                                @error('address')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Warehouses</h6>
                        <button type="button" class="btn btn-success btn-sm" wire:click="addWarehouseRow">
                            <i class="bi bi-plus-circle me-1"></i>Add Warehouse
                        </button>
                    </div>
                    <div class="card-body">
                        @foreach($warehouses as $index => $warehouse)
                        <div class="row g-3 mb-3 align-items-end warehouse-row" wire:key="warehouse-{{ $index }}">
                            <div class="col-md-5">
                                <label class="form-label" for="warehouse_type_id_{{ $index }}">
                                    Warehouse Type <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select wire:model="warehouses.{{ $index }}.warehouse_type_id"
                                        id="warehouse_type_id_{{ $index }}"
                                        class="selectpicker w-100 warehouse-type-select" title="Select Warehouse Type"
                                        data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                        data-size="5" data-tick-icon="ti-check text-white" data-index="{{ $index }}">
                                        @foreach($warehouseTypes as $type)
                                        <option value="{{ $type['id'] }}" @selected($type['id'] == $warehouses[$index]['warehouse_type_id'])>{{ $type['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('warehouses.' . $index . '.warehouse_type_id')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-5">
                                <label for="warehouse_name_{{ $index }}" class="form-label">
                                    Warehouse Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="warehouse_name_{{ $index }}"
                                    wire:model="warehouses.{{ $index }}.warehouse_name"
                                    placeholder="Enter warehouse name">
                                @error('warehouses.' . $index . '.warehouse_name')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                @if(count($warehouses) > 1)
                                <button type="button" class="btn btn-danger btn-sm"
                                    wire:click="removeWarehouseRow({{ $index }})">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        @if(empty($warehouses))
                        <div class="text-center text-muted py-4">
                            <p>No warehouses added yet. Click "Add Warehouse" to add one.</p>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="col-12 text-end mt-2">
                    <button type="button" class="btn btn-primary me-sm-3 me-1" wire:click="submit">{{ $editing ? 'Update' : 'Submit' }}</button>
                </div>
            </div>
        </div>
    </form>

    @script
    <script>
        $('.selectpicker').selectpicker()
        Livewire.hook('morph.added', ({ el }) => {
            $('.selectpicker').selectpicker();
        });


        $(document).on('change', '.selectpicker', function() {
            $wire.set($(this).attr('wire:model'), $(this).val())
        })


       $(document).on('change', '#company_id', function() {
           $wire.dispatch('getWarehouseTypes', {
               companyId: $(this).val()
           })
       })

       $wire.on('setWarehouseTypes', function (params) {
            let warehouseTypes = params[0];
            setOptions($('.warehouse-type-select'), warehouseTypes)
        })
    </script>
    @endscript
</div>