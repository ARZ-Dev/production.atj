<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Raw Material List</h5>
                    @hasPermission('rawMaterial-create')
                    <a href="{{ route('raw-materials.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Raw Material
                    </a>
                    @endhasPermission
                </div>

                <div class="card-body">
                    <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Base Unit</th>
                                <th>Purchase Unit</th>
                                <th>Min. Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rawMaterials as $rawMaterial)
                            <tr>
                                <td>{{ $rawMaterial->id }}</td>
                                <td>{{ $rawMaterial->code }}</td>
                                <td>{{ $rawMaterial->name }}</td>
                                <td>{{ ucfirst($rawMaterial->type) }}</td>
                                <td>{{ $rawMaterial->baseUnit->name }} ({{ $rawMaterial->baseUnit->symbol }})</td>
                                <td>{{ $rawMaterial->purchaseUnit?->name ?? 'N/A' }}</td>
                                <td>{{ $rawMaterial->minimum_stock }}</td>
                                <td>
                                    @if($rawMaterial->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @hasPermission('rawMaterial-edit')
                                    <a href="{{ route('raw-materials.edit', $rawMaterial->id) }}"
                                        class="btn btn-light-primary icon-btn-sm" data-bs-toggle="tooltip"
                                        data-bs-custom-class="tooltip-white" data-bs-placement="top"
                                        data-bs-title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @endhasPermission

                                    @hasPermission('rawMaterial-delete')
                                    <button type="button" class="btn btn-light-danger icon-btn-sm delete-button"
                                        data-id="{{ $rawMaterial->id }}" data-bs-toggle="tooltip"
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