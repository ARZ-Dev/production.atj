{{--
    Quantity rows for the status-action modal, grouped by item type
    (Raw Material, Packaging, …). The original row index is preserved as the
    group key so the wire:model bindings keep pointing at the right row.

    Expects: $rows (array), $model (Livewire property name),
             $actualLabel, $percentLabel
    Optional: $showOriginal (bool, default true) — hide the "Original Qty"
              and "% Difference" columns for events without a planned
              quantity (non-recipe).
              $showAvailable (bool, default false) — add an "In Stock" column
              showing what the source warehouse currently has. Display only:
              the start is still validated against a fresh read on submit.
              $showPerBatch (bool, default false) — add a "Per Batch" column
              and spell out "per batch × batches" under the total, for
              produced quantities that scale with the event's batch count.
--}}
@php
    $showOriginal  = $showOriginal ?? true;
    $showAvailable = $showAvailable ?? false;
    $showPerBatch  = $showPerBatch ?? false;

    $colSpan = 3
        + ($showAvailable ? 1 : 0)
        + ($showPerBatch ? 1 : 0)
        + ($showOriginal ? 2 : 0);

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
                @if($showAvailable)
                <th style="width: 150px;">In Stock</th>
                @endif
                @if($showPerBatch)
                <th style="width: 120px;">Per Batch</th>
                @endif
                @if($showOriginal)
                <th style="width: 130px;">{{ $showPerBatch ? 'Total Qty' : 'Original Qty' }}</th>
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
                @if($showAvailable)
                @php $available = $row['available_quantity'] ?? null; @endphp
                <td>
                    @if($available === null)
                    <span class="aqt-stock aqt-stock--unknown"
                        title="{{ $row['warehouse_id'] ? 'Stock could not be read from the inventory service.' : 'No source warehouse is configured for this item type.' }}">—</span>
                    @else
                    <span class="aqt-stock {{ $available > 0 ? 'aqt-stock--ok' : 'aqt-stock--none' }}">
                        {{ format_quantity($available) }}
                    </span>
                    @endif
                    @if($row['warehouse_name'] ?? null)
                    <div class="aqt-stock-where" title="{{ $row['warehouse_name'] }}">{{ $row['warehouse_name'] }}</div>
                    @endif
                </td>
                @endif
                @if($showPerBatch)
                <td>
                    <input type="number" class="form-control form-control-sm"
                        value="{{ ($row['per_batch_quantity'] ?? 0) + 0 }}" disabled>
                </td>
                @endif
                @if($showOriginal)
                <td>
                    <input type="number" class="form-control form-control-sm"
                        value="{{ $row['planned_quantity'] + 0 }}" disabled>
                    @if($showPerBatch)
                    <div class="aqt-batch-math">
                        {{ ($row['per_batch_quantity'] ?? 0) + 0 }} × {{ ($row['batch_count'] ?? 1) + 0 }}
                        {{ ($row['batch_count'] ?? 1) == 1 ? 'batch' : 'batches' }}
                    </div>
                    @endif
                </td>
                @endif
                <td>
                    <input type="number" step="any" min="0"
                        class="form-control form-control-sm @error("{$model}.{$i}.actual_quantity") is-invalid @enderror"
                        wire:model.live.debounce.600ms="{{ $model }}.{{ $i }}.actual_quantity" placeholder="0">
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
