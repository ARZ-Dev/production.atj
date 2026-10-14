<div>
    <form wire:submit.prevent="submit">

        {{-- Main Info Card --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $editing ? 'Edit' : 'Add' }} Packaging</h5>
                <div class="d-flex gap-2">
                    @if($editing)
                    <a href="{{ route('packagings.capacity', $packagingId) }}" class="btn btn-light-success">
                        <i class="bi bi-speedometer2 me-1"></i>Capacity
                    </a>
                    @endif
                    <a href="{{ route('packagings') }}" class="btn btn-light-light text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
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

        {{-- Submit --}}
        <div class="text-end mb-2">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading wire:target="submit" class="spinner-border spinner-border-sm me-1"></span>
                <i class="bi bi-check-lg me-1" wire:loading.remove wire:target="submit"></i>
                {{ $editing ? 'Update' : 'Create' }} Packaging
            </button>
        </div>

    </form>

    @script
    <script>
        $('#pk_department_id, #pk_factory_id, #pk_source_warehouse_ids, #pk_fg_warehouse_id, #pk_event_types').selectpicker();

        // ── Selectpickers → Livewire ─────────────────────────────────────────
        $(document).on('change', '#pk_department_id', function () {
            $wire.call('onDepartmentChange', parseInt($(this).val()) || null);
        });
        $(document).on('change', '#pk_factory_id', function () {
            $wire.$set('factory_id', parseInt($(this).val()) || null, false);
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
