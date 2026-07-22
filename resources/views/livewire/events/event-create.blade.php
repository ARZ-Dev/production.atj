<div>
    <div class="ec-board">
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(empty($events))
        <div class="text-center py-5" style="opacity:.4">
            <i class="bi bi-calendar-plus d-block fs-1 mb-2"></i>
            <p class="mb-0 small">No events yet — click below to add one.</p>
        </div>
        @endif

        @foreach($events as $index => $event)
        <div class="ec-card" wire:key="ec-{{ $event['key'] }}">

            <div class="ec-card-head">
                <span class="ec-seq-dot">{{ $loop->iteration }}</span>

                <div wire:ignore class="ec-type-select-wrap @error('events.'.$index.'.event_type_id') is-invalid @enderror">
                    <select id="ec_event_type_{{ $event['key'] }}" data-key="{{ $event['key'] }}"
                        class="selectpicker ec-event-type-select" data-width="100%"
                        data-live-search="true" title="— Select event type —"
                        wire:model="events.{{ $index }}.event_type_id">
                        @foreach($eventTypes as $type)
                        <option value="{{ $type->id }}"
                            @selected((int)($event['event_type_id'] ?? 0) === $type->id)>
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <span class="{{ !empty($event['id']) ? 'ec-badge-saved' : 'ec-badge-new' }}">
                    {{ !empty($event['id']) ? 'Saved' : 'New' }}
                </span>

                @if($event['scheduled_label'])
                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-clock-history me-1"></i>{{ $event['scheduled_label'] }}
                </span>
                @else
                <span class="badge rounded-pill bg-light-secondary text-secondary">
                    <i class="bi bi-inbox me-1"></i>Not placed yet
                </span>
                @endif

                @if(!$editingEventId && count($events) > 1)
                <button type="button" class="ec-remove-btn"
                    wire:click="removeEventRow({{ $index }})">
                    <i class="bi bi-x"></i>
                </button>
                @endif
            </div>

            @error('events.' . $index . '.event_type_id')
            <div class="px-3 pt-2" style="font-size:11px; color:#dc3545;">
                <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
            </div>
            @enderror

            <div class="ec-card-body">

                @php $rowKey = $event['key']; $rowHasRecipe = !empty($event['event_type_has_recipe']); @endphp
                <div class="row g-2 mb-2">
                    {{-- Item type only applies to recipe event types --}}
                    @if($rowHasRecipe)
                    <div class="col-6 col-md-3">
                        <div class="ec-field-label">Item Type <span class="text-danger">*</span></div>
                        <div wire:ignore>
                            <select id="ec_item_type_{{ $rowKey }}" data-key="{{ $rowKey }}"
                                class="selectpicker ec-item-type-select" data-width="100%"
                                data-live-search="true" title="— Select —"
                                wire:model="events.{{ $index }}.item_type_id">
                                @foreach($itemTypesByRow[$index] ?? [] as $type)
                                <option value="{{ $type['id'] }}" @selected((int)($event['item_type_id'] ?? 0) === $type['id'])>
                                    {{ $type['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('events.' . $index . '.item_type_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="ec-field-label">Item <span class="text-danger">*</span></div>
                        <div wire:ignore>
                            <select id="ec_item_{{ $rowKey }}" data-key="{{ $rowKey }}"
                                class="selectpicker ec-item-select" data-width="100%"
                                data-live-search="true" title="— Select —"
                                wire:model="events.{{ $index }}.item_id">
                                @foreach($itemsByRow[$index] ?? [] as $item)
                                <option value="{{ $item['id'] }}" @selected((int)($event['item_id'] ?? 0) === $item['id'])>
                                    {{ $item['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('events.' . $index . '.item_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="ec-field-label">Recipe <span class="text-danger">*</span></div>
                        <div wire:ignore>
                            <select id="ec_recipe_{{ $rowKey }}" data-key="{{ $rowKey }}"
                                class="selectpicker ec-recipe-select" data-width="100%"
                                data-live-search="true" title="— Select —"
                                wire:model="events.{{ $index }}.recipe_id">
                                @foreach($recipesByRow[$index] ?? [] as $recipe)
                                <option value="{{ $recipe['id'] }}" @selected((int)($event['recipe_id'] ?? 0) === $recipe['id'])>
                                    {{ $recipe['name'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @error('events.' . $index . '.recipe_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="ec-field-label">Batch Number <span class="text-danger">*</span></div>
                        <input type="text"
                            class="form-control form-control-sm @error('events.'.$index.'.batch_count') is-invalid @enderror"
                            wire:model.defer="events.{{ $index }}.batch_count"
                            wire:change="onBatchCountChanged({{ $index }}, $event.target.value)"
                            placeholder="e.g. 1">
                        @error('events.' . $index . '.batch_count')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="ec-field-label">Duration</div>
                        <div class="ec-duration-note">
                            <i class="bi bi-info-circle"></i>
                            Duration will be calculated when the event is placed on a production/preparation line.
                        </div>
                    </div>
                    @else
                    <div class="col-6 col-md-3">
                        <div class="ec-field-label">Duration</div>
                        <input type="text" class="form-control form-control-sm" disabled
                               value="{{ $event['duration'] ? $event['duration'] . ' min' : '—' }}">
                    </div>
                    @endif
                </div>

                <div>
                    <div class="ec-field-label">Description</div>
                    <textarea
                        class="form-control form-control-sm @error('events.'.$index.'.description') is-invalid @enderror"
                        rows="2"
                        placeholder="Optional notes…"
                        wire:model.defer="events.{{ $index }}.description">{{ $event['description'] ?? '' }}</textarea>
                    @error('events.' . $index . '.description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>
        @endforeach

        @unless($editingEventId)
        <button type="button" class="ec-add-card" wire:click="addEventRow">
            <i class="bi bi-plus-circle"></i> Add event
        </button>
        @endunless
    </div>

    @if(!empty($events))
    <div class="ec-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary px-4"
            wire:click="submit" wire:loading.attr="disabled">
            <span wire:loading wire:target="submit"
                class="spinner-border spinner-border-sm me-1"></span>
            <i class="bi bi-check-lg me-1" wire:loading.remove wire:target="submit"></i>
            {{ count($events) > 1 ? 'Save Events' : 'Save Event' }}
        </button>
    </div>
    @endif

    @script
    <script>

        const ecRowIndex = (el) => $('.ec-card').index($(el).closest('.ec-card'));

        const ecInitPicker = (el) => {
            const $el = $(el);
            if (!$el.data('selectpicker')) $el.selectpicker();
        };

        // Replace a dependent picker's options (and clear its selection).
        const ecApplyOptions = (id, options) => {
            const $el = $('#' + $.escapeSelector(id));
            // console.log('ecApplyOptions', id, options, $el, '#' + $.escapeSelector(id));
            if (!$el.length || typeof setOptions !== 'function') return;
            setOptions($el, options || []);
            $el.selectpicker('val', '');
        };

        const EC_SELECTS = '.ec-event-type-select, .ec-item-type-select, .ec-item-select, .ec-recipe-select';

        // Boot: initialise pickers already in the DOM.
        $('.ec-card .selectpicker').each(function () { ecInitPicker(this); });

        // New rows / recipe fields that Livewire adds to the DOM.
        Livewire.hook('morph.added', ({ el }) => {
            if (el.nodeType !== 1) return;
            $(el).find('.selectpicker').addBack('.selectpicker').filter(EC_SELECTS)
                .each(function () { ecInitPicker(this); });
        });

        // Pickers measure to zero width while the modal is hidden — refresh
        // them once it is shown.
        $(document).on('shown.bs.modal', '#eventCreateModal', function () {
            $('#eventCreateModal .selectpicker').selectpicker();
        });

        // ── Cascades: a change calls its server handler, which dispatches
        //    "ec-cascade" with the dependent options for this row. ────────────
        $(document).on('change', '.ec-event-type-select', function () {
            if ($(this).val() === '') return;
            $wire.call('onEventTypeChanged', ecRowIndex(this), $(this).val());
        });
        $(document).on('change', '.ec-item-type-select', function () {
            if ($(this).val() === '') return;
            $wire.call('onItemTypeChanged', ecRowIndex(this), $(this).val());
        });
        $(document).on('change', '.ec-item-select', function () {
            if ($(this).val() === '') return;
            $wire.call('onItemChanged', ecRowIndex(this), $(this).val());
        });
        $(document).on('change', '.ec-recipe-select', function () {
            if ($(this).val() === '') return;
            $wire.call('onRecipeChanged', ecRowIndex(this), $(this).val());
        });

        $wire.on('ec-cascade', (e) => {
            if (!e || !e.key) return;

            if (e.itemTypes !== undefined) ecApplyOptions('ec_item_type_' + e.key, e.itemTypes);
            if (e.items      !== undefined) ecApplyOptions('ec_item_'      + e.key, e.items);
            if (e.recipes    !== undefined) ecApplyOptions('ec_recipe_'    + e.key, e.recipes);
        });

    </script>
    @endscript
</div>
