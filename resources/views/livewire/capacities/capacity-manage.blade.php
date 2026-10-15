<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form wire:submit.prevent="save">

        {{-- Capacity Card --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0">Capacity — {{ $modelName }}</h5>
                    <small class="text-muted">Output per hour this {{ $modelType }} can produce — one figure per item, entered in any of its units.</small>
                </div>
                <a href="{{ $modelType === 'preparation' ? route('preparations') : route('lines') }}"
                    class="btn btn-light-light text-muted">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body">

                @if(!$catalogLoaded)
                <div class="alert alert-warning d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div>Items or the {{ $outputWarehouseLabel }} couldn't be loaded from the auth service. Capacities can't be edited until it's reachable — reload the page to try again.</div>
                </div>
                @endif

                <div class="row g-3 align-items-end mb-3">
                    <div class="col-lg-8">
                        <label for="cap_item_type_ids" class="form-label">Item Types</label>
                        <div wire:ignore>
                            <select id="cap_item_type_ids" multiple
                                class="selectpicker w-100"
                                title="Select the item types to set capacities for…"
                                data-style="btn-default"
                                data-live-search="true"
                                data-actions-box="true"
                                data-selected-text-format="count > 3"
                                data-size="8">
                                @foreach($itemTypes as $type)
                                <option value="{{ $type['id'] }}"
                                    data-subtext="{{ $type['item_count'] }} {{ \Illuminate\Support\Str::plural('item', $type['item_count']) }}{{ ($type['in_warehouse'] ?? true) ? '' : ' · not held by ' . ($outputWarehouseName ?? 'the warehouse') }}"
                                    @selected(in_array($type['id'], $item_type_ids, true))>
                                    {{ $type['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('item_type_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @if($outputWarehouseId)
                        <div class="form-text">
                            <i class="bi bi-funnel me-1"></i>Showing the item types held by <strong>{{ $outputWarehouseName }}</strong> — the {{ $outputWarehouseLabel }} this {{ $modelType }} produces into.
                        </div>
                        @else
                        <div class="form-text text-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>No {{ $outputWarehouseLabel }} is set on this {{ $modelType }}, so there are no item types to offer. Set it on the {{ $modelType }} first.
                        </div>
                        @endif
                    </div>

                    <div class="col-lg-4" wire:ignore>
                        <label for="cap_item_filter" class="form-label">Find Item</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="search" id="cap_item_filter" class="form-control"
                                placeholder="Filter by item name or code…" autocomplete="off">
                        </div>
                    </div>
                </div>

                @if(empty($groups))
                @if($storedCount > 0 && $catalogLoaded)
                {{-- Save is always available, so say what it does with nothing ticked. --}}
                <div class="alert alert-warning d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div>No item types are selected — saving now clears all {{ $storedCount }} {{ \Illuminate\Support\Str::plural('capacity', $storedCount) }} set for this {{ $modelType }}.</div>
                </div>
                @endif
                <div class="pv-empty">
                    <i class="bi bi-speedometer2"></i>
                    <p class="mb-0 text-muted">Select one or more item types above to list their items and units.</p>
                </div>
                @else
                <div class="table-responsive border rounded">
                    <table class="table table-sm align-middle mb-0 aqt-table pk-cap-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="width: 34%;">Unit</th>
                                <th style="width: 190px;">Capacity / Hour</th>
                            </tr>
                        </thead>
                        @foreach($groups as $typeId => $group)
                        <tbody class="pk-group" wire:key="cap-group-{{ $typeId }}">
                            <tr class="aqt-group-row">
                                <td colspan="3" class="aqt-group-title">
                                    <i class="bi bi-tag-fill me-1"></i>{{ $group['name'] }}
                                    <span class="aqt-group-count">{{ count($group['items']) }}</span>
                                    @unless($group['in_warehouse'])
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" title="Kept because capacities are already set for it — untick it and save to clear them">
                                        Not held by {{ $outputWarehouseName ?? 'the ' . $outputWarehouseLabel }}
                                    </span>
                                    @endunless
                                </td>
                            </tr>
                            @forelse($group['items'] as $item)
                                @php
                                    $units  = $item['units'];
                                    $basic  = collect($units)->firstWhere('basic', true);
                                    $search = trim($item['name'] . ' ' . ($item['code'] ?? ''));
                                    $stored = $capacities[$item['id']] ?? '';
                                    $hasErr = $errors->has('capacities.' . $item['id']);
                                @endphp
                                @if(empty($units))
                                <tr class="pk-unit-row" data-pk-name="{{ $search }}" wire:key="cap-item-{{ $item['id'] }}-no-units">
                                    <td class="pk-item">
                                        <div class="pk-item-name">{{ $item['name'] }}</div>
                                        @if($item['code'])<div class="pk-item-code">{{ $item['code'] }}</div>@endif
                                    </td>
                                    <td colspan="2" class="text-muted small">
                                        <i class="bi bi-info-circle me-1"></i>No units are defined for this item.
                                    </td>
                                </tr>
                                @else
                                @foreach($units as $unit)
                                @php
                                    // formula = basic units in 1 of this unit (the basic unit's own is 1),
                                    // so the stored basic figure ÷ formula is this unit's rate.
                                    $formula = $unit['formula'] > 0 ? $unit['formula'] : 1;
                                    $display = $stored === '' || !is_numeric($stored) ? '' : format_quantity(round((float) $stored / $formula, 4));
                                @endphp
                                <tr class="pk-unit-row {{ $loop->last ? '' : 'pk-unit-row--more' }}"
                                    data-pk-name="{{ $search }}" wire:key="cap-unit-{{ $unit['id'] }}">
                                    @if($loop->first)
                                    <td class="pk-item" rowspan="{{ count($units) }}">
                                        <div class="pk-item-name">{{ $item['name'] }}</div>
                                        @if($item['code'])<div class="pk-item-code">{{ $item['code'] }}</div>@endif
                                        {{-- The only bound fields: the basic figure and the unit it was entered in. --}}
                                        <input type="hidden" class="cap-basic" data-cap-item="{{ $item['id'] }}"
                                            wire:model="capacities.{{ $item['id'] }}">
                                        <input type="hidden" class="cap-unit" data-cap-item="{{ $item['id'] }}"
                                            wire:model="capacityUnits.{{ $item['id'] }}">
                                        @error('capacities.' . $item['id'])
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    @endif
                                    <td>
                                        {{ $unit['name'] }}
                                        @if($unit['symbol'] !== '' && strcasecmp($unit['symbol'], $unit['name']) !== 0)
                                            <span class="text-muted">({{ $unit['symbol'] }})</span>
                                        @endif
                                        @if($unit['basic'])
                                            <span class="pk-unit-basic">basic</span>
                                        @elseif($basic && $unit['formula'] > 0)
                                            <div class="pk-unit-math">
                                                1 {{ $unit['symbol'] ?: $unit['name'] }} = {{ format_quantity($unit['formula']) }} {{ $basic['symbol'] ?: $basic['name'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal"
                                            class="form-control form-control-sm pk-cap-input cap-unit-input {{ $hasErr ? 'is-invalid' : '' }}"
                                            data-cap-item="{{ $item['id'] }}"
                                            data-unit-id="{{ $unit['id'] }}"
                                            data-formula="{{ $formula }}"
                                            value="{{ $display }}"
                                            placeholder="—">
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            @empty
                            <tr>
                                <td colspan="3" class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i>No active items in this item type.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @endforeach
                    </table>
                </div>
                <div class="form-text">
                    Type the capacity in any unit — the item's other units follow. Leave it blank (or 0) if this {{ $modelType }} doesn't produce the item; only items with a capacity are saved.
                </div>
                @endif

            </div>
        </div>

        {{-- Submit --}}
        <div class="text-end mb-2">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save"
                @disabled(!$catalogLoaded)>
                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                <i class="bi bi-check-lg me-1" wire:loading.remove wire:target="save"></i>
                Save Capacities
            </button>
        </div>

    </form>

    @script
    <script>
        $('#cap_item_type_ids').selectpicker();

        // ── Capacity inputs: Cleave thousands formatting ─────────────────────
        // The guard is a JS expando rather than a data attribute: morph would strip
        // an attribute the server didn't render, and Cleave would then stack.
        const initCapacityInputs = (root) => {
            if (!root || root.nodeType !== 1) return;
            const inputs = root.matches('.cap-unit-input') ? [root] : root.querySelectorAll('.cap-unit-input');
            inputs.forEach(el => {
                if (el._capCleave) return;
                el._capCleave = new Cleave(el, {
                    numeral: true,
                    numeralThousandsGroupStyle: 'thousand',
                    numeralPositiveOnly: true,
                    numeralDecimalScale: 4,
                });
            });
        };
        initCapacityInputs(document.body);
        Livewire.hook('morph.added', ({ el }) => initCapacityInputs(el));

        // ── Linked units ─────────────────────────────────────────────────────
        // One figure per item, kept in its basic unit. Whichever unit is typed in
        // becomes the source: basic = entered × its formula, and every other unit
        // shows basic ÷ its formula. Only the hidden basic value and the source
        // unit are bound (deferred), so nothing round-trips while typing.
        const roundTo = (value, digits) => Math.round(value * 10 ** digits) / 10 ** digits;
        const rawOf   = (el) => el._capCleave ? el._capCleave.getRawValue() : el.value.replace(/,/g, '');
        const writeTo = (el, value) => el._capCleave ? el._capCleave.setRawValue(value) : (el.value = value);
        const setBound = (selector, value) => {
            const el = document.querySelector(selector);
            if (!el || el.value === value) return;
            el.value = value;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        };

        $(document).on('input', '.cap-unit-input', function () {
            const itemId  = this.dataset.capItem;
            const raw     = rawOf(this).trim();
            const entered = raw === '' ? NaN : parseFloat(raw);
            const basic   = isNaN(entered) ? null : entered * (parseFloat(this.dataset.formula) || 1);

            document.querySelectorAll(`.cap-unit-input[data-cap-item="${itemId}"]`).forEach(other => {
                if (other === this) return;
                const formula = parseFloat(other.dataset.formula) || 1;
                writeTo(other, basic === null ? '' : String(roundTo(basic / formula, 4)));
            });

            setBound(`.cap-basic[data-cap-item="${itemId}"]`, basic === null ? '' : String(roundTo(basic, 6)));
            setBound(`.cap-unit[data-cap-item="${itemId}"]`, this.dataset.unitId);
        });

        // ── Item filter ──────────────────────────────────────────────────────
        // Applied as a stylesheet rule rather than inline styles, so a Livewire
        // re-render (which would strip inline display:none) can't undo it.
        const filterStyle = document.createElement('style');
        document.head.appendChild(filterStyle);
        $(document).on('input', '#cap_item_filter', function () {
            const q = this.value.trim();
            if (q === '') {
                filterStyle.textContent = '';
                return;
            }
            const needle = '"' + q.replace(/["\\]/g, '\\$&') + '"';
            filterStyle.textContent =
                `.pk-cap-table tr.pk-unit-row:not([data-pk-name*=${needle} i]) { display: none; }` +
                `.pk-cap-table tbody.pk-group:not(:has(tr.pk-unit-row[data-pk-name*=${needle} i])) { display: none; }`;
        });

        // ── Selectpicker → Livewire ──────────────────────────────────────────
        $(document).on('change', '#cap_item_type_ids', function () {
            $wire.call('setItemTypes', ($(this).val() || []).map(Number));
        });
    </script>
    @endscript
</div>
