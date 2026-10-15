<div class="ec-root">
    @php
        // Quantities in the split panel: separators, at most $dp decimals, no trailing zeros.
        $ecQty = function ($n, int $dp = 2) {
            if ($n === null) {
                return '—';
            }
            $s = number_format((float) $n, $dp);
            return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
        };
        $ecMinutes = function ($m) {
            $m = (int) $m;
            $h = intdiv($m, 60);
            $r = $m % 60;
            return $h ? ($r ? "{$h}h {$r}m" : "{$h}h") : "{$r}m";
        };
        // One colour per companion, shared by its number badge and its slice of the bar.
        $ecShareColors = ['#14b8a6', '#6366f1', '#f59e0b', '#ec4899', '#0ea5e9', '#84cc16'];
    @endphp

    {{-- Shown while any request of this popup is in flight (every picker change
         is a round trip: options, durations and the split are worked out on the
         server). It also covers the form, so a second change can't be made
         against the options the first one is about to replace. The short delay
         keeps instant responses from flickering. --}}
    <div class="ec-busy" wire:loading.flex.delay.shorter role="status" aria-live="polite">
        <div class="ec-busy-pill">
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            <span wire:loading.remove wire:target="submit">Updating…</span>
            <span wire:loading wire:target="submit">Saving…</span>
        </div>
    </div>

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
        @php $rowStarted = !empty($event['started']); @endphp
        <div class="ec-card" wire:key="ec-{{ $event['key'] }}">

            <div class="ec-card-head">
                <span class="ec-seq-dot">{{ $loop->iteration }}</span>

                <div wire:ignore class="ec-type-select-wrap @error('events.'.$index.'.event_type_id') is-invalid @enderror">
                    <select id="ec_event_type_{{ $event['key'] }}" data-key="{{ $event['key'] }}"
                        class="selectpicker ec-event-type-select" data-width="100%" @disabled($rowStarted)
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

            @if($rowStarted)
            <div class="px-3 pt-2 text-muted small">
                <i class="bi bi-lock me-1"></i>This event has started — its stock was reserved from these
                settings, so only the description can be changed.
            </div>
            @endif

            <div class="ec-card-body">

                @php $rowKey = $event['key']; $rowHasRecipe = !empty($event['event_type_has_recipe']); @endphp
                <div class="row g-2 mb-2">
                    {{-- Item type only applies to recipe event types --}}
                    @if($rowHasRecipe)
                    <div class="col-6 col-md-3">
                        <div class="ec-field-label">Item Type <span class="text-danger">*</span></div>
                        <div wire:ignore>
                            <select id="ec_item_type_{{ $rowKey }}" data-key="{{ $rowKey }}"
                                class="selectpicker ec-item-type-select" data-width="100%" @disabled($rowStarted)
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
                                class="selectpicker ec-item-select" data-width="100%" @disabled($rowStarted)
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
                                class="selectpicker ec-recipe-select" data-width="100%" @disabled($rowStarted)
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
                            @disabled($rowStarted)
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

                {{-- The event type executed with this one (e.g. packaging alongside
                     production) — one companion per lane the run is packed on.
                     Their fields live in this card — the group is one run — and
                     are bound under events.{i}.companions.{key}, keyed by a stable
                     key so removing one never shifts another's bindings. --}}
                @if(!empty($event['companions']))
                @php
                    $companions     = $event['companions'];
                    $companionKeys  = array_map('strval', array_keys($companions));
                    $firstCompanion = reset($companions);
                    $dist           = $distributions[$index] ?? null;
                    $shareColors    = array_map(fn($i) => $ecShareColors[$i % count($ecShareColors)], array_keys($companionKeys));
                    $packagingOpts  = $companionPackagingsByRow[$index] ?? [];
                    $leadUnit       = !empty($dist['unit']) ? ' ' . $dist['unit'] : '';
                @endphp
                <div class="ec-companion" wire:key="ec-companion-{{ $rowKey }}">
                    <div class="ec-companion-head">
                        <i class="bi bi-link-45deg"></i>
                        <span>Runs together with</span>
                        <span class="ec-companion-type">
                            <span class="d-inline-block rounded-circle" style="width:9px;height:9px;background-color: {{ $firstCompanion['color'] }};"></span>
                            {{ $firstCompanion['type_name'] }}
                        </span>
                        <span class="ec-companion-count">× {{ count($companions) }}</span>
                    </div>

                    @include('livewire.events.partials._companion-split', [
                        'dist'          => $dist,
                        'index'         => $index,
                        'colors'        => $shareColors,
                        'companionKeys' => $companionKeys,
                        'rowStarted'    => $rowStarted,
                    ])

                    @foreach($companions as $cKey => $companion)
                    @php
                        $cKey   = (string) $cKey;
                        $cPath  = 'events.' . $index . '.companions.' . $cKey;
                        $cId    = $rowKey . '_' . $cKey;
                        $share  = $dist['companions'][$cKey] ?? null;
                        $packagingName = collect($packagingOpts)->firstWhere('id', $companion['packaging_id'])['name'] ?? null;
                    @endphp
                    <div class="ec-companion-item" wire:key="ec-companion-{{ $rowKey }}-{{ $cKey }}">
                        <div class="ec-companion-item-head">
                            <span class="ec-share-dot" style="background-color: {{ $shareColors[$loop->index] }};">{{ $loop->iteration }}</span>
                            <span class="ec-companion-item-title">{{ $companion['type_name'] }} #{{ $loop->iteration }}</span>
                            <span class="{{ !empty($companion['id']) ? 'ec-badge-saved' : 'ec-badge-new' }}">
                                {{ !empty($companion['id']) ? 'Saved' : 'New' }}
                            </span>
                            @if($companion['placed'])
                            <span class="ec-companion-where"><i class="bi bi-geo-alt"></i> On the board</span>
                            @elseif($packagingName)
                            <span class="ec-companion-where" title="Placing this event on the board puts this one on {{ $packagingName }} at the same time">
                                <i class="bi bi-box-seam"></i> Goes on {{ $packagingName }} when this event is placed
                            </span>
                            @endif
                            @if(count($companions) > 1 && !$rowStarted)
                            <button type="button" class="ec-remove-btn ms-auto" title="Remove this {{ $companion['type_name'] }} event"
                                wire:click="removeCompanion({{ $index }}, @js($cKey))">
                                <i class="bi bi-x"></i>
                            </button>
                            @endif
                        </div>

                        <div class="row g-2">
                            <div class="col-6 col-lg">
                                <div class="ec-field-label">Packaging</div>
                                <div wire:ignore>
                                    <select id="ec_c_packaging_{{ $cId }}" data-key="{{ $rowKey }}" data-ckey="{{ $cKey }}"
                                        class="selectpicker ec-c-packaging-select" data-width="100%" @disabled($rowStarted)>
                                        <option value="">— Place it later —</option>
                                        @foreach($packagingOpts as $packaging)
                                        <option value="{{ $packaging['id'] }}" @selected((int) ($companion['packaging_id'] ?? 0) === $packaging['id'])>
                                            {{ $packaging['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error($cPath . '.packaging_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                @if(!$packagingOpts)
                                <div class="ec-companion-hint">No packaging of this factory runs {{ $companion['type_name'] }} — drag it onto a lane instead.</div>
                                @endif
                            </div>

                            <div class="col-6 col-lg">
                                <div class="ec-field-label">Item Type <span class="text-danger">*</span></div>
                                <div wire:ignore>
                                    <select id="ec_c_item_type_{{ $cId }}" data-key="{{ $rowKey }}" data-ckey="{{ $cKey }}"
                                        class="selectpicker ec-c-item-type-select" data-width="100%" @disabled($rowStarted)
                                        data-live-search="true" title="— Select —">
                                        @foreach($companionItemTypesByRow[$index] ?? [] as $type)
                                        <option value="{{ $type['id'] }}" @selected((int)($companion['item_type_id'] ?? 0) === $type['id'])>
                                            {{ $type['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error($cPath . '.item_type_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6 col-lg">
                                <div class="ec-field-label">Item <span class="text-danger">*</span></div>
                                <div wire:ignore>
                                    <select id="ec_c_item_{{ $cId }}" data-key="{{ $rowKey }}" data-ckey="{{ $cKey }}"
                                        class="selectpicker ec-c-item-select" data-width="100%" @disabled($rowStarted)
                                        data-live-search="true" title="— Select —">
                                        @foreach($companionItemsByRow[$index][$cKey] ?? [] as $item)
                                        <option value="{{ $item['id'] }}" @selected((int)($companion['item_id'] ?? 0) === $item['id'])>
                                            {{ $item['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error($cPath . '.item_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6 col-lg">
                                <div class="ec-field-label">Recipe <span class="text-danger">*</span></div>
                                <div wire:ignore>
                                    <select id="ec_c_recipe_{{ $cId }}" data-key="{{ $rowKey }}" data-ckey="{{ $cKey }}"
                                        class="selectpicker ec-c-recipe-select" data-width="100%" @disabled($rowStarted)
                                        data-live-search="true" title="— Select —">
                                        @foreach($companionRecipesByRow[$index][$cKey] ?? [] as $recipe)
                                        <option value="{{ $recipe['id'] }}" @selected((int)($companion['recipe_id'] ?? 0) === $recipe['id'])>
                                            {{ $recipe['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error($cPath . '.recipe_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                @if(!empty($companion['item_id']) && !empty($event['item_id']) && empty($companionRecipesByRow[$index][$cKey] ?? []))
                                <div class="text-warning small mt-1">
                                    <i class="bi bi-exclamation-circle"></i> No active recipe for this item uses what this production makes.
                                </div>
                                @endif
                            </div>

                            <div class="col-6 col-lg">
                                <div class="ec-field-label">Batch Number <span class="text-danger">*</span></div>
                                <input type="text"
                                    class="form-control form-control-sm @error($cPath . '.batch_count') is-invalid @enderror"
                                    wire:model.defer="{{ $cPath }}.batch_count"
                                    wire:change="onCompanionBatchChanged({{ $index }}, @js($cKey), $event.target.value)"
                                    @disabled($rowStarted)
                                    placeholder="e.g. 1">
                                @error($cPath . '.batch_count')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @if($companion['batch_auto'] !== null)
                                <div class="ec-companion-hint">
                                    @if(abs((float) $companion['batch_auto'] - (float) str_replace(',', '', (string) $companion['batch_count'])) < 0.00005)
                                    <i class="bi bi-magic"></i> Its share of the run
                                    @else
                                    Its share of the run: {{ $companion['batch_auto'] }}
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- This companion's slice of the run --}}
                        @if($share && $share['issue'] !== 'recipe')
                        <div class="ec-share">
                            @if($share['issue'] === 'consumes')
                            <span class="ec-share-chip ec-share-chip--warn">
                                <i class="bi bi-exclamation-circle"></i> This recipe doesn't use {{ $dist['item_name'] }}, so it takes no share of the run.
                            </span>
                            @else
                            <span class="ec-share-chip" title="What one batch of {{ $companion['type_name'] }}'s recipe uses and makes">
                                <span class="ec-share-k">Per batch</span>
                                {{ $ecQty($share['in_qty'], 3) }} {{ $share['in_unit'] }}
                                <i class="bi bi-arrow-right"></i>
                                {{ $ecQty($share['per_batch_out'], 3) }} {{ $share['out_unit'] }}
                            </span>
                            @if($share['assigned'] !== null)
                            <span class="ec-share-chip ec-share-chip--strong" style="--ec-share: {{ $shareColors[$loop->index] }};">
                                <span class="ec-share-k">Takes</span>
                                {{ $ecQty($share['assigned'], 3) }}{{ $leadUnit }}
                                @if($share['assigned_share'] !== null)
                                <span class="ec-share-pct">{{ round($share['assigned_share'] * 100) }}%</span>
                                @endif
                            </span>
                            <span class="ec-share-chip">
                                <span class="ec-share-k">Makes</span>
                                {{ $ecQty($share['makes'], 3) }} {{ $share['out_unit'] }} {{ $share['out_item'] }}
                            </span>
                            @else
                            <span class="ec-share-chip ec-share-chip--muted">Enter a batch number to see its share.</span>
                            @endif
                            @if($share['rate'])
                            <span class="ec-share-chip" title="{{ $share['packaging_name'] }}'s capacity for {{ $share['out_item'] }}">
                                <i class="bi bi-speedometer2"></i>
                                {{ $ecQty($share['rate']['capacity'], 2) }} {{ $share['rate']['unit'] }}/h
                                @if($share['minutes'])
                                · ≈ {{ $ecMinutes($share['minutes']) }}
                                @endif
                            </span>
                            @elseif($share['issue'] === 'packaging')
                            <span class="ec-share-chip ec-share-chip--muted">
                                <i class="bi bi-speedometer2"></i> Pick a packaging to use its capacity
                            </span>
                            @elseif($share['issue'] === 'capacity')
                            <span class="ec-share-chip ec-share-chip--warn">
                                <i class="bi bi-exclamation-circle"></i> No capacity set on {{ $share['packaging_name'] }} for {{ $share['out_item'] }}
                            </span>
                            @endif
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach

                    @unless($rowStarted)
                    <button type="button" class="ec-companion-add" wire:click="addCompanion({{ $index }})"
                        wire:loading.attr="disabled" wire:target="addCompanion">
                        <i class="bi bi-plus-lg"></i> Add another {{ $firstCompanion['type_name'] }}
                        <span class="ec-companion-add-sub">— pack part of this run on another lane</span>
                    </button>
                    @endunless

                    <div class="ec-companion-note">
                        <i class="bi bi-info-circle"></i>
                        All of these start and end together with this event. The pre-finished goods made here pass
                        straight to {{ $firstCompanion['type_name'] }} without going through a warehouse, so they're
                        never checked or stocked — each packaging event takes the share its batch number says.
                    </div>
                </div>
                @endif

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

        const EC_SELECTS = '.ec-event-type-select, .ec-item-type-select, .ec-item-select, .ec-recipe-select, '
            + '.ec-c-packaging-select, .ec-c-item-type-select, .ec-c-item-select, .ec-c-recipe-select';

        // A companion's stable key within its row (read as an attribute: jQuery's
        // .data() would turn a key that looks numeric into a number).
        const ecCompanionKey = (el) => String($(el).attr('data-ckey'));

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

        // The companion events' cascade (the event type executed with the
        // row's type), inside the same card — same row index, plus the
        // companion's key.
        $(document).on('change', '.ec-c-packaging-select', function () {
            // Clearing it is a choice too: "place it later".
            $wire.call('onCompanionPackagingChanged', ecRowIndex(this), ecCompanionKey(this), $(this).val() || '');
        });
        $(document).on('change', '.ec-c-item-type-select', function () {
            if ($(this).val() === '') return;
            $wire.call('onCompanionItemTypeChanged', ecRowIndex(this), ecCompanionKey(this), $(this).val());
        });
        $(document).on('change', '.ec-c-item-select', function () {
            if ($(this).val() === '') return;
            $wire.call('onCompanionItemChanged', ecRowIndex(this), ecCompanionKey(this), $(this).val());
        });
        $(document).on('change', '.ec-c-recipe-select', function () {
            if ($(this).val() === '') return;
            $wire.call('onCompanionRecipeChanged', ecRowIndex(this), ecCompanionKey(this), $(this).val());
        });

        $wire.on('ec-cascade', (e) => {
            if (!e || !e.key) return;

            if (e.itemTypes !== undefined) ecApplyOptions('ec_item_type_' + e.key, e.itemTypes);
            if (e.items      !== undefined) ecApplyOptions('ec_item_'      + e.key, e.items);
            if (e.recipes    !== undefined) ecApplyOptions('ec_recipe_'    + e.key, e.recipes);

            if (!e.ckey) return;
            const cId = e.key + '_' + e.ckey;

            if (e.cItems   !== undefined) ecApplyOptions('ec_c_item_'   + cId, e.cItems);
            if (e.cRecipes !== undefined) ecApplyOptions('ec_c_recipe_' + cId, e.cRecipes);
        });

    </script>
    @endscript
</div>
