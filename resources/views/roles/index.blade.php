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
                                <a href="{{ route('roles.edit', $role['id']) }}" class="btn btn-sm btn-warning" title="Edit Role">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" title="Delete Role"
                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                        data-role-id="{{ $role['id'] }}" data-role-name="{{ $role['name'] }}">
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

    {{-- Delete Confirmation Modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Delete Role
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the role <strong id="deleteRoleName"></strong>?</p>
                    <p class="text-danger mb-0">
                        <small>This action cannot be undone.</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

