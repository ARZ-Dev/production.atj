@php
    $hasRecipe = (bool) ($event->eventType?->has_recipe);
    $durationMinutes = $hasRecipe ? $event->calculated_duration : $event->planned_duration;
    $color = $event->eventType->color ?? '#818cf8';

    $durationLabel = null;
    if ($durationMinutes) {
        $h = intdiv((int) $durationMinutes, 60);
        $m = $durationMinutes % 60;
        $durationLabel = $h > 0 ? ($m > 0 ? "{$h}h {$m}m" : "{$h}h") : "{$m}m";
    }

    $isCarryOver = (bool) ($event->is_carry_over ?? false);
    // Placed event whose to_time wrapped past midnight — it continues on the
    // next day's board (carry-over cards are the tail end, not a continuation).
    $continuesNextDay = !$isCarryOver && $event->placeable_id && $event->from_time && $event->to_time
        && \Carbon\Carbon::parse($event->to_time)->lte(\Carbon\Carbon::parse($event->from_time));

    $timeLabel = $event->from_time ? ' — ' . \Carbon\Carbon::parse($event->from_time)->format('H:i') : '';
    $timeLabel .= $event->to_time ? ' – ' . \Carbon\Carbon::parse($event->to_time)->format('H:i') : '';
    if ($isCarryOver) {
        $timeLabel .= ' (started previous day)';
    } elseif ($continuesNextDay) {
        $timeLabel .= ' (ends next day)';
    }
@endphp
<div class="pbc-card {{ $isCarryOver ? 'pbc-card--carry' : '' }} {{ $continuesNextDay ? 'pbc-card--continues' : '' }}"
    draggable="{{ $isCarryOver ? 'false' : 'true' }}" data-event-id="{{ $event->id }}" wire:key="pbc-event-{{ $event->id }}"
    wire:click="showEventDetails({{ $event->id }})"
    style="left: calc(var(--pbc-hour-w) * {{ $fromHour }}); width: calc(var(--pbc-hour-w) * {{ $spanHours }} - 4px); top: {{ $track * 48 + 4 }}px; background-color: {{ $color }}; color: #fff;"
    title="{{ $event->eventType->name ?? 'No type' }}{{ $timeLabel }}">
    <span class="pbc-card-type">
        @if($isCarryOver)<i class="bi bi-arrow-bar-right"></i>@endif
        {{ $event->eventType->name ?? 'No type' }}{{ $event->batch_count ? ' · '.$event->batch_count.' batches' : '' }}
        @if($continuesNextDay)<i class="bi bi-arrow-bar-right"></i>@endif
    </span>
    @if($durationLabel)
        <span class="pbc-card-duration">Duration: {{ $durationLabel }}</span>
    @elseif($hasRecipe)
        <span class="pbc-card-duration text-warning">
            <i class="bi bi-exclamation-circle"></i> Drop on an allowed lane
        </span>
    @endif
</div>
