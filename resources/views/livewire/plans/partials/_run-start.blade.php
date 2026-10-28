{{-- Start of a line run: nothing is taken from stock here — the line's dough
     is mixed while it runs, and stock moves once, at End. --}}
@php
    $fmt = fn($n, $dp = 2) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, $dp), '0'), '.');
@endphp

<div class="alert alert-info py-2 px-3 mb-3" style="font-size: 12px;">
    <i class="bi bi-diagram-3 me-1"></i>
    <strong>{{ $eventAction['planned'] + 0 }} {{ $eventAction['unit'] }}</strong> on <strong>{{ $eventAction['line_name'] }}</strong>.
    Every station runs at once from this start; nothing is taken from stock now — what each station counts is entered when the run ends.
</div>

<div class="ec-run-stations mb-2">
    @foreach($eventAction['stages'] as $stage)
    <div class="ec-run-station {{ $stage['stage'] === $eventAction['bottleneck'] ? 'is-slowest' : '' }} {{ $stage['station_id'] ? '' : 'is-missing' }}">
        <div class="ec-run-station-name">{{ $stage['stage'] }}</div>
        <div class="ec-run-station-rate">{{ $stage['rate_label'] ?? ($stage['station_id'] ? 'no rate' : 'no station') }}</div>
        @if($stage['stage'] === $eventAction['bottleneck'])<div class="ec-run-station-tag">slowest</div>@endif
    </div>
    @endforeach
</div>
<div class="text-muted mb-3" style="font-size: 12px;">
    @if($eventAction['per_hour'])
        Target: <strong>{{ $fmt($eventAction['per_hour'], 0) }} {{ $eventAction['unit'] }} an hour</strong>, set by {{ $eventAction['bottleneck'] }} — what the hourly counts are measured against.
    @else
        No station of this line has a rate, so the hourly counts will have no target.
    @endif
</div>

@if($eventAction['first_inputs'])
<div class="fw-bold mb-1" style="font-size: 13px;">What the first station starts with</div>
<table class="table table-sm align-middle aqt-table mb-2" style="font-size: 12px;">
    <thead>
        <tr><th>Item</th><th>Warehouse</th><th class="text-end">In stock now</th></tr>
    </thead>
    <tbody>
        @foreach($eventAction['first_inputs'] as $input)
        <tr>
            <td>{{ $input['item_name'] }} <span class="text-muted">· {{ $input['stage'] }}</span></td>
            <td>{{ $input['warehouse_name'] ?? '—' }}</td>
            <td class="text-end">
                @if($input['available'] === null)
                    <span class="aqt-stock aqt-stock--unknown">—</span>
                @else
                    <span class="aqt-stock {{ $input['available'] > 0 ? 'aqt-stock--ok' : 'aqt-stock--none' }}">{{ $fmt($input['available'], 3) }} {{ $input['unit_name'] }}</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="text-muted mb-2" style="font-size: 11px;">
    <i class="bi bi-info-circle me-1"></i>
    The run can't start while there is none — the rest is mixed while the line runs.
</div>
@endif

<div class="row g-3 mt-0">
    <div class="col-md-6">
        <label class="form-label">Start Time <span class="text-danger">*</span></label>
        <input type="datetime-local" class="form-control @error('actionTime') is-invalid @enderror" wire:model="actionTime">
        @error('actionTime')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="2" wire:model="actionNotes" placeholder="Optional notes about the start…"></textarea>
    </div>
</div>
