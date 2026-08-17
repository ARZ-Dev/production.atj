@php
    /**
     * Warehouse per item type, grouped by event type.
     *
     * Expects: $groups, $sourceWarehouseIds, $warehouseOptions.
     *
     * The map is keyed by item type only, so an item type appearing under two
     * event types shows twice bound to the same value — changing one changes both.
     */
    $sourceOptions = collect($sourceWarehouseIds)->map(function ($id) use ($warehouseOptions) {
        $warehouse = collect($warehouseOptions)->firstWhere('id', (int) $id);

        return ['id' => (int) $id, 'name' => $warehouse['name'] ?? "Warehouse #{$id}"];
    });
@endphp

<label class="form-label mb-1">
    Warehouse Per Item Type
    <span class="text-muted" style="font-size: 11px;">
        — items of an item type are consumed from the warehouse chosen here instead of the first one selected above
    </span>
</label>

@if(!count($groups))
    <div class="border rounded p-3 text-muted" style="font-size: 12px;">
        <i class="bi bi-info-circle me-1"></i>Select the allowed event types above to route their item types.
    </div>
@else
    <div class="table-responsive border rounded">
        <table class="table table-sm align-middle mb-0 aqt-table" style="font-size: 12px;">
            <thead>
                <tr>
                    <th>Item Type</th>
                    <th style="width: 45%;">Consume From</th>
                </tr>
            </thead>
            @foreach($groups as $group)
            <tbody wire:key="itw-group-{{ $group['event_type_id'] }}">
                <tr class="aqt-group-row">
                    <td colspan="2" class="aqt-group-title">
                        <span class="d-inline-block rounded-circle me-1"
                            style="width: 8px; height: 8px; background: {{ $group['color'] }};"></span>{{ $group['event_type_name'] }}
                        <span class="aqt-group-count">{{ count($group['rows']) }}</span>
                    </td>
                </tr>

                @forelse($group['rows'] as $row)
                <tr wire:key="itw-{{ $group['event_type_id'] }}-{{ $row['item_type_id'] }}">
                    <td>{{ $row['item_type_name'] }}</td>
                    <td>
                        @if($sourceOptions->isEmpty())
                        <span class="text-muted">Select a warehouse above first</span>
                        @else
                        <select class="form-select form-select-sm"
                            wire:model="itemTypeWarehouses.{{ $row['item_type_id'] }}">
                            <option value="">Default — {{ $sourceOptions->first()['name'] }}</option>
                            @foreach($sourceOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>No item types are configured on this event type.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @endforeach
        </table>
    </div>
@endif
