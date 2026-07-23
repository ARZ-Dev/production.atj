<div>
    @php
        $statusMap = [
            'pending'  => ['amber', 'bi-hourglass-split'],
            'loaded'   => ['blue',  'bi-truck'],
            'approved' => ['green', 'bi-check-circle'],
        ];
        [$statusClass, $statusIcon] = $statusMap[$transfer->status] ?? ['muted', 'bi-circle'];
        $showReceived = $transfer->status === 'approved';
    @endphp

    {{-- Header --}}
    <div class="pv-header">
        <div>
            <div class="pv-title">Transfer #{{ $transfer->id }}</div>
            <div class="pv-chips">
                <span class="pv-chip {{ $statusClass }}">
                    <i class="bi {{ $statusIcon }} chip-icon"></i>
                    {{ ucfirst($transfer->status) }}
                </span>
                <span class="pv-chip muted">
                    <i class="bi bi-calendar3 chip-icon"></i>
                    {{ $transfer->created_at?->format('d M Y, H:i') }}
                </span>
            </div>
        </div>
        <a href="{{ route('item-transfers') }}" class="btn btn-light btn-sm flex-shrink-0">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    {{-- From → To route --}}
    <div class="sv-route mb-4">
        <div class="sv-node from">
            <div class="s-label"><i class="bi bi-box-arrow-up-right me-1"></i> From Warehouse</div>
            <div class="s-val">{{ $warehouseFromName }}</div>
        </div>
        <div class="sv-arrow"><i class="bi bi-arrow-right"></i></div>
        <div class="sv-node to">
            <div class="s-label"><i class="bi bi-box-arrow-in-down-right me-1"></i> To Warehouse</div>
            <div class="s-val">{{ $warehouseToName }}</div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="pv-stats">
        <div class="pv-stat pv-stat--primary">
            <div class="s-label">Line Items</div>
            <div class="s-val">{{ count($rows) }}</div>
        </div>
        <div class="pv-stat pv-stat--info">
            <div class="s-label">Status</div>
            <div class="s-val s-val--sm">{{ ucfirst($transfer->status) }}</div>
        </div>
        <div class="pv-stat pv-stat--success">
            <div class="s-label">Created</div>
            <div class="s-val s-val--sm">{{ $transfer->created_at?->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Items --}}
    <div class="pv-board">
        <div class="pv-board-head">
            <i class="bi bi-arrow-left-right text-primary"></i>
            <div class="pv-board-title">Transfer Items</div>
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
                        <th class="text-end">Loaded Qty</th>
                        @if($showReceived)
                        <th class="text-end">Received Qty</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $row)
                    <tr>
                        <td><span class="sv-seq">{{ $index + 1 }}</span></td>
                        <td class="fw-semibold">{{ $row['item'] }}</td>
                        <td><span class="sv-unit">{{ $row['unit'] }}</span></td>
                        <td class="text-end"><span class="sv-qty">{{ $row['quantity'] }}</span></td>
                        @if($showReceived)
                        <td class="text-end">
                            @if($row['received'] !== null)
                            <span class="sv-qty">{{ $row['received'] }}</span>
                            @else
                            <span class="sv-qty-muted">—</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="pv-empty">
            <i class="bi bi-inbox"></i>
            <p class="mb-0 text-muted">No items on this transfer.</p>
        </div>
        @endif
    </div>
</div>
