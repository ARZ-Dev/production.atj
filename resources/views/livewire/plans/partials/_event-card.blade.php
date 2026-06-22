@php $hasRecipe = (bool) ($event->eventType?->has_recipe); @endphp
<div class="pb-card" data-event-id="{{ $event->id }}" wire:key="pb-event-{{ $event->id }}">
    <div class="pb-card-head">
        <span class="pb-card-type">{{ $event->eventType->name ?? 'No type' }}</span>
        @if($hasRecipe)
        <span class="pb-card-recipe-badge" title="Recipe-driven"><i class="bi bi-droplet-half"></i></span>
        @endif
    </div>
    <div class="pb-card-body">
        @if($event->from_time)
        <span class="pb-card-time">
            <i class="bi bi-clock chip-icon"></i>
            {{ \Carbon\Carbon::parse($event->from_time)->format('H:i') }}
            @if($event->to_time) – {{ \Carbon\Carbon::parse($event->to_time)->format('H:i') }} @endif
        </span>
        @endif

        @if($event->batch_count)
        <span class="pb-card-batch">Batch #{{ $event->batch_count }}</span>
        @endif

        <span class="pb-card-duration {{ $hasRecipe && !$event->calculated_duration ? 'pb-card-duration--pending' : '' }}">
            @if($hasRecipe)
                {{ $event->calculated_duration ? $event->calculated_duration . ' min' : 'Drop on a line to calculate' }}
            @else
                {{ $event->planned_duration ? $event->planned_duration . ' min' : '—' }}
            @endif
        </span>
    </div>
</div>
