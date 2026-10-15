{{--
    What a lead event's run makes, and how it's shared out between the
    companions it runs with — see LinkedEventService::distribution().

    Expects: $dist (array|null), $index (lead row), $colors (one per companion,
    in order), $companionKeys (in order), $rowStarted, $ecQty, $ecMinutes.

    The bar shows the batch counts as entered, not the suggestion, so a
    hand-edited count shows up as quantity left over or taken twice.
--}}
<div class="ec-split">
    @if(!$dist)
    <div class="ec-split-empty">
        <i class="bi bi-calculator"></i>
        Pick the recipe and batch number above to see what this run makes and how it's shared out.
    </div>
    @else
    @php
        $unit       = $dist['unit'] ? ' ' . $dist['unit'] : '';
        $total      = (float) $dist['total'];
        $assigned   = (float) $dist['assigned'];
        $over       = $dist['balance'] < 0;
        $scale      = $over ? $assigned : $total;   // what 100% of the bar stands for
        $differs    = collect($dist['companions'])->contains(fn($c) => $c['suggested_batches'] !== null
                        && abs(((float) $c['suggested_batches']) - ((float) ($c['batches'] ?? 0))) > 0.00005);
    @endphp

    <div class="ec-split-head">
        <div class="ec-split-stat">
            <div class="ec-split-label">Per batch</div>
            <div class="ec-split-value">{{ $ecQty($dist['per_batch'], 3) }}<small>{{ $unit }}</small></div>
        </div>
        <div class="ec-split-op">×</div>
        <div class="ec-split-stat">
            <div class="ec-split-label">Batches</div>
            <div class="ec-split-value">{{ $ecQty($dist['batches'], 4) }}</div>
        </div>
        <div class="ec-split-op">=</div>
        <div class="ec-split-stat ec-split-stat--total">
            <div class="ec-split-label">Total made</div>
            <div class="ec-split-value">{{ $ecQty($total, 3) }}<small>{{ $unit }}</small></div>
        </div>
        <div class="ec-split-item">
            <i class="bi bi-box-arrow-right"></i> {{ $dist['item_name'] }}
            <div class="ec-split-item-sub">passes straight to packaging — no warehouse</div>
        </div>
        @if($dist['mode'])
        <span class="ec-split-mode {{ $dist['mode'] === 'capacity' ? 'ec-split-mode--capacity' : '' }}">
            <i class="bi bi-{{ $dist['mode'] === 'capacity' ? 'speedometer2' : 'distribute-horizontal' }}"></i>
            {{ $dist['mode'] === 'capacity' ? 'Split by capacity' : 'Split evenly' }}
        </span>
        @endif
    </div>

    <div class="ec-split-bar {{ $over ? 'ec-split-bar--over' : '' }}" role="img"
        aria-label="{{ $ecQty($assigned, 3) }}{{ $unit }} of {{ $ecQty($total, 3) }}{{ $unit }} assigned">
        @foreach($companionKeys as $i => $cKey)
            @php $part = $dist['companions'][$cKey] ?? null; $qty = (float) ($part['assigned'] ?? 0); @endphp
            @if($part && $qty > 0 && $scale > 0)
            <span class="ec-split-seg" style="width: {{ round($qty / $scale * 100, 3) }}%; background-color: {{ $colors[$i] }};"
                title="#{{ $i + 1 }} · {{ $part['packaging_name'] ?? 'no packaging yet' }} · {{ $ecQty($qty, 3) }}{{ $unit }}">
                #{{ $i + 1 }} · {{ round($qty / max($total, 1e-9) * 100) }}%
            </span>
            @endif
        @endforeach
        @if(!$over && $dist['balance'] > 0 && $total > 0)
        <span class="ec-split-seg ec-split-seg--free" style="width: {{ round($dist['balance'] / $total * 100, 3) }}%;"
            title="{{ $ecQty($dist['balance'], 3) }}{{ $unit }} not assigned">
            {{ round($dist['balance'] / $total * 100) }}% free
        </span>
        @endif
    </div>

    <div class="ec-split-foot">
        @if($dist['balance'] > 0)
        <span class="ec-split-status ec-split-status--warn">
            <i class="bi bi-exclamation-triangle"></i>
            {{ $ecQty($dist['balance'], 3) }}{{ $unit }} isn't assigned to any packaging event.
        </span>
        @elseif($over)
        <span class="ec-split-status ec-split-status--danger">
            <i class="bi bi-exclamation-octagon"></i>
            The packaging events take {{ $ecQty(-$dist['balance'], 3) }}{{ $unit }} more than this run makes.
        </span>
        @elseif($dist['mode'])
        <span class="ec-split-status ec-split-status--ok">
            <i class="bi bi-check-circle"></i> All {{ $ecQty($total, 3) }}{{ $unit }} is assigned.
        </span>
        @endif

        <span class="ec-split-note">
            @if($dist['mode'] === 'capacity')
                Shared in proportion to each packaging's capacity, so they finish together{{ $dist['finish_minutes'] ? ' — in about ' : '' }}@if($dist['finish_minutes'])<strong>{{ $ecMinutes($dist['finish_minutes']) }}</strong>@endif.
            @elseif($dist['mode'] === 'even')
                @php $missing = collect($dist['companions'])->firstWhere('issue', 'capacity'); @endphp
                @if($missing)
                    No capacity is set on {{ $missing['packaging_name'] }} for {{ $missing['out_item'] }}, so the run is split evenly.
                @else
                    Pick a packaging for every event to split the run by capacity.
                @endif
            @else
                Pick each packaging event's recipe to see how the run is shared out.
            @endif
        </span>

        @if($dist['mode'] && ($differs || $dist['balance'] != 0) && !$rowStarted)
        <button type="button" class="btn btn-sm btn-light-primary ms-auto"
            wire:click="resplitCompanions({{ $index }})" wire:loading.attr="disabled" wire:target="resplitCompanions">
            <i class="bi bi-arrow-repeat me-1"></i>{{ $dist['mode'] === 'capacity' ? 'Split by capacity' : 'Split evenly' }}
        </button>
        @endif
    </div>
    @endif
</div>
