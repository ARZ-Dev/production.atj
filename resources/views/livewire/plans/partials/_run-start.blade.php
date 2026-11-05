{{-- Start of a line run: nothing is taken from stock here — the line's dough
     is mixed while it runs, and stock moves once, at End. Every material the
     run needs is checked against its stock first (runMaterialsCheck()). --}}
@php
    $fmt = fn($n, $dp = 2) => $n === null ? '—' : ($dp > 0 ? rtrim(rtrim(number_format((float) $n, $dp), '0'), '.') : number_format((float) $n));
@endphp

<div class="alert alert-info py-2 px-3 mb-3" style="font-size: 12px;">
    <i class="bi bi-diagram-3 me-1"></i>
    <strong>{{ $eventAction['planned'] + 0 }} {{ $eventAction['unit'] }}</strong> on <strong>{{ $eventAction['line_name'] }}</strong>.
    Every station runs at once from this start; nothing is taken from stock now — what each station counts is entered when the run ends.
    The run starts only when there is enough of every material below.
</div>

<div class="ec-run-stations mb-2">
    @foreach($eventAction['stages'] as $stage)
    <div class="ec-run-station {{ $stage['stage'] === $eventAction['bottleneck'] ? 'is-slowest' : '' }} {{ !empty($stage['station_ids']) ? '' : 'is-missing' }}">
        <div class="ec-run-station-name">{{ $stage['stage'] }}</div>
        <div class="ec-run-station-rate">{{ $stage['rate_label'] ?? (!empty($stage['station_ids']) ? 'no rate' : 'no station') }}</div>
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

@if($eventAction['materials'] ?? [])
@php $problems = $eventAction['problems'] ?? []; @endphp
<div class="fw-bold mb-1" style="font-size: 13px;"><i class="bi bi-box-seam me-1 text-primary"></i> Materials for this run</div>
<div class="table-responsive">
<table class="table table-sm align-middle aqt-table mb-2" style="font-size: 12px;">
    <thead>
        <tr>
            <th>Item</th>
            <th>Warehouse</th>
            <th class="text-end">Needed</th>
            <th class="text-end">In stock now</th>
            <th class="text-end">Still to mix</th>
            <th class="text-end" style="width: 110px;"></th>
        </tr>
    </thead>
    <tbody>
        @foreach($eventAction['materials'] as $material)
        <tr wire:key="rs-material-{{ $material['warehouse_id'] }}-{{ $material['item_id'] }}-{{ $material['item_unit_id'] }}">
            <td>
                {{ $material['item_name'] }} <span class="text-muted">({{ $material['unit_name'] }})</span>
                @if($material['stage'])<div class="text-muted" style="font-size: 11px;">{{ $material['stage'] }}</div>@endif
            </td>
            <td>{{ $material['warehouse_name'] ?? '—' }}</td>
            <td class="text-end">{{ $fmt($material['needed'], 4) }}</td>
            <td class="text-end">
                @if($material['available'] === null)
                    <span class="aqt-stock aqt-stock--unknown">—</span>
                @else
                    <span class="aqt-stock {{ in_array($material['status'], ['short', 'empty'], true) ? 'aqt-stock--none' : 'aqt-stock--ok' }}">{{ $fmt($material['available'], 4) }}</span>
                @endif
            </td>
            <td class="text-end">{{ $material['coming'] > 0 ? $fmt($material['coming'], 4) : '—' }}</td>
            <td class="text-end">
                @switch($material['status'])
                    @case('ok')
                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check-lg me-1"></i>Enough</span>
                        @break
                    @case('short')
                        <span class="badge bg-danger-subtle text-danger">{{ $fmt($material['short'], 4) }} short</span>
                        @break
                    @case('empty')
                        <span class="badge bg-danger-subtle text-danger">None to start</span>
                        @break
                    @case('unknown')
                        <span class="badge bg-warning-subtle text-warning">Not read</span>
                        @break
                    @default
                        <span class="badge bg-danger-subtle text-danger">No warehouse</span>
                @endswitch
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>
@if($problems)
<div class="alert alert-danger py-2 px-3 mb-2" style="font-size: 12px;">
    <div class="fw-semibold mb-1"><i class="bi bi-x-circle me-1"></i>The run can't start yet:</div>
    <ul class="mb-0 ps-3">
        @foreach($problems as $problem)<li>{{ $problem }}</li>@endforeach
    </ul>
</div>
@endif
<div class="text-muted mb-2" style="font-size: 11px;">
    <i class="bi bi-info-circle me-1"></i>
    Every material must cover the whole run; dough also counts the mixes still planned for this run, and the first station needs some on hand.
    Nothing is held now — stock is read again when you press Start, and moves at End.
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
