<div>
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
                                    <div class="border rounded p-3 text-center h-100">
                                        <div class="text-muted text-uppercase small mb-1">On Hand</div>
                                        <div class="fs-4 fw-bold">{{ $selectedInventory->quantity }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border border-success rounded p-3 text-center h-100">
                                        <div class="text-success text-uppercase small mb-1">Pending In</div>
                                        <div class="fs-4 fw-bold text-success">{{ $selectedInventory->quantity_pending_in }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border border-warning rounded p-3 text-center h-100">
                                        <div class="text-warning text-uppercase small mb-1">Pending Out</div>
                                        <div class="fs-4 fw-bold text-warning">{{ $selectedInventory->quantity_pending_out }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border border-info rounded p-3 text-center h-100">
                                        <div class="text-info text-uppercase small mb-1">In Process</div>
                                        <div class="fs-4 fw-bold text-info">{{ $selectedInventory->quantity_in_process }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Action</th>
                                        <th>Item</th>
                                        <th>Unit</th>
                                        <th>Quantity</th>
                                        <th>Stock Total</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inventoryItems as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if($item->stock_in_id)
                                                <span class="badge bg-success">Stock In</span>
                                            @elseif($item->stock_out_id)
                                                <span class="badge bg-danger">Stock Out</span>
                                            @elseif($item->waste_id)
                                                <span class="badge bg-warning text-dark">Waste</span>
                                            @elseif($item->transfer_id)
                                                <span class="badge bg-info text-dark">Transfer</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->item_name }}</td>
                                        <td>{{ $item->unit_name }}</td>
                                        <td>{{ $item->transfer_id ? $item->received_quantity : $item->quantity }}</td>
                                        <td>{{ $item->stock_total }}</td>
                                        <td>{{ $item->created_at->format('Y-m-d') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No activity found for this item</td>
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
