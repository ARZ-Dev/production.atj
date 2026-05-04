<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">StockOut List</h5>
                     @hasPermission('production.rawMaterialStockOut-create')
                    <a href="{{ route('raw-material-stock-outs.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Stock Out
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Warehouse</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockOuts as $stockOut)
                            <tr>
                                <td>{{ $stockOut->id }}</td>
                                <td>{{ $warehouseMap[$stockOut->warehouse_id] ?? 'Unknown' }}</td>
                                @if ($stockOut->status == 'pending')
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                @elseif ($stockOut->status == 'approved')
                                <td><span class="badge bg-success">Approved</span></td>
                                @endif
                                <td>
                                    @if ($stockOut->status === 'pending')
                                     @hasPermission('production.rawMaterialStockOut-approve')
                                    <button type="button" class="btn btn-light-success icon-btn-sm approve-button"
                                        data-id="{{ $stockOut->id }}" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Approve">
                                        <i class="bi bi-check"></i>
                                    </button>
                                    @endhasPermission
                                     @hasPermission('production.rawMaterialStockOut-edit')
                                    <a href="{{ route('raw-material-stock-outs.edit', $stockOut->id) }}"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @endhasPermission
                                    @endif

                                     @hasPermission('production.rawMaterialStockOut-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $stockOut->id }}" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endhasPermission
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
    @script
    <script>
        $(document).on('click', '.approve-button', function () {
            let id = $(this).data('id');
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: 'Yes, approve it!',
                customClass: {
                    confirmButton: 'btn btn-danger me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then((willApprove) => {
                if (willApprove.isConfirmed) {
                    $wire.dispatch("approve", { id: id });
                }
            });
        });
    </script>
    @endscript
</div>