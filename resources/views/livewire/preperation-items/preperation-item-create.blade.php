<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        {{ $status ? "View" : ($item ? "Edit" : "Add") }} Preperation Item
                    </h5>
                    <a href="{{ route('preperation-items') }}" class="btn btn-light-light text-muted">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>

                <div class="card-body">

                    @if(count($errors) > 0)
                        <div class="alert alert-danger">
                            <strong>Oops!</strong> Something went wrong, please check below errors.<br><br>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ───────────────────────────── Basic Info ───────────────────────────── --}}
                    <div class="col-12">
                        <h6 class="text-muted fw-semibold mb-0">Basic Information</h6>
                        <hr class="mt-1">
                    </div>

                    <div class="row g-4">

                        <div class="col-lg-3 col-sm-12">
                            <label class="form-label fw-semibold" for="itemName">Name <span class="text-danger">*</span></label>
                            <input type="text" id="itemName"
                                   class="form-control @error('itemName') is-invalid @enderror"
                                   wire:model="itemName"
                                   placeholder="Preperation item name"
                                   {{ $statusAttributes }}>
                            @error('itemName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-lg-3 col-sm-12">
                            <label class="form-label fw-semibold" for="itemCode">Code <span class="text-danger">*</span></label>
                            <input type="text" id="itemCode"
                                   class="form-control @error('itemCode') is-invalid @enderror"
                                   wire:model="itemCode"
                                   placeholder="Item code"
                                   {{ $statusAttributes }}>
                            @error('itemCode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>

                    {{-- ───────────────────────────── Input Mode & Measurements ───────────────────────────── --}}
                    <div class="col-12 mt-4">
                        <h6 class="text-muted fw-semibold mb-0">Measurements</h6>
                        <hr class="mt-1">
                    </div>

                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold d-block">Input Mode</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio"
                                       wire:model="itemWithFormula"
                                       wire:click="$set('itemWithFormula','1')"
                                       name="itemWithFormula" value="1" id="withFormula">
                                <label class="form-check-label" for="withFormula">With Formula</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio"
                                       wire:model="itemWithFormula"
                                       wire:click="$set('itemWithFormula','0')"
                                       name="itemWithFormula" value="0" id="manualInput">
                                <label class="form-check-label" for="manualInput">Manual Input</label>
                            </div>
                        </div>

                        @if($itemWithFormula == 1)
                            <div class="col-lg-3 col-sm-12">
                                <label class="form-label fw-semibold" for="itemWeight">
                                    Weight <small class="text-muted fw-normal">(per basic unit)</small>
                                </label>
                                <input type="text" id="itemWeight"
                                       class="form-control @error('itemWeight') is-invalid @enderror"
                                       wire:model="itemWeight"
                                       placeholder="Item weight"
                                       {{ $statusAttributes }}>
                                @error('itemWeight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-lg-3 col-sm-12">
                                <label class="form-label fw-semibold" for="itemVolume">
                                    Volume <small class="text-muted fw-normal">(per basic unit)</small>
                                </label>
                                <input type="text" id="itemVolume"
                                       class="form-control @error('itemVolume') is-invalid @enderror"
                                       wire:model="itemVolume"
                                       placeholder="Item volume"
                                       {{ $statusAttributes }}>
                                @error('itemVolume') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="col-lg-3 col-sm-12">
                            <label class="form-label fw-semibold" for="itemVAT">VAT</label>
                            <input type="text" id="itemVAT"
                                   class="form-control @error('itemVAT') is-invalid @enderror"
                                   wire:model="itemVAT"
                                   placeholder="VAT"
                                   {{ $statusAttributes }}>
                            @error('itemVAT') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- ───────────────────────────── Units Table ───────────────────────────── --}}
                    <div class="col-12 mt-4">
                        <h6 class="text-muted fw-semibold mb-0">Units</h6>
                        <hr class="mt-1">
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-nowrap table-striped table-bordered w-100 align-middle">
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>Symbol</th>
                                            <th>Basic</th>
                                            <th>Is Box?</th>
                                            <th>Box Qty</th>
                                            <th>Sales Option</th>
                                            @if($itemWithFormula == 1)
                                                <th>Formula</th>
                                            @else
                                                <th>Weight</th>
                                                <th>Volume (m³)</th>
                                            @endif
                                            @if(!$status)
                                                <th>Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($units as $key => $unit)
                                            <tr>
                                                <td>
                                                    <input type="text"
                                                           class="form-control form-control-sm @error("units.$key.unitName") is-invalid @enderror"
                                                           wire:model="units.{{ $key }}.unitName"
                                                           placeholder="Unit name"
                                                           {{ $statusAttributes }}>
                                                    @error("units.$key.unitName") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           class="form-control form-control-sm @error("units.$key.symbol") is-invalid @enderror"
                                                           wire:model="units.{{ $key }}.symbol"
                                                           placeholder="Symbol"
                                                           {{ $statusAttributes }}>
                                                    @error("units.$key.symbol") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </td>
                                                <td class="text-center">
                                                    <input type="radio" class="form-check-input"
                                                           wire:click="toggleBasic({{ $key }})"
                                                           x-bind:checked="{{ $unit['basic'] == 1 ? 'true' : 'false' }}"
                                                           wire:model.lazy="units.{{ $key }}.basic"
                                                           name="basic" value="1"
                                                           {{ $statusAttributes }}
                                                           @if($units[$key]['is_box'] == 1) disabled @endif>
                                                </td>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input is-box-checkbox"
                                                           wire:model="units.{{ $key }}.is_box"
                                                           data-key="{{ $key }}"
                                                           value="1"
                                                           {{ $statusAttributes }}
                                                           @if($units[$key]['basic'] == 1) disabled @endif>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm box-qty-input"
                                                           wire:model="units.{{ $key }}.box_qty"
                                                           data-key="{{ $key }}"
                                                           placeholder="Quantity"
                                                           {{ $statusAttributes }}
                                                           {{ $unit['is_box'] != 1 ? 'disabled' : '' }}>
                                                </td>
                                                <td>
                                                    <div wire:ignore>
                                                        <select class="selectpicker w-100"
                                                                id="price_type_{{ $key }}"
                                                                title="Select"
                                                                data-style="btn-default"
                                                                data-icon-base="ti"
                                                                data-tick-icon="ti-check text-white"
                                                                {{ $statusAttributes }}>
                                                            @foreach($priceTypes as $priceType)
                                                                <option value="{{ $priceType['value'] }}"
                                                                    @selected($priceType['value'] == $unit['price_type'])>
                                                                    {{ $priceType['name'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </td>

                                                @if($itemWithFormula == 1)
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm"
                                                               wire:model="units.{{ $key }}.formula"
                                                               placeholder="Formula"
                                                               {{ $statusAttributes }}>
                                                    </td>
                                                @else
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm"
                                                               wire:model="units.{{ $key }}.weight"
                                                               placeholder="Weight"
                                                               {{ $statusAttributes }}>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm"
                                                               wire:model="units.{{ $key }}.volume"
                                                               placeholder="Volume"
                                                               {{ $statusAttributes }}>
                                                    </td>
                                                @endif

                                                @if(!$status)
                                                    <td class="text-center">
                                                        @if($key == 0)
                                                            <button type="button"
                                                                    wire:click="addRow()"
                                                                    wire:loading.remove
                                                                    class="btn btn-light-primary icon-btn-sm"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-custom-class="tooltip-white"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="Add unit">
                                                                <i class="bi bi-plus-lg"></i>
                                                            </button>
                                                            <div wire:loading wire:target="addRow">
                                                                <i class="bi bi-arrow-clockwise"></i>
                                                            </div>
                                                        @else
                                                            <button type="button"
                                                                    wire:click="removeRow({{ $key }})"
                                                                    class="btn btn-light-danger icon-btn-sm"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-custom-class="tooltip-white"
                                                                    data-bs-placement="top"
                                                                    data-bs-title="Remove unit">
                                                                <i class="bi bi-dash-lg"></i>
                                                            </button>
                                                        @endif
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ───────────────────────────── Product Image ───────────────────────────── --}}
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Item Image</h6>
                    @error('itemImage')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="card-body">
                    <x-filepond
                        :images="$itemImage"
                        wire:model="itemImage"
                        :file-path="$itemImagePath ? asset('storage/' . $itemImagePath) : ''"
                        is-multiple="false"
                        allow-remove="true"
                        delete-event="deleteImage"
                    />
                </div>
            </div>

            {{-- ───────────────────────────── Submit ───────────────────────────── --}}
            @if(!$status)
                <div class="col-12 text-end mt-3 mb-2">
                    @if($item)
                        <button type="button" wire:click="update" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Update Changes
                        </button>
                    @else
                        <button type="button" wire:click="store" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    @endif
                </div>
            @endif

        </div>
    </div>

    @script
    <script>
        // ─── Selectpicker init ───────────────────────────────────────────────────────
        $('.selectpicker').selectpicker();

        $(document).on('change', '.selectpicker', function () {
            $wire.set($(this).attr('wire:model'), $(this).val());
        });

        // ─── Price Type → Livewire sync ──────────────────────────────────────────────
        $(document).on('change', '[id^="price_type_"]', function () {
            var key = $(this).attr('id').replace('price_type_', '');
            $wire.set('units.' + key + '.price_type', $(this).val());
        });

        // ─── Is Box checkbox → enable/disable Box Qty instantly ─────────────────────
        $(document).on('change', '.is-box-checkbox', function () {
            var key  = $(this).data('key');
            var $qty = $('input.box-qty-input[data-key="' + key + '"]');

            if ($(this).is(':checked')) {
                $qty.prop('disabled', false).focus();
            } else {
                $qty.prop('disabled', true).val('');
                $wire.set('units.' + key + '.box_qty', '');
            }
        });

        // ─── Re-sync all states after every Livewire re-render ──────────────────────
        function syncBoxQtyStates() {
            $('.is-box-checkbox').each(function () {
                var key  = $(this).data('key');
                var $qty = $('input.box-qty-input[data-key="' + key + '"]');
                $qty.prop('disabled', !$(this).is(':checked'));
            });
        }

        Livewire.hook('morph.updated', ({ el }) => {
            $('.selectpicker').selectpicker();
            syncBoxQtyStates();
        });

        // Run once on initial load for edit mode
        syncBoxQtyStates();
    </script>
    @endscript
</div>