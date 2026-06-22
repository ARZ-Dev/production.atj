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
            <a href="{{ route('events.create', $plan->id) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i> Edit Events
            </a>
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

    <div class="pb-tabs">
        <button type="button" class="pb-tab {{ $boardView === 'calendar' ? 'active' : '' }}"
            wire:click="setView('calendar')">
            <i class="bi bi-calendar-week me-1"></i> Calendar
        </button>
        <button type="button" class="pb-tab {{ $boardView === 'kanban' ? 'active' : '' }}"
            wire:click="setView('kanban')">
            <i class="bi bi-kanban me-1"></i> Lines &amp; Preparations
        </button>
    </div>

    @if($boardView === 'calendar')
    <div class="pbc-wrap">
        <div class="pbc-tray">
            <div class="pbc-tray-head">
                <i class="bi bi-inbox text-primary"></i>
                <span class="pb-tray-title">Unscheduled</span>
                <span class="badge bg-light-primary rounded-pill">{{ count($unscheduledEvents) }}</span>
            </div>
            <div class="pbc-tray-body pbc-drop" data-tray>
                @forelse($unscheduledEvents as $event)
                    @include('livewire.plans.partials._calendar-event-card', [
                        'event' => $event,
                        'track' => 0,
                        'fromHour' => 0,
                        'spanHours' => 1,
                    ])
                @empty
                <div class="pb-empty-hint">All events are scheduled.</div>
                @endforelse
            </div>
        </div>

        <div class="pbc-grid">
            <div class="pbc-corner"></div>
            <div class="pbc-hour-row">
                @for($h = 0; $h < 24; $h++)
                <div class="pbc-hour-cell">{{ sprintf('%02d:00', $h) }}</div>
                @endfor
            </div>

            @php $calendarRows = $this->calendarRows(); @endphp
            @forelse($calendarRows as $row)
            @php $pl = $row['production_line']; $layout = $row['layout']; @endphp
            <div class="pbc-row-label" wire:key="pbc-label-{{ $pl->id }}">{{ $pl->name }}</div>
            <div class="pbc-row-track pbc-drop" data-production-line-id="{{ $pl->id }}"
                wire:key="pbc-track-{{ $pl->id }}"
                style="height: {{ max($layout['tracks'], 1) * 36 + 12 }}px;">
                @foreach($layout['items'] as $item)
                    @include('livewire.plans.partials._calendar-event-card', [
                        'event' => $item['event'],
                        'track' => $item['track'],
                        'fromHour' => $item['fromHour'],
                        'spanHours' => $item['spanHours'],
                    ])
                @endforeach
            </div>
            @empty
            <div class="pbc-empty">
                No production lines found for this factory.
            </div>
            @endforelse
        </div>
    </div>
    @else

    <div class="pb-board">

        <div class="pb-tray">
            <div class="pb-tray-head">
                <i class="bi bi-inbox text-primary"></i>
                <span class="pb-tray-title">Unplaced Events</span>
                <span class="badge bg-light-primary rounded-pill">{{ count($unplacedEvents) }}</span>
            </div>
            <div class="pb-tray-body pb-lane-drop" data-lane-tray>
                @forelse($unplacedEvents as $event)
                    @include('livewire.plans.partials._event-card', ['event' => $event])
                @empty
                <div class="pb-empty-hint">All events are placed.</div>
                @endforelse
            </div>
        </div>

        <div class="pb-columns">
            @forelse($productionLines as $pl)
            @php
                $lanes = collect($pl->preparations->map(fn($p) => ['type' => 'preparation', 'id' => $p->id, 'name' => $p->name]))
                    ->concat($pl->lines->map(fn($l) => ['type' => 'line', 'id' => $l->id, 'name' => $l->name]));
            @endphp
            <div class="pb-column" wire:key="pb-pl-{{ $pl->id }}">
                <div class="pb-column-head">{{ $pl->name }}</div>

                @forelse($lanes as $lane)
                @php
                    $laneKey = $this->laneKey($pl->id, $lane['type'], $lane['id']);
                    $laneEvents = $placedEvents[$laneKey] ?? [];
                @endphp
                <div class="pb-lane" wire:key="pb-lane-{{ $pl->id }}-{{ $lane['type'] }}-{{ $lane['id'] }}">
                    <div class="pb-lane-head">
                        <i class="bi bi-{{ $lane['type'] === 'preparation' ? 'egg-fried' : 'diagram-3' }}"></i>
                        {{ $lane['name'] }}
                    </div>
                    <div class="pb-lane-body pb-lane-drop"
                        data-production-line-id="{{ $pl->id }}"
                        data-placeable-type="{{ $lane['type'] }}"
                        data-placeable-id="{{ $lane['id'] }}">
                        @forelse($laneEvents as $event)
                            @include('livewire.plans.partials._event-card', ['event' => $event])
                        @empty
                        <div class="pb-empty-hint">Drop here</div>
                        @endforelse
                    </div>
                </div>
                @empty
                <div class="pb-empty-hint">No preparations or lines attached to this production line.</div>
                @endforelse
            </div>
            @empty
            <div class="pb-empty">
                <i class="bi bi-diagram-3"></i>
                <p class="mb-1 fw-semibold" style="opacity:.55">No production lines found</p>
                <p class="small mb-0" style="opacity:.35">This factory has no production lines set up yet.</p>
            </div>
            @endforelse
        </div>

    </div>
    @endif
    @endif

    @script
    <script>
    (() => {
        // ── Kanban tab: lane-based drag (Sortable) ─────────────────────────────
        const initPlanBoard = () => {
            document.querySelectorAll('.pb-lane-drop').forEach(el => {
                if (el.dataset.sortableInit) return;
                el.dataset.sortableInit = '1';

                new Sortable(el, {
                    group: 'plan-board',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function (evt) {
                        const eventId = parseInt(evt.item.dataset.eventId);
                        const target  = evt.to;

                        const productionLineId = target.dataset.productionLineId
                            ? parseInt(target.dataset.productionLineId) : null;
                        const placeableType = target.dataset.placeableType || null;
                        const placeableId   = target.dataset.placeableId
                            ? parseInt(target.dataset.placeableId) : null;

                        $wire.call('placeEvent', eventId, productionLineId, placeableType, placeableId);
                    }
                });
            });
        };

        // ── Calendar tab: time + row drag (native HTML5 DnD) ───────────────────
        const pxPerHour = (el) => {
            const v = parseFloat(getComputedStyle(el).getPropertyValue('--pbc-hour-w'));
            return isFinite(v) && v > 0 ? v : 70;
        };

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

                    let hour = 0;
                    if (productionLineId) {
                        const rect = drop.getBoundingClientRect();
                        const offsetX = e.clientX - rect.left + drop.scrollLeft;
                        hour = Math.round(offsetX / pxPerHour(drop));
                        hour = Math.max(0, Math.min(23, hour));
                    }

                    $wire.call('rescheduleEvent', eventId, productionLineId, hour);
                });
            });
        };

        const initAll = () => {
            initPlanBoard();
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
