<div>
    <form wire:submit.prevent="submit">

        {{-- Main Info Card --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $editing ? 'Edit' : 'Add' }} Packaging</h5>
                <a href="{{ route('packagings') }}" class="btn btn-light-light text-muted">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-lg-4">
                        <label for="pk_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="pk_name"
                            class="form-control @error('name') is-invalid @enderror"
                            wire:model="name"
                            placeholder="e.g. Packaging Line 1">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="pk_department_id" class="form-label">Department <span class="text-danger">*</span></label>
                        <div wire:ignore>
                            <select id="pk_department_id"
                                class="selectpicker w-100"
                                title="Select department…"
                                data-style="btn-default"
                                data-live-search="true"
                                data-size="6">
                                @foreach($departments as $dept)
                                <option value="{{ $dept['id'] }}" @selected($department_id == $dept['id'])>
                                    {{ $dept['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('department_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="pk_factory_id" class="form-label">Factory <span class="text-danger">*</span></label>
                        <div wire:ignore>
                            <select id="pk_factory_id"
                                class="selectpicker w-100"
                                title="Select factory warehouse…"
                                data-style="btn-default"
                                data-live-search="true"
                                data-size="6">
                                @foreach($factories as $factory)
                                <option value="{{ $factory['id'] }}" @selected($factory_id == $factory['id'])>
                                    {{ $factory['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('factory_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Board Setup Card: what can run on this packaging's plan-board lane, and where its stock moves --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Board Setup</h5>
                <small class="text-muted">Which events can be placed on this packaging in the plan board, and the warehouses they consume from and deliver into.</small>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label for="pk_source_warehouse_ids" class="form-label">Consume From Warehouses <span class="text-danger">*</span></label>
                        <div wire:ignore>
                            <select id="pk_source_warehouse_ids" multiple
                                class="selectpicker w-100"
                                title="Select warehouses…"
                                data-style="btn-default"
                                data-live-search="true"
                                data-actions-box="true"
                                data-selected-text-format="count > 2"
                                data-size="6">
                                @foreach($departmentWarehouses as $warehouse)
                                <option value="{{ $warehouse['id'] }}" @selected(in_array($warehouse['id'], $source_warehouse_ids))>
                                    {{ $warehouse['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text">Items are consumed from the first one unless an item type is routed elsewhere below.</div>
                        @error('source_warehouse_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('source_warehouse_ids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="pk_fg_warehouse_id" class="form-label">Finished Goods Warehouse <span class="text-danger">*</span></label>
                        <div wire:ignore>
                            <select id="pk_fg_warehouse_id"
                                class="selectpicker w-100"
                                title="Select warehouse…"
                                data-style="btn-default"
                                data-live-search="true"
                                data-size="6">
                                @foreach($departmentInternalWarehouses as $warehouse)
                                <option value="{{ $warehouse['id'] }}" @selected($fg_warehouse_id == $warehouse['id'])>
                                    {{ $warehouse['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text">Packed items are delivered here when an event ends.</div>
                        @error('fg_warehouse_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="pk_event_types" class="form-label">Allowed Event Types <span class="text-danger">*</span></label>
                        <div wire:ignore>
                            <select id="pk_event_types" multiple
                                class="selectpicker w-100"
                                title="Select event types…"
                                data-style="btn-default"
                                data-live-search="true"
                                data-actions-box="true"
                                data-size="8">
                                @foreach($eventTypes as $eventType)
                                <option value="{{ $eventType['id'] }}" @selected(in_array($eventType['id'], $selectedEventTypes, true))>
                                    {{ $eventType['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text">Only these event types can be dropped on this packaging's lane.</div>
                        @error('selectedEventTypes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        @include('livewire.partials.item-type-warehouse-routing', [
                            'groups'             => $itemTypeRoutingGroups,
                            'sourceWarehouseIds' => $source_warehouse_ids,
                            'warehouseOptions'   => $departmentWarehouses,
                        ])
                    </div>

                </div>
            </div>
        </div>

        {{-- Capacity Card --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Capacity</h5>
                <small class="text-muted">Units per hour this packaging can handle, set per item unit.</small>
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
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submit"
                @disabled(!$catalogLoaded)>
                <span wire:loading wire:target="submit" class="spinner-border spinner-border-sm me-1"></span>
                <i class="bi bi-check-lg me-1" wire:loading.remove wire:target="submit"></i>
                {{ $editing ? 'Update' : 'Create' }} Packaging
            </button>
        </div>

    </form>

    @script
    <script>
        $('#pk_department_id, #pk_factory_id, #pk_source_warehouse_ids, #pk_fg_warehouse_id, #pk_event_types, #pk_item_type_ids').selectpicker();

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

        // ── Selectpickers → Livewire ─────────────────────────────────────────
        $(document).on('change', '#pk_department_id', function () {
            $wire.call('onDepartmentChange', parseInt($(this).val()) || null);
        });
        $(document).on('change', '#pk_factory_id', function () {
            $wire.$set('factory_id', parseInt($(this).val()) || null, false);
        });
        $(document).on('change', '#pk_item_type_ids', function () {
            $wire.call('setItemTypes', ($(this).val() || []).map(Number));
        });
        // Live: the routing table's "Consume From" options follow this list.
        $(document).on('change', '#pk_source_warehouse_ids', function () {
            $wire.set('source_warehouse_ids', ($(this).val() || []).map(Number));
        });
        $(document).on('change', '#pk_fg_warehouse_id', function () {
            $wire.$set('fg_warehouse_id', parseInt($(this).val()) || null, false);
        });
        // Rebuilds the item-type routing table for the newly selected types.
        $(document).on('change', '#pk_event_types', function () {
            $wire.call('onEventTypesChange', ($(this).val() || []).map(Number));
        });

        // A new department replaces every warehouse picker's options and clears
        // their selections (the server has already reset the values).
        $wire.on('packagingDepartmentLoaded', ({ factories, warehouses, internalWarehouses }) => {
            setOptions($('#pk_factory_id'), factories || []);
            setOptions($('#pk_source_warehouse_ids'), warehouses || []);
            setOptions($('#pk_fg_warehouse_id'), internalWarehouses || []);
        });
    </script>
    @endscript
</div>
