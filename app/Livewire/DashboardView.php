<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\EventPauseActivity;
use App\Models\MonthPlan;
use App\Models\Plan;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Transfer;
use App\Models\Waste;
use App\Services\ApiService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Production dashboard.
 *
 * Everything here is derived from the module's own tables — plans → events →
 * status logs / quantities, and the four stock documents. The only remote call
 * is the warehouse name lookup (warehouses live in the parent auth-service), and
 * it degrades to "Warehouse #id" when the parent is unreachable.
 */
class DashboardView extends Component
{
    /** Period options for the header switcher. */
    public const PERIODS = [
        '7d'  => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        'mtd' => 'This month',
    ];

    /** Statuses an event can be sitting in, in board order. */
    public const STATUS_META = [
        'planned'     => ['label' => 'Planned',     'color' => '#94a3b8'],
        'in_progress' => ['label' => 'In Progress', 'color' => '#38bdf8'],
        'paused'      => ['label' => 'Paused',      'color' => '#fbbf24'],
        'terminated'  => ['label' => 'Ended',       'color' => '#34d399'],
    ];

    #[Url(except: '30d')]
    public string $period = '30d';

    /** Factory (warehouse) filter; empty string = all factories. */
    #[Url(except: '')]
    public string $factoryId = '';

    /** [['id' => …, 'name' => …], …] — the factories that actually have plans. */
    public array $factories = [];

    /** Lazily-loaded warehouse id => name map from the parent service. */
    protected ?array $warehouseNames = null;

    protected ApiService $api;

    public function boot(ApiService $api): void
    {
        $this->api = $api;
    }

    public function mount(): void
    {
        if (!array_key_exists($this->period, self::PERIODS)) {
            $this->period = '30d';
        }

        $this->loadFactories();

        // Drop a factory filter carried in the URL that no longer has plans.
        if ($this->factoryId !== '' && !collect($this->factories)->contains(fn($f) => (string) $f['id'] === $this->factoryId)) {
            $this->factoryId = '';
        }
    }

    public function updatedPeriod($value): void
    {
        if (!array_key_exists($value, self::PERIODS)) {
            $this->period = '30d';
        }

        $this->dispatch('dashboard:chart', payload: $this->activitySeries());
    }

    public function updatedFactoryId(): void
    {
        $this->dispatch('dashboard:chart', payload: $this->activitySeries());
    }

    public function setPeriod(string $period): void
    {
        $this->period = array_key_exists($period, self::PERIODS) ? $period : '30d';

        $this->dispatch('dashboard:chart', payload: $this->activitySeries());
    }

    // ─── Filters ──────────────────────────────────────────────────────────────

