{{-- A line run in the add-event popup: the stations it runs on, how long it
     takes at the slowest one, and what it uses. $run comes from
     EventCreate::runInfo(). --}}
<div class="ec-run" wire:key="ec-run-{{ $rowKey }}">
    <div class="ec-run-head">
        <i class="bi bi-diagram-3"></i>
        <span>Line run</span>
        <span class="ec-run-sub">
            {{ $ecQty($run['output_quantity'], 4) }} {{ $run['output_unit'] }}
            @if($run['line_name'])
                · {{ $run['placed'] ? 'on' : 'estimated on' }} {{ $run['line_name'] }}
            @endif
        </span>
    </div>

    @if($run['line_name'])
    <div class="ec-run-stations">
        @foreach($run['stages'] as $stage)
        <div class="ec-run-station {{ $stage['stage'] === $run['bottleneck'] ? 'is-slowest' : '' }} {{ !empty($stage['station_ids']) ? '' : 'is-missing' }}">
            <div class="ec-run-station-name">{{ $stage['stage'] }}</div>
            <div class="ec-run-station-rate">
                @if(empty($stage['station_ids']))
                    no station
                @elseif($stage['batches_per_hour'])
                    {{ $ecQty($stage['batches_per_hour'] * $run['per_batch'], 0) }} {{ $run['output_unit'] }}/h
                @else
                    no rate
                @endif
            </div>
            @if($stage['stage'] === $run['bottleneck'])
            <div class="ec-run-station-tag">slowest</div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="ec-run-duration">
        @if($run['duration'])
            <i class="bi bi-stopwatch"></i>
            <strong>{{ $ecMinutes($run['duration']) }}</strong>
            at {{ $ecQty($run['per_hour'], 0) }} {{ $run['output_unit'] }}/h, set by {{ $run['bottleneck'] }}{{ $run['placed'] ? '' : ' — worked out again when placed' }}.
        @else
            <i class="bi bi-exclamation-triangle text-warning"></i>
            No station of {{ $run['line_name'] }} has a rate for this recipe yet, so the duration can't be worked out.
        @endif
    </div>

    @if($run['missing'])
    <div class="ec-run-warn">
        <i class="bi bi-exclamation-circle"></i>
        {{ $run['line_name'] }} has no {{ implode(', ', $run['missing']) }} station, so {{ count($run['missing']) > 1 ? 'those stages' : 'that stage' }} can't limit its speed. Add one on the line's Stations page.
    </div>
    @endif
    @else
    <div class="ec-run-warn">
        <i class="bi bi-info-circle"></i>
        No line that runs this event type has stations yet. Set them on the line's Stations page.
    </div>
    @endif

    @if($run['materials'])
    <table class="ec-run-materials">
        <thead>
            <tr>
                <th>Stage</th>
                <th>Material</th>
                <th class="text-end">Needed</th>
                <th class="text-end">In stock now</th>
            </tr>
        </thead>
        <tbody>
            @foreach($run['materials'] as $material)
            <tr>
                <td class="text-muted">{{ $material['stage'] ?? '—' }}</td>
                <td>{{ $material['item_name'] }}</td>
                <td class="text-end">{{ $ecQty($material['total'], 3) }} {{ $material['unit_name'] }}</td>
                <td class="text-end">
                    @if($material['available'] === null)
                        <span class="text-muted">—</span>
                    @else
                        <span class="{{ $material['available'] + 1e-6 < $material['total'] ? 'text-danger' : 'text-success' }}">{{ $ecQty($material['available'], 3) }}</span>
                    @endif
                    @if($material['warehouse_name'])<div class="ec-run-where">{{ $material['warehouse_name'] }}</div>@endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="ec-run-note">
        <i class="bi bi-info-circle"></i>
        Nothing is taken at Start — dough is mixed while the line runs. Stock moves once, when the run ends, from what is counted at each station.
    </div>
    @endif
</div>
