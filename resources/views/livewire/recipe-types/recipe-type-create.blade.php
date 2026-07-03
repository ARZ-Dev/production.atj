<div>
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-xl">

                {{-- Main Info Card --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $editing ? 'Edit' : 'Add' }} Recipe Type</h5>
                        <a href="{{ route('recipe-types') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Name --}}
                            <div class="col-md-6">
                                <label for="name" class="form-label">
                                    Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" class="form-control" wire:model.defer="name" placeholder="Recipe Type Name ...">
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Item Type --}}
                            <div class="col-md-6">
                                <div wire:ignore>
                                    <label for="item_type_ids" class="form-label">
                                        Item Types <span class="text-danger">*</span>
                                    </label>
                                    <select id="item_type_ids" multiple
                                            class="selectpicker w-100"
                                            title="Select Item Types"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white">
                                        @foreach($itemTypes as $itemType)
                                            <option value="{{ $itemType['id'] }}"
                                                @selected(in_array($itemType['id'], (array) $item_type_ids))>
                                                {{ $itemType['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('item_type_ids')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Side Product Item Type --}}
                            <div class="col-md-6">
                                <div wire:ignore>
                                    <label for="side_item_type_ids" class="form-label">
                                        Side Product Item Types
                                    </label>
                                    <select id="side_item_type_ids" multiple
                                            class="selectpicker w-100"
                                            title="Select Side Product Item Types"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white">
                                        @foreach($itemTypes as $itemType)
                                            <option value="{{ $itemType['id'] }}"
                                                @selected(in_array($itemType['id'], (array) $side_item_type_ids))>
                                                {{ $itemType['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('side_item_type_ids')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Output Item Type --}}
                            <div class="col-md-6">
                                <div wire:ignore>
                                    <label for="output_item_type_ids" class="form-label">
                                        Output Item Types <span class="text-danger">*</span>
                                    </label>
                                    <select id="output_item_type_ids" multiple
                                            class="selectpicker w-100"
                                            title="Select Output Item Types"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white">
                                        @foreach($itemTypes as $itemType)
                                            <option value="{{ $itemType['id'] }}"
                                                @selected(in_array($itemType['id'], (array) $output_item_type_ids))>
                                                {{ $itemType['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('output_item_type_ids')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="col-12 text-end mb-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i> Submit
                    </button>
                </div>

            </div>
        </div>
    </form>

    @script
    <script>
        // Initial boot
        $('#item_type_ids').selectpicker();
        $('#side_item_type_ids').selectpicker();
        $('#output_item_type_ids').selectpicker();

        // Sync selectpicker -> Livewire (the plugin mutates the hidden select
        // without firing the native event wire:model listens for)
        $('#item_type_ids').on('changed.bs.select', function () {
            @this.set('item_type_ids', $(this).val());
        });
        $('#side_item_type_ids').on('changed.bs.select', function () {
            @this.set('side_item_type_ids', $(this).val());
        });
        $('#output_item_type_ids').on('changed.bs.select', function () {
            @this.set('output_item_type_ids', $(this).val());
        });

        // Re-init any selectpicker added to the DOM via morph
        Livewire.hook('morph.added', ({ el }) => {
            $(el).find('.selectpicker').selectpicker();
        });
    </script>
    @endscript
</div>