{{-- Details modal: a line run's line, rate, planned mixes and counts. --}}
@php
    $run = $selectedEvent['run'];
    $fmt = fn($n, $dp = 2) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, $dp), '0'), '.');
@endphp
<div class="col-12">
    <div class="pvc-body-label">Line run</div>
    <div>
        {{ $fmt($run['planned']) }} {{ $run['unit'] }}
        @if($run['line_name'])
            on {{ $run['line_name'] }}
            @if($run['per_hour'])
            · {{ $fmt($run['per_hour'], 0) }} {{ $run['unit'] }}/h, set by {{ $run['bottleneck'] }}
            @else
            · <span class="text-muted">no station rates</span>
            @endif
        @else
            <span class="text-muted">· not on a line yet</span>
        @endif
        @if($run['needs_review'])
        <span class="badge bg-warning text-dark ms-1"><i class="bi bi-flag me-1"></i>Needs review</span>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-1 mt-1">
        @foreach($run['stages'] as $stage)
        <span class="badge {{ ($stage['stage'] ?? null) === $run['bottleneck'] ? 'bg-warning text-dark' : 'bg-light-secondary text-secondary' }}">
            {{ $stage['stage'] }}{{ !empty($stage['rate_label']) ? ' · ' . $stage['rate_label'] : '' }}
        </span>
        @endforeach
    </div>
</div>

<div class="col-12">
    <div class="pvc-body-label d-flex align-items-center gap-2">
        Mixes for this run
        @hasPermission('production.event-create')
        @if($run['can_plan_mixes'] && !$selectedEvent['is_carry_over'])
        <button type="button" class="btn btn-light-primary btn-sm py-0 ms-auto" wire:click="planMixes({{ $selectedEvent['id'] }})"
            wire:loading.attr="disabled" wire:target="planMixes"
            @if($run['mixes']) wire:confirm="Replace the {{ count($run['mixes']) }} planned mixes with a new plan?" @endif>
            <i class="bi bi-magic me-1"></i>{{ $run['mixes'] ? 'Plan again' : 'Plan mixes' }}
        </button>
        @endif
        @endhasPermission
    </div>
    @if($run['mixes'])
    <div class="d-flex flex-wrap gap-1">
        @foreach($run['mixes'] as $mix)
        <span class="badge bg-light-primary text-primary" title="Event #{{ $mix['id'] }} · {{ $mix['status'] }}">
            {{ $mix['from'] }}–{{ $mix['to'] }}{{ (float) $mix['batch'] !== 1.0 ? ' · ' . $mix['batch'] . ' batch' : '' }}
            @if($mix['status'] !== 'Planned')· {{ $mix['status'] }}@endif
        </span>
        @endforeach
    </div>
    @else
    <div class="text-muted" style="font-size: 12px;">
        None planned. {{ $run['can_plan_mixes'] ? '"Plan mixes" schedules them from the run\'s start and the line\'s rate.' : 'Place the run on its line to plan them.' }}
    </div>
    @endif
</div>

@if($run['counts'])
<div class="col-12">
    <div class="pvc-body-label">Hourly counts</div>
    <div class="d-flex flex-wrap gap-1">
        @foreach($run['counts'] as $count)
        <span class="badge {{ $count['pace'] !== null && $count['pace'] < \App\Services\LineRunService::PACE_REASON_BELOW ? 'bg-warning text-dark' : 'bg-light-success text-success' }}"
            title="{{ $count['reason'] }}">
            {{ $count['at'] }} · +{{ $fmt($count['quantity'], 0) }}{{ $count['pace'] !== null ? ' · ' . $fmt($count['pace'], 0) . '%' : '' }}
        </span>
        @endforeach
    </div>
</div>
@endif
