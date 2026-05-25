<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Transfer List</h5>
                    @hasPermission('production.itemTransfer-create')
                    <a href="{{ route('item-transfers.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Transfer
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Warehouse From</th>
                                <th>Warehouse To</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transfers as $transfer)
                            <tr>
                                <td>{{ $transfer->id }}</td>
                                <td>{{ $warehouseMap[$transfer->warehouse_from_id] ?? 'Unknown' }}</td>
                                <td>{{ $warehouseMap[$transfer->warehouse_to_id] ?? 'Unknown' }}</td>
                                <td>
                                    @if ($transfer->status == 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif ($transfer->status == 'loaded')
                                        <span class="badge bg-info">Loaded</span>
                                    @elseif ($transfer->status == 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($transfer->status === 'pending')
                                        @hasPermission('production.itemTransfer-approve')
                                        <a href="{{ route('item-transfers.approve-load', $transfer->id) }}"
                                            class="btn btn-light-success icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Approve Load">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                        @endhasPermission

                                        @hasPermission('production.itemTransfer-edit')
                                        <a href="{{ route('item-transfers.edit', $transfer->id) }}"
                                            class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        @endhasPermission

                                        @hasPermission('production.itemTransfer-delete')
                                        <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                            data-id="{{ $transfer->id }}" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endhasPermission

                                    @elseif ($transfer->status === 'loaded')
                                        @hasPermission('production.itemTransfer-approve')
                                        <a href="{{ route('item-transfers.approve-receive', $transfer->id) }}"
                                            class="btn btn-light-success icon-btn-sm" data-bs-toggle="tooltip"
                                            data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                            data-bs-title="Approve Receive">
                                            <i class="bi bi-check-all"></i>
                                        </a>
                                        @endhasPermission
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @script
        @include('livewire.deleteConfirm')
    @endscript
</div>