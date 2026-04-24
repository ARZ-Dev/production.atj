<x-layouts.app title="Warehouses Management">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Warehouse List</h5>
            @hasPermission('production.warehouse-create')
            <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add New Warehouse
            </a>
            @endhasPermission
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Short Name</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Item Types</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($warehouses as $warehouse)
                        <tr>
                            <td>{{ $warehouse['id'] }}</td>
                            <td>{{ $warehouse['name'] }}</td>
                            <td>{{ $warehouse['shortname'] }}</td>
                            <td>{{ $warehouse['department_id'] }}</td>
                            <td>{{ $warehouse['type_id'] }}</td>
                            <td>
                                @php
                                    $itemTypeIds = json_decode($warehouse['items_type_id'], true) ?? [];
                                @endphp
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($itemTypeIds as $itemTypeId)
                                        <span class="badge bg-secondary">{{ $itemTypeId }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-center">
                                @hasPermission('production.warehouse-edit')
                                <a href="{{ route('warehouses.edit', $warehouse['id']) }}"
                                   class="btn btn-sm btn-warning"
                                   title="Edit Warehouse">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                @endhasPermission
                                @hasPermission('production.warehouse-delete')
                                <button type="button"
                                        class="btn btn-sm btn-danger delete-warehouse"
                                        title="Delete Warehouse"
                                        data-id="{{ $warehouse['id'] }}"
                                        data-name="{{ $warehouse['name'] }}">
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
</x-layouts.app>

<script>
    $(document).on('click', '.delete-warehouse', function () {
        let warehouseId   = $(this).data('id');
        let warehouseName = $(this).data('name');

        Swal.fire({
            title: 'Delete Warehouse?',
            text: "You are about to delete \"" + warehouseName + "\"",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('warehouses.destroy', '%warehouseId%') }}".replace('%warehouseId%', warehouseId);
            }
        });
    });
</script>