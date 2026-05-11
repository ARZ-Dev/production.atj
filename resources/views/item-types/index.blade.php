<x-layouts.app title="Item Types Management">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Item Type List</h5>
            @hasPermission('production.itemType-create')
            <a href="{{ route('item-types.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add New Item Type
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
                        <th>Group Entity Relation</th>
                        <th>Has POS Suppliers</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($item_types as $item_type)
                        <tr>
                            <td>{{ $item_type['id'] }}</td>
                            <td>{{ $item_type['name'] }}</td>
                            <td>{{ getRelationName($item_type['groupEntityRelation']) }}</td>
                            <td>
                                @if($item_type['has_pos_suppliers'])
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-danger">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('item-types.edit', $item_type['id']) }}" class="btn btn-sm btn-warning" title="Edit Item Type">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-danger delete-item-type"
                                        title="Delete Item Type"
                                        data-id="{{ $item_type['id'] }}"
                                        data-name="{{ $item_type['name'] }}">
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
    $(document).on('click', '.delete-item-type', function () {
        let typeId = $(this).data('id');
        let typeName = $(this).data('name');

        Swal.fire({
            title: 'Delete Item Type?',
            text: "You are about to delete \"" + typeName + "\"",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('item-types.destroy', '%typeId%') }}".replace('%typeId%', typeId);
            }
        });
    });
</script>