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
                    <h5 class="mb-0">Capacity — {{ $packagingName }}</h5>
                    <small class="text-muted">Units per hour this packaging can handle, set per item unit.</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('packagings.edit', $packagingId) }}" class="btn btn-light-primary">
                        <i class="bi bi-pencil-square me-1"></i>Edit Details
                    </a>
                    <a href="{{ route('packagings') }}" class="btn btn-light-light text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>
            <div class="card-body">

                @if(!$catalogLoaded)
                <div class="alert alert-warning d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div>Items couldn't be loaded from the auth service. Capacities can't be edited until it's reachable — reload the page to try again.</div>
                </div>
                @endif

                <div class="row g-3 align-items-end mb-3">
                    <div class="col-lg-8">
                        <label for="pk_item_type_ids" class="form-label">Item Types</label>
                        <div wire:ignore>
                            <select id="pk_item_type_ids" multiple
                                class="selectpicker w-100"
                                title="Select the item types to set capacities for…"
                                data-style="btn-default"
                                data-live-search="true"
                                data-actions-box="true"
                                data-selected-text-format="count > 3"
                                data-size="8">
                                @foreach($itemTypes as $type)
                                <option value="{{ $type['id'] }}"
                                    data-subtext="{{ $type['item_count'] }} {{ \Illuminate\Support\Str::plural('item', $type['item_count']) }}"
                                    @selected(in_array($type['id'], $item_type_ids, true))>
                                    {{ $type['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('item_type_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4" wire:ignore>
                        <label for="pk_item_filter" class="form-label">Find Item</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="search" id="pk_item_filter" class="form-control"
                                placeholder="Filter by item name or code…" autocomplete="off">
                        </div>
                    </div>
                </div>

                @if(empty($groups))
                <div class="pv-empty">
                    <i class="bi bi-box-seam"></i>
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
                        <tbody class="pk-group" wire:key="pk-group-{{ $typeId }}">
                            <tr class="aqt-group-row">
                                <td colspan="3" class="aqt-group-title">
                                    <i class="bi bi-tag-fill me-1"></i>{{ $group['name'] }}
                                    <span class="aqt-group-count">{{ count($group['items']) }}</span>
                                </td>
                            </tr>
                            @forelse($group['items'] as $item)
                                @php
                                    $units  = $item['units'];
                                    $basic  = collect($units)->firstWhere('basic', true);
                                    $search = trim($item['name'] . ' ' . ($item['code'] ?? ''));
                                @endphp
                                @if(empty($units))
                                <tr class="pk-unit-row" data-pk-name="{{ $search }}" wire:key="pk-item-{{ $item['id'] }}-no-units">
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
                                <tr class="pk-unit-row {{ $loop->last ? '' : 'pk-unit-row--more' }}"
                                    data-pk-name="{{ $search }}" wire:key="pk-unit-{{ $unit['id'] }}">
                                    @if($loop->first)
                                    <td class="pk-item" rowspan="{{ count($units) }}">
                                        <div class="pk-item-name">{{ $item['name'] }}</div>
                                        @if($item['code'])<div class="pk-item-code">{{ $item['code'] }}</div>@endif
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
                                            class="form-control form-control-sm pk-cap-input @error('capacities.' . $unit['id']) is-invalid @enderror"
                                            wire:model="capacities.{{ $unit['id'] }}"
                                            placeholder="—">
                                        @error('capacities.' . $unit['id'])
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
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
                    Leave a unit blank (or 0) if this packaging doesn't handle it — only units with a capacity are saved.
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
        $('#pk_item_type_ids').selectpicker();

        // ── Capacity inputs: Cleave thousands formatting ─────────────────────
        // The guard is a JS expando rather than a data attribute: morph would strip
        // an attribute the server didn't render, and Cleave would then stack.
        const initCapacityInputs = (root) => {
            if (!root || root.nodeType !== 1) return;
            const inputs = root.matches('.pk-cap-input') ? [root] : root.querySelectorAll('.pk-cap-input');
            inputs.forEach(el => {
                if (el._pkCleave) return;
                el._pkCleave = new Cleave(el, {
                    numeral: true,
                    numeralThousandsGroupStyle: 'thousand',
                    numeralPositiveOnly: true,
                    numeralDecimalScale: 2,
                });
            });
        };
        initCapacityInputs(document.body);
        Livewire.hook('morph.added', ({ el }) => initCapacityInputs(el));

        // ── Item filter ──────────────────────────────────────────────────────
        // Applied as a stylesheet rule rather than inline styles, so a Livewire
        // re-render (which would strip inline display:none) can't undo it.
        const filterStyle = document.createElement('style');
        document.head.appendChild(filterStyle);
        $(document).on('input', '#pk_item_filter', function () {
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
        $(document).on('change', '#pk_item_type_ids', function () {
            $wire.call('setItemTypes', ($(this).val() || []).map(Number));
        });
    </script>
    @endscript
</div>
