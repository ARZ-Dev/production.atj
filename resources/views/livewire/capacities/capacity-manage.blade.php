<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">
                            Manage Capacity — {{ $modelName }}
                        </h5>
                        <small class="text-muted text-capitalize">{{ $modelType }}</small>
                    </div>
                    <a href="{{ $modelType === 'preparation' ? route('preparations') : route('lines') }}"
                        class="btn btn-light-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>

                <div class="card-body">

                    {{-- Add Item Type --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Add Item Type</label>
                        @if(empty($this->availableItemTypes()))
                        <p class="text-muted small mb-0">All item types have been added.</p>
                        @else
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($this->availableItemTypes() as $type)
                            <button type="button" wire:click="addItemType({{ $type['id'] }})"
                                class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> {{ $type['name'] }}
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    {{-- Item type sections --}}
                    @if(empty($sections))
                    <div class="alert alert-secondary">
                        <i class="bi bi-arrow-up-circle me-2"></i>
                        Add an item type above to set capacity values.
                    </div>
                    @else
                    <form wire:submit.prevent="save">
                        @foreach($sections as $index => $section)
                        <div class="card mb-3" wire:key="cap-section-{{ $section['item_type_id'] }}">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <h6 class="fw-semibold mb-0 text-capitalize">{{ $section['item_type_name'] }}</h6>
                                <button type="button" class="btn btn-light-danger icon-btn-sm" wire:click="removeItemType({{ $index }})" title="Remove">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="card-body">

                            @if(!empty($section['items']))
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:25%">Item</th>
                                            <th style="width:75%">Output / Hr — enter in any unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($section['items'] as $item)
                                        @php
                                            $units      = $itemUnits[$item['id']] ?? [];
                                            $row        = $section['capacityRows'][$item['id']] ?? ['unit_id' => null, 'value' => ''];
                                            $basicValue = $row['value'] !== '' ? (float) $row['value'] : null;
                                        @endphp
                                        <tr class="cap-item-row" data-units="{{ json_encode($units) }}">
                                            <td class="align-middle">{{ $item['name'] }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($units as $unit)
                                                    @php
                                                        $isBasic = !empty($unit['basic']);
                                                        $formula = (float) ($unit['formula'] ?? 1) ?: 1;
                                                        $display = $basicValue === null ? '' : ($isBasic ? $basicValue : $basicValue * $formula);
                                                    @endphp
                                                    <div style="min-width:120px;">
                                                        <input
                                                            type="number"
                                                            step="0.0001"
                                                            min="0"
                                                            class="form-control form-control-sm cap-unit-value"
                                                            data-unit-id="{{ $unit['id'] }}"
                                                            data-basic="{{ $isBasic ? 1 : 0 }}"
                                                            data-formula="{{ $formula }}"
                                                            value="{{ $display === '' ? '' : round($display, 4) }}"
                                                            placeholder="0.0000"
                                                        />
                                                        <small class="text-muted">{{ $unit['symbol'] ?? $unit['name'] }}{{ $isBasic ? ' (basic)' : '' }}</small>
                                                    </div>
                                                    @endforeach
                                                </div>
                                                <input type="hidden" class="cap-basic-value"
                                                    wire:model="sections.{{ $index }}.capacityRows.{{ $item['id'] }}.value">
                                                <input type="hidden" class="cap-unit-id-value"
                                                    wire:model="sections.{{ $index }}.capacityRows.{{ $item['id'] }}.unit_id">
                                                @error('sections.'.$index.'.capacityRows.'.$item['id'].'.value')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No items found for this type.
                            </div>
                            @endif
                            </div>
                        </div>
                        @endforeach

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="bi bi-check-circle me-1"></i> Save Capacities
                            </button>
                        </div>
                    </form>
                    @endif

                </div>
            </div>
        </div>
    </div>

    @script
    <script>
    (() => {
        // ── Capacity unit conversion ──────────────────────────────────────────
        // All units for an item are always shown and editable. Whichever input
        // the user types into becomes the source: its value is converted to the
        // basic unit, then every other unit's input is recalculated from that.
        // basic_capacity = source unit's basic flag ? entered value : entered value / formula
        // display(unit)  = unit's basic flag ? basic_capacity : basic_capacity * formula
        const recalcCapacityRow = (row, sourceInput) => {
            const units = JSON.parse(row.dataset.units || '[]');
            if (!units.length) return;

            const hiddenValue  = row.querySelector('.cap-basic-value');
            const hiddenUnitId = row.querySelector('.cap-unit-id-value');
            const inputs       = row.querySelectorAll('.cap-unit-value');

            const sourceId   = parseInt(sourceInput.dataset.unitId);
            const sourceUnit = units.find(u => u.id === sourceId);
            if (!sourceUnit) return;

            const entered = parseFloat(sourceInput.value);

            let basic;
            if (isNaN(entered)) {
                basic = parseFloat(hiddenValue.value) || 0;
            } else {
                const formula = parseFloat(sourceUnit.formula || 1) || 1;
                basic = sourceUnit.basic ? entered : entered / formula;
            }

            inputs.forEach(input => {
                if (input === sourceInput) return;

                const unitId = parseInt(input.dataset.unitId);
                const unit   = units.find(u => u.id === unitId);
                if (!unit) return;

                const formula = parseFloat(unit.formula || 1) || 1;
                const display = unit.basic ? basic : basic * formula;
                input.value = isFinite(display) ? Math.round(display * 10000) / 10000 : '';
            });

            if (parseFloat(hiddenValue.value) !== basic) {
                hiddenValue.value = basic;
                hiddenValue.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (parseInt(hiddenUnitId.value) !== sourceId) {
                hiddenUnitId.value = sourceId;
                hiddenUnitId.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        const initCapacityRows = (root) => {
            root.querySelectorAll('.cap-item-row').forEach(row => {
                if (row.dataset.capInit) return;
                row.dataset.capInit = '1';

                row.querySelectorAll('.cap-unit-value').forEach(input => {
                    input.addEventListener('input', () => recalcCapacityRow(row, input));
                });
            });
        };

        initCapacityRows(document);

        Livewire.hook('morph.added', ({ el }) => {
            if (el.nodeType === 1) initCapacityRows(el);
        });
    })();
    </script>
    @endscript
</div>
