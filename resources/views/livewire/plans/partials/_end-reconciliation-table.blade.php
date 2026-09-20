{{--
    Leftover reconciliation for events whose consumed quantities couldn't be
    verified when they started (event_types.start_items_unverifiable). Those
    events were started on their planned amounts; this is where the real
    figures are entered and the difference is routed.

    Grouped by item type like the other action tables, keeping each row's
    original index as the group key so the wire:model bindings stay pointed at
    the right row.

    The actual-used input binds deferred (plain wire:model) so a response in
    flight can never overwrite what is being typed; "Remaining Qty" and the
    disposition dropdown are kept in step by the data-recon-* handler in
    plan-board.blade.php, mirroring PlanBoard::recalcRemaining(), which runs
    again over every row at the top of submitTerminate().

    Expects: $rows (array), $model (Livewire property name)
--}}
@php
    $groups = [];
    foreach ($rows as $i => $row) {
        $groups[$row['item_type_name'] ?? 'Other'][$i] = $row;
    }
@endphp
<div class="table-responsive">
    <table class="table table-sm align-middle mb-2 aqt-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Unit</th>
                <th style="width: 130px;">Used Qty (start)</th>
                <th style="width: 150px;">Actual Used Qty</th>
                <th style="width: 130px;">Remaining Qty</th>
                <th style="width: 190px;">Remaining Goes To</th>
            </tr>
        </thead>
        @foreach($groups as $groupName => $groupRows)
        <tbody wire:key="{{ $model }}-group-{{ $loop->index }}">
            <tr class="aqt-group-row">
                <td colspan="6" class="aqt-group-title">
                    <i class="bi bi-tag-fill me-1"></i>{{ $groupName }}
                    <span class="aqt-group-count">{{ count($groupRows) }}</span>
                </td>
            </tr>
            @foreach($groupRows as $i => $row)
            @php $remaining = (float) ($row['remaining_quantity'] ?? 0); @endphp
            <tr wire:key="{{ $model }}-row-{{ $i }}">
                <td>{{ $row['item_name'] ?? '—' }}</td>
                <td>{{ $row['unit_name'] ?? '—' }}</td>
                <td>
                    <input type="number" class="form-control form-control-sm"
                        value="{{ ($row['start_quantity'] ?? 0) + 0 }}" disabled>
                </td>
                <td>
                    <input type="number" step="any" min="0"
                        class="form-control form-control-sm @error("{$model}.{$i}.actual_used_quantity") is-invalid @enderror"
                        wire:model="{{ $model }}.{{ $i }}.actual_used_quantity" placeholder="0"
                        data-recon-input data-start="{{ ($row['start_quantity'] ?? 0) + 0 }}">
                    @error("{$model}.{$i}.actual_used_quantity")
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm {{ $remaining > 0 ? 'aqt-remaining' : '' }}"
                        value="{{ $remaining + 0 }}" disabled data-recon-remaining>
                </td>
                <td>
                    <select class="form-select form-select-sm @error("{$model}.{$i}.remaining_action") is-invalid @enderror"
                        wire:model="{{ $model }}.{{ $i }}.remaining_action" data-recon-action
                        @disabled($remaining <= 0)>
                        <option value="">{{ $remaining > 0 ? 'Select…' : '—' }}</option>
                        <option value="stock_in" @selected(($row['remaining_action'] ?? null) === 'stock_in')>Back to stock</option>
                        <option value="waste" @selected(($row['remaining_action'] ?? null) === 'waste')>Waste</option>
                    </select>
                    @error("{$model}.{$i}.remaining_action")
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </td>
            </tr>
            @endforeach
        </tbody>
        @endforeach
    </table>
</div>
