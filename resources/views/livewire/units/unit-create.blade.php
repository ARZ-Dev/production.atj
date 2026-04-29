<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ $id ? 'Edit Unit' : 'Add Unit' }}</h6>
                        <a href="{{ route('units') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">

                            {{-- Name --}}
                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" wire:model="name"
                                    placeholder="Enter unit name">
                                @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Symbol --}}
                            <div class="col-12 col-md-6">
                                <label for="symbol" class="form-label">Symbol <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="symbol" wire:model="symbol"
                                    placeholder="e.g. kg, m, pcs">
                                @error('symbol') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Type --}}
                            <div class="col-12 col-md-6">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <div wire:ignore>

                                    <select wire:model="type" id="type" class="selectpicker w-100" title="Select Type"
                                        data-style="btn-default" data-live-search="true">
                                        <option value="">Select Type</option>
                                        <option value="weight" @selected($type=='weight' )>Weight</option>
                                        <option value="volume" @selected($type=='volume' )>Volume</option>
                                        <option value="count" @selected($type=='count' )>Count</option>
                                    </select>
                                </div>
                                @error('type') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Base Unit --}}
                            <div class="col-12 col-md-6">
                                <label for="base_unit_id" class="form-label">Base Unit</label>
                                <div wire:ignore>
                                    <select wire:model="base_unit_id" id="base_unit_id" class="selectpicker w-100"
                                        title="Select Base Unit" data-style="btn-default" data-live-search="true"
                                        data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" @selected($unit->id == $base_unit_id)>
                                            {{ $unit->name }} ({{ $unit->symbol }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('base_unit_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Conversion Factor --}}
                            <div class="col-12 col-md-6">
                                <label for="conversion_factor_to_base" class="form-label">
                                    Conversion Factor to Base <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="any" class="form-control" id="conversion_factor_to_base"
                                    wire:model="conversion_factor_to_base" placeholder="e.g. 1000">
                                @error('conversion_factor_to_base') <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Is Base --}}
                            <div class="col-12 col-md-3 d-flex align-items-center gap-2 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_base" wire:model="is_base"
                                        @checked((bool) $is_base)>
                                    <label class="form-check-label" for="is_base">Is Base Unit</label>
                                </div>
                            </div>

                            {{-- Is Active --}}
                            <div class="col-12 col-md-3 d-flex align-items-center gap-2 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active"
                                        wire:model="is_active" @checked((bool) $is_active)>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-12 text-end mt-2">
                    <button type="button" class="btn btn-primary me-sm-3 me-1" wire:click="save">Submit</button>
                </div>
            </div>
        </div>
    </form>

    @script
    <script>
        $('.selectpicker').selectpicker()

        $(document).on('change', '.selectpicker', function () {
            $wire.set($(this).attr('wire:model'), $(this).val())
        })
    </script>
    @endscript
</div>