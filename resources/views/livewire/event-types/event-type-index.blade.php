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
                    <h5 class="card-title mb-0">Event Types</h5>
                    @hasPermission('production.eventType-create')
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="bi bi-plus-lg me-1"></i> Add Event Type
                    </button>
                    @endhasPermission
                </div>

                <div class="card-body" wire:ignore>
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Color</th>
                                <th>Name</th>
                                <th>With Recipe</th>
                                <th>Duration</th>
                                <th>Item Types</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eventTypes as $eventType)
                            <tr>
                                <td>{{ $eventType->id }}</td>
                                <td>
                                    <span class="d-inline-block rounded-circle" style="width:16px;height:16px;background-color:{{ $eventType->color ?? '#818cf8' }};border:1px solid rgba(0,0,0,.15)"></span>
                                </td>
                                <td>{{ $eventType->name }}</td>
                                <td>
                                    @if($eventType->has_recipe)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td>{{ $eventType->has_recipe ? '—' : ($eventType->duration ? $eventType->duration . ' min' : '—') }}</td>
                                <td>
                                    @php
                                        $etItemTypeNames = collect((array) $eventType->item_type_ids)
                                            ->map(fn($id) => $itemTypesMap[$id] ?? "ID:$id")
                                            ->implode(', ');
                                    @endphp
                                    {{ $etItemTypeNames ?: '—' }}
                                </td>
                                <td>
                                    @hasPermission('production.eventType-edit')
                                    <button type="button" wire:click="edit({{ $eventType->id }})"
                                        class="btn btn-light-primary icon-btn-sm"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endhasPermission

                                    @hasPermission('production.eventType-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $eventType->id }}"
                                        data-bs-toggle="tooltip" data-bs-custom-class="tooltip-white"
                                        data-bs-placement="top" data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endhasPermission
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="eventTypeModal" tabindex="-1" aria-labelledby="eventTypeModalLabel" aria-hidden="true"
        wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventTypeModalLabel">
                        {{ $editing ? 'Edit Event Type' : 'New Event Type' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="et_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="et_name" wire:model="name" placeholder="e.g. Cleaning, Production, Maintenance">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="et_color" class="form-label">Color <span class="text-danger">*</span></label>
                        <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                            id="et_color" wire:model="color" title="Choose the color used for this event type's events">
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Duration source <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="et_duration_mode" id="et_mode_recipe"
                                wire:model.live="has_recipe" value="1" autocomplete="off">
                            <label class="btn btn-outline-primary" for="et_mode_recipe">
                                <i class="bi bi-droplet-half me-1"></i> Has Recipe
                            </label>

                            <input type="radio" class="btn-check" name="et_duration_mode" id="et_mode_fixed"
                                wire:model.live="has_recipe" value="0" autocomplete="off">
                            <label class="btn btn-outline-primary" for="et_mode_fixed">
                                <i class="bi bi-clock me-1"></i> Fixed Duration
                            </label>
                        </div>
                        <div class="form-text">
                            "Has Recipe" events calculate their duration per event from the recipe and the
                            capacity of the preparation/line they're placed on. "Fixed Duration" events use the
                            duration set below for every event of this type.
                        </div>
                    </div>

                    @if(!$has_recipe)
                    <div class="mb-3">
                        <label for="et_duration" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                        <input type="number" min="1" class="form-control @error('duration') is-invalid @enderror"
                            id="et_duration" wire:model="duration" placeholder="e.g. 30">
                        @error('duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif

                    <div class="mb-3">
                        <div wire:ignore>
                            <label for="et_item_type_ids" class="form-label">Item Types</label>
                            <select id="et_item_type_ids" multiple
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
                        <div class="form-text">Restricts which item types can be selected for events of this type.</div>
                        @error('item_type_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    @if(!$has_recipe)
                        @if(count($eventTypeItems))
                        @php
                            $etGroups = [];
                            foreach ($eventTypeItems as $i => $r) {
                                $etGroups[$r['item_type_name']][$i] = $r;
                            }
                        @endphp
                        <div class="mb-3">
                            <label class="form-label mb-1">
                                Items <span class="text-muted" style="font-size: 11px;">— set a unit &amp; quantity for the items this event uses</span>
                            </label>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm align-middle mb-0 aqt-table" style="font-size: 12px;">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th style="width: 190px;">Unit</th>
                                            <th style="width: 130px;">Quantity</th>
                                        </tr>
                                    </thead>
                                    @foreach($etGroups as $groupName => $groupRows)
                                    <tbody wire:key="et-item-group-{{ $loop->index }}">
                                        <tr class="aqt-group-row">
                                            <td colspan="3" class="aqt-group-title">
                                                <i class="bi bi-tag-fill me-1"></i>{{ $groupName }}
                                                <span class="aqt-group-count">{{ count($groupRows) }}</span>
                                            </td>
                                        </tr>
                                        @foreach($groupRows as $i => $row)
                                        <tr wire:key="et-item-{{ $row['item_id'] }}">
                                            <td>{{ $row['item_name'] }}</td>
                                            <td>
                                                <select class="form-select form-select-sm"
                                                    wire:model="eventTypeItems.{{ $i }}.item_unit_id">
                                                    @foreach($row['units'] as $unit)
                                                    <option value="{{ $unit['id'] }}">{{ $unit['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0" placeholder="0"
                                                    class="form-control form-control-sm"
                                                    wire:model="eventTypeItems.{{ $i }}.quantity">
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    @endforeach
                                </table>
                            </div>
                            <div class="form-text">Only items with a quantity are saved — leave the rest empty.</div>
                        </div>
                        @elseif(count($item_type_ids))
                        <div class="mb-3 text-muted" style="font-size: 12px;">
                            <i class="bi bi-info-circle me-1"></i>No active items found for the selected item types.
                        </div>
                        @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"
                        wire:click="resetForm">Cancel</button>
                    <button type="button" id="et_submit_btn" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submit">
                        <span wire:loading wire:target="submit" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $editing ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        const eventTypeModal = new bootstrap.Modal(document.getElementById('eventTypeModal'));

        $('#et_item_type_ids').selectpicker();

        // Delegated on document: the widget is destroyed & rebuilt every
        // time the modal opens (see below — needed because it's initialized
        // while the modal is still display:none, which leaves bootstrap-select's
        // menu malformed until it's rebuilt once visible). That destroy()
        // call internally does `$element.off('.bs.select')`, which would
        // silently strip a handler bound directly on the element with the
        // same namespace (e.g. `.on('changed.bs.select', ...)`). Binding on
        // `document` instead keeps the sync alive across every rebuild.
        $(document).on('changed.bs.select', '#et_item_type_ids', function () {
            @this.set('item_type_ids', $(this).val());
        });

        $wire.on('openModal', () => {
            eventTypeModal.show();
            setTimeout(() => {
                $('#et_item_type_ids').selectpicker('destroy').selectpicker();
                $('#et_item_type_ids').selectpicker('val', ($wire.get('item_type_ids') || []).map(String));
            }, 150);
        });
        $wire.on('closeModal', () => eventTypeModal.hide());

        // Belt-and-suspenders: rather than trust every intermediate
        // `changed.bs.select` event to have landed (and survived any widget
        // rebuild) before this point, read the widget's current selection
        // directly off the DOM at the moment Save is clicked and push it in
        // right before submitting.
        $(document).on('click', '#et_submit_btn', function () {
            // Keep the same string representation the live `changed.bs.select`
            // sync uses, so re-setting here doesn't look "dirty" and rebuild
            // the items table (which would drop unsaved quantities).
            const selected = ($('#et_item_type_ids').val() || []).map(String);
            $wire.set('item_type_ids', selected, false);
            $wire.call('submit');
        });

        Livewire.hook('morph.added', ({ el }) => {
            $(el).find('.selectpicker').selectpicker();
        });
    </script>
    @endscript

    @script
        @include('livewire.deleteConfirm')
    @endscript
</div>
