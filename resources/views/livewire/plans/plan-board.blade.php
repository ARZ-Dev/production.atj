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

    @script
    <script>
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

        initPlanBoard();

        Livewire.hook('morph.added', ({ el }) => {
            if (el.nodeType === 1) initPlanBoard();
        });
    </script>
    @endscript
</div>
