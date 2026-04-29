<div>
    <form>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ $id ? 'Edit Raw Material' : 'Add Raw Material' }}</h6>
                        <a href="{{ route('raw-materials') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">

                            {{-- Code --}}
                            <div class="col-12 col-md-6">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" wire:model="code"
                                    placeholder="Enter material code">
                                @error('code') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Name --}}
                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" wire:model="name"
                                    placeholder="Enter material name">
                                @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Type --}}
                            <div class="col-12 col-md-6">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <div wire:ignore>
                                    <select wire:model="type" id="type" class="selectpicker w-100"
                                        title="Select Type" data-style="btn-default" data-live-search="true">
                                        <option value="solid"     @selected($type == 'solid')>Solid</option>
                                        <option value="liquid"    @selected($type == 'liquid')>Liquid</option>
                                        <option value="countable" @selected($type == 'countable')>Countable</option>
                                        <option value="other"     @selected($type == 'other')>Other</option>
                                    </select>
                                </div>
                                @error('type') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Base Unit --}}
                            <div class="col-12 col-md-6">
                                <label for="base_unit_id" class="form-label">Base Unit <span class="text-danger">*</span></label>
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

                            {{-- Purchase Unit --}}
                            <div class="col-12 col-md-6">
                                <label for="purchase_unit_id" class="form-label">Purchase Unit</label>
                                <div wire:ignore>
                                    <select wire:model="purchase_unit_id" id="purchase_unit_id" class="selectpicker w-100"
                                        title="Select Purchase Unit" data-style="btn-default" data-live-search="true"
                                        data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" @selected($unit->id == $purchase_unit_id)>
                                            {{ $unit->name }} ({{ $unit->symbol }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('purchase_unit_id') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Density --}}
                            <div class="col-12 col-md-6">
                                <label for="density" class="form-label">Density <small class="text-muted">(for liquids, e.g. 0.92)</small></label>
                                <input type="number" step="any" class="form-control" id="density"
                                    wire:model="density" placeholder="e.g. 0.92">
                                @error('density') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Minimum Stock --}}
                            <div class="col-12 col-md-6">
                                <label for="minimum_stock" class="form-label">Minimum Stock <span class="text-danger">*</span></label>
                                <input type="number" step="any" class="form-control" id="minimum_stock"
                                    wire:model="minimum_stock" placeholder="e.g. 100">
                                @error('minimum_stock') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>

                            {{-- Reorder Point --}}
                            <div class="col-12 col-md-6">
                                <label for="reorder_point" class="form-label">Reorder Point</label>
                                <input type="number" step="any" class="form-control" id="reorder_point"
                                    wire:model="reorder_point" placeholder="e.g. 150">
                                @error('reorder_point') <div class="text-danger">{{ $message }}</div> @enderror
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