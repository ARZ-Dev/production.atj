<x-layouts.app title="Users Management">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">User List</h5>
            @hasPermission('production.warehouseType-create')
            <a href="{{ route('warehouse-types.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add New Warehouse Type
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
                        <th>Classification</th>
                        <th>Info</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($warehouse_types as $warehouse_type)
                        <tr>
                            <td>{{ $warehouse_type['id'] }}</td>
                            <td>{{ $warehouse_type['name'] }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if(!empty($warehouse_type['is_internal']))
                                        <span class="badge bg-info">Internal</span>
                                    @endif
                                    @if(!empty($warehouse_type['is_factory']))
                                        <span class="badge bg-warning text-dark">Factory</span>
                                    @endif
                                    @if(empty($warehouse_type['is_internal']) && empty($warehouse_type['is_factory']))
                                        <span class="text-muted small">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php
                                    $fields = [
                                        'can_receive_transfer' => 'Receive Transfer',
                                        'can_receive_local_incoming_order' => 'Receive Local Order',
                                        'can_receive_international_incoming_order' => 'Receive Intl Order',
                                        'can_receive_production' => 'Receive Production',
                                        'can_send_transfer' => 'Send Transfer',
                                        'can_send_order' => 'Send Order',
                                        'can_request_product' => 'Request Product',
                                        'have_pos' => 'Has POS',
                                        'can_distribute' => 'Distribute',
                                        'can_return_distribute' => 'Return Distribute',
                                    ];
                                @endphp

                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($fields as $column => $label)
                                        @if($warehouse_type[$column])
                                            <span class="badge bg-secondary">
                                                {{ $label }}
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                {{ $label }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('warehouse-types.edit', $warehouse_type['id']) }}" class="btn btn-sm btn-warning" title="Edit User">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-danger delete-warehouse-type"
                                        title="Delete Warehouse Type"
                                        data-id="{{ $warehouse_type['id'] }}"
                                        data-name="{{ $warehouse_type['name'] }}">
                                    <i class="bi bi-trash"></i>
                                </button>
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

    $(document).on('click', '.delete-warehouse-type', function () {

        let typeId = $(this).data('id');
        let typeName = $(this).data('name');

        Swal.fire({
            title: 'Delete Warehouse Type?',
            text: "You are about to delete \"" + typeName + "\"",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('warehouse-types.destroy', '%typeId%') }}".replace('%typeId%', typeId);
            }
        });
    });

</script>

