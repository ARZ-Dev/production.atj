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
@endphp
<div class="pbc-card" draggable="true" data-event-id="{{ $event->id }}" wire:key="pbc-event-{{ $event->id }}"
    wire:click="showEventDetails({{ $event->id }})"
    style="left: calc(var(--pbc-hour-w) * {{ $fromHour }}); width: calc(var(--pbc-hour-w) * {{ $spanHours }} - 4px); top: {{ $track * 48 + 4 }}px; border-left-color: {{ $color }};"
    title="{{ $event->eventType->name ?? 'No type' }} — {{ \Carbon\Carbon::parse($event->from_time)->format('H:i') }}{{ $event->to_time ? ' – '.\Carbon\Carbon::parse($event->to_time)->format('H:i') : '' }}">
    <span class="pbc-card-type">
        {{ $event->eventType->name ?? 'No type' }}{{ $event->batch_count ? ' · '.$event->batch_count.' batches' : '' }}
    </span>
    @if($durationLabel)
        <span class="pbc-card-duration">Duration: {{ $durationLabel }}</span>
    @elseif($hasRecipe)
        <span class="pbc-card-duration text-warning">
            <i class="bi bi-exclamation-circle"></i> Drop on an allowed lane
        </span>
    @endif
</div>
