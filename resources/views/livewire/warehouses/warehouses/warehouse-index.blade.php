<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Warehouse List</h5>
                    @hasPermission('production.warehouseType-create')
                    <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Warehouse
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Factory</th>
                                <th>Company</th>
                                <th>Warehouse Type</th>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouses as $warehouse)
                            <tr>
                                <td>{{ $warehouse->id }}</td>
                                <td>{{ $warehouse->factory?->name ?? 'N/A' }}</td>
                                <td>{{ $warehouse->company->name }}</td>
                                <td>{{ $warehouse->warehouseType->name }}</td>
                                <td>{{ $warehouse->name }}</td>
                                <td>
                                    @hasPermission('production.warehouse-edit')
                                    <a href="{{ route('warehouses.edit', $warehouse->id) }}"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @endhasPermission

                                    @hasPermission('production.warehouse-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $warehouse->id }}" data-bs-toggle="tooltip"
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
</div>
