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
                                <input type="text"
                                       id="name"
                                       class="form-control"
                                       wire:model="name"
                                       placeholder="Enter recipe name">
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Recipe Type --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="recipe_type">
                                    Recipe Type <span class="text-danger">*</span>
                                </label>
                                <div wire:ignore>
                                    <select id="recipe_type"
                                            class="selectpicker w-100"
                                            title="Select Type"
                                            data-style="btn-default"
                                            data-icon-base="ti"
                                            data-tick-icon="ti-check text-white">
                                        <option value="1" @selected($recipe_type == 1)>Preparation</option>
                                        <option value="2" @selected($recipe_type == 2)>Production</option>
                                    </select>
                                </div>
                                @error('recipe_type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            @if($recipe_type)

                                {{-- Output Item --}}
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="header_item">
                                        {{ $recipe_type == 1 ? 'Semi-Finished Goods Item' : 'Finished Goods Item' }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div wire:ignore>
                                        <select id="header_item"
                                                class="selectpicker w-100"
                                                title="Select Item"
                                                data-style="btn-default"
                                                data-live-search="true"
                                                data-icon-base="ti"
                                                data-size="5"
                                                data-tick-icon="ti-check text-white">
                                            @foreach($headerItems as $item)
                                                <option value="{{ $item['id'] }}"
                                                    @selected($item_id == $item['id'])>
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
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="header_unit">
                                        Output Unit <span class="text-danger">*</span>
                                    </label>
                                    <select id="header_unit"
                                            class="selectpicker w-100"
                                            title="Select Unit"
                                            data-style="btn-default"
                                            data-live-search="true"
                                            data-icon-base="ti"
                                            data-size="5"
                                            data-tick-icon="ti-check text-white">
                                        @foreach($headerUnits as $unit)
                                            <option value="{{ $unit['id'] }}"
                                                @selected($item_unit_id == $unit['id'])>
                                                {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('item_unit_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Batch --}}
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="batch">
                                        Batch Size <span class="text-danger">*</span>
                                    </label>
                                    <input type="number"
                                           id="batch"
                                           class="form-control"
                                           wire:model="batch"
                                           min="1"
                                           placeholder="e.g. 100">
                                    @error('batch')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Batch Weight --}}
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="batch_weight">Batch Weight (kg)</label>
                                    <input type="text"
                                           id="batch_weight"
                                           class="form-control"
                                           wire:model="batch_weight"
                                           placeholder="Optional">
                                    @error('batch_weight')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Batch Volume --}}
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="batch_volume">Batch Volume (L)</label>
                                    <input type="text"
                                           id="batch_volume"
                                           class="form-control"
                                           wire:model="batch_volume"
                                           placeholder="Optional">
                                    @error('batch_volume')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Status --}}
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="status"
                                               wire:model="status">
                                        <label class="form-check-label" for="status">
                                            {{ $status ? 'Active' : 'Inactive' }}
                                        </label>
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <div class="col-12">
                                    <label class="form-label" for="notes">Notes</label>
                                    <textarea class="form-control"
                                              id="notes"
                                              wire:model="notes"
                                              rows="2"
                                              placeholder="Recipe notes (optional)"></textarea>
                                </div>

                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Card 2: Ingredients ─────────────────────────────────────────── --}}
                @if($recipe_type)
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                {{ $recipe_type == 1 ? 'Raw Materials' : 'Semi-Finished Goods' }}
                            </h5>
                            <button type="button" class="btn btn-success btn-sm" wire:click="addInputRow">
                                <i class="ti ti-plus me-1"></i>
                                Add {{ $recipe_type == 1 ? 'Raw Material' : 'Semi-Finished Item' }}
                            </button>
                        </div>
                        <div class="card-body">
                            @if(count($inputRows) > 0)
                                <div class="row g-3">
                                    @foreach($inputRows as $index => $row)
                                        <div class="col-12" wire:key="input-row-{{ $index }}">
                                            <div class="border rounded p-3">

                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <label class="form-label mb-0 fw-semibold">#{{ $index + 1 }}</label>
                                                    @if(count($inputRows) > 1)
                                                        <button type="button"
                                                                class="btn btn-danger btn-sm"
                                                                wire:click="removeInputRow({{ $index }})">
                                                            <i class="ti ti-trash me-1"></i> Remove
                                                        </button>
                                                    @endif
                                                </div>

                                                <div class="row g-3 align-items-end">

                                                    {{-- Item --}}
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label" for="input_item_{{ $index }}">
                                                            Item <span class="text-danger">*</span>
                                                        </label>
                                                        <div wire:ignore>
                                                            <select id="input_item_{{ $index }}"
                                                                    class="selectpicker w-100"
                                                                    title="Select Item"
                                                                    data-style="btn-default"
                                                                    data-live-search="true"
                                                                    data-icon-base="ti"
                                                                    data-size="5"
                                                                    data-tick-icon="ti-check text-white"
                                                                    data-index="{{ $index }}">
                                                                @foreach($inputItems as $item)
                                                                    <option value="{{ $item['id'] }}"
                                                                        @selected($row['item_id'] == $item['id'])>
                                                                        {{ $item['name'] }} ({{ $item['code'] ?? '' }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        @error('inputRows.' . $index . '.item_id')
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    {{-- Unit --}}
                                                    <div class="col-12 col-md-3">
                                                        <label class="form-label" for="input_unit_{{ $index }}">
                                                            Unit <span class="text-danger">*</span>
                                                        </label>
                                                        <select id="input_unit_{{ $index }}"
                                                                class="selectpicker w-100"
                                                                title="Select Unit"
                                                                data-style="btn-default"
                                                                data-live-search="true"
                                                                data-icon-base="ti"
                                                                data-size="5"
                                                                data-tick-icon="ti-check text-white"
                                                                wire:model="inputRows.{{ $index }}.item_unit_id"
                                                                data-index="{{ $index }}">
                                                            @foreach($inputRowUnits[$index] ?? [] as $unit)
                                                                <option value="{{ $unit['id'] }}"
                                                                    @selected($row['item_unit_id'] == $unit['id'])>
                                                                    {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('inputRows.' . $index . '.item_unit_id')
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    {{-- Quantity --}}
                                                    <div class="col-12 col-md-3">
                                                        <label class="form-label" for="input_qty_{{ $index }}">
                                                            Quantity <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text"
                                                               wire:model="inputRows.{{ $index }}.quantity"
                                                               id="input_qty_{{ $index }}"
                                                               class="form-control"
                                                               placeholder="Enter quantity">
                                                        @error('inputRows.' . $index . '.quantity')
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    {{-- Notes --}}
                                                    <div class="col-12 col-md-2">
                                                        <label class="form-label" for="input_notes_{{ $index }}">Notes</label>
                                                        <input type="text"
                                                               wire:model="inputRows.{{ $index }}.notes"
                                                               id="input_notes_{{ $index }}"
                                                               class="form-control"
                                                               placeholder="Optional">
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

                    {{-- ── Card 3: Packaging (production only) ────────────────────────── --}}
                    @if($recipe_type == 2)
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Packaging</h5>
                                <button type="button" class="btn btn-success btn-sm" wire:click="addPackagingRow">
                                    <i class="ti ti-plus me-1"></i> Add Packaging Item
                                </button>
                            </div>
                            <div class="card-body">
                                @if(count($packagingRows) > 0)
                                    <div class="row g-3">
                                        @foreach($packagingRows as $index => $row)
                                            <div class="col-12" wire:key="packaging-row-{{ $index }}">
                                                <div class="border rounded p-3">

                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <label class="form-label mb-0 fw-semibold">Packaging #{{ $index + 1 }}</label>
                                                        @if(count($packagingRows) > 1)
                                                            <button type="button"
                                                                    class="btn btn-danger btn-sm"
                                                                    wire:click="removePackagingRow({{ $index }})">
                                                                <i class="ti ti-trash me-1"></i> Remove
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <div class="row g-3 align-items-end">

                                                        {{-- Item --}}
                                                        <div class="col-12 col-md-4">
                                                            <label class="form-label" for="pkg_item_{{ $index }}">
                                                                Item <span class="text-danger">*</span>
                                                            </label>
                                                            <div wire:ignore>
                                                                <select id="pkg_item_{{ $index }}"
                                                                        class="selectpicker w-100"
                                                                        title="Select Item"
                                                                        data-style="btn-default"
                                                                        data-live-search="true"
                                                                        data-icon-base="ti"
                                                                        data-size="5"
                                                                        data-tick-icon="ti-check text-white"
                                                                        data-index="{{ $index }}">
                                                                    @foreach($packagingItems as $item)
                                                                        <option value="{{ $item['id'] }}"
                                                                            @selected($row['item_id'] == $item['id'])>
                                                                            {{ $item['name'] }} ({{ $item['code'] ?? '' }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            @error('packagingRows.' . $index . '.item_id')
                                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                                            @enderror
                                                        </div>

                                                        {{-- Unit --}}
                                                        <div class="col-12 col-md-3">
                                                            <label class="form-label" for="pkg_unit_{{ $index }}">
                                                                Unit <span class="text-danger">*</span>
                                                            </label>
                                                            <select id="pkg_unit_{{ $index }}"
                                                                    class="selectpicker w-100"
                                                                    title="Select Unit"
                                                                    data-style="btn-default"
                                                                    data-live-search="true"
                                                                    data-icon-base="ti"
                                                                    data-size="5"
                                                                    data-tick-icon="ti-check text-white"
                                                                    wire:model="packagingRows.{{ $index }}.item_unit_id"
                                                                    data-index="{{ $index }}">
                                                                @foreach($packagingRowUnits[$index] ?? [] as $unit)
                                                                    <option value="{{ $unit['id'] }}"
                                                                        @selected($row['item_unit_id'] == $unit['id'])>
                                                                        {{ $unit['name'] }} ({{ $unit['symbol'] ?? '' }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            @error('packagingRows.' . $index . '.item_unit_id')
                                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                                            @enderror
                                                        </div>

                                                        {{-- Quantity --}}
                                                        <div class="col-12 col-md-3">
                                                            <label class="form-label" for="pkg_qty_{{ $index }}">
                                                                Quantity <span class="text-danger">*</span>
                                                            </label>
                                                            <input type="text"
                                                                   wire:model="packagingRows.{{ $index }}.quantity"
                                                                   id="pkg_qty_{{ $index }}"
                                                                   class="form-control"
                                                                   placeholder="Enter quantity">
                                                            @error('packagingRows.' . $index . '.quantity')
                                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                                            @enderror
                                                        </div>

                                                        {{-- Notes --}}
                                                        <div class="col-12 col-md-2">
                                                            <label class="form-label" for="pkg_notes_{{ $index }}">Notes</label>
                                                            <input type="text"
                                                                   wire:model="packagingRows.{{ $index }}.notes"
                                                                   id="pkg_notes_{{ $index }}"
                                                                   class="form-control"
                                                                   placeholder="Optional">
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <p class="text-muted mb-0">No packaging items added yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Submit --}}
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

        // ── New DOM nodes (new rows added by Livewire) ────────────────────────
        // morph.added only fires for genuinely new nodes — no double-init risk
        Livewire.hook('morph.added', ({ el }) => {
            $(el).find('[id^="input_item_"], [id^="pkg_item_"], [id^="input_unit_"], [id^="pkg_unit_"]').selectpicker();
        });

        // ── After Livewire round-trip: re-init unit pickers only ──────────────
        // Unit pickers have no wire:ignore — Livewire re-renders their options
        // Item pickers have wire:ignore — handled separately via events
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                $nextTick(() => {
                    $('[id^="input_unit_"], [id^="pkg_unit_"], #header_unit').each(function () {
                        $(this).selectpicker('destroy').selectpicker();
                    });
                });
            });
        });

        // ── Recipe type change: rebuild row item pickers with new options ──────
        $wire.on('input-items-updated', ({ items }) => {
            $nextTick(() => {
                $('[id^="input_item_"]').each(function () {
                    setOptions($(this), items);
                });
            });
        });

        $wire.on('packaging-items-updated', ({ items }) => {
            $nextTick(() => {
                $('[id^="pkg_item_"]').each(function () {
                    setOptions($(this), items);
                });
            });
        });

        // ── After row removed: re-sync picker values to new index order ────────
        $wire.on('rows-removed', ({ rows, type }) => {
            $nextTick(() => {
                const prefix = type === 'input' ? 'input_item_' : 'pkg_item_';
                rows.forEach(function (itemId, i) {
                    $('#' + prefix + i).val(itemId ? String(itemId) : '').selectpicker('refresh');
                });
            });
        });

        // ── Sync all wire:ignore pickers to Livewire ──────────────────────────
        $(document).on('changed.bs.select', '.selectpicker', function () {
            const id    = $(this).attr('id');
            const val   = $(this).val();
            const index = parseInt($(this).data('index'));

            // Recipe type → triggers updatedRecipeType()
            if (id === 'recipe_type') {
                $wire.set('recipe_type', val ? parseInt(val) : null).then(() => {
                    setOptions($('#header_item'), []);
                    setOptions($('#header_unit'), []);
                });
                return;
            }

            // Header item → triggers updatedItemId()
            if (id === 'header_item') {
                $wire.set('item_id', val ? parseInt(val) : null);
                return;
            }

            // Header unit
            if (id === 'header_unit') {
                $wire.set('item_unit_id', val ? parseInt(val) : null);
                return;
            }

            // Input row item pickers → load units via dedicated method
            if (id.startsWith('input_item_') && !isNaN(index)) {
                $wire.call('onInputItemChanged', index, val ? parseInt(val) : null);
                return;
            }

            // Packaging row item pickers
            if (id.startsWith('pkg_item_') && !isNaN(index)) {
                $wire.call('onPackagingItemChanged', index, val ? parseInt(val) : null);
                return;
            }
        });
    </script>
    @endscript
</div>