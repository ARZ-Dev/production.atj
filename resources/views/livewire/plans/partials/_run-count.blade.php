{{-- An hourly count of a line run: the last station's counter, against the
     line's rate since the previous count. For watching the pace — no stock
     moves. --}}
@php
    $fmt     = fn($n, $dp = 2) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, $dp), '0'), '.');
    $preview = $eventAction['preview'] ?? null;
    $below   = $preview && $preview['pace'] !== null && $preview['pace'] < \App\Services\LineRunService::PACE_REASON_BELOW;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Count Time <span class="text-danger">*</span></label>
        <input type="datetime-local" class="form-control @error('actionTime') is-invalid @enderror" wire:model.blur="actionTime">
        @error('actionTime')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Counter reading ({{ $eventAction['unit'] }}) <span class="text-danger">*</span></label>
        <input type="text" inputmode="decimal" class="form-control @error('runCount.counter') is-invalid @enderror"
            wire:model.blur="runCount.counter" placeholder="Total so far on the last station's counter">
        @error('runCount.counter')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@if($preview)
<div class="pv-stats mt-3 mb-2" style="margin-bottom: 12px;">
    <div class="pv-stat pv-stat--primary">
        <div class="s-label">Since {{ $preview['from'] }} ({{ $preview['minutes'] }} min)</div>
        <div class="s-val">{{ $preview['quantity'] === null ? '—' : $fmt($preview['quantity'], 0) }}</div>
    </div>
    <div class="pv-stat">
        <div class="s-label">Target</div>
        <div class="s-val">{{ $preview['target'] === null ? '—' : $fmt($preview['target'], 0) }}</div>
    </div>
    <div class="pv-stat {{ $preview['pace'] === null ? '' : ($below ? 'pv-stat--warning' : 'pv-stat--success') }}">
        <div class="s-label">Pace</div>
        <div class="s-val">{{ $preview['pace'] === null ? '—' : $fmt($preview['pace'], 1) . ' %' }}</div>
    </div>
</div>
@if($preview['stops'])
<div class="text-muted mb-2" style="font-size: 12px;">
    <i class="bi bi-exclamation-octagon me-1"></i>Stops in this period: {{ implode('; ', $preview['stops']) }}
</div>
@endif
@endif

<div>
    <label class="form-label">Reason @if($below)<span class="text-danger">*</span>@endif</label>
    <textarea class="form-control @error('runCount.reason') is-invalid @enderror" rows="2" wire:model="runCount.reason"
        placeholder="{{ $below ? 'Below ' . (int) \App\Services\LineRunService::PACE_REASON_BELOW . ' % of target — why?' : 'Optional' }}"></textarea>
    @error('runCount.reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($below && !empty($preview['stops']))
    <button type="button" class="btn btn-link btn-sm px-0" wire:click="$set('runCount.reason', @js(implode('; ', $preview['stops'])))">
        Use the stops logged above
    </button>
    @endif
</div>

@if($eventAction['counts'])
<div class="fw-bold mt-3 mb-1" style="font-size: 13px;">Counts so far</div>
<table class="table table-sm mb-0" style="font-size: 12px;">
    <thead><tr><th>At</th><th class="text-end">Counter</th><th class="text-end">Made</th><th class="text-end">Target</th><th class="text-end">Pace</th><th>Reason</th></tr></thead>
    <tbody>
        @foreach($eventAction['counts'] as $count)
        <tr>
            <td>{{ $count['at'] }}</td>
            <td class="text-end">{{ $fmt($count['counter'], 0) }}</td>
            <td class="text-end">{{ $fmt($count['quantity'], 0) }}</td>
            <td class="text-end">{{ $fmt($count['target'], 0) }}</td>
            <td class="text-end">{{ $count['pace'] === null ? '—' : $fmt($count['pace'], 1) . ' %' }}</td>
            <td class="text-muted">{{ $count['reason'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
