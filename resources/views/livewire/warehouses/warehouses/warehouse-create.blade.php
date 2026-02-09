<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Add Warehouse</h6>
                        <a href="{{ route('warehouses') }}" class="btn btn-light-light text-muted"><i
                                class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                             @if(auth()->user()->hasRole('Super Admin'))
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="company_id">Company</label>
                                <div wire:ignore>
                                    <select wire:model="company_id" id="company_id"
                                        class="selectpicker w-100" title="Select Company"
                                        data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                        data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($companies as $company)
                                        <option value="{{ $company->id }}" @selected($company->id == $company_id)>{{
                                            $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('company_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            @endif
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="warehouse_type_id">Warehouse Type</label>
                                <div wire:ignore>
                                    <select wire:model="warehouse_type_id" id="warehouse_type_id"
                                        class="selectpicker w-100" title="Select Warehouse Type"
                                        data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                        data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($warehouseTypes as $warehouseType)
                                        <option value="{{ $warehouseType->id }}" @selected($warehouseType->id == $warehouse_type_id)>{{
                                            $warehouseType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('warehouse_type_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" wire:model="name"
                                    placeholder="Enter content title">
                                @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" wire:model="address"
                                    placeholder="Enter warehouse address" rows="5" ></textarea>
                                @error('address')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 text-end mt-2">
                    <button type="button" class="btn btn-primary me-sm-3 me-1" wire:click="submit">Submit</button>
                </div>
            </div>
        </div>
    </form>

    @script
    <script>
       $('.selectpicker').selectpicker()

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
            setOptions($('#warehouse_type_id'), warehouseTypes)
        })
    </script>
    @endscript
</div>