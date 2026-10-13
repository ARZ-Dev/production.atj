{{--
    One planned (drag & drop) board: the production board or the packaging board.
    Expects: $rows (PlanBoard::laneRows output), $board ('production' | 'packaging'), $emptyMessage
--}}
<div class="pbc-grid-wrap" wire:key="pbc-board-{{ $board }}">
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
            <div class="pbc-pl-label {{ $board === 'packaging' ? 'pbc-pl-label--packaging' : '' }}" style="grid-row: span {{ $laneCount }};" wire:key="pbc-{{ $row['key'] }}">
                {{ $row['label'] }}
            </div>
            @endif
            <div class="pbc-row-label" wire:key="pbc-lane-label-{{ $row['key'] }}-{{ $lane['type'] }}-{{ $lane['id'] }}">
                <i class="bi bi-{{ $lane['icon'] }} me-1"></i>
                {{ $lane['name'] }}
            </div>
            <div class="pbc-row-track pbc-drop"
                data-production-line-id="{{ $row['production_line_id'] }}"
                data-placeable-type="{{ $lane['type'] }}"
                data-placeable-id="{{ $lane['id'] }}"
                wire:key="pbc-lane-track-{{ $row['key'] }}-{{ $lane['type'] }}-{{ $lane['id'] }}"
                style="height: {{ max($layout['tracks'], 1) * 48 + 12 }}px;">
                @foreach($layout['items'] as $item)
                    @include('livewire.plans.partials._calendar-event-card', [
                        'event' => $item['event'],
                        'track' => $item['track'],
                        'fromHour' => $item['fromHour'],
                        'spanHours' => $item['spanHours'],
                    ])
                @endforeach
            </div>
            @endforeach
        @else
            <div class="pbc-pl-label {{ $board === 'packaging' ? 'pbc-pl-label--packaging' : '' }}" style="grid-row: span 1;" wire:key="pbc-{{ $row['key'] }}">
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
