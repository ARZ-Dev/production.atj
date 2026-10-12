{{--
    One read-only board for the "Actual" and "Downtime" modes — the production
    board or the packaging board. The "nothing recorded" notice lives in the
    caller, since it covers both boards.
    Expects: $rows (readonlyLaneRows output), $mode, $board, $emptyMessage
--}}
<div class="pbc-grid-wrap" wire:key="ro-{{ $mode }}-board-{{ $board }}">
    <div class="pbc-grid" data-board="{{ $board }}">
        <div class="pbc-corner"></div>
        <div class="pbc-hour-row">
            @for($slot = 0; $slot < 96; $slot++)
            @php $mins = $slot * 15; @endphp
            <div class="pbc-hour-cell {{ $mins % 60 === 0 ? 'is-hour' : '' }}"><span>{{ sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60) }}</span></div>
            @endfor
        </div>

        @forelse($rows as $row)
        @php $laneCount = max(count($row['lanes']), 1); @endphp

        @if(count($row['lanes']))
            @foreach($row['lanes'] as $i => $laneRow)
            @php $lane = $laneRow['lane']; $layout = $laneRow['layout']; @endphp
            @if($i === 0)
            <div class="pbc-pl-label {{ $board === 'packaging' ? 'pbc-pl-label--packaging' : '' }}" style="grid-row: span {{ $laneCount }};" wire:key="ro-{{ $mode }}-{{ $row['key'] }}">
                {{ $row['label'] }}
            </div>
            @endif
            <div class="pbc-row-label" wire:key="ro-{{ $mode }}-lane-label-{{ $row['key'] }}-{{ $lane['type'] }}-{{ $lane['id'] }}">
                <i class="bi bi-{{ $lane['icon'] }} me-1"></i>
                {{ $lane['name'] }}
            </div>
            <div class="pbc-row-track"
                wire:key="ro-{{ $mode }}-lane-track-{{ $row['key'] }}-{{ $lane['type'] }}-{{ $lane['id'] }}"
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
            <div class="pbc-pl-label {{ $board === 'packaging' ? 'pbc-pl-label--packaging' : '' }}" style="grid-row: span 1;" wire:key="ro-{{ $mode }}-{{ $row['key'] }}">
                {{ $row['label'] }}
            </div>
            <div class="pbc-row-track-empty">{{ $row['empty'] }}</div>
        @endif

        @empty
        <div class="pbc-empty">
            {{ $emptyMessage }}
        </div>
        @endforelse
    </div>
</div>
