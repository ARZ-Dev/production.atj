<x-layouts.app title="Roles Management">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Role List</h5>
            @hasPermission('production.role-create')
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add New Role
            </a>
            @endhasPermission
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Role</th>
                        <th>Users</th>
                        <th>Created</th>
                        <th class="text-center" style="width: 100px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $role['name'] }}</td>
                            <td>{{ $role['users_count'] }}</td>
                            <td>{{ $role['created_at'] }}</td>
                            <td class="text-center">
                                @if($role['name'] !== 'Super Admin')

                                <a href="{{ route('roles.edit', $role['id']) }}" class="btn btn-sm btn-warning" title="Edit Role">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-danger delete-role"
                                        title="Delete Role"
                                        data-id="{{ $role['id'] }}"
                                        data-name="{{ $role['name'] }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
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

    $(document).on('click', '.delete-role', function () {

        let roleId = $(this).data('id');
        let roleName = $(this).data('name');

        Swal.fire({
            title: 'Delete Role?',
            text: "You are about to delete \"" + roleName + "\"",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('roles.destroy', '%roleId%') }}".replace('%roleId%', roleId);
            }
        });
    });

</script>

