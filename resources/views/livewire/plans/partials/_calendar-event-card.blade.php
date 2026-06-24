@php
    $hasRecipe = (bool) ($event->eventType?->has_recipe);
    $duration  = $hasRecipe ? $event->calculated_duration : $event->planned_duration;
    $color     = $event->eventType->color ?? '#818cf8';
@endphp
<div class="pbc-card" draggable="true" data-event-id="{{ $event->id }}" wire:key="pbc-event-{{ $event->id }}"
    style="left: calc(var(--pbc-hour-w) * {{ $fromHour }}); width: calc(var(--pbc-hour-w) * {{ $spanHours }} - 4px); top: {{ $track * 36 + 4 }}px; border-left-color: {{ $color }}; background-color: {{ $color }}1a;"
    title="{{ $event->eventType->name ?? 'No type' }} — {{ \Carbon\Carbon::parse($event->from_time)->format('H:i') }}{{ $event->to_time ? ' – '.\Carbon\Carbon::parse($event->to_time)->format('H:i') : '' }}">
    <span class="pbc-card-type" style="color: {{ $color }}">{{ $event->eventType->name ?? 'No type' }}</span>
    <span class="pbc-card-time">{{ \Carbon\Carbon::parse($event->from_time)->format('H:i') }}</span>
    @if($event->batch_count)
        <span class="pbc-card-batch">#{{ $event->batch_count }}</span>
    @endif
    @if($hasRecipe && !$event->calculated_duration)
        <i class="bi bi-exclamation-circle text-warning ms-1" title="Drop on an allowed line/preparation to calculate duration"></i>
    @endif
</div>