    /** The factories to offer, named from the month plans' snapshot where possible. */
    protected function loadFactories(): void
    {
        $ids = Plan::whereNotNull('factory_id')->distinct()->pluck('factory_id');

        if ($ids->isEmpty()) {
            $this->factories = [];
            return;
        }

        $snapshots = MonthPlan::whereNotNull('factory_name')->pluck('factory_name', 'factory_id');

        $this->factories = $ids
            ->map(fn($id) => [
                'id'   => (int) $id,
                'name' => $snapshots[$id] ?? $this->warehouseName($id),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    /** Warehouse names live in the parent service; fall back to the id when it's down. */
    protected function warehouseName($id): string
    {
        if ($this->warehouseNames === null) {
            $data = $this->api->get('/v1/warehouses', ['module' => 'production'])['data'] ?? [];
            $this->warehouseNames = collect($data)->pluck('name', 'id')->toArray();
        }

        return $this->warehouseNames[$id] ?? 'Warehouse #' . $id;
    }

    /** [$from, $to] for the selected period. */
    protected function range(): array
    {
        $to = Carbon::today()->endOfDay();

        $from = match ($this->period) {
            '7d'  => Carbon::today()->subDays(6)->startOfDay(),
            '90d' => Carbon::today()->subDays(89)->startOfDay(),
            'mtd' => Carbon::today()->startOfMonth(),
            default => Carbon::today()->subDays(29)->startOfDay(),
        };

        return [$from, $to];
    }

    protected function factoryFilter(): ?int
    {
        return $this->factoryId !== '' ? (int) $this->factoryId : null;
    }

    /** Events whose plan day falls inside the period (and factory). */
    protected function scopedEvents(): Builder
    {
        [$from, $to] = $this->range();
        $factory = $this->factoryFilter();

        return Event::query()->whereHas('plan', function ($q) use ($from, $to, $factory) {
            $q->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

            if ($factory) {
                $q->where('factory_id', $factory);
            }
        });
    }

    /** Events currently running or paused — live, never date-scoped. */
    protected function liveEvents(): Builder
    {
        $factory = $this->factoryFilter();

        return Event::query()
            ->whereIn('status', ['in_progress', 'paused'])
            ->when($factory, fn($q) => $q->whereHas('plan', fn($p) => $p->where('factory_id', $factory)));
    }

    /**
     * Constrain a query builder joined to `plans as p` to the period + factory.
     */
    protected function applyScope($query, string $planAlias = 'p')
    {
        [$from, $to] = $this->range();
        $factory = $this->factoryFilter();

        $query->whereBetween("{$planAlias}.date", [$from->toDateString(), $to->toDateString()]);

        if ($factory) {
            $query->where("{$planAlias}.factory_id", $factory);
        }

        return $query;
    }

    // ─── Headline numbers ─────────────────────────────────────────────────────

    protected function stats(): array
    {
        $scoped = $this->scopedEvents();

        $byStatus = (clone $scoped)
            ->selectRaw('COALESCE(status, "planned") as s, COUNT(*) as c')
            ->groupBy('s')
            ->pluck('c', 's');

        $totalEvents = (int) $byStatus->sum();
        $completed   = (int) ($byStatus['terminated'] ?? 0);

        $live = (clone $this->liveEvents())
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $adherence = $this->startAdherence();

        return [
            'events'        => $totalEvents,
            'byStatus'      => $byStatus,
            'completed'     => $completed,
            'completedPct'  => $totalEvents ? round($completed / $totalEvents * 100) : 0,
            'running'       => (int) ($live['in_progress'] ?? 0),
            'paused'        => (int) ($live['paused'] ?? 0),
            'plans'         => $this->scopedPlans()->count(),
            'downtime'      => $this->downtime(),
            'openEmergency' => $this->openEmergencyQuery()->count(),
            'startedCount'  => $adherence['started'],
            'onTimePct'     => $adherence['onTimePct'],
            'avgDelay'      => $adherence['avgDelay'],
        ];
    }

    protected function scopedPlans(): Builder
    {
        [$from, $to] = $this->range();
        $factory = $this->factoryFilter();

        return Plan::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($factory, fn($q) => $q->where('factory_id', $factory));
    }

    /**
     * How punctual starts were: an event is on time when its first `start` log
     * happened no later than its planned from_time.
     */
    protected function startAdherence(): array
    {
        $delays = $this->applyScope(
            DB::table('event_status_logs as l')
                ->join('events as e', 'e.id', '=', 'l.event_id')
                ->join('plans as p', 'p.id', '=', 'e.plan_id')
                ->whereNull('l.deleted_at')
                ->whereNull('e.deleted_at')
                ->whereNull('p.deleted_at')
                ->where('l.action', 'start')
                ->whereNotNull('e.from_time')
        )
            ->selectRaw('TIMESTAMPDIFF(MINUTE, e.from_time, COALESCE(l.happened_at, l.created_at)) as delay')
            ->pluck('delay')
            ->filter(fn($d) => $d !== null)
            ->map(fn($d) => (int) $d);

        if ($delays->isEmpty()) {
            return ['started' => 0, 'onTimePct' => null, 'avgDelay' => null];
        }

        return [
            'started'   => $delays->count(),
            'onTimePct' => (int) round($delays->filter(fn($d) => $d <= 0)->count() / $delays->count() * 100),
            'avgDelay'  => (int) round($delays->avg()),
        ];
    }

    /** Emergency events still open (never ended), oldest first. */
    protected function openEmergencyQuery(): Builder
    {
        $factory = $this->factoryFilter();

        return EventPauseActivity::query()
            ->whereNull('ended_at')
            ->when($factory, fn($q) => $q->whereHas('event.plan', fn($p) => $p->where('factory_id', $factory)));
    }

    /**
     * Total downtime in the period: every emergency event that started inside
     * it, counted up to its end (or to now while it is still open).
     */
    protected function downtime(): array
    {
        [$from, $to] = $this->range();
        $factory = $this->factoryFilter();

        $activities = EventPauseActivity::query()
            ->whereBetween(DB::raw('COALESCE(happened_at, created_at)'), [$from, $to])
            ->when($factory, fn($q) => $q->whereHas('event.plan', fn($p) => $p->where('factory_id', $factory)))
            ->get(['id', 'event_type_name', 'happened_at', 'created_at', 'ended_at']);

        $minutes = 0;
        $byType  = [];

        foreach ($activities as $activity) {
            $start = Carbon::parse($activity->happened_at ?: $activity->created_at);
            $end   = $activity->ended_at ? Carbon::parse($activity->ended_at) : now();
            $span  = (int) max(0, $start->diffInMinutes($end));

            $minutes += $span;

            $name = $activity->event_type_name ?: 'Unspecified';
            $byType[$name] = ($byType[$name] ?? 0) + $span;
        }

        arsort($byType);

        return [
            'minutes' => $minutes,
            'count'   => $activities->count(),
            'byType'  => array_slice($byType, 0, 5, true),
        ];
    }

    // ─── Activity chart ───────────────────────────────────────────────────────

    /**
     * Daily planned / started / completed counts across the period, in the
     * shape the ApexCharts block in the blade expects.
     */
    public function activitySeries(): array
    {
        [$from, $to] = $this->range();

        $planned   = $this->dailyPlanned();
        $started   = $this->dailyLogs('start');
        $completed = $this->dailyLogs('terminate');

        $labels = [];
        $p = $s = $c = [];

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key      = $day->format('Y-m-d');
            $labels[] = $key;
            $p[]      = (int) ($planned[$key] ?? 0);
            $s[]      = (int) ($started[$key] ?? 0);
            $c[]      = (int) ($completed[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'series' => [
                ['name' => 'Planned',   'data' => $p],
                ['name' => 'Started',   'data' => $s],
                ['name' => 'Completed', 'data' => $c],
            ],
        ];
    }

    /** Events scheduled per plan day. */
    protected function dailyPlanned(): array
    {
        return $this->applyScope(
            DB::table('events as e')
                ->join('plans as p', 'p.id', '=', 'e.plan_id')
                ->whereNull('e.deleted_at')
                ->whereNull('p.deleted_at')
        )
            ->selectRaw('p.date as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd')
            ->mapWithKeys(fn($c, $d) => [Carbon::parse($d)->format('Y-m-d') => $c])
            ->toArray();
    }

    /** Distinct events that hit a given status action, per day it happened. */
    protected function dailyLogs(string $action): array
    {
        [$from, $to] = $this->range();
        $factory = $this->factoryFilter();

        $query = DB::table('event_status_logs as l')
            ->join('events as e', 'e.id', '=', 'l.event_id')
            ->join('plans as p', 'p.id', '=', 'e.plan_id')
            ->whereNull('l.deleted_at')
            ->whereNull('e.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('l.action', $action)
            ->whereBetween(DB::raw('COALESCE(l.happened_at, l.created_at)'), [$from, $to]);

        if ($factory) {
            $query->where('p.factory_id', $factory);
        }

        return $query
            ->selectRaw('DATE(COALESCE(l.happened_at, l.created_at)) as d, COUNT(DISTINCT l.event_id) as c')
            ->groupBy('d')
            ->pluck('c', 'd')
            ->mapWithKeys(fn($c, $d) => [Carbon::parse($d)->format('Y-m-d') => $c])
            ->toArray();
    }

    // ─── Panels ───────────────────────────────────────────────────────────────

    /** Running + paused events with their elapsed time and where they run. */
    protected function liveBoard(): Collection
    {
        return $this->liveEvents()
            ->with(['eventType', 'plan.monthPlan', 'placeable', 'recipe'])
            ->withExists(['pauseActivities as has_open_emergencies' => fn($q) => $q->whereNull('ended_at')])
            ->with(['statusLogs' => fn($q) => $q->whereIn('action', ['start', 'pause', 'resume'])->orderBy('id')])
            ->orderBy('from_time')
            ->get()
            ->map(function (Event $event) {
                $startLog = $event->statusLogs->firstWhere('action', 'start');
                $startedAt = $startLog ? Carbon::parse($startLog->happened_at ?: $startLog->created_at) : null;

                return [
                    'id'          => $event->id,
                    'name'        => $event->name,
                    'status'      => $event->status,
                    'type'        => $event->eventType?->name,
                    'color'       => $event->eventType?->color ?: '#818cf8',
                    'recipe'      => $event->recipe?->name,
                    'lane'        => $event->placeable?->name,
                    'planId'      => $event->plan_id,
                    'planLabel'   => $event->plan?->monthPlan?->display_name
                        ?: ($event->plan ? Carbon::parse($event->plan->date)->format('d M Y') : null),
                    'planned'     => $event->from_time ? Carbon::parse($event->from_time) : null,
                    'plannedEnd'  => $event->to_time ? Carbon::parse($event->to_time) : null,
                    'startedAt'   => $startedAt,
                    'elapsed'     => $startedAt ? (int) $startedAt->diffInMinutes(now()) : null,
                    'emergencies' => (bool) $event->has_open_emergencies,
                ];
            });
    }

    /** Emergency events still open, longest-running first. */
    protected function openEmergencies(): Collection
    {
        return $this->openEmergencyQuery()
            ->with(['eventType', 'event.eventType', 'event.plan', 'event.placeable'])
            ->orderByRaw('COALESCE(happened_at, created_at) asc')
            ->limit(8)
            ->get()
            ->map(function (EventPauseActivity $activity) {
                $start = Carbon::parse($activity->happened_at ?: $activity->created_at);

                return [
                    'id'        => $activity->id,
                    'type'      => $activity->event_type_name ?: $activity->eventType?->name ?: 'Emergency',
                    'reason'    => $activity->reason,
                    'startedAt' => $start,
                    'elapsed'   => (int) $start->diffInMinutes(now()),
                    'expected'  => $activity->expected_duration,
                    'eventName' => $activity->event?->name,
                    'eventType' => $activity->event?->eventType?->name,
                    'lane'      => $activity->event?->placeable?->name,
                    'planId'    => $activity->event?->plan_id,
                    'by'        => $activity->created_by_name,
                ];
            });
    }

    /** Today's plans first, then the next scheduled ones. */
    /**
     * The plans closest to today: everything from today forward, backfilled
     * with the most recent past ones so the panel still says something useful
     * once the schedule has been worked through.
     */
    protected function upcomingPlans(int $limit = 6): Collection
    {
        $upcoming = $this->planQuery()
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->limit($limit)
            ->get();

        if ($upcoming->count() < $limit) {
            $past = $this->planQuery()
                ->whereDate('date', '<', Carbon::today())
                ->orderByDesc('date')
                ->limit($limit - $upcoming->count())
                ->get();

            $upcoming = $past->reverse()->concat($upcoming);
        }

        return $upcoming->values()->map(function (Plan $plan) {
            $date = Carbon::parse($plan->date);

            return [
                'id'      => $plan->id,
                'date'    => $date,
                'isToday' => $date->isToday(),
                'isPast'  => $date->isBefore(Carbon::today()),
                'name'    => $plan->monthPlan?->display_name
                    ?: ($plan->factory_id ? $this->warehouseName($plan->factory_id) : 'Plan #' . $plan->id),
                'events'  => $plan->events_count,
                'done'    => $plan->done_events_count,
                'live'    => $plan->live_events_count,
            ];
        });
    }

    /** Plans with their event tallies, honouring the factory filter. */
    protected function planQuery(): Builder
    {
        $factory = $this->factoryFilter();

        return Plan::query()
            ->with('monthPlan')
            ->withCount([
                'events',
                'events as done_events_count' => fn($q) => $q->where('status', 'terminated'),
                'events as live_events_count' => fn($q) => $q->whereIn('status', ['in_progress', 'paused']),
            ])
            ->when($factory, fn($q) => $q->where('factory_id', $factory));
    }

    /** Event types used in the period, biggest first. */
    protected function eventTypeMix(): Collection
    {
        return $this->scopedEvents()
            ->selectRaw('events.event_type_id, COUNT(*) as c')
            ->groupBy('events.event_type_id')
            ->orderByDesc('c')
            ->with('eventType')
            ->limit(6)
            ->get()
            ->map(fn(Event $row) => [
                'name'  => $row->eventType?->name ?: 'Unknown',
                'color' => $row->eventType?->color ?: '#818cf8',
                'count' => (int) $row->c,
            ]);
    }

    /**
     * Item movement recorded against events in the period. Names are snapshots
     * on `event_quantities`, so this needs no API call.
     */
    protected function topItems(array $sources): Collection
    {
        return $this->applyScope(
            DB::table('event_quantities as q')
                ->join('events as e', 'e.id', '=', 'q.event_id')
                ->join('plans as p', 'p.id', '=', 'e.plan_id')
                ->whereNull('q.deleted_at')
                ->whereNull('e.deleted_at')
                ->whereNull('p.deleted_at')
                ->whereIn('q.source', $sources)
                ->whereNotNull('q.item_name')
        )
            ->selectRaw('q.item_name, q.unit_name, SUM(q.actual_quantity) as qty, SUM(q.planned_quantity) as planned_qty, COUNT(*) as line_count')
            ->groupBy('q.item_name', 'q.unit_name')
            ->orderByDesc('qty')
            ->limit(6)
            ->get()
            ->map(fn($row) => [
                'item'     => $row->item_name,
                'unit'     => $row->unit_name,
                'qty'      => (float) $row->qty,
                'planned'  => $row->planned_qty !== null ? (float) $row->planned_qty : null,
                'variance' => $row->planned_qty > 0
                    ? round((((float) $row->qty - (float) $row->planned_qty) / (float) $row->planned_qty) * 100, 1)
                    : null,
                'lines'    => (int) $row->line_count,
            ]);
    }

    // ─── Stock documents ──────────────────────────────────────────────────────

    /** Which stock document types this user is allowed to see at all. */
    protected function stockAccess(): array
    {
        $user = authUser();

        return [
            'stock_in'  => (bool) $user?->hasPermission('production.itemStockIn-list'),
            'stock_out' => (bool) $user?->hasPermission('production.itemStockOut-list'),
            'waste'     => (bool) $user?->hasPermission('production.itemWaste-list'),
            'transfer'  => (bool) $user?->hasPermission('production.itemTransfer-list'),
        ];
    }

    /**
     * Documents still waiting on someone: pending stock in/out/waste, and
     * transfers that are pending (to load) or loaded (to receive).
     */
    protected function stockQueue(array $access): array
    {
        $tiles = [];
        $rows  = collect();

        $definitions = [
            'stock_in' => [
                'label'    => 'Stock In',
                'icon'     => 'bi-box-arrow-in-down',
                'accent'   => '#34d399',
                'model'    => StockIn::class,
                'statuses' => ['pending'],
                'route'    => 'item-stock-ins',
                'view'     => 'item-stock-ins.view',
            ],
            'stock_out' => [
                'label'    => 'Stock Out',
                'icon'     => 'bi-box-arrow-up',
                'accent'   => '#f87171',
                'model'    => StockOut::class,
                'statuses' => ['pending'],
                'route'    => 'item-stock-outs',
                'view'     => 'item-stock-outs.view',
            ],
            'waste' => [
                'label'    => 'Waste',
                'icon'     => 'bi-trash',
                'accent'   => '#fbbf24',
                'model'    => Waste::class,
                'statuses' => ['pending'],
                'route'    => 'item-wastes',
                'view'     => 'item-wastes.view',
            ],
            'transfer' => [
                'label'    => 'Transfers',
                'icon'     => 'bi-arrow-left-right',
                'accent'   => '#38bdf8',
                'model'    => Transfer::class,
                'statuses' => ['pending', 'loaded'],
                'route'    => 'item-transfers',
                'view'     => 'item-transfers.view',
            ],
        ];

        foreach ($definitions as $key => $def) {
            if (!($access[$key] ?? false)) {
                continue;
            }

            $open = $def['model']::whereIn('status', $def['statuses'])->count();

            $tiles[] = [
                'key'    => $key,
                'label'  => $def['label'],
                'icon'   => $def['icon'],
                'accent' => $def['accent'],
                'count'  => $open,
                'route'  => route($def['route']),
            ];

            $recent = $def['model']::withCount('reportItems')
                ->whereIn('status', $def['statuses'])
                ->latest()
                ->limit(5)
                ->get();

            foreach ($recent as $doc) {
                $rows->push([
                    'key'       => $key,
                    'label'     => $def['label'],
                    'icon'      => $def['icon'],
                    'accent'    => $def['accent'],
                    'id'        => $doc->id,
                    'status'    => $doc->status,
                    'items'     => $doc->report_items_count,
                    'createdAt' => $doc->created_at,
                    'where'     => $key === 'transfer'
                        ? $this->warehouseName($doc->warehouse_from_id) . ' → ' . $this->warehouseName($doc->warehouse_to_id)
                        : $this->warehouseName($doc->warehouse_id),
                    'url'       => route($def['view'], ['id' => $doc->id, 'viewStatus' => 'view']),
                ]);
            }
        }

        return [
            'tiles' => $tiles,
            'rows'  => $rows->sortByDesc('createdAt')->take(8)->values(),
        ];
    }

    // ─── Rendering ────────────────────────────────────────────────────────────

    /** Minutes as "2h 15m" / "45m". */
    public static function humanMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        $minutes = (int) $minutes;
        $sign    = $minutes < 0 ? '-' : '';
        $minutes = abs($minutes);

        if ($minutes < 60) {
            return $sign . $minutes . 'm';
        }

        $hours = intdiv($minutes, 60);
        $rest  = $minutes % 60;

        return $sign . $hours . 'h' . ($rest ? ' ' . $rest . 'm' : '');
    }

    public function render()
    {
        [$from, $to] = $this->range();

        $access = $this->stockAccess();
        $stock  = in_array(true, $access, true)
            ? $this->stockQueue($access)
            : ['tiles' => [], 'rows' => collect()];

        return view('livewire.dashboard-view', [
            'from'          => $from,
            'to'            => $to,
            'periodLabel'   => self::PERIODS[$this->period],
            'stats'         => $this->stats(),
            'chart'         => $this->activitySeries(),
            'liveBoard'     => $this->liveBoard(),
            'emergencies'   => $this->openEmergencies(),
            'plans'         => $this->upcomingPlans(),
            'eventTypeMix'  => $this->eventTypeMix(),
            'consumed'      => $this->topItems(['input', 'emergency']),
            'produced'      => $this->topItems(['output', 'side_product']),
            'stockTiles'    => $stock['tiles'],
            'stockRows'     => $stock['rows'],
        ]);
    }
}
