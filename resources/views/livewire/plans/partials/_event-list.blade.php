@php
    $statusMeta = [
        ''            => ['label' => 'Planned',     'badge' => 'bg-secondary'],
        'in_progress' => ['label' => 'In Progress', 'badge' => 'bg-success'],
        'paused'      => ['label' => 'Paused',      'badge' => 'bg-warning text-dark'],
        'terminated'  => ['label' => 'Ended',       'badge' => 'bg-danger'],
    ];
@endphp
<div class="card mb-0">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <i class="bi bi-list-ul text-primary"></i>
            <span class="fw-bold" style="font-size: 13px;">Day Events</span>
            <span class="badge bg-primary rounded-pill">{{ count($rows) }}</span>
            <span class="text-muted ms-auto" style="font-size: 11px;">Click a row for details</span>
        </div>

        @if($rows->isEmpty())
        <div class="pb-empty-hint">No events in this plan yet.</div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="white-space: nowrap;">Planned</th>
                        <th style="white-space: nowrap;">Actual</th>
                        <th>Event</th>
                        <th>Recipe</th>
                        <th>Duration</th>
                        <th>Production Line</th>
                        <th>Lane</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $event)
                    @php
                        $hasRecipe       = (bool) ($event->eventType?->has_recipe);
                        $durationMinutes = $hasRecipe ? $event->calculated_duration : $event->planned_duration;

                        $durationLabel = null;
                        if ($durationMinutes) {
                            $h = intdiv((int) $durationMinutes, 60);
                            $m = ((int) $durationMinutes) % 60;
                            $durationLabel = $h > 0 ? ($m > 0 ? "{$h}h {$m}m" : "{$h}h") : "{$m}m";
                        }

                        $isCarryOver = (bool) ($event->is_carry_over ?? false);
                        $continuesNextDay = !$isCarryOver && $event->placeable_id && $event->from_time && $event->to_time
                            && \Carbon\Carbon::parse($event->to_time)->gt(\Carbon\Carbon::parse($event->from_time)->startOfDay()->addDay());

                        // Recipe events confirm terminate through the quantities
                        // modal instead of a plain confirm dialog.
                        $needsQtyModal = $hasRecipe && $event->recipe_id;

                        $statusKey = $event->status ?: '';
                        $status    = $statusMeta[$statusKey] ?? ['label' => ucfirst($statusKey), 'badge' => 'bg-secondary'];

                        $actual = $this->eventActualTimes($event);

                        $fmtDelay = function ($mins) {
                            $m = abs((int) $mins);
                            if ($m >= 60) {
                                $h = intdiv($m, 60); $r = $m % 60;
                                return $r ? "{$h}h {$r}m" : "{$h}h";
                            }
                            return "{$m}m";
                        };
                    @endphp
                    <tr wire:key="pbl-row-{{ $event->id }}{{ $isCarryOver ? '-carry' : '' }}"
                        wire:click="showEventDetails({{ $event->id }})"
                        style="cursor: pointer;" class="{{ $isCarryOver ? 'opacity-75' : '' }}">
                        <td style="white-space: nowrap;">
                            @if($event->from_time)
                            <div class="text-muted" style="font-size: 11px;"><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($event->from_time)->format('d M Y') }}</div>
                            <div><span class="fw-semibold">{{ \Carbon\Carbon::parse($event->from_time)->format('H:i') }}</span>{{ $event->to_time ? ' – ' . \Carbon\Carbon::parse($event->to_time)->format('H:i') : '' }}</div>
                            @if($isCarryOver)
                            <div class="text-muted" style="font-size: 11px;"><i class="bi bi-arrow-bar-right"></i> started {{ \Carbon\Carbon::parse($event->from_time)->format('d M') }}</div>
                            @elseif($continuesNextDay)
                            <div class="text-muted" style="font-size: 11px;">ends {{ \Carbon\Carbon::parse($event->to_time)->format('d M') }} <i class="bi bi-arrow-bar-right"></i></div>
                            @endif
                            @else
                            <span class="badge bg-light-secondary text-secondary">Not placed</span>
                            @endif
                        </td>
                        <td style="white-space: nowrap;">
                            @if($actual['start'])
                            <div class="text-muted" style="font-size: 11px;"><i class="bi bi-calendar-check me-1"></i>{{ $actual['start']->format('d M Y') }}</div>
                            <div>
                                <span class="fw-semibold">{{ $actual['start']->format('H:i') }}</span>@if($actual['running'])<span class="text-success fw-semibold"> – running</span>@elseif($actual['end']) – {{ $actual['end']->format('H:i') }}@endif
                            </div>
                            @if($actual['delay'] !== null)
                                @if($actual['delay'] > 0)
                                <span class="badge bg-danger-subtle text-danger" style="font-size: 10px;" title="Started {{ $fmtDelay($actual['delay']) }} after the planned time">
                                    <i class="bi bi-hourglass-split me-1"></i>{{ $fmtDelay($actual['delay']) }} late
                                </span>
                                @elseif($actual['delay'] < 0)
                                <span class="badge bg-success-subtle text-success" style="font-size: 10px;" title="Started {{ $fmtDelay($actual['delay']) }} before the planned time">
                                    <i class="bi bi-lightning-charge me-1"></i>{{ $fmtDelay($actual['delay']) }} early
                                </span>
                                @else
                                <span class="badge bg-light-secondary text-secondary" style="font-size: 10px;">on time</span>
                                @endif
                            @endif
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="d-inline-block rounded-circle me-1"
                                style="width: 10px; height: 10px; background-color: {{ $event->eventType->color ?? '#818cf8' }};"></span>
                            {{ $event->eventType->name ?? 'No type' }}
                            @if($event->batch_count)
                            <span class="text-muted" style="font-size: 11px;">· {{ $event->batch_count }} batches</span>
                            @endif
                        </td>
                        <td>{{ $event->recipe?->name ?? '—' }}</td>
                        <td style="white-space: nowrap;">{{ $durationLabel ?? '—' }}</td>
                        <td>{{ $event->productionLine?->name ?? '—' }}</td>
                        <td>
                            @if($event->placeable_id)
                            <i class="bi bi-{{ $event->placeable_type === \App\Models\Preparation::class ? 'egg-fried' : 'diagram-3' }} me-1"></i>{{ $event->placeable?->name ?? '—' }}
                            @else
                            <span class="badge bg-primary">Unplaced</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $status['badge'] }}">{{ $status['label'] }}</span></td>
                        <td class="text-end" style="white-space: nowrap;">
                            <button type="button" class="btn btn-light-secondary btn-sm"
                                wire:click.stop="showEventHistory({{ $event->id }})"
                                wire:loading.attr="disabled" wire:target="showEventHistory"
                                title="Status history">
                                <i class="bi bi-clock-history"></i>
                            </button>
                            @if($isCarryOver)
                            <span class="text-muted" style="font-size: 11px;" title="Manage this event from the previous day's plan">—</span>
                            @else
                            @hasPermission('production.event-create')
                            @if($statusKey === 'paused' || ($event->has_open_emergencies ?? false))
                            <button type="button" class="btn btn-light-primary btn-sm"
                                wire:click.stop="openPauseActivities({{ $event->id }})"
                                wire:loading.attr="disabled" wire:target="openPauseActivities"
                                title="{{ $statusKey === 'paused' ? 'Record emergency events during this pause (cleaning, maintenance, …)' : 'This event still has ongoing emergency events — end them here' }}">
                                <i class="bi bi-exclamation-triangle"></i> Emergency
                                @if($event->has_open_emergencies ?? false)
                                <span class="badge bg-danger ms-1">!</span>
                                @endif
                            </button>
                            @endif
                            @if($statusKey === '')
                            <button type="button" class="btn btn-light-success btn-sm"
                                wire:click.stop="updateEventStatus({{ $event->id }}, 'start')"
                                wire:loading.attr="disabled" wire:target="updateEventStatus">
                                <i class="bi bi-play-fill"></i> Start
                            </button>
                            @elseif($statusKey === 'in_progress')
                            <button type="button" class="btn btn-light-warning btn-sm"
                                wire:click.stop="updateEventStatus({{ $event->id }}, 'pause')"
                                wire:loading.attr="disabled" wire:target="updateEventStatus">
                                <i class="bi bi-pause-fill"></i> Pause
                            </button>
                            <button type="button" class="btn btn-light-danger btn-sm"
                                wire:click.stop="updateEventStatus({{ $event->id }}, 'terminate')"
                                @unless($needsQtyModal) wire:confirm="End this event? It cannot be resumed afterwards." @endunless
                                wire:loading.attr="disabled" wire:target="updateEventStatus">
                                <i class="bi bi-stop-fill"></i> End
                            </button>
                            @elseif($statusKey === 'paused')
                            <button type="button" class="btn btn-light-success btn-sm"
                                wire:click.stop="updateEventStatus({{ $event->id }}, 'resume')"
                                wire:loading.attr="disabled" wire:target="updateEventStatus">
                                <i class="bi bi-play-fill"></i> Resume
                            </button>
                            <button type="button" class="btn btn-light-danger btn-sm"
                                wire:click.stop="updateEventStatus({{ $event->id }}, 'terminate')"
                                @unless($needsQtyModal) wire:confirm="End this event? It cannot be resumed afterwards." @endunless
                                wire:loading.attr="disabled" wire:target="updateEventStatus">
                                <i class="bi bi-stop-fill"></i> End
                            </button>
                            @else
                            <span class="text-muted" style="font-size: 11px;">—</span>
                            @endif
                            @endhasPermission
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
