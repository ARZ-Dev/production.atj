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
              $keyPrefix (string, default $model) — wire:key prefix, for a
              modal that draws one $model as several tables (a linked pair).
              $handoffMode ('skip' | 'record', default 'skip') + $handoffNote —
              how rows flagged `handoff` (the pre-finished good passed straight
              between a linked pair) are drawn: 'skip' shows the row without an
              input (a companion's start — never entered, checked or reserved);
              'record' keeps the input (a lead's end — recorded, never stocked).
              Either way $handoffNote is printed under the item name.

    The quantity input binds deferred (plain wire:model), so nothing is sent
    while the operator types and an in-flight response can never write a stale
    value back over the character just typed. The "% Difference" cell is
    therefore recomputed in the browser (see the data-qty-* handler in
    plan-board.blade.php); the server recomputes it from the same figures when
    the modal is submitted.
--}}
@php
    $showOriginal  = $showOriginal ?? true;
    $showAvailable = $showAvailable ?? false;
    $showPerBatch  = $showPerBatch ?? false;
    $keyPrefix     = $keyPrefix ?? $model;
    $handoffMode   = $handoffMode ?? 'skip';
    $handoffNote   = $handoffNote ?? null;

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
        <tbody wire:key="{{ $keyPrefix }}-group-{{ $loop->index }}">
            <tr class="aqt-group-row">
                <td colspan="{{ $colSpan }}" class="aqt-group-title">
                    <i class="bi bi-tag-fill me-1"></i>{{ $groupName }}
                    <span class="aqt-group-count">{{ count($groupRows) }}</span>
                </td>
            </tr>
            @foreach($groupRows as $i => $row)
            @php
                $handoff     = !empty($row['handoff']);
                $skipHandoff = $handoff && $handoffMode === 'skip';
            @endphp
            <tr wire:key="{{ $model }}-row-{{ $i }}" class="{{ $handoff ? 'aqt-row--handoff' : '' }}">
                <td>
                    {{ $row['item_name'] ?? '—' }}
                    @if($handoff && $handoffNote)
                    <div class="aqt-handoff-note"><i class="bi bi-arrow-left-right"></i> {{ $handoffNote }}</div>
                    @endif
                </td>
                <td>{{ $row['unit_name'] ?? '—' }}</td>
                @if($showAvailable)
                @php $available = $row['available_quantity'] ?? null; @endphp
                <td>
                    @if($skipHandoff)
                    <span class="aqt-stock aqt-stock--unknown" title="Passed straight between the two events — not held in any warehouse.">Not stocked</span>
                    @elseif($available === null)
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
                    @if($skipHandoff)
                    {{-- Not entered: nothing is taken from stock for it. --}}
                    <input type="text" class="form-control form-control-sm" value="—" disabled>
                    @else
                    <input type="number" step="any" min="0"
                        class="form-control form-control-sm @error("{$model}.{$i}.actual_quantity") is-invalid @enderror"
                        wire:model="{{ $model }}.{{ $i }}.actual_quantity" placeholder="0"
                        data-qty-input data-planned="{{ $row['planned_quantity'] ?? '' }}">
                    @error("{$model}.{$i}.actual_quantity")
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @endif
                </td>
                @if($showOriginal)
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" data-qty-percent
                            value="{{ !$skipHandoff && ($row['percentage'] ?? null) !== null ? $row['percentage'] + 0 : '' }}" disabled>
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
