<div>
    <style>
        .plan-view-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }
        .plan-view-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .plan-view-sub {
            font-size: 14px;
            opacity: 0.7;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .stat-cards-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .stat-card .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 16px;
        }
        .stat-card .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.6;
            margin-bottom: 4px;
            font-weight: 500;
        }
        .stat-card .stat-value {
            font-size: 18px;
            font-weight: 700;
        }
        .mini-progress {
            height: 4px;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
            margin-top: 10px;
            overflow: hidden;
        }
        .mini-progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.8s ease;
        }
        .timeline-section-title {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .evt-timeline {
            position: relative;
            padding-left: 40px;
        }
        .evt-node {
            position: relative;
            margin-bottom: 16px;
        }
        .evt-node:last-child { margin-bottom: 0; }
        .evt-node::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 30px;
            bottom: -16px;
            width: 2px;
            background: rgba(130, 130, 180, 0.15);
        }
        .evt-node:last-child::before { display: none; }
        .evt-dot {
            position: absolute;
            left: -30px;
            top: 18px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2.5px solid #6c757d;
            background: var(--bs-card-bg, #1a1d29);
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .evt-dot::after {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #6c757d;
        }
        .evt-node.node-completed .evt-dot { border-color: #34d399; }
        .evt-node.node-completed .evt-dot::after { background: #34d399; }
        .evt-node.node-active .evt-dot { border-color: #fbbf24; }
        .evt-node.node-active .evt-dot::after { background: #fbbf24; animation: dotPulse 2s infinite; }
        .evt-node.node-pending .evt-dot { border-color: #64748b; }
        .evt-node.node-pending .evt-dot::after { background: #64748b; }
        .evt-node.node-cancelled .evt-dot { border-color: #f87171; }
        .evt-node.node-cancelled .evt-dot::after { background: #f87171; }
        .evt-card {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .evt-card:hover { transform: translateX(3px); }
        .evt-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            gap: 12px;
        }
        .evt-card-head-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }
        .evt-name {
            font-size: 15px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .evt-card-head-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .evt-time {
            font-size: 12px;
            opacity: 0.5;
        }
        .evt-arrow {
            font-size: 13px;
            opacity: 0.4;
            transition: transform 0.3s ease;
        }
        .evt-card.open .evt-arrow { transform: rotate(180deg); }
        .evt-card-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
        }
        .evt-card.open .evt-card-body { max-height: 500px; }
        .evt-card-detail {
            padding: 0 18px 18px 18px;
            border-top: 1px solid rgba(130,130,180,0.08);
            padding-top: 16px;
        }
        .evt-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }
        .evt-detail-box {
            border-radius: 8px;
            padding: 12px;
            background: rgba(130,130,180,0.04);
            border: 1px solid rgba(130,130,180,0.06);
        }
        .evt-detail-box .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            opacity: 0.5;
            margin-bottom: 2px;
            font-weight: 500;
        }
        .evt-detail-box .value {
            font-size: 14px;
            font-weight: 600;
        }
        .dur-compare {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }
        .dur-track {
            flex: 1;
            height: 5px;
            border-radius: 5px;
            background: rgba(130,130,180,0.08);
            overflow: hidden;
        }
        .dur-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.8s ease;
        }
        .dur-fill.ok { background: linear-gradient(90deg, #818cf8, #34d399); }
        .dur-fill.over { background: linear-gradient(90deg, #fbbf24, #f87171); }
        .dur-text {
            font-size: 11px;
            opacity: 0.5;
            white-space: nowrap;
        }
        .evt-tags {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .evt-empty {
            text-align: center;
            padding: 60px 30px;
            border-radius: 12px;
            border: 1px dashed rgba(130,130,180,0.12);
        }
        .evt-empty i { font-size: 40px; opacity: 0.3; margin-bottom: 12px; }
        .evt-empty h4 { margin-bottom: 6px; opacity: 0.7; }
        .evt-empty p { font-size: 14px; opacity: 0.4; }
        @keyframes dotPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        @media (max-width: 900px) {
            .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .stat-cards-row { grid-template-columns: 1fr; }
            .plan-view-header { flex-direction: column; }
            .evt-card-head { flex-direction: column; align-items: flex-start; }
            .evt-card-head-right { width: 100%; justify-content: flex-end; }
        }
    </style>

    <div class="row">
        <div class="col-12">

            {{-- Breadcrumb --}}
            {{-- <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('plans') }}">Plans</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Plan #{{ $plan->id }}</li>
                </ol>
            </nav> --}}

            {{-- Header --}}
            <div class="plan-view-header">
                <div>
                    <h2>Production Plan #{{ $plan->id }}</h2>
                    <div class="plan-view-sub">
                        <i class="bi bi-building"></i>
                        {{ $plan->company->name }} — {{ $plan->productionLine->name ?? 'Production Line #' . $plan->productionLine->id }}
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('plans') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>

                </div>
            </div>

            {{-- Stat Cards --}}
            @php
                $totalEvents = $events->count();
                $totalPlannedMin = $events->sum('planned_duration');
                $totalCalcMin = $events->sum('calculated_duration');
                $durPct = $totalPlannedMin > 0 ? min(round(($totalCalcMin / $totalPlannedMin) * 100), 100) : 0;
            @endphp

            <div class="stat-cards-row">
                <div class="card stat-card mb-0" style="border-left: 3px solid #818cf8;">
                    <div class="stat-icon" style="background: rgba(129,140,248,0.12); color: #818cf8;">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <div class="stat-label">Plan Date</div>
                    <div class="stat-value">{{ \Carbon\Carbon::parse($plan->date)->format('d / m / Y') }}</div>
                </div>
                <div class="card stat-card mb-0" style="border-left: 3px solid #34d399;">
                    <div class="stat-icon" style="background: rgba(52,211,153,0.12); color: #34d399;">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <div class="stat-label">Total Events</div>
                    <div class="stat-value">{{ $totalEvents }} {{ Str::plural('Event', $totalEvents) }}</div>
                </div>
                <div class="card stat-card mb-0" style="border-left: 3px solid #fbbf24;">
                    <div class="stat-icon" style="background: rgba(251,191,36,0.12); color: #fbbf24;">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stat-label">Planned Duration</div>
                    <div class="stat-value">
                        @if($totalPlannedMin >= 60)
                            {{ intdiv($totalPlannedMin, 60) }}h {{ $totalPlannedMin % 60 }}min
                        @else
                            {{ $totalPlannedMin }} min
                        @endif
                    </div>
                </div>
                <div class="card stat-card mb-0" style="border-left: 3px solid #38bdf8;">
                    <div class="stat-icon" style="background: rgba(56,189,248,0.12); color: #38bdf8;">
                        <i class="bi bi-stopwatch"></i>
                    </div>
                    <div class="stat-label">Actual Duration</div>
                    <div class="stat-value">
                        @if($totalCalcMin >= 60)
                            {{ intdiv($totalCalcMin, 60) }}h {{ $totalCalcMin % 60 }}min
                        @else
                            {{ $totalCalcMin }} min
                        @endif
                    </div>
                    @if($totalPlannedMin > 0)
                        <div class="mini-progress">
                            <div class="mini-progress-fill" style="width: {{ $durPct }}%; background: linear-gradient(90deg, #38bdf8, #34d399);"></div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Event Timeline --}}
            <div class="card">
                <div class="card-body">
                    <div class="timeline-section-title">
                        <i class="bi bi-diagram-3 text-primary"></i>
                        Event Timeline
                        <span class="badge bg-light-primary rounded-pill">{{ $totalEvents }} {{ Str::plural('event', $totalEvents) }}</span>
                    </div>

                    @if($events->count() > 0)
                        <div class="evt-timeline">
                            @foreach($events as $event)
                                @php
                                    $nodeClass = 'node-pending';
                                    $statusLabel = 'Pending';
                                    $badgeClass = 'bg-light-secondary';

                                    if (in_array($event->status, ['completed', 'done'])) {
                                        $nodeClass = 'node-completed';
                                        $statusLabel = 'Completed';
                                        $badgeClass = 'bg-light-success';
                                    } elseif (in_array($event->status, ['active', 'in_progress', 'in-progress'])) {
                                        $nodeClass = 'node-active';
                                        $statusLabel = 'In Progress';
                                        $badgeClass = 'bg-light-warning';
                                    } elseif (in_array($event->status, ['cancelled', 'canceled'])) {
                                        $nodeClass = 'node-cancelled';
                                        $statusLabel = 'Cancelled';
                                        $badgeClass = 'bg-light-danger';
                                    }

                                    $planned = $event->planned_duration ?? 0;
                                    $calculated = $event->calculated_duration ?? 0;
                                    $durPercent = $planned > 0 ? min(round(($calculated / $planned) * 100), 100) : 0;
                                    $isOver = $calculated > $planned && $planned > 0;

                                    $fromTime = $event->from_time ? \Carbon\Carbon::parse($event->from_time)->format('H:i') : '—';
                                    $toTime = $event->to_time ? \Carbon\Carbon::parse($event->to_time)->format('H:i') : '—';
                                @endphp

                                <div class="evt-node {{ $nodeClass }}">
                                    <div class="evt-dot"></div>
                                    <div class="evt-card card mb-0" onclick="this.classList.toggle('open')">
                                        <div class="evt-card-head">
                                            <div class="evt-card-head-left">
                                                <span class="badge bg-light-secondary" style="font-size:11px;">EVT-{{ str_pad($event->id, 3, '0', STR_PAD_LEFT) }}</span>
                                                <span class="evt-name">{{ $event->name }}</span>
                                            </div>
                                            <div class="evt-card-head-right">
                                                <span class="badge {{ $badgeClass }} rounded-pill">
                                                    <i class="bi bi-circle-fill" style="font-size: 6px; vertical-align: middle; margin-right: 4px;"></i>
                                                    {{ $statusLabel }}
                                                </span>
                                                <span class="evt-time">{{ $fromTime }} — {{ $toTime }}</span>
                                                <i class="bi bi-chevron-down evt-arrow"></i>
                                            </div>
                                        </div>
                                        <div class="evt-card-body">
                                            <div class="evt-card-detail">
                                                <div class="evt-detail-grid">
                                                    <div class="evt-detail-box">
                                                        <div class="label">From</div>
                                                        <div class="value">{{ $fromTime }}</div>
                                                    </div>
                                                    <div class="evt-detail-box">
                                                        <div class="label">To</div>
                                                        <div class="value">{{ $toTime }}</div>
                                                    </div>
                                                    <div class="evt-detail-box">
                                                        <div class="label">Planned</div>
                                                        <div class="value">{{ $planned }} min</div>
                                                    </div>
                                                    <div class="evt-detail-box">
                                                        <div class="label">Actual</div>
                                                        <div class="value">
                                                            {{ $calculated }} min
                                                            @if($isOver)
                                                                <span class="text-danger" style="font-size:11px;">(+{{ $calculated - $planned }})</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($event->shift)
                                                        <div class="evt-detail-box">
                                                            <div class="label">Shift</div>
                                                            <div class="value">{{ $event->shift->name ?? 'Shift #' . $event->shift_id }}</div>
                                                        </div>
                                                    @endif
                                                    @if($event->recipe)
                                                        <div class="evt-detail-box">
                                                            <div class="label">Recipe</div>
                                                            <div class="value">{{ $event->recipe->name ?? 'Recipe #' . $event->recipe_id }}</div>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if($planned > 0)
                                                    <div class="dur-compare">
                                                        <div class="dur-track">
                                                            <div class="dur-fill {{ $isOver ? 'over' : 'ok' }}" style="width: {{ $durPercent }}%;"></div>
                                                        </div>
                                                        <span class="dur-text">
                                                            {{ $calculated }}/{{ $planned }} min
                                                            @if($isOver)
                                                                <span class="text-danger">(+{{ $calculated - $planned }})</span>
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif

                                                <div class="evt-tags">
                                                    @if($event->eventType)
                                                        <span class="badge bg-light-primary rounded-pill">{{ $event->eventType->name ?? 'Type #' . $event->event_type_id }}</span>
                                                    @endif
                                                    @if($event->recipe)
                                                        <span class="badge bg-light-info rounded-pill">{{ $event->recipe->name ?? 'Recipe' }}</span>
                                                    @endif
                                                    @if($isOver)
                                                        <span class="badge bg-light-danger rounded-pill">Over Time</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="evt-empty">
                            <i class="bi bi-calendar-x d-block"></i>
                            <h4>No Events Yet</h4>
                            <p>This plan doesn't have any events. Add one to get started.</p>
                            <a href="{{ route('events.create', $plan->id) }}" class="btn btn-primary btn-sm mt-2">
                                <i class="bi bi-plus-lg me-1"></i> Add First Event
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>