<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">StockIn List</h5>
                     @hasPermission('production.itemStockIn-create')
                    <a href="{{ route('item-stock-ins.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Stock In
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
                            @foreach($stockIns as $stockIn)
                            <tr>
                                <td>{{ $stockIn->id }}</td>
                                <td>{{ $warehouseMap[$stockIn->warehouse_id] ?? 'Unknown' }}</td>
                                @if ($stockIn->status == 'pending')
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                @elseif ($stockIn->status == 'approved')
                                <td><span class="badge bg-success">Approved</span></td>
                                @endif
                                <td>
                                    @hasPermission('production.itemStockIn-view')
                                    <a href="{{ route('item-stock-ins.view',[ $stockIn->id , $viewStatus = 1]) }}"
                                        class="btn btn-light-info icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endhasPermission
                                    @if ($stockIn->status === 'pending')
                                     @hasPermission('production.itemStockIn-approve')
                                    <button type="button" class="btn btn-light-success icon-btn-sm approve-button"
                                        data-id="{{ $stockIn->id }}" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Approve">
                                        <i class="bi bi-check"></i>
                                    </button>
                                    @endhasPermission
                                     @hasPermission('production.itemStockIn-edit')
                                    <a href="{{ route('item-stock-ins.edit', $stockIn->id) }}"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @endhasPermission
                                    @endif

                                    @if ($stockIn->status === 'pending')
                                     @hasPermission('production.itemStockIn-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $stockIn->id }}" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
