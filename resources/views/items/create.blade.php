<x-layouts.app title="Items Management">

    <div class="row">
        <form action="{{ $route }}" method="POST" id="itemForm" enctype="multipart/form-data">
            @csrf

            {{-- ── Basic Info ─────────────────────────────────────────────────── --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ $editing ? 'Edit' : 'Create' }} Item</h6>
                    <a href="{{ route('items.index') }}" class="btn btn-light-light text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>

                <div class="card-body">

                    {{-- Validation Errors --}}
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <strong><i class="bi bi-exclamation-triangle"></i> Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row g-4">

                        {{-- Item Type --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold" for="item_type_id">
                                Item Type <span class="text-danger">*</span>
                            </label>
                            <select class="selectpicker w-100" name="item_type_id" id="item_type_id"
                                    title="Select Item Type" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white" required>
                                @foreach($item_types as $type)
                                    <option value="{{ $type['id'] }}"
                                        @selected(old('item_type_id', $item['item_type_id'] ?? '') == $type['id'])>
                                        {{ $type['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('item_type_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Sub Type --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold" for="sub_type_id">Sub Type</label>
                            <select class="selectpicker w-100" name="sub_type_id" id="sub_type_id"
                                    title="Select Sub Type" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                @foreach($sub_types as $subType)
                                    <option value="{{ $subType['id'] }}"
                                        @selected(old('sub_type_id', $item['sub_type_id'] ?? '') == $subType['id'])>
                                        {{ $subType['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sub_type_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Code --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">
                                Code <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="code" placeholder="Item code"
                                   value="{{ old('code', $item['code'] ?? '') }}" required>
                            @error('code')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Name --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="name" placeholder="Item name"
                                   value="{{ old('name', $item['name'] ?? '') }}" required>
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Weight --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">Weight</label>
                            <input type="number" class="form-control" name="weight" placeholder="Weight"
                                   step="0.0001" min="0" value="{{ old('weight', $item['weight'] ?? '') }}">
                            @error('weight')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Volume --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">Volume</label>
                            <input type="number" class="form-control" name="volume" placeholder="Volume"
                                   step="0.0001" min="0" value="{{ old('volume', $item['volume'] ?? '') }}">
                            @error('volume')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- VAT --}}
                        <div class="col-lg-4 col-sm-12">
                            <label class="form-label fw-semibold">VAT (%)</label>
                            <input type="number" class="form-control" name="vat" placeholder="VAT percentage"
                                   step="0.01" min="0" max="100" value="{{ old('vat', $item['vat'] ?? '') }}">
                            @error('vat')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- With Formula --}}
                        <div class="col-lg-4 col-sm-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="with_formula"
                                       id="with_formula"
                                       @checked(old('with_formula', $item['with_formula'] ?? true))>
                                <label class="form-check-label fw-medium" for="with_formula">
                                    With Formula
                                </label>
                            </div>
                        </div>

                        {{-- Is Active --}}
                        <div class="col-lg-4 col-sm-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="is_active"
                                       @checked(old('is_active', $item['is_active'] ?? true))>
                                <label class="form-check-label fw-medium" for="is_active">
                                    Is Active
                                </label>
                            </div>
                        </div>

                    </div>

                    {{-- ── Units Table ─────────────────────────────────────────────── --}}
                    <div class="col-12 mt-4">
                        <h6 class="text-muted fw-semibold mb-0">Units</h6>
                        <hr class="mt-1">
                    </div>

                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle" id="unitsTable">
                                <thead>
                                    <tr>
                                        <th>Unit Name <span class="text-danger">*</span></th>
                                        <th>Symbol <span class="text-danger">*</span></th>
                                        <th class="text-center">Basic</th>
                                        <th class="text-center">Is Box?</th>
                                        <th>Box Qty</th>
                                        <th>Sales Option</th>
                                        <th class="formula-col">Formula</th>
                                        <th class="manual-col">Weight</th>
                                        <th class="manual-col">Volume (m³)</th>
                                        <th>VAT</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="unitsBody">
                                    @php
                                        $existingUnits = old('units', $item['units'] ?? [[
                                            'name'       => '',
                                            'symbol'     => '',
                                            'basic'      => 1,
                                            'is_box'     => 0,
                                            'box_qty'    => '',
                                            'price_type' => 0,
                                            'formula'    => '',
                                            'weight'     => '',
                                            'volume'     => '',
                                            'vat'        => '',
                                        ]]);
                                        $priceTypes = [0 => 'ALL', 1 => 'B2B/B2C', 2 => 'POS'];
                                    @endphp

                                    @foreach($existingUnits as $k => $unit)
                                        <tr>
                                            @if(!empty($unit['id']))
                                                <input type="hidden" name="units[{{ $k }}][id]" value="{{ $unit['id'] }}">
                                            @endif

                                            {{-- Name --}}
                                            <td>
                                                <input type="text" class="form-control form-control-sm"
                                                       name="units[{{ $k }}][name]"
                                                       value="{{ $unit['name'] ?? '' }}"
                                                       placeholder="Unit name" required>
                                            </td>

                                            {{-- Symbol --}}
                                            <td>
                                                <input type="text" class="form-control form-control-sm"
                                                       name="units[{{ $k }}][symbol]"
                                                       value="{{ $unit['symbol'] ?? '' }}"
                                                       placeholder="e.g. KG" required>
                                            </td>

                                            {{-- Basic --}}
                                            <td class="text-center">
                                                <input type="radio" class="form-check-input basic-radio"
                                                       name="units_basic" value="{{ $k }}"
                                                       @checked(($unit['basic'] ?? 0) == 1)>
                                                <input type="hidden" name="units[{{ $k }}][basic]"
                                                       value="{{ ($unit['basic'] ?? 0) == 1 ? 1 : 0 }}">
                                            </td>

                                            {{-- Is Box --}}
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input is-box-cb" value="1"
                                                       @checked(!empty($unit['is_box']))>
                                                <input type="hidden" name="units[{{ $k }}][is_box]"
                                                       value="{{ !empty($unit['is_box']) ? 1 : 0 }}">
                                            </td>

                                            {{-- Box Qty --}}
                                            <td>
                                                <input type="number" class="form-control form-control-sm box-qty-input"
                                                       name="units[{{ $k }}][box_qty]"
                                                       value="{{ $unit['box_qty'] ?? '' }}"
                                                       placeholder="Qty" min="0"
                                                       {{ empty($unit['is_box']) ? 'disabled' : '' }}>
                                            </td>

                                            {{-- Price Type --}}
                                            <td>
                                                <select class="form-select form-select-sm"
                                                        name="units[{{ $k }}][price_type]">
                                                    @foreach($priceTypes as $val => $label)
                                                        <option value="{{ $val }}"
                                                            @selected(($unit['price_type'] ?? 0) == $val)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>

                                            {{-- Formula (with_formula mode) --}}
                                            <td class="formula-col">
                                                <input type="number" class="form-control form-control-sm"
                                                       name="units[{{ $k }}][formula]"
                                                       value="{{ $unit['formula'] ?? '' }}"
                                                       placeholder="Formula" step="0.0001" min="0">
                                            </td>

                                            {{-- Weight (manual mode) --}}
                                            <td class="manual-col">
                                                <input type="number" class="form-control form-control-sm"
                                                       name="units[{{ $k }}][weight]"
                                                       value="{{ $unit['weight'] ?? '' }}"
                                                       placeholder="Weight" step="0.0001" min="0">
                                            </td>

                                            {{-- Volume (manual mode) --}}
                                            <td class="manual-col">
                                                <input type="number" class="form-control form-control-sm"
                                                       name="units[{{ $k }}][volume]"
                                                       value="{{ $unit['volume'] ?? '' }}"
                                                       placeholder="Volume" step="0.0001" min="0">
                                            </td>

                                            {{-- VAT --}}
                                            <td>
                                                <input type="number" class="form-control form-control-sm"
                                                       name="units[{{ $k }}][vat]"
                                                       value="{{ $unit['vat'] ?? '' }}"
                                                       placeholder="VAT" step="0.01" min="0" max="100">
                                            </td>

                                            {{-- Action --}}
                                            <td class="text-center">
                                                @if($k == 0)
                                                    <button type="button"
                                                            class="btn btn-light-primary icon-btn-sm"
                                                            id="addUnitRow"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            title="Add unit">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            class="btn btn-light-danger icon-btn-sm remove-unit-row"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            title="Remove unit">
                                                        <i class="bi bi-dash-lg"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Submit
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

</x-layouts.app>

<script>
    $('.selectpicker').selectpicker();

    // ── Item Type → Sub Types (AJAX) ───────────────────────────────────────
    $(document).on('change', '#item_type_id', function () {
        const typeId = $(this).val();
        const $sub   = $('#sub_type_id');

        $sub.html('<option value="">Select Sub Type</option>').selectpicker('refresh');

        if (!typeId) return;

        $.ajax({
            url: "{{ route('items.get-sub-types', '%typeId%') }}".replace('%typeId%', typeId),
            type: 'GET',
            success: function (data) {
                setOptions($sub, data.sub_types);
            },
            error: function () {
                $sub.html('<option value="">Error loading sub types</option>').selectpicker('refresh');
            }
        });
    });

    // ── With Formula toggle ────────────────────────────────────────────────
    function syncFormulaMode() {
        const withFormula = $('#with_formula').is(':checked');
        $('.formula-col').toggleClass('d-none', !withFormula);
        $('.manual-col').toggleClass('d-none', withFormula);
    }

    $('#with_formula').on('change', syncFormulaMode);
    syncFormulaMode(); // run on page load

    // ── Add unit row ───────────────────────────────────────────────────────
    $(document).on('click', '#addUnitRow', function () {
        const idx         = $('#unitsBody tr').length;
        const withFormula = $('#with_formula').is(':checked');
        const priceOpts   = `
            <option value="0">ALL</option>
            <option value="1">B2B/B2C</option>
            <option value="2">POS</option>`;

        const row = `
        <tr>
            <td>
                <input type="text" class="form-control form-control-sm"
                       name="units[${idx}][name]" placeholder="Unit name" required>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm"
                       name="units[${idx}][symbol]" placeholder="e.g. KG" required>
            </td>
            <td class="text-center">
                <input type="radio" class="form-check-input basic-radio" name="units_basic" value="${idx}">
                <input type="hidden" name="units[${idx}][basic]" value="0">
            </td>
            <td class="text-center">
                <input type="checkbox" class="form-check-input is-box-cb" value="1">
                <input type="hidden" name="units[${idx}][is_box]" value="0">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm box-qty-input"
                       name="units[${idx}][box_qty]" placeholder="Qty" min="0" disabled>
            </td>
            <td>
                <select class="form-select form-select-sm" name="units[${idx}][price_type]">
                    ${priceOpts}
                </select>
            </td>
            <td class="formula-col ${withFormula ? '' : 'd-none'}">
                <input type="number" class="form-control form-control-sm"
                       name="units[${idx}][formula]" placeholder="Formula" step="0.0001" min="0">
            </td>
            <td class="manual-col ${withFormula ? 'd-none' : ''}">
                <input type="number" class="form-control form-control-sm"
                       name="units[${idx}][weight]" placeholder="Weight" step="0.0001" min="0">
            </td>
            <td class="manual-col ${withFormula ? 'd-none' : ''}">
                <input type="number" class="form-control form-control-sm"
                       name="units[${idx}][volume]" placeholder="Volume" step="0.0001" min="0">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm"
                       name="units[${idx}][vat]" placeholder="VAT" step="0.01" min="0" max="100">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-light-danger icon-btn-sm remove-unit-row"
                        data-bs-toggle="tooltip" data-bs-placement="top" title="Remove unit">
                    <i class="bi bi-dash-lg"></i>
                </button>
            </td>
        </tr>`;

        $('#unitsBody').append(row);
    });

    // ── Remove unit row ────────────────────────────────────────────────────
    $(document).on('click', '.remove-unit-row', function () {
        $(this).closest('tr').remove();
        reindexUnits();
    });

    // ── Reindex all unit rows after removal ────────────────────────────────
    function reindexUnits() {
        $('#unitsBody tr').each(function (i) {
            $(this).find('input[name], select[name]').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/units\[\d+\]/, `units[${i}]`));
                }
            });
            // Keep radio value in sync with row index
            $(this).find('.basic-radio').val(i);
        });
    }

    // ── Basic radio → update hidden field ─────────────────────────────────
    $(document).on('change', '.basic-radio', function () {
        const selected = parseInt($(this).val());
        $('#unitsBody tr').each(function (i) {
            $(this).find('input[name$="[basic]"]').val(i === selected ? 1 : 0);
        });
    });

    // ── Is Box checkbox → toggle box qty + sync hidden field ──────────────
    $(document).on('change', '.is-box-cb', function () {
        const $row    = $(this).closest('tr');
        const $qty    = $row.find('.box-qty-input');
        const $hidden = $row.find('input[name$="[is_box]"]');
        const checked = $(this).is(':checked');

        $qty.prop('disabled', !checked);
        if (!checked) $qty.val('');
        $hidden.val(checked ? 1 : 0);
    });
</script>