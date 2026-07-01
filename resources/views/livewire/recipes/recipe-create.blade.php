<div>
    <form wire:submit.prevent="submit">
        <div class="row">
            <div class="col-xl">

                {{-- ── Card 1: Recipe Header ──────────────────────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $editing ? 'Edit' : 'Add' }} Recipe</h5>
                        <a href="{{ route('recipes') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="name">
                                    Recipe Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" class="form-control" wire:model="name"
                                    placeholder="Enter recipe name">
                                @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Recipe Type (from DB) --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="recipe_type">
                                    Recipe Type <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select id="recipe_type" wire:model="recipe_type_id" class="selectpicker w-100"
                                        title="Select Type" data-style="btn-default" data-icon-base="ti"
                                        data-tick-icon="ti-check text-white">
                                        @foreach($recipeTypes as $rt)
                                        <option value="{{ $rt['id'] }}" @selected($recipe_type_id==$rt['id'])>
                                            {{ $rt['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('recipe_type_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            @if($recipe_type_id)

                            {{-- Output Item Type --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="output_item_type">
                                    Output Item Type <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select id="output_item_type" wire:model="output_item_type_id"
                                        class="selectpicker w-100" title="Select Item Type" data-style="btn-default"
                                        data-live-search="true" data-icon-base="ti" data-size="5"
                                        data-tick-icon="ti-check text-white">
                                        @foreach($itemTypes as $type)
                                        <option value="{{ $type['id'] }}" @selected($output_item_type_id==$type['id'])>
                                            {{ $type['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- @if($output_item_type_id) --}}

                            {{-- Output Item --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="header_item">
                                    Output Item <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select id="header_item" wire:model="item_id" class="selectpicker w-100"
                                        title="Select Item" data-style="btn-default" data-live-search="true"
                                        data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($headerItems as $item)
                                        <option value="{{ $item['id'] }}" @selected($item_id==$item['id'])>
                                            {{ $item['name'] }} ({{ $item['code'] ?? '' }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('item_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Output Unit --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="header_unit">
                                    Output Unit <span class="text-danger">*</span>
                                </label>
                                <select id="header_unit" wire:model="item_unit_id" class="selectpicker w-100"
                                    title="Select Unit" data-style="btn-default" data-live-search="true"
                                    data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                    @foreach($headerUnits as $unit)
                                    <option value="{{ $unit['id'] }}" @selected($item_unit_id==$unit['id'])>
                                        {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('item_unit_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- @endif --}}

                            {{-- Batch --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="batch">
                                    Batch Size
                                </label>
                                <input type="number" id="batch" class="form-control" wire:model="batch" value="1"
                                    disabled>
                                @error('batch')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Quantity Per Batch --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="quantity_per_batch">
                                    Quantity Per Batch <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="quantity_per_batch" class="form-control"
                                    wire:model="quantity_per_batch" placeholder="e.g. 100">
                                @error('quantity_per_batch')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Batch Weight --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="batch_weight">Batch Weight (kg)</label>
                                <input type="text" id="batch_weight" class="form-control" wire:model="batch_weight"
                                    placeholder="Optional">
                                @error('batch_weight')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Batch Volume --}}
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="batch_volume">Batch Volume (M³)</label>
                                <input type="text" id="batch_volume" class="form-control" wire:model="batch_volume"
                                    placeholder="Optional">
                                @error('batch_volume')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" id="status" wire:model="status">
                                    <label class="form-check-label" for="status">
                                        {{ $status ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div class="col-12">
                                <label class="form-label" for="notes">Notes</label>
                                <textarea class="form-control" id="notes" wire:model="notes" rows="2"
                                    placeholder="Recipe notes (optional)"></textarea>
                            </div>

                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Dynamic Ingredient Sections (one per item_type_id) ──────────── --}}
                @foreach($sections as $sectionIndex => $section)
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $section['title'] }}</h5>
                        <button type="button" class="btn btn-success btn-sm"
                            wire:click="addRowToSection({{ $sectionIndex }})">
                            <i class="ti ti-plus me-1"></i> Add {{ $section['title'] }}
                        </button>
                    </div>
                    <div class="card-body">
                        @if(count($section['rows']) > 0)
                        <div class="row g-3">
                            @foreach($section['rows'] as $rowIndex => $row)
                            <div class="col-12" wire:key="section-{{ $sectionIndex }}-row-{{ $rowIndex }}">
                                <div class="border rounded p-3">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label mb-0 fw-semibold">#{{ $rowIndex + 1 }}</label>
                                        @if(count($section['rows']) > 1)
                                        <button type="button" class="btn btn-danger btn-sm"
                                            wire:click="removeRowFromSection({{ $sectionIndex }}, {{ $rowIndex }})">
                                            <i class="ti ti-trash me-1"></i> Remove
                                        </button>
                                        @endif
                                    </div>

                                    <div class="row g-3 align-items-end">

                                        {{-- Item --}}
                                        <div class="col-12 col-md-4">
                                            <label class="form-label">
                                                Item <span class="text-danger">*</span>
                                            </label>
                                            <div wire:ignore>
                                                <select id="section_item_{{ $sectionIndex }}_{{ $rowIndex }}"
                                                    class="selectpicker w-100 section-item-select" title="Select Item"
                                                    data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                                    data-size="5" data-tick-icon="ti-check text-white"
                                                    data-section="{{ $sectionIndex }}" data-row="{{ $rowIndex }}">
                                                    @foreach($sectionItems[$sectionIndex] ?? [] as $item)
                                                    <option value="{{ $item['id'] }}"
                                                        @selected($row['item_id']==$item['id'])>
                                                        {{ $item['name'] }} ({{ $item['code'] ?? '' }})
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error("sections.{$sectionIndex}.rows.{$rowIndex}.item_id")
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Unit --}}
                                        <div class="col-12 col-md-3">
                                            <label class="form-label">
                                                Unit <span class="text-danger">*</span>
                                            </label>
                                            <select id="section_unit_{{ $sectionIndex }}_{{ $rowIndex }}"
                                                class="selectpicker w-100" title="Select Unit" data-style="btn-default"
                                                data-live-search="true" data-icon-base="ti" data-size="5"
                                                data-tick-icon="ti-check text-white"
                                                wire:model="sections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.item_unit_id"
                                                data-section="{{ $sectionIndex }}" data-row="{{ $rowIndex }}">
                                                @foreach($section['rowUnits'][$rowIndex] ?? [] as $unit)
                                                <option value="{{ $unit['id'] }}"
                                                    @selected($row['item_unit_id']==$unit['id'])>
                                                    {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                                </option>
                                                @endforeach
                                            </select>
                                            @error("sections.{$sectionIndex}.rows.{$rowIndex}.item_unit_id")
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Quantity --}}
                                        <div class="col-12 col-md-3">
                                            <label class="form-label">
                                                Quantity <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                wire:model="sections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.quantity"
                                                id="section_qty_{{ $sectionIndex }}_{{ $rowIndex }}"
                                                class="form-control" placeholder="Enter quantity">
                                            @error("sections.{$sectionIndex}.rows.{$rowIndex}.quantity")
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Notes --}}
                                        <div class="col-12 col-md-2">
                                            <label class="form-label">Notes</label>
                                            <input type="text"
                                                wire:model="sections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.notes"
                                                id="section_notes_{{ $sectionIndex }}_{{ $rowIndex }}"
                                                class="form-control" placeholder="Optional">
                                        </div>

                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No items added yet.</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- ── Dynamic Side Product Sections (one per side_item_type_id) ───── --}}
                @foreach($sideSections as $sectionIndex => $section)
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $section['title'] }} <span class="badge bg-light-info text-info ms-1">Side Product</span></h5>
                        <button type="button" class="btn btn-success btn-sm"
                            wire:click="addRowToSideSection({{ $sectionIndex }})">
                            <i class="ti ti-plus me-1"></i> Add {{ $section['title'] }}
                        </button>
                    </div>
                    <div class="card-body">
                        @if(count($section['rows']) > 0)
                        <div class="row g-3">
                            @foreach($section['rows'] as $rowIndex => $row)
                            <div class="col-12" wire:key="side-section-{{ $sectionIndex }}-row-{{ $rowIndex }}">
                                <div class="border rounded p-3">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label mb-0 fw-semibold">#{{ $rowIndex + 1 }}</label>
                                        @if(count($section['rows']) > 1)
                                        <button type="button" class="btn btn-danger btn-sm"
                                            wire:click="removeRowFromSideSection({{ $sectionIndex }}, {{ $rowIndex }})">
                                            <i class="ti ti-trash me-1"></i> Remove
                                        </button>
                                        @endif
                                    </div>

                                    <div class="row g-3 align-items-end">

                                        {{-- Item --}}
                                        <div class="col-12 col-md-4">
                                            <label class="form-label">
                                                Item <span class="text-danger">*</span>
                                            </label>
                                            <div wire:ignore>
                                                <select id="side_section_item_{{ $sectionIndex }}_{{ $rowIndex }}"
                                                    class="selectpicker w-100 side-section-item-select" title="Select Item"
                                                    data-style="btn-default" data-live-search="true" data-icon-base="ti"
                                                    data-size="5" data-tick-icon="ti-check text-white"
                                                    data-section="{{ $sectionIndex }}" data-row="{{ $rowIndex }}">
                                                    @foreach($sideSectionItems[$sectionIndex] ?? [] as $item)
                                                    <option value="{{ $item['id'] }}"
                                                        @selected($row['item_id']==$item['id'])>
                                                        {{ $item['name'] }} ({{ $item['code'] ?? '' }})
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error("sideSections.{$sectionIndex}.rows.{$rowIndex}.item_id")
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Unit --}}
                                        <div class="col-12 col-md-3">
                                            <label class="form-label">
                                                Unit <span class="text-danger">*</span>
                                            </label>
                                            <select id="side_section_unit_{{ $sectionIndex }}_{{ $rowIndex }}"
                                                class="selectpicker w-100" title="Select Unit" data-style="btn-default"
                                                data-live-search="true" data-icon-base="ti" data-size="5"
                                                data-tick-icon="ti-check text-white"
                                                wire:model="sideSections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.item_unit_id"
                                                data-section="{{ $sectionIndex }}" data-row="{{ $rowIndex }}">
                                                @foreach($section['rowUnits'][$rowIndex] ?? [] as $unit)
                                                <option value="{{ $unit['id'] }}"
                                                    @selected($row['item_unit_id']==$unit['id'])>
                                                    {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                                </option>
                                                @endforeach
                                            </select>
                                            @error("sideSections.{$sectionIndex}.rows.{$rowIndex}.item_unit_id")
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Quantity --}}
                                        <div class="col-12 col-md-3">
                                            <label class="form-label">
                                                Quantity <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                wire:model="sideSections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.quantity"
                                                id="side_section_qty_{{ $sectionIndex }}_{{ $rowIndex }}"
                                                class="form-control" placeholder="Enter quantity">
                                            @error("sideSections.{$sectionIndex}.rows.{$rowIndex}.quantity")
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Notes --}}
                                        <div class="col-12 col-md-2">
                                            <label class="form-label">Notes</label>
                                            <input type="text"
                                                wire:model="sideSections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.notes"
                                                id="side_section_notes_{{ $sectionIndex }}_{{ $rowIndex }}"
                                                class="form-control" placeholder="Optional">
                                        </div>

                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-4">
                            <p class="text-muted mb-0">No items added yet.</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Submit --}}
                @if($recipe_type_id)
                <div class="col-12 text-end mb-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>
                        {{ $editing ? 'Update Recipe' : 'Save Recipe' }}
                    </button>
                </div>
                @endif

            </div>
        </div>
    </form>

    @script
    <script>
        // ── Boot ─────────────────────────────────────────────────────────────
        $('.selectpicker').selectpicker();

        // ── New DOM nodes (new rows / cards added by Livewire) ───────────────
        Livewire.hook('morph.added', ({ el }) => {
            $(el).find('[id^="section_item_"], [id^="section_unit_"], [id^="side_section_item_"], [id^="side_section_unit_"], #header_item, #header_unit, #output_item_type').selectpicker();
        });

        // ── After Livewire round-trip: re-init non-wire:ignore unit pickers ───
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                $nextTick(() => {
                    $('[id^="section_unit_"], [id^="side_section_unit_"], #header_unit').each(function () {
                        $(this).selectpicker('destroy').selectpicker();
                    });
                });
            });
        });

        // ── Recipe type changed: reset output item type picker ────────────────
        $wire.on('recipe-type-changed', () => {
            $nextTick(() => {
                $('#output_item_type').val('').selectpicker('refresh');
            });
        });

        // ── Output item type changed: update header item picker options ────────
        $wire.on('output-type-changed', ({ headerItems }) => {
            $nextTick(() => {
                if (typeof setOptions === 'function') {
                    setOptions($('#header_item'), headerItems);
                }
            });
        });

        // ── After row removed from a section: re-sync picker values ──────────
        $wire.on('section-rows-removed', ({ sectionIndex, rows }) => {
            $nextTick(() => {
                rows.forEach(function (itemId, i) {
                    $(`#section_item_${sectionIndex}_${i}`)
                        .val(itemId ? String(itemId) : '')
                        .selectpicker('refresh');
                });
            });
        });

        // ── Sync wire:ignore selectpickers to Livewire via wire:model attr ────
        $(document).on('change', '.selectpicker', function () {
            let wireModel = $(this).attr('wire:model');
            if (wireModel) {
                $wire.set(wireModel, $(this).val());
            }
        });

        // ── Header item change: fetch units from server ───────────────────────
        $(document).on('change', '#header_item', function () {
            $wire.dispatch('getHeaderUnits', { itemId: $(this).val() });
        });

        $wire.on('setHeaderUnits', function (params) {
            let headerUnits = params[0];
            if (typeof setOptions === 'function') {
                setOptions($('#header_unit'), headerUnits);
            }
        });

        // ── Section item change: load units for that row ──────────────────────
        $(document).on('change', '.section-item-select', function () {
            const sectionIndex = parseInt($(this).data('section'));
            const rowIndex     = parseInt($(this).data('row'));
            const val          = $(this).val();

            if (isNaN(sectionIndex) || isNaN(rowIndex)) return;

            $wire.call('onSectionItemChanged', sectionIndex, rowIndex, val ? parseInt(val) : null);
        });

        // ── After row removed from a side section: re-sync picker values ─────
        $wire.on('side-section-rows-removed', ({ sectionIndex, rows }) => {
            $nextTick(() => {
                rows.forEach(function (itemId, i) {
                    $(`#side_section_item_${sectionIndex}_${i}`)
                        .val(itemId ? String(itemId) : '')
                        .selectpicker('refresh');
                });
            });
        });

        // ── Side section item change: load units for that row ─────────────────
        $(document).on('change', '.side-section-item-select', function () {
            const sectionIndex = parseInt($(this).data('section'));
            const rowIndex     = parseInt($(this).data('row'));
            const val          = $(this).val();

            if (isNaN(sectionIndex) || isNaN(rowIndex)) return;

            $wire.call('onSideSectionItemChanged', sectionIndex, rowIndex, val ? parseInt(val) : null);
        });
    </script>
    @endscript
</div>