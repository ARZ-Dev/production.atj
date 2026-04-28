<x-layouts.app title="Suppliers Management">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Supplier List</h5>
            @hasPermission('admin.suppliers.create')
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add New Supplier
            </a>
            @endhasPermission
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Reg. Number</th>
                            <th>Phone</th>
                            <th>Contact Name</th>
                            <th>Contact Phone</th>
                            <th>Address</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier['id'] }}</td>
                            <td>{{ $supplier['company_name'] }}</td>
                            <td>{{ $supplier['company_registration_number'] }}</td>
                            <td>{{ $supplier['company_phone'] }}</td>
                            <td>{{ $supplier['poc_name'] }}</td>
                            <td>{{ $supplier['poc_phone'] }}</td>
                            <td>{{ $supplier['company_address'] }}</td>
                            <td class="text-center">
                                @hasPermission('admin.suppliers.edit')
                                <a href="{{ route('suppliers.edit', $supplier['id']) }}"
                                    class="btn btn-sm btn-warning" title="Edit Supplier">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                @endhasPermission
                                @hasPermission('admin.suppliers.delete')
                                <button type="button" class="btn btn-sm btn-danger delete-supplier"
                                    title="Delete Supplier" data-id="{{ $supplier['id'] }}"
                                    data-name="{{ $supplier['company_name'] }}">
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
    $(document).on('click', '.delete-supplier', function () {
        let supplierId   = $(this).data('id');
        let supplierName = $(this).data('name');

        Swal.fire({
            title: 'Delete Supplier?',
            text: "You are about to delete \"" + supplierName + "\"",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('suppliers.destroy', '%supplierId%') }}".replace('%supplierId%', supplierId);
            }
        });
    });
</script>