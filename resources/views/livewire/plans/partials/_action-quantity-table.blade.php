{{--
    Quantity rows for the status-action modal, grouped by item type
    (Raw Material, Packaging, …). The original row index is preserved as the
    group key so the wire:model bindings keep pointing at the right row.

    Expects: $rows (array), $model (Livewire property name),
             $actualLabel, $percentLabel
    Optional: $showOriginal (bool, default true) — hide the "Original Qty"
              and "% Difference" columns for events without a planned
              quantity (non-recipe).
--}}
@php
    $showOriginal = $showOriginal ?? true;
    $colSpan = $showOriginal ? 5 : 3;

    $groups = [];
    foreach ($rows as $i => $row) {
        $groupName = $row['item_type_name'] ?? 'Other';
        $groups[$groupName][$i] = $row;
    }
@endphp
<div class="table-responsive">
    <table class="table table-sm align-middle mb-2 aqt-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Unit</th>
                @if($showOriginal)
                <th style="width: 130px;">Original Qty</th>
                @endif
                <th style="width: 150px;">{{ $actualLabel }}</th>
                @if($showOriginal)
                <th style="width: 130px;">{{ $percentLabel }}</th>
                @endif
            </tr>
        </thead>
        @foreach($groups as $groupName => $groupRows)
        <tbody wire:key="{{ $model }}-group-{{ $loop->index }}">
            <tr class="aqt-group-row">
                <td colspan="{{ $colSpan }}" class="aqt-group-title">
                    <i class="bi bi-tag-fill me-1"></i>{{ $groupName }}
                    <span class="aqt-group-count">{{ count($groupRows) }}</span>
                </td>
            </tr>
            @foreach($groupRows as $i => $row)
            <tr wire:key="{{ $model }}-row-{{ $i }}">
                <td>{{ $row['item_name'] ?? '—' }}</td>
                <td>{{ $row['unit_name'] ?? '—' }}</td>
                @if($showOriginal)
                <td>
                    <input type="number" class="form-control form-control-sm"
                        value="{{ $row['planned_quantity'] + 0 }}" disabled>
                </td>
                @endif
                <td>
                    <input type="number" step="any" min="0"
                        class="form-control form-control-sm @error("{$model}.{$i}.actual_quantity") is-invalid @enderror"
                        wire:model.live="{{ $model }}.{{ $i }}.actual_quantity" placeholder="0">
                    @error("{$model}.{$i}.actual_quantity")
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </td>
                @if($showOriginal)
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control"
                            value="{{ $row['percentage'] !== null ? $row['percentage'] + 0 : '' }}" disabled>
                        <span class="input-group-text">%</span>
                    </div>
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
        @endforeach
    </table>
</div>
