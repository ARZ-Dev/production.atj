<div>
    <style>
        .activity-filter-card { cursor: pointer; transition: box-shadow .12s ease-in-out, transform .12s ease-in-out; user-select: none; }
        .activity-filter-card:hover { transform: translateY(-2px); box-shadow: 0 .25rem .5rem rgba(0, 0, 0, .1); }
        .activity-filter-card.active-filter { box-shadow: 0 0 0 .18rem rgba(13, 110, 253, .45); }
    </style>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Warehouse Inventory</h4>
        </div>
        <div class="card-body row g-3">
            <div class="col-lg-3 col-sm-12">
                <label class="form-label" for="warehouse_id">
                    Warehouse <span class="text-danger">*</span>
                </label>
                <div wire:ignore>
                    <select id="warehouse_id"
                            class="selectpicker w-100"
                            title="Select Warehouse"
                            data-style="btn-default"
                            data-live-search="true"
                            data-icon-base="ti"
                            data-tick-icon="ti-check text-white"
                            required>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse['id'] }}"
                                @selected($warehouse['id'] == $warehouse_id)>
                                {{ $warehouse['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('warehouse_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title">
                {{ $warehouse_id ? ($warehouseMap[$warehouse_id] ?? 'N/A') : 'N/A' }} Statement
            </h5>
        </div>
        <div class="card-body">
            <div wire:loading wire:target="getData">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div wire:loading.remove wire:target="getData" class="table-responsive">
                <table class="table text-center text-nowrap table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>On Hand</th>
                            <th>Pending In</th>
                            <th>Pending Out</th>
                            <th>In Process</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouseUnits as $index => $unit)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $unit->item ?? 'N/A' }}</td>
                            <td>{{ $unit->unit ?? 'N/A' }}</td>
                            <td class="fw-semibold">{{ $unit->quantity }}</td>
                            <td>
                                @if($unit->quantity_pending_in > 0)
                                    <span class="badge bg-success">{{ $unit->quantity_pending_in }}</span>
                                @else
                                    <span class="text-muted">{{ $unit->quantity_pending_in }}</span>
                                @endif
                            </td>
                            <td>
                                @if($unit->quantity_pending_out > 0)
                                    <span class="badge bg-warning text-dark">{{ $unit->quantity_pending_out }}</span>
                                @else
                                    <span class="text-muted">{{ $unit->quantity_pending_out }}</span>
                                @endif
                            </td>
                            <td>
                                @if($unit->quantity_in_process > 0)
                                    <span class="badge bg-info text-dark">{{ $unit->quantity_in_process }}</span>
                                @else
                                    <span class="text-muted">{{ $unit->quantity_in_process }}</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info"
                                    wire:click="viewUnitActivity({{ $unit->warehouse_id }}, {{ $unit->item_id }}, {{ $unit->item_unit_id }})">
                                    Check Activity
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No items found for this warehouse</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Warehouse Activity Modal --}}
    <div wire:ignore.self class="modal fade" id="warehouseDetailsModal" tabindex="-1"
        aria-labelledby="warehouseDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="warehouseDetailsModalLabel">
                        {{ $selectedWarehouse ? $selectedWarehouse->name : 'Warehouse' }} - Item Activity
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div wire:loading wire:target="viewUnitActivity">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>

                    <div wire:loading.remove wire:target="viewUnitActivity">
                        @if($selectedInventory)
                        <div class="mb-4">
                            <h6 class="mb-3">
                                {{ $selectedInventory->item }}
                                <span class="text-muted">— {{ $selectedInventory->unit }}</span>
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center h-100 activity-filter-card {{ $activityFilter === 'on_hand' ? 'active-filter' : '' }}"
                                         role="button" title="Show on-hand movements"
                                         wire:click="setActivityFilter('on_hand')">
                                        <div class="text-muted text-uppercase small mb-1">On Hand</div>
                                        <div class="fs-4 fw-bold">{{ $selectedInventory->quantity }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border border-success rounded p-3 text-center h-100 activity-filter-card {{ $activityFilter === 'pending_in' ? 'active-filter' : '' }}"
                                         role="button" title="Show pending-in movements"
                                         wire:click="setActivityFilter('pending_in')">
                                        <div class="text-success text-uppercase small mb-1">Pending In</div>
                                        <div class="fs-4 fw-bold text-success">{{ $selectedInventory->quantity_pending_in }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border border-warning rounded p-3 text-center h-100 activity-filter-card {{ $activityFilter === 'pending_out' ? 'active-filter' : '' }}"
                                         role="button" title="Show pending-out movements"
                                         wire:click="setActivityFilter('pending_out')">
                                        <div class="text-warning text-uppercase small mb-1">Pending Out</div>
                                        <div class="fs-4 fw-bold text-warning">{{ $selectedInventory->quantity_pending_out }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border border-info rounded p-3 text-center h-100 activity-filter-card {{ $activityFilter === 'in_process' ? 'active-filter' : '' }}"
                                         role="button" title="Show in-process movements"
                                         wire:click="setActivityFilter('in_process')">
                                        <div class="text-info text-uppercase small mb-1">In Process</div>
                                        <div class="fs-4 fw-bold text-info">{{ $selectedInventory->quantity_in_process }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @php
                            $filterLabels = [
                                'on_hand'     => 'On Hand',
                                'pending_in'  => 'Pending In',
                                'pending_out' => 'Pending Out',
                                'in_process'  => 'In Process',
                            ];
                        @endphp

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Stock Movement Activity</h6>
                            @if($activityFilter !== 'all')
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        wire:click="setActivityFilter('all')">
                                    <i class="bi bi-x-lg me-1"></i>
                                    Clear filter: {{ $filterLabels[$activityFilter] ?? '' }}
                                </button>
                            @else
                                <span class="text-muted small">Click a card above to filter</span>
                            @endif
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-nowrap">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Action</th>
                                        <th>Item</th>
                                        <th>Unit</th>
                                        <th>Pending In</th>
                                        <th>Pending Out</th>
                                        <th>Qty</th>
                                        <th>In Process</th>
                                        <th>Stock Total</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($activityRows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <span class="badge {{ $row['badge_class'] }}">{{ $row['type_label'] }}</span>
                                            @hasPermission($row['view_permission'])
                                                <a href="{{ $row['view_url'] }}" target="_blank"
                                                   class="ms-1 fw-semibold text-decoration-none">#{{ $row['reference_id'] }}</a>
                                            @else
                                                <span class="ms-1 text-muted">#{{ $row['reference_id'] }}</span>
                                            @endhasPermission
                                            <div class="small text-muted mt-1">{{ ucfirst($row['status']) }}</div>
                                        </td>
                                        <td>{{ $row['item_name'] }}</td>
                                        <td>{{ $row['unit_name'] }}</td>
                                        <td>
                                            @if(!is_null($row['pending_in']))
                                                <span class="badge bg-success">{{ $row['pending_in'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!is_null($row['pending_out']))
                                                <span class="badge bg-warning text-dark">{{ $row['pending_out'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!is_null($row['on_hand']))
                                                <span class="fw-semibold">{{ $row['on_hand'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!is_null($row['in_process']))
                                                <span class="badge bg-info text-dark">{{ $row['in_process'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ !is_null($row['stock_total']) ? $row['stock_total'] : '—' }}</td>
                                        <td>{{ $row['date'] }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            @if($activityFilter !== 'all')
                                                No {{ $filterLabels[$activityFilter] ?? '' }} activity for this item
                                            @else
                                                No activity found for this item
                                            @endif
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $('.selectpicker').selectpicker();

        Livewire.hook('morph.added', ({ el }) => {
            $('.selectpicker').selectpicker();
        });

        $(document).on('change', '#warehouse_id', function () {
            let warehouseId = $(this).val();
            $wire.set('warehouse_id', warehouseId);
            $wire.call('getData');
        });

        $wire.on('openDetailsModal', function () {
            $('#warehouseDetailsModal').modal('show');
        });

        $wire.on('closeDetailsModal', function () {
            $('#warehouseDetailsModal').modal('hide');
        });
    </script>
    @endscript
</div>
