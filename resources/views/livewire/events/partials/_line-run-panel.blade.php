{{-- A line run in the add-event popup: every line of the plan's factory that
     takes it — its stations, the slowest one and how long the run takes there
     — and what the run uses. Folded by default, with the answer in the header;
     each line folds too. $run comes from EventCreate::runInfo().

     Every <details> carries wire:ignore.self: a re-render (any field change is
     one) morphs what's inside but leaves it open or closed as it was. --}}
@php
    $runMinutes = fn ($m) => $m === null ? null : ($m < 0.5 ? 'under 1m' : $ecMinutes((int) round($m)));
    $lead       = $run['lead'];
@endphp
<details class="ec-run" wire:key="ec-run-{{ $rowKey }}" wire:ignore.self>
    <summary class="ec-run-head">
        <i class="bi bi-chevron-right ec-run-caret"></i>
        <i class="bi bi-diagram-3"></i>
        <span>Line run</span>
        <span class="ec-run-sub">
            {{ $ecQty($run['output_quantity'], 4) }} {{ $run['output_unit'] }}
            @if($lead)
                · {{ $run['placed'] ? 'on' : 'fastest on' }} {{ $lead['name'] }} — {{ $runMinutes($lead['minutes']) ?? 'no rate yet' }}
            @elseif(count($run['lines']))
                · no line has a rate for it yet
            @else
                · no line takes this event type
            @endif
        </span>
        <span class="ec-run-chips">
            @if(count($run['lines']) > 1)
            <span class="ec-run-chip">{{ count($run['lines']) }} lines</span>
            @endif
            @if($run['short'])
            <span class="ec-run-chip is-short" title="Not enough in stock now">
                <i class="bi bi-exclamation-circle"></i> {{ $run['short'] }} short
            </span>
            @endif
        </span>
    </summary>

    <div class="ec-run-body">
        @if($run['lines'])
        <div class="ec-run-lines">
            @foreach($run['lines'] as $line)
            <details class="ec-run-line {{ $line['fastest'] ? 'is-fastest' : '' }}" wire:key="ec-run-{{ $rowKey }}-line-{{ $line['id'] }}" wire:ignore.self>
                <summary class="ec-run-line-head">
                    <i class="bi bi-chevron-right ec-run-caret"></i>
                    <span class="ec-run-line-name">
                        {{ $line['name'] }}
                        @if($line['placed'])<span class="ec-run-badge ec-run-badge--placed">placed</span>@endif
                        @if($line['fastest'])<span class="ec-run-badge ec-run-badge--fastest">fastest</span>@endif
                    </span>
                    <span class="ec-run-line-slowest">
                        @if($line['bottleneck'])
                            set by {{ $line['bottleneck'] }}
                        @elseif($line['has_stations'])
                            no station rated for it
                        @else
                            no stations
                        @endif
                    </span>
                    <span class="ec-run-line-rate">{{ $line['per_hour'] ? $ecQty($line['per_hour'], 0) . ' ' . $run['output_unit'] . '/h' : '—' }}</span>
                    <span class="ec-run-line-time">{{ $runMinutes($line['minutes']) ?? '—' }}</span>
                </summary>

                <div class="ec-run-line-body">
                    @if($line['has_stations'])
                    <div class="ec-run-stations">
                        @foreach($line['stages'] as $stage)
                        <div class="ec-run-station {{ $stage['stage'] === $line['bottleneck'] ? 'is-slowest' : '' }} {{ !empty($stage['station_ids']) ? '' : 'is-missing' }}">
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
                            @if($stage['stage'] === $line['bottleneck'])
                            <div class="ec-run-station-tag">slowest</div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    @if($line['minutes'] === null)
                    <div class="ec-run-warn">
                        <i class="bi bi-exclamation-triangle"></i>
                        No station of {{ $line['name'] }} has a rate for this recipe yet, so the duration can't be worked out.
                    </div>
                    @endif

                    @if($line['missing'])
                    <div class="ec-run-warn">
                        <i class="bi bi-exclamation-circle"></i>
                        {{ $line['name'] }} has no {{ implode(', ', $line['missing']) }} station, so {{ count($line['missing']) > 1 ? 'those stages' : 'that stage' }} can't limit its speed. Add one on the line's Stations page.
                    </div>
                    @endif
                    @else
                    <div class="ec-run-warn">
                        <i class="bi bi-info-circle"></i>
                        {{ $line['name'] }} has no stations yet. Set them on the line's Stations page.
                    </div>
                    @endif
                </div>
            </details>
            @endforeach
        </div>
        @unless($run['placed'])
        <div class="ec-run-note">
            <i class="bi bi-info-circle"></i>
            Estimates — the duration is worked out again for the line the run is placed on.
        </div>
        @endunless
        @else
        <div class="ec-run-warn">
            <i class="bi bi-info-circle"></i>
            No line of this plan's factory takes this event type. Allow it on a line first.
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
                        @forelse($material['stock'] as $stock)
                        <div>
                            @if($stock['available'] === null)
                                <span class="text-muted">—</span>
                            @else
                                <span class="{{ $stock['available'] + 1e-6 < $material['total'] ? 'text-danger' : 'text-success' }}">{{ $ecQty($stock['available'], 3) }}</span>
                            @endif
                            <div class="ec-run-where">{{ $stock['warehouse_name'] }}</div>
                        </div>
                        @empty
                        <span class="text-muted">—</span>
                        @endforelse
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
</details>
