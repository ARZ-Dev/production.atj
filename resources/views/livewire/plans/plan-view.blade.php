<div>
    <div>
        <div class="pv-header">
            <div>
                <div class="pv-title">Plan #{{ $plan->id }}</div>
                <div class="pv-chips">
                    <span class="pv-chip">
                        <i class="bi bi-calendar3 text-primary chip-icon"></i>
                        {{ \Carbon\Carbon::parse($plan->date)->format('d F Y') }}
                    </span>
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

        @php $total = $events->count(); @endphp
        <div class="pv-stats">
            <div class="card pv-stat pv-stat--primary mb-0">
                <div class="s-label">Total Events</div>
                <div class="s-val">{{ $total }}</div>
            </div>
            <div class="card pv-stat pv-stat--warning mb-0">
                <div class="s-label">Date</div>
                <div class="s-val s-val--sm">
                    {{ \Carbon\Carbon::parse($plan->date)->format('d/m/Y') }}
                </div>
            </div>
        </div>

        <div class="pv-board">
            <div class="pv-board-head">
                <i class="bi bi-list-task text-primary"></i>
                <span class="pv-board-title">Events</span>
                <span class="badge bg-light-primary rounded-pill">{{ $total }}</span>
            </div>

            @if($total > 0)
                @foreach($events as $event)
                <div class="pvc-card">
                    <div class="pvc-head">
                        <span class="pvc-seq">#{{ $loop->iteration }}</span>
                        @if($event->eventType)
                        <span class="pvc-type-badge">
                            <i class="bi bi-tag-fill icon-xxs"></i>
                            {{ $event->eventType->name }}
                        </span>
                        @else
                        <span class="pvc-type-badge" style="opacity:.4">No Type</span>
                        @endif
                    </div>
                    @if($event->from_time || $event->description)
                    <div class="pvc-body">
                        @if($event->from_time)
                        <div class="d-flex align-items-center gap-2 mb-2 pvc-time-info">
                            <i class="bi bi-clock chip-icon"></i>
                            <span>{{ \Carbon\Carbon::parse($event->from_time)->format('H:i') }}</span>
                            @if($event->to_time)
                            <i class="bi bi-arrow-right pvc-time-arrow"></i>
                            <span>{{ \Carbon\Carbon::parse($event->to_time)->format('H:i') }}</span>
                            @endif
                        </div>
                        @endif
                        @if($event->description)
                        <div class="pvc-body-label">Description</div>
                        {{ $event->description }}
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
            @else
                <div class="pv-empty">
                    <i class="bi bi-calendar-x"></i>
                    <p class="mb-1 fw-semibold" style="opacity:.55">No events yet</p>
                    <p class="small mb-3" style="opacity:.35">Add events to this plan to get started.</p>
                    @hasPermission('production.event-create')
                    <a href="{{ route('events.create', $plan->id) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Add Events
                    </a>
                    @endhasPermission
                </div>
            @endif
        </div>
    </div>
</div>
