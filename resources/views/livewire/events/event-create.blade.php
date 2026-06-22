<div>
    <script>window.ecShifts = @json($shifts);</script>
    <div class="ec-page">

        <div class="ec-info-bar card mb-0">
            <div class="ec-info-left">
                <i class="bi bi-card-checklist text-primary fs-5"></i>
                <span class="fw-bold">Plan #{{ $plan->id }}</span>
                <span class="ec-chip">
                    <i class="bi bi-calendar3 text-primary chip-icon"></i>
                    {{ \Carbon\Carbon::parse($plan->date)->format('d F Y') }}
                </span>
                @if($plan->shift)
                <span class="ec-chip green">
                    <i class="bi bi-clock chip-icon"></i>
                    {{ $plan->shift->name }} &nbsp;
                    <span class="chip-subtext">
                        {{ \Carbon\Carbon::parse($plan->shift->from_time)->format('H:i') }}
                        – {{ \Carbon\Carbon::parse($plan->shift->to_time)->format('H:i') }}
                    </span>
                </span>
                @endif
            </div>
            <a href="{{ route('plans') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="ec-board">
            <div class="ec-board-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-list-task text-primary"></i>
                    <span class="ec-board-title">Events</span>
                    <span class="badge bg-light-primary rounded-pill">{{ count($events) }}</span>
                </div>
                @if($plan->shift)
                <span class="ec-shift-hint">
                    <i class="bi bi-clock-history"></i>
                    Allowed: {{ \Carbon\Carbon::parse($plan->shift->from_time)->format('H:i') }}
                    – {{ \Carbon\Carbon::parse($plan->shift->to_time)->format('H:i') }}
                </span>
                @endif
            </div>

            @if(empty($events))
            <div class="text-center py-5" style="opacity:.4">
                <i class="bi bi-calendar-plus d-block fs-1 mb-2"></i>
                <p class="mb-0 small">No events yet — click below to add one.</p>
            </div>
            @endif

            @foreach($events as $index => $event)
            <div class="ec-card" wire:key="ec-{{ $event['key'] }}"
                x-data="{
                    fromTime: '{{ $event['from_time'] ?? '' }}',
                    get matchingShift() {
                        const t = this.fromTime;
                        if (!t) return null;
                        return (window.ecShifts || []).find(s =>
                            s.from <= s.to
                                ? (t >= s.from && t <= s.to)
                                : (t >= s.from || t <= s.to)
                        ) ?? null;
                    }
                }">

                <div class="ec-card-head">
                    <span class="ec-seq-dot">{{ $loop->iteration }}</span>

                    <select
                        class="form-select form-select-sm ec-type-select @error('events.'.$index.'.event_type_id') is-invalid @enderror"
                        wire:model="events.{{ $index }}.event_type_id"
                        wire:change="onEventTypeChanged({{ $index }}, $event.target.value)">
                        <option value="">— Select event type —</option>
                        @foreach($eventTypes as $type)
                        <option value="{{ $type->id }}"
                            @selected((int)($event['event_type_id'] ?? 0) === $type->id)>
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>

                    <span class="{{ !empty($event['id']) ? 'ec-badge-saved' : 'ec-badge-new' }}">
                        {{ !empty($event['id']) ? 'Saved' : 'New' }}
                    </span>

                    @if(count($events) > 1)
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

                    @if(!empty($event['event_type_has_recipe']))
                    <div class="row g-2 mb-2">
                        <div class="col-6 col-md-3">
                            <div class="ec-field-label">Item Type <span class="text-danger">*</span></div>
                            <select class="form-select form-select-sm @error('events.'.$index.'.item_type_id') is-invalid @enderror"
                                wire:model="events.{{ $index }}.item_type_id"
                                wire:change="onItemTypeChanged({{ $index }}, $event.target.value)">
                                <option value="">— Select —</option>
                                @foreach($itemTypesByRow[$index] ?? [] as $type)
                                <option value="{{ $type['id'] }}" @selected((int)($event['item_type_id'] ?? 0) === $type['id'])>
                                    {{ $type['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('events.' . $index . '.item_type_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="ec-field-label">Item <span class="text-danger">*</span></div>
                            <select class="form-select form-select-sm @error('events.'.$index.'.item_id') is-invalid @enderror"
                                wire:model="events.{{ $index }}.item_id"
                                wire:change="onItemChanged({{ $index }}, $event.target.value)">
                                <option value="">— Select —</option>
                                @foreach($itemsByRow[$index] ?? [] as $item)
                                <option value="{{ $item['id'] }}" @selected((int)($event['item_id'] ?? 0) === $item['id'])>
                                    {{ $item['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('events.' . $index . '.item_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="ec-field-label">Recipe Type <span class="text-danger">*</span></div>
                            <select class="form-select form-select-sm @error('events.'.$index.'.recipe_type_id') is-invalid @enderror"
                                wire:model="events.{{ $index }}.recipe_type_id"
                                wire:change="onRecipeTypeChanged({{ $index }}, $event.target.value)">
                                <option value="">— Select —</option>
                                @foreach($recipeTypesByRow[$index] ?? [] as $rt)
                                <option value="{{ $rt['id'] }}" @selected((int)($event['recipe_type_id'] ?? 0) === $rt['id'])>
                                    {{ $rt['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('events.' . $index . '.recipe_type_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="ec-field-label">Recipe <span class="text-danger">*</span></div>
                            <select class="form-select form-select-sm @error('events.'.$index.'.recipe_id') is-invalid @enderror"
                                wire:model="events.{{ $index }}.recipe_id"
                                wire:change="onRecipeChanged({{ $index }}, $event.target.value)">
                                <option value="">— Select —</option>
                                @foreach($recipesByRow[$index] ?? [] as $recipe)
                                <option value="{{ $recipe['id'] }}" @selected((int)($event['recipe_id'] ?? 0) === $recipe['id'])>
                                    {{ $recipe['name'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('events.' . $index . '.recipe_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="ec-field-label">Batch Number <span class="text-danger">*</span></div>
                            <input type="text"
                                class="form-control form-control-sm @error('events.'.$index.'.batch_count') is-invalid @enderror"
                                wire:model.defer="events.{{ $index }}.batch_count" placeholder="e.g. 1">
                            @error('events.' . $index . '.batch_count')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="ec-field-label">Duration</div>
                            <input type="text" class="form-control form-control-sm" disabled
                                value="{{ $event['duration'] ? $event['duration'] . ' min' : 'Set after placement on a line' }}">
                        </div>
                    </div>
                    @else
                    <div class="row g-2 mb-2">
                        <div class="col-6 col-md-3">
                            <div class="ec-field-label">Duration</div>
                            <input type="text" class="form-control form-control-sm" disabled
                                value="{{ $event['duration'] ? $event['duration'] . ' min' : '—' }}">
                        </div>
                    </div>
                    @endif

                    <div class="ec-time-row">
                        <div>
                            <div class="ec-field-label">From <span class="text-danger">*</span></div>
                            <input type="time"
                                class="form-control form-control-sm @error('events.'.$index.'.from_time') is-invalid @enderror"
                                wire:model="events.{{ $index }}.from_time"
                                wire:change="onFromTimeChanged({{ $index }}, $event.target.value)"
                                value="{{ $event['from_time'] ?? '' }}"
                                @input="fromTime = $event.target.value">
                            @error('events.' . $index . '.from_time')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="ec-time-sep"><i class="bi bi-arrow-right"></i></div>

                        <div>
                            <div class="ec-field-label">
                                To
                                @if(!($event['event_type_has_recipe'] ?? false))
                                <span class="opacity-50">(auto)</span>
                                @endif
                            </div>
                            <input type="time"
                                class="form-control form-control-sm @error('events.'.$index.'.to_time') is-invalid @enderror"
                                wire:model.defer="events.{{ $index }}.to_time"
                                value="{{ $event['to_time'] ?? '' }}"
                                @if(!($event['event_type_has_recipe'] ?? false)) disabled @endif>
                            @error('events.' . $index . '.to_time')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="ms-auto d-flex align-items-center" x-show="matchingShift">
                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle d-flex align-items-center gap-1" style="font-size: 11px; padding: 5px 10px;">
                                <i class="bi bi-flag-fill" style="font-size: 9px;"></i>
                                <span x-text="matchingShift?.name"></span>
                                <span class="opacity-75" x-text="matchingShift ? matchingShift.from + '–' + matchingShift.to : ''"></span>
                            </span>
                        </div>
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

            <button type="button" class="ec-add-card" wire:click="addEventRow">
                <i class="bi bi-plus-circle"></i> Add event
            </button>
        </div>

        @if(!empty($events))
        <div class="ec-footer">
            <a href="{{ route('plans.view', $planId) }}" class="btn btn-light">Cancel</a>
            <button type="button" class="btn btn-primary px-4"
                wire:click="submit" wire:loading.attr="disabled">
                <span wire:loading wire:target="submit"
                    class="spinner-border spinner-border-sm me-1"></span>
                <i class="bi bi-check-lg me-1" wire:loading.remove wire:target="submit"></i>
                Save Events
            </button>
        </div>
        @endif

    </div>
</div>
