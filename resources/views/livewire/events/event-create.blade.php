<div>
    <form>
        <div class="row">
            <div class="col-12">

                {{-- Header Card --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Events</h6>
                        <a href="{{ route('plans') }}" class="btn btn-light-light text-muted">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>

                    {{-- Company Selection (Super Admin Only) --}}
                    @if(auth()->user()->hasRole('Super Admin'))
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="company_id">Company</label>
                                <div wire:ignore>
                                    <select wire:model="company_id" id="company_id" class="selectpicker w-100"
                                        title="Select Company" data-style="btn-default" data-live-search="true"
                                        data-icon-base="ti" data-size="5" data-tick-icon="ti-check text-white">
                                        @foreach($companies as $company)
                                        <option value="{{ $company->id }}" @selected($company->id == $company_id)>
                                            {{ $company->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('company_id')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Events Container --}}
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Events</h6>
                        <button type="button" class="btn btn-success btn-sm" wire:click="addEventRow">
                            <i class="bi bi-plus-circle me-1"></i>Add Event
                        </button>
                    </div>
                    <div class="card-body">

                        {{-- Empty State --}}
                        @if(empty($events))
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-calendar-plus fs-1 d-block mb-2"></i>
                            <p>No events added yet. Click "Add Event" to add one.</p>
                        </div>
                        @endif

                        {{-- Event Cards --}}
                        @foreach($events as $index => $event)
                        <div class="card border shadow-sm mb-3" wire:key="event-{{ $index }}-{{ $event['id'] ?? 'new' }}">

                            {{-- Event Card Header --}}
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                <span class="fw-semibold text-primary">
                                    <i class="bi bi-calendar-event me-1"></i>Event #{{ $index + 1 }}
                                    @if(!empty($event['id']))
                                    <span class="badge bg-label-info ms-2">Existing</span>
                                    @else
                                    <span class="badge bg-label-success ms-2">New</span>
                                    @endif
                                </span>
                                @if(count($events) > 1)
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                    wire:click="removeEventRow({{ $index }})">
                                    <i class="bi bi-trash me-1"></i>Remove
                                </button>
                                @endif
                            </div>

                            {{-- Event Card Body --}}
                            <div class="card-body">
                                <div class="row g-3">

                                    {{-- Event Type --}}
                                    <div class="col-md-6">
                                        <label class="form-label" for="event_type_id_{{ $index }}">
                                            Event Type <span class="text-danger">*</span>
                                        </label>
                                        <div wire:ignore>
                                            <select wire:model="events.{{ $index }}.event_type_id"
                                                id="event_type_id_{{ $index }}"
                                                class="selectpicker w-100 event-type-select"
                                                title="Select Event Type"
                                                data-style="btn-default"
                                                data-live-search="true"
                                                data-icon-base="ti"
                                                data-size="5"
                                                data-tick-icon="ti-check text-white"
                                                data-index="{{ $index }}">
                                                @foreach($eventTypes as $type)
                                                <option value="{{ $type['id'] }}"
                                                    @selected($type['id'] == $events[$index]['event_type_id'])>
                                                    {{ $type['name'] }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('events.' . $index . '.event_type_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Event Name --}}
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Event Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                            class="form-control"
                                            wire:model="events.{{ $index }}.name"
                                            placeholder="Enter event name">
                                        @error('events.' . $index . '.name')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Fields shown after event type is selected --}}
                                    @if(!empty($event['event_type_id']))

                                    {{-- Duration --}}
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            {{ $event['has_recipe'] ? 'Recipe Duration (per batch)' : 'Duration' }}
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light" readonly
                                                value="{{ $event['duration'] }}">
                                            <span class="input-group-text">minutes</span>
                                        </div>
                                    </div>

                                    {{-- Batch Count + Total Duration (recipe only) --}}
                                    @if(!empty($event['has_recipe']))
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Number of Batches <span class="text-danger">*</span>
                                        </label>
                                        <input type="number"
                                            class="form-control"
                                            wire:model.live="events.{{ $index }}.batch_count"
                                            min="1"
                                            placeholder="Enter batch count">
                                        @error('events.' . $index . '.batch_count')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Total Duration</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light fw-bold" readonly
                                                value="{{ $event['total_duration'] }}">
                                            <span class="input-group-text">minutes</span>
                                        </div>
                                        <small class="text-muted">
                                            {{ $event['duration'] }} min &times; {{ $event['batch_count'] }} batch(es)
                                        </small>
                                    </div>
                                    @endif

                                    {{-- Time Section --}}
                                    <div class="col-12">
                                        <hr class="my-2">
                                    </div>

                                    {{-- From Time --}}
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            From Time <span class="text-danger">*</span>
                                        </label>
                                        <input type="time"
                                            class="form-control"
                                            wire:model.live="events.{{ $index }}.from_time">
                                        @error('events.' . $index . '.from_time')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- To Time (auto-calculated) --}}
                                    <div class="col-md-4">
                                        <label class="form-label">To Time</label>
                                        <div class="input-group">
                                            <input type="text"
                                                class="form-control bg-light fw-bold {{ $event['to_time'] ? 'text-success' : '' }}"
                                                readonly
                                                value="{{ $event['to_time'] ?: '—' }}">
                                            <span class="input-group-text">
                                                <i class="bi bi-clock"></i>
                                            </span>
                                        </div>
                                        @if($event['to_time'])
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Auto-calculated: {{ $event['total_duration'] }} min from {{ $event['from_time'] }}
                                        </small>
                                        @endif
                                    </div>

                                    {{-- Time Summary Badge --}}
                                    <div class="col-md-4 d-flex align-items-end">
                                        @if($event['to_time'] && $event['from_time'])
                                        <div class="alert alert-light border mb-0 py-2 px-3 w-100">
                                            <small class="fw-semibold">
                                                <i class="bi bi-clock-history me-1 text-primary"></i>
                                                {{ $event['from_time'] }} &rarr; {{ $event['to_time'] }}
                                                <span class="text-muted">({{ $event['total_duration'] }} min)</span>
                                            </small>
                                        </div>
                                        @endif
                                    </div>

                                    @endif {{-- end event_type_id check --}}

                                </div>
                            </div>
                        </div>
                        @endforeach

                    </div>
                </div>

                {{-- Submit --}}
                <div class="text-end mt-3 mb-4">
                    <button type="button" class="btn btn-primary px-4" wire:click="submit">
                        <i class="bi bi-check-lg me-1"></i>Save Events
                    </button>
                </div>

            </div>
        </div>
    </form>

    @script
    <script>
        // Initialize all selectpickers
        $('.selectpicker').selectpicker();

        // Re-init on Livewire DOM additions
        Livewire.hook('morph.added', ({el}) => {
            $(el).find('.selectpicker').selectpicker();
        });

        // Sync selectpicker changes to Livewire
        $(document).on('change', '.selectpicker', function () {
            let wireModel = $(this).attr('wire:model');
            if (wireModel) {
                $wire.set(wireModel, $(this).val());
            }
        });

        // Event type selection → call component method
        $(document).on('change', '.event-type-select', function () {
            let index = $(this).data('index');
            let value = $(this).val();
            $wire.call('onEventTypeChanged', index, value);
        });

        // Company change → dispatch event
        $(document).on('change', '#company_id', function () {
            $wire.dispatch('getEventTypes', {
                companyId: $(this).val()
            });
        });

        // Refresh event type dropdowns when types are loaded
        $wire.on('setEventTypes', function (params) {
            let eventTypes = params[0];
            setOptions($('.event-type-select'), eventTypes);
        });

        // SweetAlert confirmation for removing existing events
        $wire.on('swal:confirm', function (params) {
            let data = params[0];
            Swal.fire({
                title: data.title,
                text: data.text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: data.confirmButtonText,
                cancelButtonText: data.cancelButtonText,
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-secondary',
                },
                buttonsStyling: false,
            }).then(function (result) {
                if (result.isConfirmed) {
                    $wire.call('removeEventRowConfirmed');
                }
            });
        });

        // SweetAlert error for time conflicts
        $wire.on('swal:error', function (params) {
            let data = params[0];
            Swal.fire({
                title: data.title,
                text: data.text,
                icon: 'error',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                buttonsStyling: false,
            });
        });
    </script>
    @endscript
</div>