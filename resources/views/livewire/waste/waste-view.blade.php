<div>
    @php
        $statusMap = [
            'pending'  => ['amber', 'bi-hourglass-split'],
            'approved' => ['green', 'bi-check-circle'],
        ];
        [$statusClass, $statusIcon] = $statusMap[$waste->status] ?? ['muted', 'bi-circle'];
    @endphp

    {{-- Header --}}
    <div class="pv-header">
        <div>
            <div class="pv-title">Waste #{{ $waste->id }}</div>
            <div class="pv-chips">
                <span class="pv-chip {{ $statusClass }}">
                    <i class="bi {{ $statusIcon }} chip-icon"></i>
                    {{ ucfirst($waste->status) }}
                </span>
                <span class="pv-chip muted">
                    <i class="bi bi-calendar3 chip-icon"></i>
                    {{ $waste->created_at?->format('d M Y, H:i') }}
                </span>
            </div>
        </div>
        <a href="{{ route('item-wastes') }}" class="btn btn-light btn-sm flex-shrink-0">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- Summary --}}
    <div class="pv-stats">
        <div class="pv-stat pv-stat--primary">
            <div class="s-label">Warehouse</div>
            <div class="s-val s-val--sm">{{ $warehouseName }}</div>
        </div>
        <div class="pv-stat pv-stat--success">
            <div class="s-label">Line Items</div>
            <div class="s-val">{{ count($rows) }}</div>
        </div>
        <div class="pv-stat pv-stat--warning">
            <div class="s-label">Status</div>
            <div class="s-val s-val--sm">{{ ucfirst($waste->status) }}</div>
        </div>
    </div>

    {{-- Notes --}}
    @if(!empty($waste->notes))
    <div class="sv-notes">
        <div class="sv-notes-label"><i class="bi bi-sticky me-1"></i> Notes</div>
        <div class="sv-notes-body">{{ $waste->notes }}</div>
    </div>
    @endif

    {{-- Items --}}
    <div class="pv-board">
        <div class="pv-board-head">
            <i class="bi bi-trash text-primary"></i>
            <div class="pv-board-title">Waste Items</div>
            <span class="sv-board-count">{{ count($rows) }}</span>
        </div>

        @if(count($rows) > 0)
        <div class="table-responsive">
            <table class="table sv-table align-middle">
                <thead>
                    <tr>
                        <th style="width:56px;">#</th>
                        <th>Item</th>
                        <th>Unit</th>
                        <th class="text-end">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $row)
                    <tr>
                        <td><span class="sv-seq">{{ $index + 1 }}</span></td>
                        <td class="fw-semibold">{{ $row['item'] }}</td>
                        <td><span class="sv-unit">{{ $row['unit'] }}</span></td>
                        <td class="text-end"><span class="sv-qty">{{ $row['quantity'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="pv-empty">
            <i class="bi bi-inbox"></i>
            <p class="mb-0 text-muted">No items on this waste record.</p>
        </div>
        @endif
    </div>
</div>
