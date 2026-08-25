<div>
    @php
        $mins   = fn($m) => \App\Livewire\DashboardView::humanMinutes($m);
        $meta   = \App\Livewire\DashboardView::STATUS_META;
        $labels = \App\Livewire\DashboardView::PERIODS;
    @endphp

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="db-header">
        <div>
            <div class="db-title">Production Dashboard</div>
            <div class="db-subtitle">
                <i class="bi bi-calendar3 me-1"></i>
                {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}
                <span class="db-dot-sep"></span>
                {{ $periodLabel }}
                @if($factoryId !== '')
                    <span class="db-dot-sep"></span>
                    <i class="bi bi-building me-1"></i>
                    {{ collect($factories)->firstWhere('id', (int) $factoryId)['name'] ?? 'Factory' }}
                @endif
            </div>
        </div>

        <div class="db-header-controls">
            @if(count($factories) > 1)
                <select class="form-select form-select-sm db-factory-select" wire:model.live="factoryId">
                    <option value="">All factories</option>
                    @foreach($factories as $factory)
                        <option value="{{ $factory['id'] }}">{{ $factory['name'] }}</option>
                    @endforeach
                </select>
            @endif

            <div class="db-period">
                @foreach($labels as $key => $label)
                    <button type="button"
                            class="db-period-btn {{ $period === $key ? 'is-active' : '' }}"
                            wire:click="setPeriod('{{ $key }}')">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Headline numbers ────────────────────────────────────────────── --}}
    <div class="db-kpis">
        <div class="db-kpi db-kpi--primary">
            <div class="db-kpi-top">
                <span class="db-kpi-label">Events scheduled</span>
                <span class="db-kpi-icon"><i class="bi bi-calendar2-check"></i></span>
            </div>
            <div class="db-kpi-value">{{ number_format($stats['events']) }}</div>
            <div class="db-kpi-foot">
                {{ $stats['plans'] }} plan{{ $stats['plans'] === 1 ? '' : 's' }}
                <span class="db-dot-sep"></span>
                {{ $stats['completed'] }} ended
            </div>
            <div class="db-kpi-bar">
                <span style="width: {{ $stats['completedPct'] }}%"></span>
            </div>
            <div class="db-kpi-bar-note">{{ $stats['completedPct'] }}% completed</div>
        </div>

        <div class="db-kpi db-kpi--info">
            <div class="db-kpi-top">
                <span class="db-kpi-label">Running now</span>
                <span class="db-kpi-icon"><i class="bi bi-play-circle"></i></span>
            </div>
            <div class="db-kpi-value">
                {{ $stats['running'] }}
                @if($stats['running'] > 0)<span class="db-pulse"></span>@endif
            </div>
            <div class="db-kpi-foot">
                @if($stats['paused'] > 0)
                    <span class="text-warning fw-semibold">{{ $stats['paused'] }} paused</span>
                @else
                    No paused events
                @endif
            </div>
        </div>

        <div class="db-kpi db-kpi--warning">
            <div class="db-kpi-top">
                <span class="db-kpi-label">Downtime</span>
                <span class="db-kpi-icon"><i class="bi bi-exclamation-octagon"></i></span>
            </div>
            <div class="db-kpi-value">{{ $mins($stats['downtime']['minutes']) }}</div>
            <div class="db-kpi-foot">
                {{ $stats['downtime']['count'] }} emergency event{{ $stats['downtime']['count'] === 1 ? '' : 's' }}
                @if($stats['openEmergency'] > 0)
                    <span class="db-dot-sep"></span>
                    <span class="text-danger fw-semibold">{{ $stats['openEmergency'] }} open</span>
                @endif
            </div>
        </div>

        <div class="db-kpi db-kpi--success">
            <div class="db-kpi-top">
                <span class="db-kpi-label">On-time starts</span>
                <span class="db-kpi-icon"><i class="bi bi-stopwatch"></i></span>
            </div>
            <div class="db-kpi-value">
                {{ $stats['onTimePct'] === null ? '—' : $stats['onTimePct'] . '%' }}
            </div>
            <div class="db-kpi-foot">
                @if($stats['startedCount'] > 0)
                    {{ $stats['startedCount'] }} started
                    <span class="db-dot-sep"></span>
                    avg {{ $stats['avgDelay'] > 0 ? '+' : '' }}{{ $mins($stats['avgDelay']) }}
                @else
                    Nothing started yet
                @endif
            </div>
        </div>
    </div>

    {{-- ── Activity chart + mix ────────────────────────────────────────── --}}
    <div class="row g-4 mt-0">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Production activity</h5>
                    <span class="db-hint">Events per day</span>
                </div>
                <div class="card-body">
                    <div wire:ignore>
                        <div id="db-activity-chart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Event breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="db-section-label">By status</div>
                    @php $totalEvents = max(1, $stats['events']); @endphp
                    @forelse($meta as $key => $info)
                        @php $count = (int) ($stats['byStatus'][$key] ?? 0); @endphp
                        <div class="db-bar-row">
                            <span class="db-bar-name">
                                <span class="db-swatch" style="background: {{ $info['color'] }}"></span>
                                {{ $info['label'] }}
                            </span>
                            <span class="db-bar-track">
                                <span class="db-bar-fill"
                                      style="width: {{ round($count / $totalEvents * 100) }}%; background: {{ $info['color'] }}"></span>
                            </span>
                            <span class="db-bar-value">{{ $count }}</span>
                        </div>
                    @empty
                    @endforelse

                    <div class="db-section-label mt-4">Top event types</div>
                    @php $topTypeCount = max(1, $eventTypeMix->max('count') ?? 1); @endphp
                    @forelse($eventTypeMix as $type)
                        <div class="db-bar-row">
                            <span class="db-bar-name" title="{{ $type['name'] }}">
                                <span class="db-swatch" style="background: {{ $type['color'] }}"></span>
                                {{ $type['name'] }}
                            </span>
                            <span class="db-bar-track">
                                <span class="db-bar-fill"
                                      style="width: {{ round($type['count'] / $topTypeCount * 100) }}%; background: {{ $type['color'] }}"></span>
                            </span>
                            <span class="db-bar-value">{{ $type['count'] }}</span>
                        </div>
                    @empty
                        <div class="db-empty-inline">No events in this period.</div>
                    @endforelse

                    @if(!empty($stats['downtime']['byType']))
                        <div class="db-section-label mt-4">Downtime by reason</div>
                        @php $topDowntime = max(1, max($stats['downtime']['byType'])); @endphp
                        @foreach($stats['downtime']['byType'] as $name => $span)
                            <div class="db-bar-row">
                                <span class="db-bar-name" title="{{ $name }}">
                                    <span class="db-swatch" style="background: #f87171"></span>
                                    {{ $name }}
                                </span>
                                <span class="db-bar-track">
                                    <span class="db-bar-fill"
                                          style="width: {{ round($span / $topDowntime * 100) }}%; background: #f87171"></span>
                                </span>
                                <span class="db-bar-value">{{ $mins($span) }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Live floor + open emergencies ───────────────────────────────── --}}
    <div class="row g-4 mt-0">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        On the floor now
                        @if($liveBoard->isNotEmpty())
                            <span class="db-count">{{ $liveBoard->count() }}</span>
                        @endif
                    </h5>
                    <span class="db-hint">Running &amp; paused events</span>
                </div>
                <div class="card-body p-0">
                    @if($liveBoard->isEmpty())
                        <div class="db-empty">
                            <i class="bi bi-cup-hot"></i>
                            Nothing is running right now.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 db-table">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Where</th>
                                        <th>Planned</th>
                                        <th>Started</th>
                                        <th class="text-end">Elapsed</th>
                                        <th class="text-end">Board</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($liveBoard as $event)
                                        <tr>
                                            <td>
                                                <div class="db-event-name">
                                                    <span class="db-swatch" style="background: {{ $event['color'] }}"></span>
                                                    {{ $event['name'] }}
                                                    @if($event['emergencies'])
                                                        <span class="db-flag" title="Has ongoing emergency events">!</span>
                                                    @endif
                                                </div>
                                                <div class="db-event-sub">
                                                    {{ $event['type'] ?? '—' }}
                                                    @if($event['recipe'])
                                                        <span class="db-dot-sep"></span>{{ $event['recipe'] }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="db-event-name">{{ $event['lane'] ?? 'Unplaced' }}</div>
                                                <div class="db-event-sub">{{ $event['planLabel'] ?? '—' }}</div>
                                            </td>
                                            <td class="db-nowrap">
                                                @if($event['planned'])
                                                    {{ $event['planned']->format('H:i') }}
                                                    @if($event['plannedEnd'])
                                                        <span class="db-event-sub">→ {{ $event['plannedEnd']->format('H:i') }}</span>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="db-nowrap">
                                                {{ $event['startedAt'] ? $event['startedAt']->format('d M H:i') : '—' }}
                                            </td>
                                            <td class="text-end db-nowrap">
                                                <span class="db-status db-status--{{ $event['status'] }}">
                                                    {{ $meta[$event['status']]['label'] ?? $event['status'] }}
                                                </span>
                                                <div class="db-event-sub">{{ $mins($event['elapsed']) }}</div>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('plans.view', $event['planId']) }}"
                                                   class="btn btn-sm btn-light" title="Open plan board">
                                                    <i class="bi bi-arrow-up-right"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        Open emergencies
                        @if($emergencies->isNotEmpty())
                            <span class="db-count db-count--danger">{{ $emergencies->count() }}</span>
                        @endif
                    </h5>
                    <span class="db-hint">Blocking resume &amp; end</span>
                </div>
                <div class="card-body">
                    @if($emergencies->isEmpty())
                        <div class="db-empty">
                            <i class="bi bi-shield-check"></i>
                            No open emergency events.
                        </div>
                    @else
                        @foreach($emergencies as $item)
                            <div class="db-emg">
                                <div class="db-emg-head">
                                    <span class="db-emg-type">{{ $item['type'] }}</span>
                                    <span class="db-emg-elapsed">{{ $mins($item['elapsed']) }}</span>
                                </div>
                                <div class="db-emg-body">
                                    {{ $item['eventName'] ?? 'Event' }}
                                    @if($item['lane'])
                                        <span class="db-dot-sep"></span>{{ $item['lane'] }}
                                    @endif
                                </div>
                                @if($item['reason'])
                                    <div class="db-emg-reason">{{ $item['reason'] }}</div>
                                @endif
                                <div class="db-emg-foot">
                                    <span>
                                        <i class="bi bi-clock me-1"></i>{{ $item['startedAt']->format('d M H:i') }}
                                        @if($item['expected'])
                                            <span class="db-dot-sep"></span>expected {{ $mins((int) $item['expected']) }}
                                        @endif
                                        @if($item['by'])
                                            <span class="db-dot-sep"></span>{{ $item['by'] }}
                                        @endif
                                    </span>
                                    @if($item['planId'])
                                        <a href="{{ route('plans.view', $item['planId']) }}" class="db-emg-link">
                                            Open board <i class="bi bi-arrow-up-right"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Plans + stock queue ─────────────────────────────────────────── --}}
    <div class="row g-4 mt-0">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Plans</h5>
                    <a href="{{ route('plans') }}" class="db-hint-link">All plans <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="card-body">
                    @forelse($plans as $plan)
                        @php $pct = $plan['events'] ? round($plan['done'] / $plan['events'] * 100) : 0; @endphp
                        <a href="{{ route('plans.view', $plan['id']) }}"
                           class="db-plan {{ $plan['isToday'] ? 'is-today' : '' }} {{ $plan['isPast'] ? 'is-past' : '' }}">
                            <div class="db-plan-date">
                                <span class="db-plan-day">{{ $plan['date']->format('d') }}</span>
                                <span class="db-plan-mon">{{ $plan['date']->format('M') }}</span>
                            </div>
                            <div class="db-plan-main">
                                <div class="db-plan-name">
                                    {{ $plan['name'] }}
                                    @if($plan['isToday'])<span class="db-badge-today">Today</span>@endif
                                </div>
                                <div class="db-plan-meta">
                                    {{ $plan['events'] }} event{{ $plan['events'] === 1 ? '' : 's' }}
                                    <span class="db-dot-sep"></span>{{ $plan['done'] }} ended
                                    @if($plan['live'] > 0)
                                        <span class="db-dot-sep"></span>
                                        <span class="text-info fw-semibold">{{ $plan['live'] }} live</span>
                                    @endif
                                </div>
                                <div class="db-plan-track">
                                    <span style="width: {{ $pct }}%"></span>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right db-plan-chev"></i>
                        </a>
                    @empty
                        <div class="db-empty">
                            <i class="bi bi-calendar-x"></i>
                            No plans have been created yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Stock documents awaiting action</h5>
                    <span class="db-hint">Across all warehouses</span>
                </div>
                <div class="card-body">
                    @if(empty($stockTiles))
                        <div class="db-empty">
                            <i class="bi bi-file-earmark-lock"></i>
                            You don't have access to stock documents.
                        </div>
                    @else
                        <div class="db-tiles">
                            @foreach($stockTiles as $tile)
                                <a href="{{ $tile['route'] }}" class="db-tile" style="--tile-accent: {{ $tile['accent'] }}">
                                    <span class="db-tile-icon"><i class="bi {{ $tile['icon'] }}"></i></span>
                                    <span class="db-tile-count">{{ $tile['count'] }}</span>
                                    <span class="db-tile-label">{{ $tile['label'] }}</span>
                                </a>
                            @endforeach
                        </div>

                        @if($stockRows->isEmpty())
                            <div class="db-empty mt-3">
                                <i class="bi bi-check2-circle"></i>
                                Nothing is waiting for approval.
                            </div>
                        @else
                            <div class="table-responsive mt-3">
                                <table class="table table-hover align-middle mb-0 db-table">
                                    <thead>
                                        <tr>
                                            <th>Document</th>
                                            <th>Warehouse</th>
                                            <th class="text-center">Items</th>
                                            <th>Status</th>
                                            <th class="text-end">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stockRows as $row)
                                            <tr>
                                                <td>
                                                    <a href="{{ $row['url'] }}" class="db-doc-link">
                                                        <span class="db-doc-icon" style="--tile-accent: {{ $row['accent'] }}">
                                                            <i class="bi {{ $row['icon'] }}"></i>
                                                        </span>
                                                        <span>
                                                            <span class="db-event-name">{{ $row['label'] }} #{{ $row['id'] }}</span>
                                                        </span>
                                                    </a>
                                                </td>
                                                <td class="db-event-sub">{{ $row['where'] }}</td>
                                                <td class="text-center">{{ $row['items'] }}</td>
                                                <td>
                                                    <span class="db-status db-status--{{ $row['status'] }}">
                                                        {{ ucfirst($row['status']) }}
                                                    </span>
                                                </td>
                                                <td class="text-end db-event-sub db-nowrap">
                                                    {{ $row['createdAt']?->format('d M H:i') ?? '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Item movement through events ────────────────────────────────── --}}
    <div class="row g-4 mt-0 mb-2">
        @foreach([
            ['title' => 'Most consumed items', 'hint' => 'Inputs & emergency use', 'rows' => $consumed, 'accent' => '#f87171', 'icon' => 'bi-box-arrow-up'],
            ['title' => 'Most produced items', 'hint' => 'Outputs & side products', 'rows' => $produced, 'accent' => '#34d399', 'icon' => 'bi-box-arrow-in-down'],
        ] as $panel)
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="bi {{ $panel['icon'] }} me-1" style="color: {{ $panel['accent'] }}"></i>
                            {{ $panel['title'] }}
                        </h5>
                        <span class="db-hint">{{ $panel['hint'] }}</span>
                    </div>
                    <div class="card-body p-0">
                        @if($panel['rows']->isEmpty())
                            <div class="db-empty">
                                <i class="bi bi-inboxes"></i>
                                No quantities recorded in this period.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 db-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Unit</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">vs planned</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($panel['rows'] as $row)
                                            <tr>
                                                <td class="db-event-name">{{ $row['item'] }}</td>
                                                <td class="db-event-sub">{{ $row['unit'] ?? '—' }}</td>
                                                <td class="text-end fw-semibold">{{ format_quantity($row['qty']) }}</td>
                                                <td class="text-end">
                                                    @if($row['variance'] === null)
                                                        <span class="db-event-sub">—</span>
                                                    @else
                                                        <span class="db-variance {{ $row['variance'] > 0 ? 'is-up' : ($row['variance'] < 0 ? 'is-down' : '') }}">
                                                            {{ $row['variance'] > 0 ? '+' : '' }}{{ $row['variance'] }}%
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @push('scripts')
        <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    @endpush

    @script
    <script>
        let dbChart = null;

        const dbRenderChart = (payload) => {
            const el = document.getElementById('db-activity-chart');

            if (!el || typeof ApexCharts === 'undefined') {
                return;
            }

            const options = {
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                    animations: { easing: 'easeinout', speed: 350 },
                },
                series: payload.series,
                colors: ['#94a3b8', '#38bdf8', '#34d399'],
                stroke: { curve: 'smooth', width: 2 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 95] },
                },
                dataLabels: { enabled: false },
                grid: { borderColor: 'rgba(130,130,180,.16)', strokeDashArray: 4 },
                legend: { position: 'top', horizontalAlign: 'right', markers: { radius: 12 } },
                xaxis: {
                    type: 'datetime',
                    categories: payload.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { fontSize: '11px' } },
                },
                yaxis: {
                    // Event counts are whole numbers — don't let Apex invent 0.5 ticks.
                    labels: { formatter: (value) => Math.round(value) },
                    min: 0,
                    forceNiceScale: true,
                },
                tooltip: { x: { format: 'ddd dd MMM yyyy' } },
                noData: { text: 'No activity in this period.' },
            };

            if (dbChart) {
                dbChart.updateOptions({
                    series: payload.series,
                    xaxis: { categories: payload.labels },
                }, false, true);
                return;
            }

            dbChart = new ApexCharts(el, options);
            dbChart.render();
        };

        dbRenderChart(@js($chart));

        $wire.on('dashboard:chart', (event) => {
            // Livewire hands listeners the dispatched payload; unwrap either shape.
            const payload = Array.isArray(event) ? event[0] : event;
            dbRenderChart(payload.payload ?? payload);
        });
    </script>
    @endscript
</div>
