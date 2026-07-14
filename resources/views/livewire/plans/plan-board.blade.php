<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="pv-header">
        <div>
            <div class="pv-title">Plan #{{ $plan->id }}</div>
            <div class="pv-chips">
                <div class="btn-group btn-group-sm me-1">
                    <button type="button" class="btn btn-light" wire:click="goToPreviousDay"
                        wire:loading.attr="disabled" wire:target="goToPreviousDay" title="Previous day">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span class="pv-chip mx-2">
                        <i class="bi bi-calendar3 text-primary chip-icon"></i>
                        {{ \Carbon\Carbon::parse($plan->date)->format('d F Y') }}
                    </span>
                    <button type="button" class="btn btn-light" wire:click="goToNextDay"
                        wire:loading.attr="disabled" wire:target="goToNextDay" title="Next day">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                @if($factoryName)
                <span class="pv-chip green">
                    <i class="bi bi-building chip-icon"></i>
                    {{ $factoryName }}
                </span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2 flex-shrink-0 align-items-start">
            @if($plan->factory_id)
            <div class="btn-group btn-group-sm" role="group" aria-label="Switch view">
                <button type="button" class="btn {{ $view === 'board' ? 'btn-primary' : 'btn-light' }}"
                    wire:click="$set('view', 'board')" title="Timeline board view">
                    <i class="bi bi-kanban me-1"></i> Board
                </button>
                <button type="button" class="btn {{ $view === 'list' ? 'btn-primary' : 'btn-light' }}"
                    wire:click="$set('view', 'list')" title="List view">
                    <i class="bi bi-list-ul me-1"></i> List
                </button>
            </div>
            @endif
            @hasPermission('production.event-create')
            <button type="button" id="addEventBtn" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Event
            </button>
            @endhasPermission
            <button type="button" class="btn btn-success btn-sm" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel">
                <span wire:loading wire:target="exportExcel" class="spinner-border spinner-border-sm me-1"></span>
                <i class="bi bi-file-earmark-excel me-1" wire:loading.remove wire:target="exportExcel"></i>
                Export Excel
            </button>
            <a href="{{ route('plans') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @if(!$plan->factory_id)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        This plan has no factory assigned. Edit the plan to assign a factory before placing events on a line.
    </div>
    @elseif($view === 'list')

    @include('livewire.plans.partials._event-list', ['rows' => $this->listRows()])

    @else

    <div class="pbc-wrap">
        <div class="pbc-tray">
            <div class="pbc-tray-head">
                <i class="bi bi-inbox text-primary"></i>
                <span class="pb-tray-title">Unplaced Events</span>
                <span class="badge bg-light-primary rounded-pill">{{ count($unplacedEvents) }}</span>
            </div>
            <div class="pbc-tray-body pbc-drop" data-tray>
                @forelse($unplacedEvents as $event)
                    @include('livewire.plans.partials._calendar-event-card', [
                        'event' => $event,
                        'track' => 0,
                        'fromHour' => 0,
                        'spanHours' => 1,
                    ])
                @empty
                <div class="pb-empty-hint">All events are placed.</div>
                @endforelse
            </div>
        </div>

        <div class="pbc-grid-wrap">
            <div class="pbc-grid">
                <div class="pbc-corner"></div>
                <div class="pbc-hour-row">
                    @for($slot = 0; $slot < 96; $slot++)
                    @php $mins = $slot * 15; @endphp
                    <div class="pbc-hour-cell {{ $mins % 60 === 0 ? 'is-hour' : '' }}"><span>{{ sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60) }}</span></div>
                    @endfor
                </div>

                @php $laneRows = $this->laneRows(); @endphp
                @forelse($laneRows as $row)
                @php $pl = $row['production_line']; $laneCount = max(count($row['lanes']), 1); @endphp

                @if(count($row['lanes']))
                    @foreach($row['lanes'] as $i => $laneRow)
                    @php $lane = $laneRow['lane']; $layout = $laneRow['layout']; @endphp
                    @if($i === 0)
                    <div class="pbc-pl-label" style="grid-row: span {{ $laneCount }};" wire:key="pbc-pl-{{ $pl->id }}">
                        {{ $pl->name }}
                    </div>
                    @endif
                    <div class="pbc-row-label" wire:key="pbc-lane-label-{{ $pl->id }}-{{ $lane['type'] }}-{{ $lane['id'] }}">
                        <i class="bi bi-{{ $lane['type'] === 'preparation' ? 'egg-fried' : 'diagram-3' }} me-1"></i>
                        {{ $lane['name'] }}
                    </div>
                    <div class="pbc-row-track pbc-drop"
                        data-production-line-id="{{ $pl->id }}"
                        data-placeable-type="{{ $lane['type'] }}"
                        data-placeable-id="{{ $lane['id'] }}"
                        wire:key="pbc-lane-track-{{ $pl->id }}-{{ $lane['type'] }}-{{ $lane['id'] }}"
                        style="height: {{ max($layout['tracks'], 1) * 48 + 12 }}px;">
                        @foreach($layout['items'] as $item)
                            @include('livewire.plans.partials._calendar-event-card', [
                                'event' => $item['event'],
                                'track' => $item['track'],
                                'fromHour' => $item['fromHour'],
                                'spanHours' => $item['spanHours'],
                            ])
                        @endforeach
                    </div>
                    @endforeach
                @else
                    <div class="pbc-pl-label" style="grid-row: span 1;" wire:key="pbc-pl-{{ $pl->id }}">
                        {{ $pl->name }}
                    </div>
                    <div class="pbc-row-track-empty">No preparations or lines attached to this production line.</div>
                @endif

                @empty
                <div class="pbc-empty">
                    No production lines found for this factory.
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailsModalLabel">
                        @if($selectedEvent)
                        <span class="d-inline-block rounded-circle me-2" style="width:12px;height:12px;background-color: {{ $selectedEvent['color'] }}"></span>
                        {{ $selectedEvent['type_name'] }}
                        @else
                        Event Details
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @if($selectedEvent)
                <div class="modal-body">
                    @if($selectedEvent['is_carry_over'])
                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size:12px;">
                        <i class="bi bi-arrow-return-right me-1"></i>
                        Started on {{ $selectedEvent['plan_date'] }} and runs past midnight into this day.
                        Open that day's plan to edit it.
                    </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="pvc-body-label">Time</div>
                            <div>
                                {{ $selectedEvent['from_time'] ?? '—' }}{{ $selectedEvent['to_time'] ? ' – '.$selectedEvent['to_time'] : '' }}
                                @if($selectedEvent['crosses_midnight'] && $selectedEvent['to_time'])
                                <span class="text-muted" style="font-size:11px;">(+{{ $selectedEvent['to_day_offset'] }} day{{ $selectedEvent['to_day_offset'] > 1 ? 's' : '' }})</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="pvc-body-label">Duration</div>
                            <div>{{ $selectedEvent['duration'] ? $selectedEvent['duration'].' min' : '—' }}</div>
                        </div>

                        @if($selectedEvent['has_recipe'])
                        <div class="col-6">
                            <div class="pvc-body-label">Batch Count</div>
                            <div>{{ $selectedEvent['batch_count'] ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="pvc-body-label">Item</div>
                            <div>{{ $selectedEvent['item_name'] ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="pvc-body-label">Recipe Type</div>
                            <div>{{ $selectedEvent['recipe_type_name'] ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="pvc-body-label">Recipe</div>
                            <div>{{ $selectedEvent['recipe_name'] ?? '—' }}</div>
                        </div>
                        @endif

                        <div class="col-6">
                            <div class="pvc-body-label">Production Line</div>
                            <div>{{ $selectedEvent['production_line_name'] ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="pvc-body-label">{{ $selectedEvent['placeable_kind'] ?? 'Lane' }}</div>
                            <div>{{ $selectedEvent['placeable_name'] ?? '—' }}</div>
                        </div>

                        @if($selectedEvent['status'])
                        <div class="col-6">
                            <div class="pvc-body-label">Status</div>
                            <div>{{ $selectedEvent['status'] }}</div>
                        </div>
                        @endif

                        @if($selectedEvent['description'])
                        <div class="col-12">
                            <div class="pvc-body-label">Description</div>
                            <div>{{ $selectedEvent['description'] }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                <div class="modal-footer">
                    @hasPermission('production.event-create')
                    @if($selectedEvent && !$selectedEvent['is_carry_over'])
                    <button type="button" class="btn btn-danger me-auto"
                        wire:click="deleteEvent({{ $selectedEvent['id'] }})"
                        wire:confirm="Delete this event? This cannot be undone.">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                    <button type="button" id="editEventBtn" class="btn btn-primary" data-event-id="{{ $selectedEvent['id'] }}">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </button>
                    @endif
                    @endhasPermission
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @hasPermission('production.event-create')
    <!-- Event Create/Edit Modal -->
    <div class="modal fade" id="eventCreateModal" tabindex="-1" aria-labelledby="eventCreateModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventCreateModalLabel">
                        <i class="bi bi-list-task text-primary me-2"></i>
                        <span id="eventCreateModalTitleText">Add Event</span> — Plan #{{ $plan->id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('events.event-create', ['planId' => $plan->id], key('event-create-' . $plan->id))
                </div>
            </div>
        </div>
    </div>
    @endhasPermission

    @hasPermission('production.event-create')
    <!-- Event Status Action Modal (start / pause / resume / terminate) -->
    <div class="modal fade" id="eventActionModal" tabindex="-1" aria-labelledby="eventActionModalLabel" aria-hidden="true"
        data-bs-backdrop="static" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                @if($eventAction)
                @php
                    $actionMeta = [
                        'start'     => ['title' => 'Start Event',     'icon' => 'play-fill',  'btn' => 'btn-success'],
                        'pause'     => ['title' => 'Pause Event',     'icon' => 'pause-fill', 'btn' => 'btn-warning'],
                        'resume'    => ['title' => 'Resume Event',    'icon' => 'play-fill',  'btn' => 'btn-success'],
                        'terminate' => ['title' => 'Terminate Event', 'icon' => 'stop-fill',  'btn' => 'btn-danger'],
                    ][$eventAction['action']];
                @endphp
                <div class="modal-header">
                    <h5 class="modal-title" id="eventActionModalLabel">
                        <i class="bi bi-{{ $actionMeta['icon'] }} me-1"></i>
                        {{ $actionMeta['title'] }} —
                        <span class="d-inline-block rounded-circle mx-1" style="width:12px;height:12px;background-color: {{ $eventAction['color'] }}"></span>
                        {{ $eventAction['type_name'] }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($eventAction['action'] === 'start')

                    <p class="text-muted mb-2" style="font-size: 12px;">
                        Record the actual quantity used for each recipe item, then start the event.
                    </p>
                    @if(count($actionInputs))
                    @include('livewire.plans.partials._action-quantity-table', [
                        'rows'         => $actionInputs,
                        'model'        => 'actionInputs',
                        'actualLabel'  => 'Used Qty',
                        'percentLabel' => '% Used',
                    ])
                    @else
                    <div class="alert alert-warning py-2 px-3" style="font-size: 12px;">
                        This recipe has no input items defined.
                    </div>
                    @endif
                    <div class="mt-2">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" rows="3" wire:model="actionNotes"
                            placeholder="Optional notes about the quantities used…"></textarea>
                    </div>

                    @elseif($eventAction['action'] === 'terminate')

                    <div class="fw-bold mb-1" style="font-size: 13px;">
                        <i class="bi bi-box-seam me-1 text-primary"></i> Produced Item
                    </div>
                    @include('livewire.plans.partials._action-quantity-table', [
                        'rows'         => $actionOutputs,
                        'model'        => 'actionOutputs',
                        'actualLabel'  => 'Produced Qty',
                        'percentLabel' => '% Produced',
                    ])

                    @if(count($actionSideProducts))
                    <div class="fw-bold mb-1 mt-3" style="font-size: 13px;">
                        <i class="bi bi-boxes me-1 text-primary"></i> Side Products
                    </div>
                    @include('livewire.plans.partials._action-quantity-table', [
                        'rows'         => $actionSideProducts,
                        'model'        => 'actionSideProducts',
                        'actualLabel'  => 'Produced Qty',
                        'percentLabel' => '% Produced',
                    ])
                    @endif

                    <div class="mt-2">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" rows="3" wire:model="actionNotes"
                            placeholder="Optional notes about the produced quantities…"></textarea>
                    </div>

                    @elseif($eventAction['action'] === 'pause')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pause Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('pauseEventTypeId') is-invalid @enderror"
                                wire:model.live="pauseEventTypeId">
                                <option value="">Select type…</option>
                                @foreach($pauseEventTypes as $type)
                                <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                @endforeach
                            </select>
                            @error('pauseEventTypeId')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Duration</label>
                            @php $selectedPauseType = collect($pauseEventTypes)->firstWhere('id', (int) $pauseEventTypeId); @endphp
                            <input type="text" class="form-control" disabled
                                value="{{ $selectedPauseType && $selectedPauseType['duration'] ? $selectedPauseType['duration'] . ' min' : '—' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" rows="3" wire:model="actionReason"
                                placeholder="Why is this event being paused?"></textarea>
                        </div>
                    </div>

                    @elseif($eventAction['action'] === 'resume')

                    <div class="row g-3">
                        @if($eventAction['paused_at'])
                        <div class="col-md-6">
                            <div class="pvc-body-label">Paused At</div>
                            <div>{{ $eventAction['paused_at'] }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="pvc-body-label">Pause Type</div>
                            <div>{{ $eventAction['pause_type_name'] ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="pvc-body-label">Expected Duration</div>
                            <div>{{ $eventAction['expected_duration'] ? $eventAction['expected_duration'] . ' min' : '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="pvc-body-label">Actual Duration (so far)</div>
                            <div>
                                {{ $eventAction['actual_duration'] !== null ? $eventAction['actual_duration'] . ' min' : '—' }}
                                @if($eventAction['expected_duration'] && $eventAction['actual_duration'] > $eventAction['expected_duration'])
                                <span class="badge bg-danger ms-1">over expected</span>
                                @endif
                            </div>
                        </div>
                        @if($eventAction['pause_reason'])
                        <div class="col-12">
                            <div class="pvc-body-label">Pause Reason</div>
                            <div>{{ $eventAction['pause_reason'] }}</div>
                        </div>
                        @endif
                        @else
                        <div class="col-12">
                            <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 12px;">
                                No pause record was found for this event.
                            </div>
                        </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" rows="3" wire:model="actionReason"
                                placeholder="Optional resume notes…"></textarea>
                        </div>
                    </div>

                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn {{ $actionMeta['btn'] }}" wire:click="submitEventAction"
                        wire:loading.attr="disabled" wire:target="submitEventAction">
                        <span wire:loading wire:target="submitEventAction" class="spinner-border spinner-border-sm me-1"></span>
                        {{ $actionMeta['title'] }}
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endhasPermission

    <!-- Event Status History Modal -->
    <div class="modal fade" id="eventHistoryModal" tabindex="-1" aria-labelledby="eventHistoryModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventHistoryModalLabel">
                        <i class="bi bi-clock-history text-primary me-2"></i>
                        @if($eventHistory)
                        Status History —
                        <span class="d-inline-block rounded-circle mx-1" style="width:12px;height:12px;background-color: {{ $eventHistory['color'] }}"></span>
                        {{ $eventHistory['type_name'] }}
                        @else
                        Status History
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($eventHistory)
                    @if(empty($eventHistory['logs']))
                    <div class="pb-empty-hint">No status changes have been recorded for this event yet.</div>
                    @else
                    @php
                        $actionBadges = [
                            'start'     => 'bg-success',
                            'pause'     => 'bg-warning text-dark',
                            'resume'    => 'bg-info text-dark',
                            'terminate' => 'bg-danger',
                        ];
                        $sourceLabels = ['input' => 'Used', 'output' => 'Produced', 'side_product' => 'Side Product'];
                    @endphp
                    <div class="d-flex flex-column gap-3">
                        @foreach($eventHistory['logs'] as $log)
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge {{ $actionBadges[$log['action']] ?? 'bg-secondary' }}">{{ ucfirst($log['action']) }}</span>
                                <span style="font-size: 12px;">{{ $log['from'] }} <i class="bi bi-arrow-right"></i> {{ $log['to'] }}</span>
                                <span class="text-muted ms-auto" style="font-size: 12px;">
                                    {{ $log['at'] }}{{ $log['by'] ? ' · ' . $log['by'] : '' }}
                                </span>
                            </div>
                            @if($log['pause_type'] || $log['expected_duration'] || $log['actual_duration'] !== null)
                            <div class="mt-2" style="font-size: 12px;">
                                @if($log['pause_type'])
                                <span class="me-3"><span class="text-muted">Type:</span> {{ $log['pause_type'] }}</span>
                                @endif
                                @if($log['expected_duration'])
                                <span class="me-3"><span class="text-muted">Expected:</span> {{ $log['expected_duration'] }} min</span>
                                @endif
                                @if($log['actual_duration'] !== null)
                                <span><span class="text-muted">Actual:</span> {{ $log['actual_duration'] }} min</span>
                                @endif
                            </div>
                            @endif
                            @if($log['reason'])
                            <div class="mt-1" style="font-size: 12px;"><span class="text-muted">Reason:</span> {{ $log['reason'] }}</div>
                            @endif
                            @if($log['notes'])
                            <div class="mt-1" style="font-size: 12px;"><span class="text-muted">Notes:</span> {{ $log['notes'] }}</div>
                            @endif
                            @if(count($log['quantities']))
                            <div class="table-responsive mt-2">
                                <table class="table table-sm mb-0" style="font-size: 12px;">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Item</th>
                                            <th>Unit</th>
                                            <th class="text-end">Original</th>
                                            <th class="text-end">Actual</th>
                                            <th class="text-end">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($log['quantities'] as $qty)
                                        <tr>
                                            <td><span class="badge bg-light-primary">{{ $sourceLabels[$qty['source']] ?? $qty['source'] }}</span></td>
                                            <td>{{ $qty['item_name'] }}</td>
                                            <td>{{ $qty['unit_name'] ?? '—' }}</td>
                                            <td class="text-end">{{ $qty['planned'] + 0 }}</td>
                                            <td class="text-end">{{ $qty['actual'] + 0 }}</td>
                                            <td class="text-end">{{ $qty['percentage'] !== null ? ($qty['percentage'] + 0) . '%' : '—' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="pbToastHost" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;"></div>

    @script
    <script>
    (() => {
        const eventDetailsModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
        $wire.on('openEventModal', () => eventDetailsModal.show());
        $wire.on('closeEventDetailsModal', () => eventDetailsModal.hide());

        // Status action modal (start / pause / resume / terminate) — only in
        // the DOM for users with the event-create permission.
        const eventActionModalEl = document.getElementById('eventActionModal');
        const eventActionModal = eventActionModalEl
            ? bootstrap.Modal.getOrCreateInstance(eventActionModalEl)
            : null;
        $wire.on('openEventActionModal', () => eventActionModal?.show());
        $wire.on('closeEventActionModal', () => eventActionModal?.hide());

        const eventHistoryModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('eventHistoryModal'));
        $wire.on('openEventHistoryModal', () => eventHistoryModal.show());

        const eventCreateModalEl = document.getElementById('eventCreateModal');
        const eventCreateModalTitleText = document.getElementById('eventCreateModalTitleText');
        const eventCreateModal = eventCreateModalEl
            ? bootstrap.Modal.getOrCreateInstance(eventCreateModalEl)
            : null;

        document.addEventListener('click', (e) => {
            if (e.target.closest('#addEventBtn')) {
                Livewire.dispatchTo('events.event-create', 'openForCreate');
                if (eventCreateModalTitleText) eventCreateModalTitleText.textContent = 'Add Event';
                eventCreateModal?.show();
                return;
            }

            const editBtn = e.target.closest('#editEventBtn');
            if (editBtn) {
                const eventId = parseInt(editBtn.dataset.eventId);
                if (!eventId) return;
                Livewire.dispatchTo('events.event-create', 'openForEdit', { eventId });
                if (eventCreateModalTitleText) eventCreateModalTitleText.textContent = 'Edit Event';
                eventDetailsModal.hide();
                eventCreateModal?.show();
            }
        });

        const pxPerSlot = (el) => {
            const v = parseFloat(getComputedStyle(el).getPropertyValue('--pbc-slot-w'));
            return isFinite(v) && v > 0 ? v : 46;
        };

        const showToast = (message, variant = 'danger', delay = 4000) => {
            const host = document.getElementById('pbToastHost');
            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-bg-${variant} border-0`;
            toastEl.setAttribute('role', 'alert');
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>`;
            host.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, { delay });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        };

        $wire.on('dropRejected', ({ message }) => showToast(message));
        $wire.on('carryOverPlanCreated', ({ message }) => showToast(message, 'success', 6000));

        const initCalendarDrag = () => {
            document.querySelectorAll('.pbc-card[draggable="true"]').forEach(card => {
                if (card.dataset.dragInit) return;
                card.dataset.dragInit = '1';

                card.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', card.dataset.eventId);
                    e.dataTransfer.effectAllowed = 'move';
                });
            });

            document.querySelectorAll('.pbc-drop').forEach(drop => {
                if (drop.dataset.dropInit) return;
                drop.dataset.dropInit = '1';

                drop.addEventListener('dragover', (e) => e.preventDefault());

                drop.addEventListener('drop', (e) => {
                    e.preventDefault();
                    const eventId = parseInt(e.dataTransfer.getData('text/plain'));
                    if (!eventId) return;

                    const productionLineId = drop.dataset.productionLineId
                        ? parseInt(drop.dataset.productionLineId) : null;
                    const placeableType = drop.dataset.placeableType || null;
                    const placeableId   = drop.dataset.placeableId
                        ? parseInt(drop.dataset.placeableId) : null;

                    if (drop.hasAttribute('data-tray')) {
                        $wire.call('unplaceEvent', eventId);
                        return;
                    }

                    if (!productionLineId || !placeableType || !placeableId) {
                        return;
                    }

                    const rect = drop.getBoundingClientRect();
                    const offsetX = e.clientX - rect.left + drop.scrollLeft;
                    const slotW = pxPerSlot(drop);
                    let slot = Math.round(offsetX / slotW);
                    slot = Math.max(0, Math.min(95, slot));

                    $wire.call('dropEvent', eventId, productionLineId, placeableType, placeableId, slot);
                });
            });
        };

        const initAll = () => {
            initCalendarDrag();
        };

        initAll();

        Livewire.hook('morph.added', ({ el }) => {
            if (el.nodeType === 1) initAll();
        });
    })();
    </script>
    @endscript
</div>
