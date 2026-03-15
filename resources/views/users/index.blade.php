<x-layouts.app title="Users Management">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">User List</h5>
            @hasPermission('production.user-create')
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add New User
            </a>
            @endhasPermission
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="buttons-datatables" class="table table-nowrap table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-center" style="width: 100px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $user['name'] }}</td>
                            <td>{{ $user['username'] }}</td>
                            <td>{{ $user['role'] }}</td>
                            <td>{{ $user['is_active'] }}</td>
                            <td>{{ $user['created_at'] }}</td>
                            <td class="text-center">
                                @if($user['role'] !== 'Super Admin')

                                <a href="{{ route('users.edit', $user['id']) }}" class="btn btn-sm btn-warning" title="Edit User">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-danger delete-user"
                                        title="Delete User"
                                        data-id="{{ $user['id'] }}"
                                        data-name="{{ $user['name'] }}">
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

    $(document).on('click', '.delete-user', function () {

        let userId = $(this).data('id');
        let userName = $(this).data('name');

        Swal.fire({
            title: 'Delete User?',
            text: "You are about to delete \"" + userName + "\"",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('users.destroy', '%userId%') }}".replace('%userId%', userId);
            }
        });
    });

</script>

