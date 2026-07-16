{{--
    Read-only board grid for the "Actual" and "Downtime" modes.
    Expects: $rows (readonlyLaneRows output), $mode, $emptyMessage
--}}
@php
    $hasItems = collect($rows)->contains(
        fn($row) => collect($row['lanes'])->contains(fn($laneRow) => count($laneRow['layout']['items']) > 0)
    );
@endphp

@unless($hasItems)
<div class="alert alert-light border text-muted py-2 px-3 mb-3" style="font-size: 12px;">
    <i class="bi bi-info-circle me-1"></i> {{ $emptyMessage }}
</div>
@endunless

<div class="pbc-wrap">
    <div class="pbc-grid-wrap">
        <div class="pbc-grid">
            <div class="pbc-corner"></div>
            <div class="pbc-hour-row">
                @for($slot = 0; $slot < 96; $slot++)
                @php $mins = $slot * 15; @endphp
                <div class="pbc-hour-cell {{ $mins % 60 === 0 ? 'is-hour' : '' }}"><span>{{ sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60) }}</span></div>
                @endfor
            </div>

            @forelse($rows as $row)
            @php $pl = $row['production_line']; $laneCount = max(count($row['lanes']), 1); @endphp

            @if(count($row['lanes']))
                @foreach($row['lanes'] as $i => $laneRow)
                @php $lane = $laneRow['lane']; $layout = $laneRow['layout']; @endphp
                @if($i === 0)
                <div class="pbc-pl-label" style="grid-row: span {{ $laneCount }};" wire:key="ro-{{ $mode }}-pl-{{ $pl->id }}">
                    {{ $pl->name }}
                </div>
                @endif
                <div class="pbc-row-label" wire:key="ro-{{ $mode }}-lane-label-{{ $pl->id }}-{{ $lane['type'] }}-{{ $lane['id'] }}">
                    <i class="bi bi-{{ $lane['type'] === 'preparation' ? 'egg-fried' : 'diagram-3' }} me-1"></i>
                    {{ $lane['name'] }}
                </div>
                <div class="pbc-row-track"
                    wire:key="ro-{{ $mode }}-lane-track-{{ $pl->id }}-{{ $lane['type'] }}-{{ $lane['id'] }}"
                    style="height: {{ max($layout['tracks'], 1) * 48 + 12 }}px;">
                    @foreach($layout['items'] as $item)
                    <div class="pbc-card pbc-card--static {{ $item['running'] ? 'pbc-card--running' : '' }}"
                        wire:key="ro-{{ $mode }}-{{ $item['key'] }}"
                        wire:click="showEventDetails({{ $item['event_id'] }})"
                        style="left: calc(var(--pbc-hour-w) * {{ $item['fromHour'] }}); width: calc(var(--pbc-hour-w) * {{ $item['spanHours'] }} - 4px); top: {{ $item['track'] * 48 + 4 }}px; background-color: {{ $item['color'] }}; color: #fff;"
                        title="{{ $item['title'] }}">
                        @foreach($item['segments'] as $seg)
                        <div class="pbc-card-seg" style="left: {{ $seg['left'] }}%; width: {{ $seg['width'] }}%;"
                            title="{{ $seg['title'] }}"></div>
                        @endforeach
                        <span class="pbc-card-type">{{ $item['label'] }}</span>
                        <span class="pbc-card-duration">{{ $item['sub'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endforeach
            @else
                <div class="pbc-pl-label" style="grid-row: span 1;" wire:key="ro-{{ $mode }}-pl-{{ $pl->id }}">
                    {{ $pl->name }}
                </div>
                <div class="pbc-row-track-empty">No preparations or lines attached to this production line.</div>
            @endif

            @empty
            <div class="pbc-empty">
                No production lines found for this factory.
            </div>
            @endforelse
        </div>
    </div>
</div>
