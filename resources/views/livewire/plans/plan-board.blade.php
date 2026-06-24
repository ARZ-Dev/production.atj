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
            <button type="button" class="pbc-scroll-btn pbc-scroll-btn--left" aria-label="Scroll left">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button type="button" class="pbc-scroll-btn pbc-scroll-btn--right" aria-label="Scroll right">
                <i class="bi bi-chevron-right"></i>
            </button>

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

    <div id="pbToastHost" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;"></div>

    @script
    <script>
    (() => {
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

        const initScrollButtons = () => {
            document.querySelectorAll('.pbc-scroll-btn').forEach(btn => {
                if (btn.dataset.scrollInit) return;
                btn.dataset.scrollInit = '1';

                btn.addEventListener('click', () => {
                    const grid = btn.closest('.pbc-grid-wrap')?.querySelector('.pbc-grid');
                    if (!grid) return;
                    const dir = btn.classList.contains('pbc-scroll-btn--left') ? -1 : 1;
                    grid.scrollBy({ left: dir * 240, behavior: 'smooth' });
                });
            });
        };

        const initAll = () => {
            initCalendarDrag();
            initScrollButtons();
        };

        initAll();

        Livewire.hook('morph.added', ({ el }) => {
            if (el.nodeType === 1) initAll();
        });
    })();
    </script>
    @endscript
</div>
