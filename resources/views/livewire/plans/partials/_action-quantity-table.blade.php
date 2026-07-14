{{--
    Quantity rows for the status-action modal.
    Expects: $rows (array), $model (Livewire property name),
             $actualLabel, $percentLabel
--}}
<div class="table-responsive">
    <table class="table table-sm align-middle mb-2">
        <thead>
            <tr>
                <th>Item</th>
                <th>Unit</th>
                <th style="width: 130px;">Original Qty</th>
                <th style="width: 150px;">{{ $actualLabel }}</th>
                <th style="width: 130px;">{{ $percentLabel }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $row)
            <tr wire:key="{{ $model }}-row-{{ $i }}">
                <td>{{ $row['item_name'] ?? '—' }}</td>
                <td>{{ $row['unit_name'] ?? '—' }}</td>
                <td>
                    <input type="number" class="form-control form-control-sm"
                        value="{{ $row['planned_quantity'] + 0 }}" disabled>
                </td>
                <td>
                    <input type="number" step="any" min="0"
                        class="form-control form-control-sm @error("{$model}.{$i}.actual_quantity") is-invalid @enderror"
                        wire:model.live="{{ $model }}.{{ $i }}.actual_quantity" placeholder="0">
                    @error("{$model}.{$i}.actual_quantity")
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control"
                            value="{{ $row['percentage'] !== null ? $row['percentage'] + 0 : '' }}" disabled>
                        <span class="input-group-text">%</span>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
