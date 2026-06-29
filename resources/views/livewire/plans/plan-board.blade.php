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
                <span class="pv-chip">
                    <i class="bi bi-calendar3 text-primary chip-icon"></i>
                    {{ \Carbon\Carbon::parse($plan->date)->format('d F Y') }}
                </span>
                @if($factoryName)
                <span class="pv-chip green">
                    <i class="bi bi-building chip-icon"></i>
                    {{ $factoryName }}
                </span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            @hasPermission('production.event-create')
            <button type="button" id="addEventBtn" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Event
            </button>
            @endhasPermission
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
                    @for($h = 0; $h < 24; $h++)
                    <div class="pbc-hour-cell">{{ sprintf('%02d:00', $h) }}</div>
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
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="pvc-body-label">Time</div>
                            <div>{{ $selectedEvent['from_time'] ?? '—' }}{{ $selectedEvent['to_time'] ? ' – '.$selectedEvent['to_time'] : '' }}</div>
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
                    @if($selectedEvent)
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

    <div id="pbToastHost" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;"></div>

    @script
    <script>
    (() => {
        const eventDetailsModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
        $wire.on('openEventModal', () => eventDetailsModal.show());

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

        const pxPerHour = (el) => {
            const v = parseFloat(getComputedStyle(el).getPropertyValue('--pbc-hour-w'));
            return isFinite(v) && v > 0 ? v : 70;
        };

        const showRejectedToast = (message) => {
            const host = document.getElementById('pbToastHost');
            const toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-center text-bg-danger border-0';
            toastEl.setAttribute('role', 'alert');
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>`;
            host.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        };

        $wire.on('dropRejected', ({ message }) => showRejectedToast(message));

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
                    let hour = Math.round(offsetX / pxPerHour(drop));
                    hour = Math.max(0, Math.min(23, hour));

                    $wire.call('dropEvent', eventId, productionLineId, placeableType, placeableId, hour);
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
